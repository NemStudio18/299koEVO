<!doctype html>
<html lang="{{ lang.getLocale}}">
	<head>
		{% HOOK.frontHead %}
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta name="robots" content="noindex"><meta name="googlebot" content="noindex">
		<title>299ko - {{ Lang.core-connection }}</title>
		<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=5"/>
		<meta name="description" content="{{ SHOW.metaDescriptionTag }}"/>
		<link rel="icon" href="{{ SHOW.themeIcon }}"/>
		{{ SHOW.linkTags }}
		{{ SHOW.scriptTags }}
		{{ SHOW.showMetas }}
		{% HOOK.endFrontHead %}
	</head>
	<body class="login">
		<div id="alert-msg">
			{{ SHOW.displayMsg }}
		</div>
		<div id="login" class="card">
			<header>
				<div>
					<h2>{{ Lang.core-connection }}</h2>
				</div>
			</header>
			<form method="post" action="{{ loginLink}}">
				<p>
					<label for="adminEmail">{{Lang.email}}</label><br>
					<input style="display:none;" type="text" name="_email" value="" autocomplete="off"/>
					<input type="email" id="adminEmail" name="adminEmail" required>
				</p>
                <p>
                    <label for="adminPwd">{{Lang.password}}</label>
                    <input type="password" id="adminPwd" name="adminPwd" required></p>
                <p>
                    <input type="checkbox" name="remember" id="remember"/>
                    <label for="remember">{{ Lang.users.remember}}</label>
                </p>
                <p>
                    <a class="button alert" href='{{CORE.getConfigVal("siteUrl")}}'>{{Lang.quit}}</a>
                    <input type="submit" class="button" value="{{Lang.validate}}"/>
                </p>

				<p>
					<a href="{{lostLink}}">{{Lang.lost-password}}</a>
				</p>
				<p class="just_using">
					<a target="_blank" href="https://github.com/299ko/">{{Lang.site-just-using( )}}</a>
				</p>
			</form>
		</div>
	</body>
</html>
