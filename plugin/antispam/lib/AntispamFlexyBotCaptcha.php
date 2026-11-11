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
    <div class="fb-challenge-panel" style="display: none;">
        <label class="fb-challenge-label" for="{$id}_ans">Vérification complémentaire : {$question} (en chiffres)</label>
        <input type="text" id="{$id}_ans" name="fb_answer" class="fb-answer-input" inputmode="numeric" autocomplete="off" value="" />
    </div>
</div>
{$this->getGenericHtml()}
<style>
.antispam-flexybot-box {
    max-width: 380px;
    padding: 12px 14px;
    border: 1px solid var(--form-border-color, #cbd5e1);
    border-radius: 10px;
    background: var(--section-head-bg-color, #f8fafc);
    margin: 10px 0;
}
.antispam-flexybot-box .fb-widget-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
}
.antispam-flexybot-box .fb-check-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: transparent;
    border: none;
    padding: 4px 0;
    cursor: pointer;
    font-size: 0.94rem;
    font-weight: 600;
    color: var(--font-color, #1e293b);
}
.antispam-flexybot-box .fb-check-square {
    width: 24px;
    height: 24px;
    border: 2px solid #94a3b8;
    border-radius: 5px;
    background: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: transparent;
    transition: all 0.2s ease;
}
.antispam-flexybot-box.is-verified .fb-check-square {
    border-color: #16a34a;
    background: #16a34a;
    color: #ffffff;
}
.antispam-flexybot-box .fb-shield-tag {
    font-size: 0.72rem;
    font-weight: 600;
    color: #64748b;
}
.antispam-flexybot-box .fb-challenge-panel {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #e2e8f0;
}
.antispam-flexybot-box .fb-challenge-label {
    display: block;
    font-size: 0.86rem;
    font-weight: 600;
    margin-bottom: 6px;
}
.antispam-flexybot-box .fb-answer-input {
    max-width: 140px;
    margin: 0;
}
</style>
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
            box.classList.add('is-verified');
            btn.setAttribute('aria-pressed', 'true');
            label.textContent = 'Vérifié';
            panel.style.display = 'none';
            if (ansInput) ansInput.removeAttribute('required');
        } else {
            box.classList.remove('is-verified');
            btn.setAttribute('aria-pressed', 'true');
            label.textContent = 'Vérification requise';
            panel.style.display = 'block';
            if (ansInput) {
                ansInput.setAttribute('required', 'required');
                ansInput.focus();
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
            return false;
        }

        $postedNonce = trim((string) ($_POST['fb_nonce'] ?? ''));
        if ($postedNonce === '' || !hash_equals($sessionData['nonce'], $postedNonce)) {
            return false;
        }

        $serverElapsed = time() - (int) ($sessionData['issued_at'] ?? 0);
        if ($serverElapsed < 1 || $serverElapsed > 3600) {
            unset($_SESSION['antispam_flexybot']);
            return false;
        }

        // Fallback interactif résolu (accessible clavier / lecteurs ou comportement limite)
        $answer = trim((string) ($_POST['fb_answer'] ?? ''));
        if ($answer !== '') {
            $ok = hash_equals($sessionData['answer_hash'], sha1($answer));
            unset($_SESSION['antispam_flexybot']);
            return $ok;
        }

        $proofRaw = (string) ($_POST['fb_proof'] ?? '');
        $proof = json_decode($proofRaw, true);
        unset($_SESSION['antispam_flexybot']);

        if (!is_array($proof) || empty($proof['clicked'])) {
            return false;
        }
        if (($proof['nonce'] ?? '') !== $postedNonce || !empty($proof['webdriver'])) {
            return false;
        }

        $clientElapsed = (int) ($proof['elapsedMs'] ?? 0);
        $mouseMoves = (int) ($proof['mouseMoves'] ?? 0);
        $touchEvents = (int) ($proof['touchEvents'] ?? 0);
        $keyPresses = (int) ($proof['keyPresses'] ?? 0);
        $scrollEvents = (int) ($proof['scrollEvents'] ?? 0);

        $hasHumanInteraction = ($mouseMoves >= 2 || $touchEvents >= 1 || $keyPresses >= 2 || $scrollEvents >= 1);
        return $clientElapsed >= 800 && $hasHumanInteraction;
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
