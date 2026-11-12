<?php

/**
 * 299nobot (FlexyBot) : Protection antispam locale multicouche côté serveur.
 *
 * Couches :
 * 1. Honeypot invisible (ant_name_a)
 * 2. Challenge signé HMAC-SHA256 (nonce unique, expiration 5 min, consommation atomique anti-rejeu)
 * 3. Proof of Work SHA-256 vérifié bit à bit côté serveur avec difficulté adaptative (16 / 18 / 20 bits)
 * 4. Rate limiting SQLite natif via Core\Database ('sqlite:antispam') avec fallback flock JSON si pdo_sqlite absent
 * 5. Signaux comportementaux JS en signal secondaire + fallback accessible sans JavaScript (<noscript>)
 */
defined('ROOT') OR exit('Access denied!');

class AntispamFlexyBotCaptcha extends AntispamAbstractCaptcha
{
    public const DIFFICULTY_BASE = 16;
    public const DIFFICULTY_SUSPECT = 18;
    public const DIFFICULTY_BURST = 20;

    public const CHALLENGE_TTL = 300; // 5 minutes
    public const RATE_WINDOW_SECONDS = 300;
    public const RATE_MAX_ATTEMPTS_ROUTE = 12;
    public const RATE_MAX_FAILURES_ROUTE = 5;
    public const RATE_MAX_ATTEMPTS_GLOBAL = 30;

    private ?\PDO $sqlitePdo = null;
    private bool $sqliteChecked = false;

