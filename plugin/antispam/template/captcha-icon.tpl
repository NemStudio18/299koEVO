<div class="antispam-container">
	<p>
		{% if lessOrMore %}
			{{Lang.antispam.icon-more-present}}
		{% else %}
			{{Lang.antispam.icon-less-present}}
		{% endif %}
	</p>
    <div class="antispam-icon-container">
        {% for k , icon in IconsToDisplay %}
            <div class="antispam-icon">
                <input type="radio" id="captcha-{{k}}" name="iconCaptcha" value="{{icon.id}}"/>
                <label for="captcha-{{k}}">
                    <i class="fa-solid fa-{{icon.name}} fa-rotate-{{icon.rotate}}"></i>
                </label>
            </div>
        {% endfor %}
    </div>
</div>