<!DOCTYPE html>
<html lang="{{CORE.getConfigVal("siteLang")}}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - {{CORE.getConfigVal("siteName")}}</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" href="{{CORE.getConfigVal("siteUrl")}}/theme/{{CORE.getConfigVal("theme")}}/icon.png" />
    <style>
        :root {
            --primary-color: #e51f1f; /* Atelier Red */
            --text-color: #333;
            --bg-color: #f4f4f4;
            --card-bg: #fff;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --primary-color: #ff4d4d;
                --text-color: #eee;
                --bg-color: #1a1a1a;
                --card-bg: #2c2c2c;
            }
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .maintenance-container {
            background-color: var(--card-bg);
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 20px;
        }
        h1 {
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        .message {
            line-height: 1.6;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        .footer {
            margin-top: 40px;
            font-size: 0.9em;
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        {% IF CORE.getConfigVal("siteLogo") %}
            <img src="{{CORE.getConfigVal("siteLogo")}}" alt="{{CORE.getConfigVal("siteName")}}" class="logo">
        {% ENDIF %}
        
        <h1>Maintenance en cours</h1>
        
        <div class="message">
            {% IF maintenanceMessage %}
                {{maintenanceMessage}}
            {% ELSE %}
                <p>Notre site est actuellement en maintenance pour améliorer votre expérience.</p>
                <p>Nous serons de retour très bientôt.</p>
            {% ENDIF %}
        </div>
        
        <div class="footer">
            &copy; {{ "now"|date("Y") }} {{CORE.getConfigVal("siteName")}}.
        </div>
    </div>
</body>
</html>