    public function getText(): string
    {
        $challenge = $this->initChallenge();
        $id = 'fb_' . substr($challenge['nonce'], 0, 8);
        $question = htmlspecialchars($challenge['question'], ENT_QUOTES, 'UTF-8');
        $nonce = htmlspecialchars($challenge['nonce'], ENT_QUOTES, 'UTF-8');
        $sig = htmlspecialchars($challenge['signature'], ENT_QUOTES, 'UTF-8');
        $difficulty = (int) $challenge['difficulty'];

        return <<<HTML
<div class="antispam-flexybot-box" id="{$id}" data-nonce="{$nonce}" data-difficulty="{$difficulty}">
    <input type="hidden" name="fb_nonce" value="{$nonce}" />
    <input type="hidden" name="fb_sig" value="{$sig}" />
    <input type="hidden" name="fb_pow_nonce" class="fb-pow-input" value="" />
    <input type="hidden" name="fb_proof" class="fb-proof-input" value="" />
    <div class="fb-widget-row">
        <button type="button" class="fb-check-btn" aria-pressed="false">
            <span class="fb-check-square"><i class="fa-solid fa-check"></i></span>
            <span class="fb-check-label">Je ne suis pas un robot</span>
        </button>
        <span class="fb-shield-tag"><i class="fa-solid fa-shield-halved"></i> 299nobot</span>
    </div>
    <div class="fb-challenge-panel">
        <noscript><p class="fb-noscript-hint">JavaScript désactivé : résolvez ce calcul pour valider.</p></noscript>
        <label class="fb-challenge-label" for="{$id}_ans">Vérification complémentaire : {$question} (en chiffres)</label>
        <input type="text" id="{$id}_ans" name="fb_answer" class="fb-answer-input" inputmode="numeric" autocomplete="off" value="" />
    </div>
</div>
<noscript>
<style>
#{$id} .fb-widget-row { display: none !important; }
#{$id} .fb-challenge-panel { display: block !important; border-top: none !important; margin-top: 0 !important; padding-top: 0 !important; }
</style>
</noscript>
{$this->getGenericHtml()}
<script>
(function() {
    const box = document.getElementById('{$id}');
    if (!box) return;
    const startTs = Date.now();
    const challengeNonce = box.getAttribute('data-nonce') || '';
    const difficultyBits = Math.max(8, Math.min(24, parseInt(box.getAttribute('data-difficulty') || '16', 10) || 16));
    let mouseMoves = 0, touchEvents = 0, keyPresses = 0, scrollEvents = 0;

    const onMove = () => { if (mouseMoves < 50) mouseMoves++; };
    const onTouch = () => { if (touchEvents < 50) touchEvents++; };
    const onKey = () => { if (keyPresses < 50) keyPresses++; };
    const onScroll = () => { if (scrollEvents < 50) scrollEvents++; };

    document.addEventListener('mousemove', onMove, { passive: true });
    document.addEventListener('touchstart', onTouch, { passive: true });
    document.addEventListener('keydown', onKey, { passive: true });
    document.addEventListener('scroll', onScroll, { passive: true });

    const btn = box.querySelector('.fb-check-btn');
    const label = box.querySelector('.fb-check-label');
    const powInput = box.querySelector('.fb-pow-input');
    const proofInput = box.querySelector('.fb-proof-input');
    const panel = box.querySelector('.fb-challenge-panel');
    const ansInput = box.querySelector('.fb-answer-input');
    const form = box.closest('form');
    const submitControls = form
        ? Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"], button:not([type])'))
            .filter(el => el !== btn)
        : [];

    let isVerified = false;
    let powPromise = null;
    let powSolved = false;

    function setSubmitEnabled(enabled) {
        submitControls.forEach(function(el) {
            el.disabled = !enabled;
            el.classList.toggle('fb-submit-locked', !enabled);
            el.setAttribute('aria-disabled', enabled ? 'false' : 'true');
        });
    }

    setSubmitEnabled(false);

    // Vérification exacte des N bits de poids fort à 0 sur les 4 premiers octets
    const fullZeroBytes = (difficultyBits / 8) | 0;
    const remBits = difficultyBits % 8;
    const remMask = remBits > 0 ? (0xFF << (8 - remBits)) & 0xFF : 0;

    function meetsBitDifficultyBytes(bytes) {
        for (let i = 0; i < fullZeroBytes; i++) {
            if (bytes[i] !== 0) return false;
        }
        if (remBits > 0 && ((bytes[fullZeroBytes] & remMask) !== 0)) {
            return false;
        }
        return true;
    }

    // Fallback SHA-256 pur JS optimisé (Int32Array pré-alloués) pour contextes HTTP non sécurisés
    const sha256BytesJs = (function() {
        const H_INIT = new Int32Array(8);
        const K = new Int32Array(64);
        const isComposite = new Uint8Array(320);
        let primeCount = 0;
        for (let c = 2; primeCount < 64; c++) {
            if (!isComposite[c]) {
                for (let i = c * c; i < 320; i += c) isComposite[i] = 1;
                H_INIT[primeCount] = (Math.pow(c, 0.5) * 4294967296) | 0;
                K[primeCount++] = (Math.pow(c, 1 / 3) * 4294967296) | 0;
            }
        }
        const W = new Int32Array(64);
        const words = new Int32Array(32);
        const out = new Uint8Array(4);

        return function(ascii) {
            const len = ascii.length;
            const totalWords = (((len + 8) >> 6) + 1) << 4;
            words.fill(0, 0, totalWords);
            for (let i = 0; i < len; i++) {
                words[i >> 2] |= (ascii.charCodeAt(i) & 0xFF) << ((3 - (i & 3)) << 3);
            }
            words[len >> 2] |= 0x80 << ((3 - (len & 3)) << 3);
            words[totalWords - 1] = len << 3;

            let h0 = H_INIT[0], h1 = H_INIT[1], h2 = H_INIT[2], h3 = H_INIT[3];
            let h4 = H_INIT[4], h5 = H_INIT[5], h6 = H_INIT[6], h7 = H_INIT[7];

            for (let offset = 0; offset < totalWords; offset += 16) {
                for (let i = 0; i < 16; i++) W[i] = words[offset + i];
                for (let i = 16; i < 64; i++) {
                    const w15 = W[i - 15], w2 = W[i - 2];
                    const s0 = ((w15 >>> 7) | (w15 << 25)) ^ ((w15 >>> 18) | (w15 << 14)) ^ (w15 >>> 3);
                    const s1 = ((w2 >>> 17) | (w2 << 15)) ^ ((w2 >>> 19) | (w2 << 13)) ^ (w2 >>> 10);
                    W[i] = (W[i - 16] + s0 + W[i - 7] + s1) | 0;
                }
                let a = h0, b = h1, c = h2, d = h3, e = h4, f = h5, g = h6, h = h7;
                for (let i = 0; i < 64; i++) {
                    const S1 = ((e >>> 6) | (e << 26)) ^ ((e >>> 11) | (e << 21)) ^ ((e >>> 25) | (e << 7));
                    const ch = (e & f) ^ ((~e) & g);
                    const temp1 = (h + S1 + ch + K[i] + W[i]) | 0;
                    const S0 = ((a >>> 2) | (a << 30)) ^ ((a >>> 13) | (a << 19)) ^ ((a >>> 22) | (a << 10));
                    const maj = (a & b) ^ (a & c) ^ (b & c);
                    const temp2 = (S0 + maj) | 0;
                    h = g; g = f; f = e; e = (d + temp1) | 0;
                    d = c; c = b; b = a; a = (temp1 + temp2) | 0;
                }
                h0 = (h0 + a) | 0; h1 = (h1 + b) | 0; h2 = (h2 + c) | 0; h3 = (h3 + d) | 0;
                h4 = (h4 + e) | 0; h5 = (h5 + f) | 0; h6 = (h6 + g) | 0; h7 = (h7 + h) | 0;
            }
            out[0] = (h0 >>> 24) & 0xFF;
            out[1] = (h0 >>> 16) & 0xFF;
            out[2] = (h0 >>> 8) & 0xFF;
            out[3] = h0 & 0xFF;
            return out;
        };
    })();

    function solvePow() {
        if (powPromise) return powPromise;
        powPromise = (async function() {
            const hasSubtle = Boolean(window.crypto && window.crypto.subtle && typeof TextEncoder !== 'undefined');
            if (hasSubtle) {
                const encoder = new TextEncoder();
                const batchSize = 128;
                for (let base = 0; base < 6000000; base += batchSize) {
                    const promises = new Array(batchSize);
                    const candidates = new Array(batchSize);
                    for (let i = 0; i < batchSize; i++) {
                        const cand = (base + i).toString(36);
                        candidates[i] = cand;
                        promises[i] = window.crypto.subtle.digest('SHA-256', encoder.encode(challengeNonce + ':' + cand));
                    }
                    const buffers = await Promise.all(promises);
                    for (let i = 0; i < batchSize; i++) {
                        if (meetsBitDifficultyBytes(new Uint8Array(buffers[i], 0, 4))) {
                            powInput.value = candidates[i];
                            powSolved = true;
                            return candidates[i];
                        }
                    }
                }
                return '';
            }

            // Fallback non-bloquant par tranches de 2048 sur HTTP non-TLS
            return await new Promise(function(resolve) {
                let n = 0;
                function step() {
                    const end = Math.min(n + 2048, 6000000);
                    for (; n < end; n++) {
                        const cand = n.toString(36);
                        if (meetsBitDifficultyBytes(sha256BytesJs(challengeNonce + ':' + cand))) {
                            powInput.value = cand;
                            powSolved = true;
                            resolve(cand);
                            return;
                        }
                    }
                    if (n < 6000000) {
                        setTimeout(step, 0);
                    } else {
                        resolve('');
                    }
                }
                step();
            });
        })();
        return powPromise;
    }

    // Pré-calcul silencieux dès la première interaction avec le formulaire pour zéro latence au clic
    if (form) {
        form.addEventListener('focusin', function() {
            solvePow();
        }, { once: true });
    }

    if (ansInput) {
        ansInput.addEventListener('input', function() {
            if (!isVerified) {
                setSubmitEnabled(powSolved && this.value.trim() !== '');
            }
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            const hasFallbackAnswer = ansInput && panel.classList.contains('is-open') && ansInput.value.trim() !== '';
            if (!powSolved || (!isVerified && !hasFallbackAnswer)) {
                e.preventDefault();
                setSubmitEnabled(false);
            }
        });
    }

    btn.addEventListener('click', async function() {
        if (btn.classList.contains('is-busy')) return;
        const elapsedMs = Date.now() - startTs;
        const isWebdriver = Boolean(navigator.webdriver);
        const humanEnough = !isWebdriver && elapsedMs >= 800 && (mouseMoves >= 2 || touchEvents >= 1 || keyPresses >= 2 || scrollEvents >= 1);

        if (!powSolved) {
            btn.classList.add('is-busy');
            label.textContent = 'Calcul de sécurité…';
            await solvePow();
            btn.classList.remove('is-busy');
        }

        proofInput.value = JSON.stringify({
            nonce: challengeNonce,
            elapsedMs: elapsedMs,
            mouseMoves: mouseMoves,
            touchEvents: touchEvents,
            keyPresses: keyPresses,
            scrollEvents: scrollEvents,
            webdriver: isWebdriver,
            clicked: true
        });

        if (humanEnough && powSolved) {
            isVerified = true;
            box.classList.add('is-verified');
            btn.setAttribute('aria-pressed', 'true');
            label.textContent = 'Vérifié';
            panel.classList.remove('is-open');
            if (ansInput) ansInput.removeAttribute('required');
            setSubmitEnabled(true);
        } else {
            isVerified = false;
            box.classList.remove('is-verified');
            btn.setAttribute('aria-pressed', 'true');
            label.textContent = 'Vérification requise';
            panel.classList.add('is-open');
            if (ansInput) {
                ansInput.setAttribute('required', 'required');
                ansInput.focus();
                setSubmitEnabled(powSolved && ansInput.value.trim() !== '');
            } else {
                setSubmitEnabled(false);
            }
        }
    });
})();
</script>
HTML;
    }

