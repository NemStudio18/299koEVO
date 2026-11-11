<?php

/**
 * Self-hosted behavioral + interactive fallback captcha inspired by FlexyBOT.
 * Zero external service / zero third-party script.
 */
defined('ROOT') OR exit('Access denied!');

class AntispamFlexyBotCaptcha extends AntispamAbstractCaptcha
{
    public function getText(): string
    {
        $challenge = $this->initChallenge();
        $id = 'fb_' . substr($challenge['nonce'], 0, 8);
        $question = htmlspecialchars($challenge['question'], ENT_QUOTES, 'UTF-8');
        $nonce = htmlspecialchars($challenge['nonce'], ENT_QUOTES, 'UTF-8');

        return <<<HTML
<div class="antispam-flexybot-box" id="{$id}" data-nonce="{$nonce}">
    <input type="hidden" name="fb_nonce" value="{$nonce}" />
    <input type="hidden" name="fb_proof" class="fb-proof-input" value="" />
    <div class="fb-widget-row">
        <button type="button" class="fb-check-btn" aria-pressed="false">
            <span class="fb-check-square"><i class="fa-solid fa-check"></i></span>
            <span class="fb-check-label">Je ne suis pas un robot</span>
        </button>
        <span class="fb-shield-tag"><i class="fa-solid fa-shield-halved"></i> Protection locale</span>
    </div>
    <div class="fb-challenge-panel">
        <label class="fb-challenge-label" for="{$id}_ans">Vérification complémentaire : {$question} (en chiffres)</label>
        <input type="text" id="{$id}_ans" name="fb_answer" class="fb-answer-input" inputmode="numeric" autocomplete="off" value="" />
    </div>
</div>
{$this->getGenericHtml()}
<script>
(function() {
    const box = document.getElementById('{$id}');
    if (!box) return;
    const startTs = Date.now();
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
    const proofInput = box.querySelector('.fb-proof-input');
    const panel = box.querySelector('.fb-challenge-panel');
    const ansInput = box.querySelector('.fb-answer-input');
    const form = box.closest('form');
    const submitControls = form
        ? Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"], button:not([type])'))
            .filter(el => el !== btn)
        : [];

    let isVerified = false;

    function setSubmitEnabled(enabled) {
        submitControls.forEach(function(el) {
            el.disabled = !enabled;
            el.classList.toggle('fb-submit-locked', !enabled);
            el.setAttribute('aria-disabled', enabled ? 'false' : 'true');
        });
    }

    setSubmitEnabled(false);

    if (ansInput) {
        ansInput.addEventListener('input', function() {
            if (!isVerified) {
                setSubmitEnabled(this.value.trim() !== '');
            }
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            const hasFallbackAnswer = ansInput && panel.classList.contains('is-open') && ansInput.value.trim() !== '';
            if (!isVerified && !hasFallbackAnswer) {
                e.preventDefault();
                setSubmitEnabled(false);
            }
        });
    }

    btn.addEventListener('click', function() {
        const elapsedMs = Date.now() - startTs;
        const isWebdriver = Boolean(navigator.webdriver);
        const humanEnough = !isWebdriver && elapsedMs >= 800 && (mouseMoves >= 2 || touchEvents >= 1 || keyPresses >= 2 || scrollEvents >= 1);

        proofInput.value = JSON.stringify({
            nonce: box.getAttribute('data-nonce'),
            elapsedMs: elapsedMs,
            mouseMoves: mouseMoves,
            touchEvents: touchEvents,
            keyPresses: keyPresses,
            scrollEvents: scrollEvents,
            webdriver: isWebdriver,
            clicked: true
        });

        if (humanEnough) {
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
                setSubmitEnabled(ansInput.value.trim() !== '');
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
        if (!$this->isGenericValid()) {
            return false;
        }
        $sessionData = $_SESSION['antispam_flexybot'] ?? null;
        if (!is_array($sessionData) || empty($sessionData['nonce'])) {
            $this->lastReason = 'missing_session';
            return false;
        }

        $postedNonce = trim((string) ($_POST['fb_nonce'] ?? ''));
        if ($postedNonce === '' || !hash_equals($sessionData['nonce'], $postedNonce)) {
            $this->lastReason = 'invalid_nonce';
            return false;
        }

        $serverElapsed = time() - (int) ($sessionData['issued_at'] ?? 0);
        if ($serverElapsed < 1) {
            unset($_SESSION['antispam_flexybot']);
            $this->lastReason = 'server_too_fast';
            return false;
        }
        if ($serverElapsed > 3600) {
            unset($_SESSION['antispam_flexybot']);
            $this->lastReason = 'expired';
            return false;
        }

        // Fallback interactif résolu (accessible clavier / lecteurs ou comportement limite)
        $answer = trim((string) ($_POST['fb_answer'] ?? ''));
        if ($answer !== '') {
            $ok = hash_equals($sessionData['answer_hash'], sha1($answer));
            unset($_SESSION['antispam_flexybot']);
            $this->lastReason = $ok ? 'fallback_answer' : 'wrong_answer';
            return $ok;
        }

        $proofRaw = (string) ($_POST['fb_proof'] ?? '');
        $proof = json_decode($proofRaw, true);
        unset($_SESSION['antispam_flexybot']);

        if (!is_array($proof) || empty($proof['clicked'])) {
            $this->lastReason = 'not_clicked';
            return false;
        }
        if (($proof['nonce'] ?? '') !== $postedNonce) {
            $this->lastReason = 'invalid_nonce';
            return false;
        }
        if (!empty($proof['webdriver'])) {
            $this->lastReason = 'webdriver';
            return false;
        }

        $clientElapsed = (int) ($proof['elapsedMs'] ?? 0);
        $mouseMoves = (int) ($proof['mouseMoves'] ?? 0);
        $touchEvents = (int) ($proof['touchEvents'] ?? 0);
        $keyPresses = (int) ($proof['keyPresses'] ?? 0);
        $scrollEvents = (int) ($proof['scrollEvents'] ?? 0);

        $hasHumanInteraction = ($mouseMoves >= 2 || $touchEvents >= 1 || $keyPresses >= 2 || $scrollEvents >= 1);
        if ($clientElapsed < 800) {
            $this->lastReason = 'client_too_fast';
            return false;
        }
        if (!$hasHumanInteraction) {
            $this->lastReason = 'no_human_interaction';
            return false;
        }

        $this->lastReason = 'human_behavior';
        return true;
    }

    protected function initChallenge(): array
    {
        $a = random_int(2, 9);
        $b = random_int(1, 9);
        $nonce = bin2hex(random_bytes(16));
        $question = sprintf('Combien font %d + %d ?', $a, $b);
        $data = [
            'nonce'       => $nonce,
            'issued_at'   => time(),
            'question'    => $question,
            'answer_hash' => sha1((string) ($a + $b)),
        ];
        $_SESSION['antispam_flexybot'] = $data;
        return $data;
    }
}

