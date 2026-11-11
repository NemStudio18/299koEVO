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
<section>
    <header>{{Lang.antispam.conf-head}}</header>
    <form method="post" action='{ ROUTER.generate("antispam-saveconf") }'>
        {{SHOW.tokenField}}
        <p>
            <input {{useText}} type="radio" name="captcha" value="useText" id="radioText"/><label for="radioText">{{Lang.antispam.use-text-captcha}}</label>
        </p>
        <p>
            <input {{useIcon}} type="radio" name="captcha" value="useIcon" id="radioIcon"/><label for="radioIcon">{{Lang.antispam.use-icon-captcha}}</label>
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
            <button type="submit" class="button">{{Lang.submit}}</button>
        </p>
    </form>
</section>