    public function isValid(): bool
    {
        $sessionData = $_SESSION['antispam_flexybot'] ?? null;
        $ctx = $this->getRateContext();
        $challengeConsumed = false;
        $postedNonce = trim((string) ($_POST['fb_nonce'] ?? ''));

        $consume = function () use (&$challengeConsumed, $postedNonce, $sessionData): void {
            if ($challengeConsumed) {
                return;
            }
            $challengeConsumed = true;
            unset($_SESSION['antispam_flexybot']);
            $targetNonce = $postedNonce !== '' ? $postedNonce : (is_array($sessionData) ? (string) ($sessionData['nonce'] ?? '') : '');
            if ($targetNonce !== '') {
                $this->markNonceUsed($targetNonce);
            }
        };

        try {
            if (!$this->isGenericValid()) {
                $consume();
                $this->recordRateAttempt($ctx, true);
                return false;
            }

            if ($this->isRateLimited($ctx)) {
                $consume();
                $this->lastReason = 'rate_limited';
                $this->recordRateAttempt($ctx, true);
                return false;
            }

            if (!is_array($sessionData) || empty($sessionData['nonce']) || empty($sessionData['signature'])) {
                $this->lastReason = 'missing_session';
                $this->recordRateAttempt($ctx, true);
                return false;
            }

            if ($postedNonce === '' || !hash_equals((string) $sessionData['nonce'], $postedNonce)) {
                $consume();
                $this->lastReason = 'invalid_nonce';
                $this->recordRateAttempt($ctx, true);
                return false;
            }

            $difficulty = (int) ($sessionData['difficulty'] ?? self::DIFFICULTY_BASE);
            $issuedAt = (int) ($sessionData['issued_at'] ?? 0);
            $expectedSig = $this->signChallenge($postedNonce, $issuedAt, $difficulty);
            $postedSig = trim((string) ($_POST['fb_sig'] ?? ''));

            if ($postedSig === '' || !hash_equals((string) $sessionData['signature'], $expectedSig) || !hash_equals($expectedSig, $postedSig)) {
                $consume();
                $this->lastReason = 'invalid_signature';
                $this->recordRateAttempt($ctx, true);
                return false;
            }

            $serverElapsed = time() - $issuedAt;
            if ($serverElapsed < 1) {
                $consume();
                $this->lastReason = 'server_too_fast';
                $this->recordRateAttempt($ctx, true);
                return false;
            }
            if ($serverElapsed > self::CHALLENGE_TTL) {
                $consume();
                $this->lastReason = 'expired';
                $this->recordRateAttempt($ctx, true);
                return false;
            }

            $powNonce = trim((string) ($_POST['fb_pow_nonce'] ?? ''));
            $proofRaw = trim((string) ($_POST['fb_proof'] ?? ''));
            $answer = trim((string) ($_POST['fb_answer'] ?? ''));

            // 1. Vérifications CPU / comportementales AVANT d'ouvrir la transaction SQLite
            $successReason = 'human_behavior';
            if ($proofRaw === '' && $powNonce === '') {
                // Voie sans JavaScript (<noscript>)
                if ($answer === '') {
                    $consume();
                    $this->lastReason = 'not_clicked';
                    $this->recordRateAttempt($ctx, true);
                    return false;
                }
                if ($serverElapsed < 2) {
                    $consume();
                    $this->lastReason = 'server_too_fast';
                    $this->recordRateAttempt($ctx, true);
                    return false;
                }
                $expectedAnswerHash = (string) ($sessionData['answer_hash'] ?? '');
                if ($expectedAnswerHash === '' || !hash_equals($expectedAnswerHash, $this->hashAnswer($answer, $postedNonce))) {
                    $consume();
                    $this->lastReason = 'wrong_answer';
                    $this->recordRateAttempt($ctx, true);
                    return false;
                }
                $successReason = 'noscript_answer';
            } else {
                // Voie JavaScript : PoW SHA-256 vérifié bit à bit côté serveur
                if ($powNonce === '') {
                    $consume();
                    $this->lastReason = 'missing_pow';
                    $this->recordRateAttempt($ctx, true);
                    return false;
                }
                if (!$this->verifyPowBits($postedNonce, $powNonce, $difficulty)) {
                    $consume();
                    $this->lastReason = 'invalid_pow';
                    $this->recordRateAttempt($ctx, true);
                    return false;
                }

                if ($answer !== '') {
                    $expectedAnswerHash = (string) ($sessionData['answer_hash'] ?? '');
                    if ($expectedAnswerHash === '' || !hash_equals($expectedAnswerHash, $this->hashAnswer($answer, $postedNonce))) {
                        $consume();
                        $this->lastReason = 'wrong_answer';
                        $this->recordRateAttempt($ctx, true);
                        return false;
                    }
                    $successReason = 'fallback_answer';
                } else {
                    $proof = json_decode($proofRaw, true);
                    if (!is_array($proof) || empty($proof['clicked'])) {
                        $consume();
                        $this->lastReason = 'not_clicked';
                        $this->recordRateAttempt($ctx, true);
                        return false;
                    }
                    if (($proof['nonce'] ?? '') !== $postedNonce) {
                        $consume();
                        $this->lastReason = 'invalid_nonce';
                        $this->recordRateAttempt($ctx, true);
                        return false;
                    }
                    if (!empty($proof['webdriver'])) {
                        $consume();
                        $this->lastReason = 'webdriver';
                        $this->recordRateAttempt($ctx, true);
                        return false;
                    }

                    $clientElapsed = (int) ($proof['elapsedMs'] ?? 0);
                    $mouseMoves = (int) ($proof['mouseMoves'] ?? 0);
                    $touchEvents = (int) ($proof['touchEvents'] ?? 0);
                    $keyPresses = (int) ($proof['keyPresses'] ?? 0);
                    $scrollEvents = (int) ($proof['scrollEvents'] ?? 0);

                    $hasHumanInteraction = ($mouseMoves >= 2 || $touchEvents >= 1 || $keyPresses >= 2 || $scrollEvents >= 1);
                    if ($clientElapsed < 800) {
                        $consume();
                        $this->lastReason = 'client_too_fast';
                        $this->recordRateAttempt($ctx, true);
                        return false;
                    }
                    if (!$hasHumanInteraction) {
                        $consume();
                        $this->lastReason = 'no_human_interaction';
                        $this->recordRateAttempt($ctx, true);
                        return false;
                    }
                }
            }

            // 2. Section critique atomique (BEGIN IMMEDIATE) : rate-limit + consommation nonce + enregistrement
            unset($_SESSION['antispam_flexybot']);
            $challengeConsumed = true;
            if (!$this->commitValidationAtomically($ctx, $postedNonce)) {
                return false;
            }
            $this->lastReason = $successReason;
            return true;
        } finally {
            if (!$challengeConsumed) {
                $consume();
            }
        }
    }

