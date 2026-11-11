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

<style>
    .antispam-icon-container {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        margin-top: 10px;
    }
    .antispam-icon input[type="radio"] {
        display: none;
    }
    .antispam-icon label {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 60px;
        height: 60px;
        border: 2px solid #ddd;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #f9f9f9;
        font-size: 24px;
        color: #444;
    }
    .antispam-icon label:hover {
        background: #eee;
        border-color: #999;
    }
    .antispam-icon input[type="radio"]:checked + label {
        background: var(--primary-color, #00acc1);
        border-color: var(--primary-color, #00acc1);
        color: #fff;
    }
</style>