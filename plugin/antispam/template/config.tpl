<script>
    function onClickRadio() {
        if (document.getElementById("radioRecaptcha").checked) {
            document.getElementById("useRecaptcha").style.display = 'block';
            document.querySelectorAll("#useRecaptcha input[type=text]").forEach(function (item) {
                item.disabled = false;
            });
        } else {
            document.getElementById("useRecaptcha").style.display = 'none';
            document.querySelectorAll("#useRecaptcha input[type=text]").forEach(function (item) {
                item.disabled = true;
            });
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        onClickRadio();
        document.querySelectorAll("input[type=radio]").forEach(function (item) {
            item.addEventListener("click", function () {
                onClickRadio();
            });
        });
    });
</script>
<section class="module-card">
    <header><i class="fa-solid fa-shield-halved"></i> {{Lang.antispam.conf-head}}</header>

    <div class="tabs-container" data-tab-key="antispam">
        <ul class="tabs-header">
            <li class="default-tab" data-tab="config">
                <i class="fa-solid fa-sliders"></i> {{Lang.core-settings}}
            </li>
            <li data-tab="help">
                <i class="fa-solid fa-circle-question"></i> {{Lang.core-help}}
            </li>
        </ul>

        <ul class="tabs">
            <!-- Tab 1: Configuration -->
            <li class="tab">
                <form method="post" action='{ ROUTER.generate("antispam-saveconf") }'>
                    {{SHOW.tokenField}}
                    <p>
                        <input {{useText}} type="radio" name="captcha" value="useText" id="radioText"/><label for="radioText">{{Lang.antispam.use-text-captcha}}</label>
                    </p>
                    <p>
                        <input {{useIcon}} type="radio" name="captcha" value="useIcon" id="radioIcon"/><label for="radioIcon">{{Lang.antispam.use-icon-captcha}}</label>
                    </p>
                    <p>
                        <input {{useFlexybot}} type="radio" name="captcha" value="useFlexybot" id="radioFlexybot"/><label for="radioFlexybot">{{Lang.antispam.use-flexybot-captcha}}</label>
                    </p>
                    <p>
                        <input {{useRecaptcha}} type="radio" name="captcha" id="radioRecaptcha" value="useRecaptcha" /><label for="radioRecaptcha">{{Lang.antispam.use-google-captcha}} (<a href="https://www.google.com/recaptcha/admin/create" target="_blank">{{Lang.antispam.use-google-captcha-register}}</a>)</label>
                    </p>
                    <section id="useRecaptcha">
                        <header>{{Lang.antispam.google-captcha-config}}</header>
                        <p>
                            <label>{{Lang.antispam.google-captcha-public-key}}</label><br>
                            <input type="text" required="required" name="recaptchaPublicKey" value="{{runPlugin.getConfigVal("recaptchaPublicKey")}}" />
                        </p>
                        <p>
                            <label>{{Lang.antispam.google-captcha-secret-key}}</label><br>
                            <input type="text" required="required" name="recaptchaSecretKey" value="{{runPlugin.getConfigVal("recaptchaSecretKey")}}" />
                        </p>
                    </section>
                    <p>
                        <button type="submit" class="button primary"><i class="fa-solid fa-save"></i> {{Lang.submit}}</button>
                    </p>
                </form>
            </li>

            <!-- Tab 2: Aide & Exemples visuels -->
            <li class="tab">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
                    <section style="margin: 0;">
                        <header><i class="fa-solid fa-calculator"></i> {{Lang.antispam.text-captcha}}</header>
                        <p>{{Lang.antispam.use-text-desc}}</p>
                        <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 14px; margin-top: 10px;">
                            <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Aperçu visiteur</div>
                            <label style="display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 6px;">Combien font quatre plus trois (en chiffres) *</label>
                            <input type="text" value="7" readonly style="max-width: 110px; background: #fff; margin: 0;" />
                        </div>
                    </section>

                    <section style="margin: 0;">
                        <header><i class="fa-solid fa-icons"></i> {{Lang.antispam.use-icon-captcha}}</header>
                        <p>{{Lang.antispam.use-icon-desc}}</p>
                        <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 14px; margin-top: 10px;">
                            <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Aperçu visiteur</div>
                            <div style="font-size: 0.85rem; margin-bottom: 10px;">Veuillez sélectionner l'image la <strong>moins</strong> présente :</div>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <span style="width: 42px; height: 42px; border: 2px solid #cbd5e1; border-radius: 8px; background: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; color: #334155;"><i class="fa-solid fa-star"></i></span>
                                <span style="width: 42px; height: 42px; border: 2px solid #cbd5e1; border-radius: 8px; background: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; color: #334155;"><i class="fa-solid fa-star"></i></span>
                                <span style="width: 42px; height: 42px; border: 2px solid var(--primary-color, #1d4ed8); border-radius: 8px; background: var(--primary-color, #1d4ed8); display: inline-flex; align-items: center; justify-content: center; font-size: 18px; color: #fff;"><i class="fa-solid fa-heart"></i></span>
                                <span style="width: 42px; height: 42px; border: 2px solid #cbd5e1; border-radius: 8px; background: #fff; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; color: #334155;"><i class="fa-solid fa-star"></i></span>
                            </div>
                        </div>
                    </section>

                    <section style="margin: 0;">
                        <header><i class="fa-solid fa-robot"></i> {{Lang.antispam.flexybot-captcha}}</header>
                        <p>{{Lang.antispam.use-flexybot-desc}}</p>
                        <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 14px; margin-top: 10px;">
                            <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Aperçu visiteur</div>
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; background: #fff; border: 1px solid #d1d5db; border-radius: 8px; padding: 10px 12px;">
                                <span style="display: inline-flex; align-items: center; gap: 8px; font-size: 0.88rem; font-weight: 600; color: #0f172a;">
                                    <span style="width: 22px; height: 22px; border-radius: 4px; background: #16a34a; color: #fff; display: inline-flex; align-items: center; justify-content: center;"><i class="fa-solid fa-check"></i></span>
                                    Vérifié (1 clic humain)
                                </span>
                                <span style="font-size: 0.72rem; color: #64748b;"><i class="fa-solid fa-shield-halved"></i> Local</span>
                            </div>
                        </div>
                    </section>

                    <section style="margin: 0;">
                        <header><i class="fa-brands fa-google"></i> {{Lang.antispam.google-captcha}}</header>
                        <p>{{Lang.antispam.google-captcha-desc}}</p>
                        <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 14px; margin-top: 10px;">
                            <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 8px;">Aperçu visiteur</div>
                            <div style="display: inline-flex; align-items: center; gap: 12px; background: #fff; border: 1px solid #d1d5db; border-radius: 6px; padding: 10px 14px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                                <span style="width: 22px; height: 22px; border: 2px solid #16a34a; border-radius: 3px; display: inline-flex; align-items: center; justify-content: center; color: #16a34a;"><i class="fa-solid fa-check"></i></span>
                                <span style="font-size: 0.88rem; font-weight: 500; color: #1f2937;">Je ne suis pas un robot</span>
                                <i class="fa-solid fa-arrows-rotate" style="color: #2563eb; margin-left: 8px;"></i>
                            </div>
                        </div>
                    </section>
                </div>
            </li>
        </ul>
    </div>
</section>