    protected function initChallenge(): array
    {
        $a = random_int(5, 19);
        $b = random_int(3, 14);
        if ($a > $b && random_int(0, 1) === 1) {
            $question = sprintf('Combien font %d - %d ?', $a, $b);
            $answer = (string) ($a - $b);
        } else {
            $question = sprintf('Combien font %d + %d ?', $a, $b);
            $answer = (string) ($a + $b);
        }

        $nonce = bin2hex(random_bytes(16));
        $issuedAt = time();
        $difficulty = $this->computeAdaptiveDifficulty();
        $signature = $this->signChallenge($nonce, $issuedAt, $difficulty);

        $data = [
            'nonce'       => $nonce,
            'issued_at'   => $issuedAt,
            'difficulty'  => $difficulty,
            'signature'   => $signature,
            'question'    => $question,
            'answer_hash' => $this->hashAnswer($answer, $nonce),
        ];
        $_SESSION['antispam_flexybot'] = $data;
        return $data;
    }

    protected function computeAdaptiveDifficulty(): int
    {
        $ctx = $this->getRateContext();
        $counts = $this->getRecentActivityCounts($ctx);
        $recentAttempts = (int) ($counts['global_attempts'] ?? 0);
        $recentFailures = (int) ($counts['global_failures'] ?? 0);

        if ($recentAttempts >= 6 || $recentFailures >= 3) {
            return self::DIFFICULTY_BURST;
        }
        if ($recentAttempts >= 3 || $recentFailures >= 1) {
            return self::DIFFICULTY_SUSPECT;
        }
        return self::DIFFICULTY_BASE;
    }

