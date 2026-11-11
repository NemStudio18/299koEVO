<h1>{{Lang.meteo.meteo}}</h1>

<form method="POST" action="{ ROUTER.generate("admin-meteo-save") }" class="meteo-form">
    {{SHOW.tokenField()}}

    <fieldset>
        <legend>{{Lang.meteo.location}}</legend>
        <div class="form-group">
            <label>{{Lang.meteo.default-city}}</label>
            <input type="text" name="default_city" value="{{defaultCity}}" required>
        </div>
        <div class="form-group">
            <label>{{Lang.meteo.default-country}}</label>
            <input type="text" name="default_country" value="{{defaultCountry}}" maxlength="2">
            <small>{{Lang.meteo.country-help}}</small>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>{{Lang.meteo.latitude}}</label>
                <input type="text" name="latitude" value="{{latitude}}">
                <small>{{Lang.meteo.coordinates-help}}</small>
            </div>
            <div class="form-group">
                <label>{{Lang.meteo.longitude}}</label>
                <input type="text" name="longitude" value="{{longitude}}">
            </div>
        </div>
    </fieldset>

    <fieldset>
        <legend>{{Lang.meteo.display}}</legend>
        <div class="form-group">
            <label>{{Lang.meteo.units}}</label>
            <select name="units">
                <option value="metric" {% IF units == "metric" %}selected{% ENDIF %}>Métrique (°C, km/h)</option>
                <option value="imperial" {% IF units == "imperial" %}selected{% ENDIF %}>Impérial (°F, mph)</option>
            </select>
        </div>
        <div class="form-group">
            <label>{{Lang.meteo.language}}</label>
            <select name="language">
                <option value="fr" {% IF language == "fr" %}selected{% ENDIF %}>Français</option>
                <option value="en" {% IF language == "en" %}selected{% ENDIF %}>English</option>
                <option value="ru" {% IF language == "ru" %}selected{% ENDIF %}>Русский</option>
            </select>
        </div>
        <div class="form-group">
            <label>{{Lang.meteo.widget-title}}</label>
            <input type="text" name="widget_title" value="{{widgetTitle}}">
        </div>
    <div class="form-group">
        <label>{{Lang.meteo.position}}</label>
        <select name="position">
            <option value="manual" {% IF position == "manual" %}selected{% ENDIF %}>{{Lang.meteo.position.manual}}</option>
            <option value="header" {% IF position == "header" %}selected{% ENDIF %}>{{Lang.meteo.position.header}}</option>
            <option value="below_header" {% IF position == "below_header" %}selected{% ENDIF %}>{{Lang.meteo.position.below-header}}</option>
            <option value="sidebar" {% IF position == "sidebar" %}selected{% ENDIF %}>{{Lang.meteo.position.sidebar}}</option>
            <option value="footer" {% IF position == "footer" %}selected{% ENDIF %}>{{Lang.meteo.position.footer}}</option>
            <option value="bottom" {% IF position == "bottom" %}selected{% ENDIF %}>{{Lang.meteo.position.bottom}}</option>
        </select>
    </div>
        <div class="form-group checkbox">
            <label>
                <input type="checkbox" name="show_forecast" {% IF showForecast %}checked{% ENDIF %}>
                {{Lang.meteo.show-forecast}}
            </label>
        </div>
    </fieldset>

    <button type="submit" class="btn">{{Lang.meteo.save}}</button>
</form>

<section class="meteo-help">
    <h2>{{Lang.meteo.manual-placement}}</h2>
    <p>{{Lang.meteo.manual-description}}</p>
    <pre>{% NOPARSE %}{% HOOK.meteoWidget %}{% ENDNOPARSE %}</pre>
    <p>{{Lang.meteo.manual-note}}</p>
</section>

