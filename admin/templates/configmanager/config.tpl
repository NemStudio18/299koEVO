<form id="configForm" method="post" action="{{link}}" autocomplete="off">
	{{SHOW.tokenField}}

	<div class='tabs-container'>
		<ul class="tabs-header">
			<li class="default-tab">
				<i class="fa-solid fa-gear"></i>
				{{Lang.configmanager-settings}}
      </li>
			<li>
				<i class="fa-solid fa-gears"></i>
				{{Lang.configmanager-advanced-settings}}
      </li>
      <li>
        <i class="fa-solid fa-gauge-high"></i>
        {{Lang.configmanager-cache-settings}}
      </li>
		</ul>
    
		<ul class="tabs">
			<li class="tab">
				<p>
					<input {% if CORE.getConfigVal("hideTitles") %} checked {% endif %} type="checkbox" name="hideTitles" id="hideTitles"/>
					<label for="hideTitles">{{Lang.configmanager-hide-titles}}</label>
				</p>
				<p>
					<label for="defaultPlugin">{{Lang.configmanager-public-default-plugin}}</label>
					<select name="defaultPlugin">
						<option value="page" {% IF defaultPlugin == "page" %}selected{% ENDIF %}>{{ Lang.page.name }}</option>
						{% FOR plugin IN pluginsManager.getPlugins %}
							{% IF plugin.getCOnfigVal("activate") && plugin.getIsCallableOnPublic %}
								<option {% IF plugin.getIsDefaultPlugin %} selected {% ENDIF %} value="{{plugin.getName}}">{{plugin.getInfoVal("name")}}</option>
							{% ENDIF %}
						{% ENDFOR %}
					</select>
				</p>
				<p>
					<label for="defaultAdminPlugin">{{Lang.configmanager-admin-default-plugin}}</label>
					<select name="defaultAdminPlugin">
						<option value="configmanager" {% IF defaultAdminPlugin == "configmanager" %}selected{% ENDIF %}>{{ Lang.configmanager.name }}</option>
						<option value="page" {% IF defaultAdminPlugin == "page" %}selected{% ENDIF %}>{{ Lang.page.name }}</option>
						<option value="users" {% IF defaultAdminPlugin == "users" %}selected{% ENDIF %}>{{ Lang.users.name }}</option>
						<option value="filemanager" {% IF defaultAdminPlugin == "filemanager" %}selected{% ENDIF %}>{{ Lang.filemanager.name }}</option>
						<option value="pluginsmanager" {% IF defaultAdminPlugin == "pluginsmanager" %}selected{% ENDIF %}>{{ Lang.pluginsmanager.name }}</option>
						<option value="marketplace" {% IF defaultAdminPlugin == "marketplace" %}selected{% ENDIF %}>{{ Lang.marketplace.name }}</option>
						{% FOR plugin IN pluginsManager.getPlugins %}
							{% IF plugin.getConfigVal("activate") && plugin.getIsCallableOnAdmin %}
								<option {% IF plugin.getIsDefaultAdminPlugin %} selected {% ENDIF %} value="{{plugin.getName}}">{{plugin.getInfoVal("name")}}</option>
							{% ENDIF %}
						{% ENDFOR %}
					</select>
				</p>
				<p>
					<label for="siteName">{{Lang.configmanager-sitename}}</label>
					<input type="text" name="siteName" id="siteName" value="{{ CORE.getConfigVal("siteName")}}" required/>
				</p>
				<p>
					<label for="siteDesc">{{Lang.configmanager-sitedesc}}</label>
					<input type="text" name="siteDesc" value="{{ CORE.getConfigVal("siteDesc")}}"/>
				</p>
				<p>
					<label for="siteLang">{{Lang.configmanager-sitelang}}</label>
					<select name="siteLang">
						{{langOptions}}
					</select>
				</p>
				<p>
					<label id="label_theme" for="theme">{{Lang.configmanager-theme}}</label>
					<select name="theme">
						{% for k, v in CORE.getThemes %}
							<option {% if CORE.getConfigVal("theme") == k %} selected {% endif %} value="{{k}}">{{v.name}}</option>
						{% endfor %}
					</select>
				</p>
			</li>
			<li class="tab">
				<p>
					<label id='delCacheDesc'>{{Lang.configmanager-delete-cache-desc}}</label><br>
					<a aria-describedby="delCacheDesc" class="button" href="{{cacheClearLink}}">{{Lang.configmanager-delete-cache}}</a>
				</p>
				<p>
					<input {% if CORE.getConfigVal("debug") %} checked {% endif %} type="checkbox" name="debug"/>
					<label for="debug">{{Lang.configmanager-debug}}</label>
				</p>
				<p>
					<label>{{Lang.configmanager-siteurl}}</label><br>
					<input type="text" name="siteUrl" value="{{CORE.getConfigVal("siteUrl")}}"/>
				</p>
				<p>
					<label for="htaccess">{{Lang.configmanager-htaccess}}</label><br>
					<textarea id="htaccess" name="htaccess">{{CORE.getHtaccess}}</textarea>
				</p>
			</li>
      <li class="tab">
        <p>
            <input {% if CORE.getConfigVal("cache_enabled") %}checked{% endif %} type="checkbox" name="cache_enabled" id="cache_enabled" /> 
            <label for="cache_enabled">{{Lang.configmanager-cache-enabled}}</label>
            <br><small>{{Lang.configmanager-cache-enabled-desc}}</small>
        </p>
        <p>
            <label for="cache_duration">{{Lang.configmanager-cache-duration}}</label>
            <input type="number" name="cache_duration" id="cache_duration" value="{% if CORE.getConfigVal("cache_duration") %}{{CORE.getConfigVal("cache_duration")}}{% else %}3600{% endif %}" min="60" max="86400" />
            <br><small>{{Lang.configmanager-cache-duration-desc}}</small>
        </p>
        <p>
            <input {% if CORE.getConfigVal("cache_minify") %}checked{% endif %} type="checkbox" name="cache_minify" id="cache_minify" /> 
            <label for="cache_minify">{{Lang.configmanager-cache-minify}}</label>
            <br><small>{{Lang.configmanager-cache-minify-desc}}</small>
        </p>
        <p>
            <input {% if CORE.getConfigVal("cache_lazy_loading") %}checked{% endif %} type="checkbox" name="cache_lazy_loading" id="cache_lazy_loading" /> 
            <label for="cache_lazy_loading">{{Lang.configmanager-cache-lazy-loading}}</label>
            <br><small>{{Lang.configmanager-cache-lazy-loading-desc}}</small>
        </p>
        <p>
            <label id="cacheClearDesc">{{Lang.configmanager-cache-clear-desc}}</label><br>
            <a aria-describedby="cacheClearDesc" class="button" href="{{cacheClearLink}}">{{Lang.configmanager-cache-clear}}</a>
        </p>

        {% if cacheStats %}
          <div class="cache-stats">
             <p><strong>{{Lang.configmanager-cache-files-count}}:</strong> {{cacheStats.files_count}}</p>
             <p><strong>{{Lang.configmanager-cache-total-size}}:</strong> {{cacheStats.total_size_formatted}}</p>
        </div>
        {% endif %}
      </li>
		</ul>
	</div>
	<p>
		<button type="submit" class="button success">{{Lang.submit}}</button>
	</p>
</form>