    protected function verifyPowBits(string $nonce, string $powNonce, int $difficultyBits): bool
    {
        if (!preg_match('/^[a-zA-Z0-9]{1,32}$/', $powNonce)) {
            return false;
        }
        $difficultyBits = max(8, min(24, $difficultyBits));
        $rawHash = hash('sha256', $nonce . ':' . $powNonce, true);

        $fullBytes = intdiv($difficultyBits, 8);
        $remBits = $difficultyBits % 8;
        for ($i = 0; $i < $fullBytes; $i++) {
            if (ord($rawHash[$i]) !== 0) {
                return false;
            }
        }
        if ($remBits > 0) {
            $mask = (0xFF << (8 - $remBits)) & 0xFF;
            if ((ord($rawHash[$fullBytes]) & $mask) !== 0) {
                return false;
            }
        }
        return true;
    }

    protected function signChallenge(string $nonce, int $issuedAt, int $difficulty): string
    {
        return hash_hmac('sha256', $nonce . '|' . $issuedAt . '|' . $difficulty, $this->getHmacSecret());
    }

    protected function hashAnswer(string $answer, string $nonce): string
    {
        return hash_hmac('sha256', trim($answer) . '|' . $nonce, $this->getHmacSecret());
    }

    protected function getHmacSecret(): string
    {
        static $cached = null;
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $dir = defined('DATA_PLUGIN') ? (DATA_PLUGIN . 'antispam' . DS) : (sys_get_temp_dir() . DIRECTORY_SEPARATOR . '299ko-antispam' . DIRECTORY_SEPARATOR);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $secretFile = $dir . 'secret.php';
        if (is_file($secretFile)) {
            $loaded = @include $secretFile;
            if (is_string($loaded) && strlen($loaded) >= 32) {
                $cached = $loaded;
                return $cached;
            }
        }

        $generated = bin2hex(random_bytes(32));
        $phpContent = "<?php\ndefined('ROOT') OR exit('Access denied!');\nreturn '" . $generated . "';\n";
        @file_put_contents($secretFile, $phpContent, LOCK_EX);
        @chmod($secretFile, 0640);
        $cached = $generated;
        return $cached;
    }

