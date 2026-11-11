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
            <li data-tab="logs">
                <i class="fa-solid fa-chart-line"></i> Journal &amp; Blocages
                {% if blockedTotal > 0 %}<span class="evo-badge evo-badge-danger">{{blockedTotal}}</span>{% endif %}
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

            <!-- Tab 2: Journal & Blocages -->
            <li class="tab">
                <div class="antispam-stats-kpis">
                    <div class="antispam-kpi is-danger">
                        <span>Bots / soumissions bloqués</span>
                        <strong>{{blockedTotal}}</strong>
                    </div>
                    <div class="antispam-kpi is-success">
                        <span>Validations humaines acceptées</span>
                        <strong>{{passedTotal}}</strong>
                    </div>
                    <div class="antispam-kpi">
                        <span>Taux de blocage</span>
                        <strong>{{blockRate}} %</strong>
                    </div>
                    <div class="antispam-kpi">
                        <span>Protection active</span>
                        <strong>{{activeMethodLabel}}</strong>
                    </div>
                </div>

                {% if reasons %}
                <div class="antispam-reasons-bar">
                    {% for r in reasons %}
                        <span class="evo-badge evo-badge-neutral">{{r.label}} : <strong>{{r.count}}</strong></span>
                    {% endfor %}
                </div>
                {% endif %}

                <div class="antispam-logs-head">
                    <h3>50 dernières vérifications</h3>
                    {% if recentLogs %}
                    <form method="post" action="{{clearStatsUrl}}" onsubmit="return confirm('Réinitialiser le compteur et le journal antispam ?');">
                        {{SHOW.tokenField}}
                        <button type="submit" class="button secondary small"><i class="fa-solid fa-rotate-left"></i> Réinitialiser</button>
                    </form>
                    {% endif %}
                </div>

                {% if recentLogs %}
                <div class="overflow-auto">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Résultat</th>
                                <th>Protection</th>
                                <th>Motif</th>
                                <th>Formulaire / URL</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            {% for log in recentLogs %}
                            <tr>
                                <td>{{log.date}}</td>
                                <td>
                                    {% if log.is_blocked %}
                                        <span class="evo-badge evo-badge-danger"><i class="fa-solid fa-ban"></i> Bloqué</span>
                                    {% else %}
                                        <span class="evo-badge evo-badge-success"><i class="fa-solid fa-check"></i> Accepté</span>
                                    {% endif %}
                                </td>
                                <td>{{log.method_label}}</td>
                                <td>{{log.reason_label}}</td>
                                <td><code>{{log.uri|default('cli')}}</code></td>
                                <td>{{log.ip}}</td>
                            </tr>
                            {% endfor %}
                        </tbody>
                    </table>
                </div>
                {% else %}
                <div class="evo-empty">
                    <i class="fa-solid fa-shield-halved"></i>
                    <p>Aucune tentative enregistrée pour le moment. Dès qu'un formulaire protégé (Contact, Petites Annonces, Blog, Inscription…) sera soumis, les blocages et validations apparaîtront ici.</p>
                </div>
                {% endif %}
            </li>

            <!-- Tab 3: Aide & Exemples visuels -->
            <li class="tab">
                <div class="antispam-help-grid">
                    <section>
                        <header><i class="fa-solid fa-calculator"></i> {{Lang.antispam.text-captcha}}</header>
                        <p>{{Lang.antispam.use-text-desc}}</p>
                        <div class="antispam-preview-card">
                            <div class="antispam-preview-kicker">Aperçu visiteur</div>
                            <label class="antispam-preview-label">Combien font quatre plus trois (en chiffres) *</label>
                            <input type="text" value="7" readonly class="antispam-preview-input" />
                        </div>
                    </section>

                    <section>
                        <header><i class="fa-solid fa-icons"></i> {{Lang.antispam.use-icon-captcha}}</header>
                        <p>{{Lang.antispam.use-icon-desc}}</p>
                        <div class="antispam-preview-card">
                            <div class="antispam-preview-kicker">Aperçu visiteur</div>
                            <div class="antispam-preview-sub">Veuillez sélectionner l'image la <strong>moins</strong> présente :</div>
                            <div class="antispam-preview-icons">
                                <span class="antispam-preview-ico"><i class="fa-solid fa-star"></i></span>
                                <span class="antispam-preview-ico"><i class="fa-solid fa-star"></i></span>
                                <span class="antispam-preview-ico is-active"><i class="fa-solid fa-heart"></i></span>
                                <span class="antispam-preview-ico"><i class="fa-solid fa-star"></i></span>
                            </div>
                        </div>
                    </section>

                    <section>
                        <header><i class="fa-solid fa-robot"></i> {{Lang.antispam.flexybot-captcha}}</header>
                        <p>{{Lang.antispam.use-flexybot-desc}}</p>
                        <div class="antispam-preview-card">
                            <div class="antispam-preview-kicker">Aperçu visiteur</div>
                            <div class="antispam-preview-widget">
                                <span class="antispam-preview-widget-left">
                                    <span class="antispam-preview-check"><i class="fa-solid fa-check"></i></span>
                                    Vérifié (1 clic humain)
                                </span>
                                <span class="antispam-preview-local"><i class="fa-solid fa-shield-halved"></i> Local</span>
                            </div>
                        </div>
                    </section>

                    <section>
                        <header><i class="fa-brands fa-google"></i> {{Lang.antispam.google-captcha}}</header>
                        <p>{{Lang.antispam.google-captcha-desc}}</p>
                        <div class="antispam-preview-card">
                            <div class="antispam-preview-kicker">Aperçu visiteur</div>
                            <div class="antispam-preview-widget">
                                <span class="antispam-preview-widget-left">
                                    <span class="antispam-preview-check-outline"><i class="fa-solid fa-check"></i></span>
                                    Je ne suis pas un robot
                                    <i class="fa-solid fa-arrows-rotate antispam-preview-rc-icon"></i>
                                </span>
                            </div>
                        </div>
                    </section>
                </div>
            </li>
        </ul>
    </div>
</section>