    protected function getRateContext(): array
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        $uri = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $uri = $uri !== '' ? $uri : '/';
        $secret = $this->getHmacSecret();

        return [
            'ipHash'    => hash_hmac('sha256', $ip, $secret),
            'routeHash' => hash_hmac('sha256', $ip . '|' . $uri, $secret),
        ];
    }

    /**
     * Réutilise Core\Database ('sqlite:antispam') lorsque pdo_sqlite est disponible.
     */
    protected function getSqlite(): ?\PDO
    {
        if ($this->sqliteChecked) {
            return $this->sqlitePdo;
        }
        $this->sqliteChecked = true;

        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            return null;
        }

        try {
            if (class_exists('\Core\Database')) {
                $pdo = \Core\Database::getInstance()->getPdo('sqlite:antispam');
            } else {
                $path = (defined('DATA') ? DATA : sys_get_temp_dir() . DIRECTORY_SEPARATOR) . 'storage' . DIRECTORY_SEPARATOR . 'antispam.sqlite';
                if (!is_dir(dirname($path))) {
                    @mkdir(dirname($path), 0755, true);
                }
                $pdo = new \PDO('sqlite:' . $path, null, null, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_TIMEOUT => 2,
                ]);
            }

            $pdo->exec('PRAGMA journal_mode=WAL;');
            $pdo->exec('PRAGMA busy_timeout=2000;');
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS antispam_rate_limits (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    ip_hash TEXT NOT NULL,
                    route_hash TEXT NOT NULL,
                    created_at INTEGER NOT NULL,
                    is_failure INTEGER NOT NULL DEFAULT 0
                );'
            );
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_rl_ip_time ON antispam_rate_limits (ip_hash, created_at);');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_rl_route_time ON antispam_rate_limits (route_hash, created_at);');
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS antispam_used_nonces (
                    nonce_hash TEXT PRIMARY KEY,
                    expires_at INTEGER NOT NULL
                );'
            );
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_used_nonces_exp ON antispam_used_nonces (expires_at);');

            $this->sqlitePdo = $pdo;
        } catch (\Throwable $e) {
            $this->sqlitePdo = null;
        }

        return $this->sqlitePdo;
    }

    /**
     * Atomically check rate limits, consume the nonce, and record the attempt
     * under a single SQLite BEGIN IMMEDIATE transaction on the same PDO connection.
     */
    protected function commitValidationAtomically(array $ctx, string $nonce): bool
    {
        $pdo = $this->getSqlite();
        if ($pdo !== null) {
            try {
                $pdo->exec('BEGIN IMMEDIATE');
                if ($this->isRateLimited($ctx)) {
                    $this->lastReason = 'rate_limited';
                    $this->recordRateAttempt($ctx, true);
                    $pdo->exec('COMMIT');
                    return false;
                }
                if (!$this->markNonceUsed($nonce, true)) {
                    $this->lastReason = 'invalid_nonce';
                    $this->recordRateAttempt($ctx, true);
                    $pdo->exec('COMMIT');
                    return false;
                }
                $this->recordRateAttempt($ctx, false);
                $pdo->exec('COMMIT');
                return true;
            } catch (\Throwable $e) {
                try {
                    $pdo->exec('ROLLBACK');
                } catch (\Throwable $ignored) {
                }
                $this->lastReason = 'invalid_nonce';
                return false;
            }
        }

        if ($this->isRateLimited($ctx)) {
            $this->lastReason = 'rate_limited';
            $this->recordRateAttempt($ctx, true);
            return false;
        }
        if (!$this->markNonceUsed($nonce, false)) {
            $this->lastReason = 'invalid_nonce';
            $this->recordRateAttempt($ctx, true);
            return false;
        }
        $this->recordRateAttempt($ctx, false);
        return true;
    }

    protected function markNonceUsed(string $nonce, bool $inTransaction = false): bool
    {
        $nonceHash = hash('sha256', $nonce);
        $now = time();
        $expiresAt = $now + self::CHALLENGE_TTL;

        $pdo = $this->getSqlite();
        if ($pdo !== null) {
            try {
                if (!$inTransaction) {
                    $pdo->exec('BEGIN IMMEDIATE');
                }
                $pdo->prepare('DELETE FROM antispam_used_nonces WHERE expires_at < :now')->execute([':now' => $now]);
                $stmt = $pdo->prepare('INSERT INTO antispam_used_nonces (nonce_hash, expires_at) VALUES (:h, :exp)');
                $stmt->execute([':h' => $nonceHash, ':exp' => $expiresAt]);
                if (!$inTransaction) {
                    $pdo->exec('COMMIT');
                }
                return true;
            } catch (\Throwable $e) {
                if (!$inTransaction) {
                    try {
                        $pdo->exec('ROLLBACK');
                    } catch (\Throwable $ignored) {
                    }
                }
                return false;
            }
        }

        // Fallback atomique flock si pdo_sqlite absent
        $file = $this->fallbackStoragePath('used_nonces.json');
        $fp = @fopen($file, 'c+');
        if (!$fp) {
            return true;
        }
        @flock($fp, LOCK_EX);
        $data = json_decode((string) stream_get_contents($fp), true);
        if (!is_array($data)) {
            $data = [];
        }
        foreach ($data as $h => $exp) {
            if ((int) $exp < $now) {
                unset($data[$h]);
            }
        }
        if (isset($data[$nonceHash])) {
            @flock($fp, LOCK_UN);
            fclose($fp);
            return false;
        }
        $data[$nonceHash] = $expiresAt;
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, (string) json_encode($data));
        fflush($fp);
        @flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    }

    protected function getRecentActivityCounts(array $ctx): array
    {
        $cutoff = time() - self::RATE_WINDOW_SECONDS;
        $pdo = $this->getSqlite();
        if ($pdo !== null) {
            try {
                $stmt = $pdo->prepare(
                    'SELECT
                        SUM(CASE WHEN ip_hash = :ip THEN 1 ELSE 0 END) AS global_attempts,
                        SUM(CASE WHEN ip_hash = :ip AND is_failure = 1 THEN 1 ELSE 0 END) AS global_failures,
                        SUM(CASE WHEN route_hash = :rt THEN 1 ELSE 0 END) AS route_attempts,
                        SUM(CASE WHEN route_hash = :rt AND is_failure = 1 THEN 1 ELSE 0 END) AS route_failures
                     FROM antispam_rate_limits
                     WHERE created_at >= :cutoff AND (ip_hash = :ip OR route_hash = :rt)'
                );
                $stmt->execute([
                    ':ip' => $ctx['ipHash'],
                    ':rt' => $ctx['routeHash'],
                    ':cutoff' => $cutoff,
                ]);
                $row = $stmt->fetch() ?: [];
                return [
                    'global_attempts' => (int) ($row['global_attempts'] ?? 0),
                    'global_failures' => (int) ($row['global_failures'] ?? 0),
                    'route_attempts'  => (int) ($row['route_attempts'] ?? 0),
                    'route_failures'  => (int) ($row['route_failures'] ?? 0),
                ];
            } catch (\Throwable $e) {
            }
        }

        $file = $this->fallbackStoragePath('ratelimit.json');
        if (!is_file($file)) {
            return ['global_attempts' => 0, 'global_failures' => 0, 'route_attempts' => 0, 'route_failures' => 0];
        }
        $fp = @fopen($file, 'r');
        if (!$fp) {
            return ['global_attempts' => 0, 'global_failures' => 0, 'route_attempts' => 0, 'route_failures' => 0];
        }
        @flock($fp, LOCK_SH);
        $data = json_decode((string) stream_get_contents($fp), true);
        @flock($fp, LOCK_UN);
        fclose($fp);

        if (!is_array($data)) {
            return ['global_attempts' => 0, 'global_failures' => 0, 'route_attempts' => 0, 'route_failures' => 0];
        }
        $gAtt = array_filter((array) ($data['global'][$ctx['ipHash']]['attempts'] ?? []), fn($ts) => (int) $ts >= $cutoff);
        $gFail = array_filter((array) ($data['global'][$ctx['ipHash']]['failures'] ?? []), fn($ts) => (int) $ts >= $cutoff);
        $rAtt = array_filter((array) ($data['routes'][$ctx['routeHash']]['attempts'] ?? []), fn($ts) => (int) $ts >= $cutoff);
        $rFail = array_filter((array) ($data['routes'][$ctx['routeHash']]['failures'] ?? []), fn($ts) => (int) $ts >= $cutoff);

        return [
            'global_attempts' => count($gAtt),
            'global_failures' => count($gFail),
            'route_attempts'  => count($rAtt),
            'route_failures'  => count($rFail),
        ];
    }

    protected function isRateLimited(array $ctx): bool
    {
        $counts = $this->getRecentActivityCounts($ctx);
        return $counts['global_attempts'] >= self::RATE_MAX_ATTEMPTS_GLOBAL
            || $counts['route_attempts'] >= self::RATE_MAX_ATTEMPTS_ROUTE
            || $counts['route_failures'] >= self::RATE_MAX_FAILURES_ROUTE;
    }

    protected function recordRateAttempt(array $ctx, bool $failed): void
    {
        $now = time();
        $cutoff = $now - self::RATE_WINDOW_SECONDS;

        $pdo = $this->getSqlite();
        if ($pdo !== null) {
            try {
                $pdo->prepare('DELETE FROM antispam_rate_limits WHERE created_at < :cutoff')->execute([':cutoff' => $cutoff]);
                $pdo->prepare(
                    'INSERT INTO antispam_rate_limits (ip_hash, route_hash, created_at, is_failure)
                     VALUES (:ip, :rt, :ts, :fail)'
                )->execute([
                    ':ip' => $ctx['ipHash'],
                    ':rt' => $ctx['routeHash'],
                    ':ts' => $now,
                    ':fail' => $failed ? 1 : 0,
                ]);
                return;
            } catch (\Throwable $e) {
            }
        }

        $file = $this->fallbackStoragePath('ratelimit.json');
        $fp = @fopen($file, 'c+');
        if (!$fp) {
            return;
        }
        if (!@flock($fp, LOCK_EX)) {
            fclose($fp);
            return;
        }
        $data = json_decode((string) stream_get_contents($fp), true);
        if (!is_array($data)) {
            $data = ['global' => [], 'routes' => []];
        }

        foreach (['global', 'routes'] as $scope) {
            foreach ((array) ($data[$scope] ?? []) as $k => $bucket) {
                $a = array_values(array_filter((array) ($bucket['attempts'] ?? []), fn($ts) => (int) $ts >= $cutoff));
                $f = array_values(array_filter((array) ($bucket['failures'] ?? []), fn($ts) => (int) $ts >= $cutoff));
                if (empty($a) && empty($f)) {
                    unset($data[$scope][$k]);
                } else {
                    $data[$scope][$k] = ['attempts' => $a, 'failures' => $f];
                }
            }
        }

        $data['global'][$ctx['ipHash']]['attempts'][] = $now;
        $data['routes'][$ctx['routeHash']]['attempts'][] = $now;
        if ($failed) {
            $data['global'][$ctx['ipHash']]['failures'][] = $now;
            $data['routes'][$ctx['routeHash']]['failures'][] = $now;
        }

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, (string) json_encode($data));
        fflush($fp);
        @flock($fp, LOCK_UN);
        fclose($fp);
    }

    protected function fallbackStoragePath(string $filename): string
    {
        $dir = defined('DATA_PLUGIN') ? (DATA_PLUGIN . 'antispam' . DS) : (sys_get_temp_dir() . DIRECTORY_SEPARATOR . '299ko-antispam' . DIRECTORY_SEPARATOR);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir . $filename;
    }
}
