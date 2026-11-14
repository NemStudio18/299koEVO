<style>
/* ANIMATED WEATHER ICONS (Injected via Widget) */
.weather-icon-container {
    width: 60px; /* Smaller for widget */
    height: 60px; /* Smaller for widget */
    border-radius: 40%;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    overflow: visible;
    z-index: 10;
    transform: scale(0.6); /* Scale down for compact widget */
}

/* 1. HOT / SUNNY */
.weather-icon-container.hot {
    background: linear-gradient(to top right, rgba(255, 87, 34, 1) 0%, rgba(251, 140, 0, 1) 100%);
    box-shadow: 1px 1px 30px rgba(255, 111, 0, 1);
}
.sun {
    position: absolute;
    top: -10%; left: 65%;
    width: 50px; height: 50px;
    border-radius: 100%;
    background: linear-gradient(to bottom left, rgba(255, 235, 59, 1) 0%, rgba(249, 168, 37, 1) 90%);
    box-shadow: 1px 1px 30px rgba(255, 160, 0, 1);
    animation: inex 3s infinite linear;
}
.sunx {
    position: absolute;
    top: 30%; left: 45%;
    width: 10px; height: 10px;
    border-radius: 100%;
    background-color: #fff;
    opacity: 0.2;
}

/* 2. CLOUDY */
.weather-icon-container.cloudy {
    background: linear-gradient(to top right, rgba(63, 81, 181, 1) 0%, rgba(3, 155, 229, 1) 40%);
    box-shadow: 1px 1px 30px rgba(2, 119, 189, 1);
}
.cloud {
    position: absolute;
    top: 5%; left: 70%;
    width: 60px; height: 20px;
    border-radius: 10px;
    background-color: rgba(129, 212, 250, 1);
    box-shadow: 1px 1px 30px rgba(0, 151, 167, 1);
    animation: move 3s infinite linear;
}
.cloudx {
    position: absolute;
    top: 23%; left: 55%;
    width: 60px; height: 20px;
    border-radius: 10px;
    background-color: rgba(129, 212, 250, 1);
    animation: move 3s infinite linear;
}

/* 3. STORMY */
.weather-icon-container.stormy {
    background: linear-gradient(to top right, rgba(117, 117, 117, 1) 0%, rgba(33, 33, 33, 1) 90%);
    box-shadow: 1px 1px 30px rgba(33, 33, 33, 1);
}
.stormy li {
    position: absolute; list-style: none; width: 5px; height: 5px; border-radius: 100%; background-color: #777; box-shadow: 1px 1px 30px #555; animation: fall 3s infinite linear; opacity: 0;
}
.stormy li:nth-child(1) { top: 0%; left: 100%; }
.stormy li:nth-child(2) { top: 5%; left: 90%; }
.stormy li:nth-child(3) { top: -10%; left: 80%; animation-delay: 2s; }
.stormy li:nth-child(4) { top: 10%; left: 100%; animation-delay: 2s; }
.stormy li:nth-child(5) { top: 20%; left: 80%; animation-delay: 0.5s; }
.stormy li:nth-child(6) { top: 35%; left: 70%; background-color: #bbb; animation-delay: 0.5s; }
.stormy li:nth-child(7) { top: 23%; left: 60%; background-color: #bbb; animation-delay: 0.8s; }
.stormy li:nth-child(8) { top: 5%; left: 70%; background-color: #bbb; animation-delay: 0.8s; }
.snowe { position: absolute; top: 60%; left: 40%; width: 40px; height: 40px; border-radius: 60px; background-color: #ddd; }
.snowex { position: absolute; top: 50%; left: 50%; width: 15px; height: 15px; border-radius: 15px; background-color: #bbb; }
.stick { position: absolute; top: 60%; left: 40%; width: 3px; height: 15px; transform: rotate(-45deg); background-color: #333; z-index: -10; }
.stick2 { position: absolute; top: 60%; left: 70%; width: 3px; height: 15px; transform: rotate(45deg); background-color: #333; z-index: -10; }

/* 4. BREEZY */
.weather-icon-container.breezy {
    background: linear-gradient(to top right, rgba(156, 204, 101, 1) 0%, rgba(38, 198, 218, 1) 50%);
    box-shadow: 1px 1px 30px rgba(38, 198, 218, 1);
}
.cloudr {
    position: absolute;
    top: 5%; left: 60%;
    width: 60px; height: 20px;
    border-radius: 10px;
    background-color: rgba(96, 125, 139, 1);
    box-shadow: 1px 1px 30px rgba(84, 110, 122, 1);
    animation: flash 1.5s infinite linear;
}
.breezy li {
    position: absolute; list-style: none; width: 2px; height: 7px; border-radius: 10%; background-color: #eee; opacity: 0; animation: fall 3s infinite linear; transform: rotate(25deg);
}
.breezy li:nth-child(1) { top: 10%; left: 95%; }
.breezy li:nth-child(2) { top: 5%; left: 83%; animation-delay: 0.3s; }
.breezy li:nth-child(3) { top: 4%; left: 87%; animation-delay: 0.6s; }
.breezy li:nth-child(4) { top: 15%; left: 70%; animation-delay: 1s; }
.breezy li:nth-child(5) { top: 10%; left: 75%; }

/* 5. NIGHT */
.weather-icon-container.night {
    background: linear-gradient(to bottom right, rgba(63, 81, 181, 1) 0%, rgba(171, 71, 188, 1) 70%);
    box-shadow: 1px 1px 30px rgba(81, 45, 168, 1);
}
.moon {
    position: absolute;
    top: -10%; left: 65%;
    width: 70px; height: 70px;
    border-radius: 100%;
    background: rgba(255, 241, 118, 1);
    box-shadow: 1px 1px 30px rgba(224, 224, 224, 1);
}
.spot1 { position: absolute; top: 0%; left: 85%; width: 10px; height: 10px; border-radius: 100%; background-color: #777; }
.spot2 { position: absolute; top: 30%; left: 75%; width: 5px; height: 5px; border-radius: 100%; background-color: #777; }
.night li {
    position: absolute; list-style: none; width: 3px; height: 3px; border-radius: 100%; background-color: #fff; transform: rotate(45deg);
}
.night li:nth-child(1) { top: 30%; left: 65%; }
.night li:nth-child(2) { top: 35%; left: 53%; }
.night li:nth-child(3) { opacity: 0; top: 20%; left: 75%; width: 2px; height: 2px; animation: meteor 1.5s infinite linear; animation-delay: 0.6s; }
.night li:nth-child(4) { top: 5%; left: 50%; }
.night li:nth-child(5) { opacity: 0; top: 20%; left: 55%; width: 1px; height: 15px; animation: meteor 1.5s infinite linear; }

/* 6. RAINY */
.weather-icon-container.rainy {
    background: linear-gradient(to top right, #4FC3F7, #1E88E5);
    box-shadow: 1px 1px 30px #1565C0;
}
.rain-cloud {
    position: absolute; top: 20%; left: 35%; width: 60px; height: 25px; border-radius: 20px; background: #BBDEFB;
}
.rainy li {
    position: absolute; list-style: none; width: 3px; height: 12px; background: #E3F2FD; border-radius: 2px; animation: rain 1.5s infinite linear; opacity: 0;
}
.rainy li:nth-child(1) { top: 45%; left: 45%; }
.rainy li:nth-child(2) { top: 50%; left: 55%; animation-delay: 0.2s; }
.rainy li:nth-child(3) { top: 48%; left: 65%; animation-delay: 0.4s; }
.rainy li:nth-child(4) { top: 52%; left: 50%; animation-delay: 0.6s; }
.rainy li:nth-child(5) { top: 47%; left: 60%; animation-delay: 0.8s; }

/* 7. FOGGY */
.weather-icon-container.foggy {
    background: linear-gradient(to bottom, #CFD8DC, #90A4AE);
    box-shadow: 1px 1px 30px #607D8B;
}
.foggy span {
    position: absolute; width: 80px; height: 8px; border-radius: 10px; background: #ECEFF1; left: 15%; animation: fogmove 3s infinite linear;
}
.foggy span:nth-child(1) { top: 35%; }
.foggy span:nth-child(2) { top: 50%; animation-delay: 0.5s; }
.foggy span:nth-child(3) { top: 65%; animation-delay: 1s; }

/* 8. PARTLY CLOUDY */
.weather-icon-container.partly {
    background: linear-gradient(to top right, #81D4FA, #FFB74D);
    box-shadow: 1px 1px 30px #FB8C00;
}
.p-sun {
    position: absolute; top: 5%; left: 60%; width: 40px; height: 40px; border-radius: 100%; background: #FFD54F; animation: inex 3s infinite linear;
}
.p-cloud {
    position: absolute; top: 45%; left: 30%; width: 60px; height: 20px; border-radius: 20px; background: #ECEFF1;
}

/* 9. HEAT / CANICULE */
.weather-icon-container.heat {
    background: linear-gradient(to top right, #FF7043, #D84315);
    box-shadow: 1px 1px 30px #BF360C;
}
.heat span {
    position: absolute; width: 6px; height: 40px; background: #FFF3E0; border-radius: 10px; animation: wave 1.5s infinite ease-in-out;
}
.heat span:nth-child(1) { left: 45%; top: 35%; }
.heat span:nth-child(2) { left: 55%; top: 35%; animation-delay: 0.3s; }
.heat span:nth-child(3) { left: 65%; top: 35%; animation-delay: 0.6s; }

/* KEYFRAMES */
@keyframes inex { 50% { opacity: 0.4; } 60% { opacity: 1; } }
@keyframes move { 50% { transform: translateX(-10px); } }
@keyframes fall { 10% { opacity: 0.8; } 50% { opacity: 1; transform: translate(-10px, 30px); } 100% { transform: translate(-25px, 70px); } }
@keyframes flash { 48% { background-color: rgba(96, 125, 139, 1); } 50% { background-color: #fff; } 55% { background-color: rgba(96, 125, 139, 1); } 57% { background-color: #fff; } }
@keyframes meteor { 10% { opacity: 1; } 80% { left: 10%; top: 75%; opacity: 0; } }
@keyframes rain { 10% { opacity: 1; } 100% { transform: translateY(40px); opacity: 0; } }
@keyframes fogmove { 50% { transform: translateX(-15px); } }
@keyframes wave { 50% { transform: translateY(-10px); } }
</style>
<a href="{ ROUTER.generate("meteo-index") }" class="meteo-widget-compact" 
   data-current-url="{{apiCurrentUrl}}"
   data-default-city="{{defaultCity}}"
   data-default-country="{{defaultCountry}}"
   data-latitude="{{latitude}}"
   data-longitude="{{longitude}}"
   data-language="{{language}}"
   data-units="{{units}}">
    <div class="meteo-widget-compact__icon-wrapper">
        <div class="meteo-widget-compact__icon-container weather-icon-container" data-role="icon-container">
            <span class="meteo-widget-compact__icon" data-role="icon">🌤️</span>
        </div>
        <span class="meteo-widget-compact__temp" data-role="temperature">--°</span>
    </div>
</a>

<script>
(function(){
    function buildUrl(base, widget) {
        const url = new URL(base, window.location.origin);
        const city = widget.dataset.defaultCity;
        const country = widget.dataset.defaultCountry;
        const lat = widget.dataset.latitude;
        const lon = widget.dataset.longitude;
        if (city) url.searchParams.set('city', city);
        if (country) url.searchParams.set('country', country);
        if (lat) url.searchParams.set('latitude', lat);
        if (lon) url.searchParams.set('longitude', lon);
        return url.toString();
    }

    function getAnimatedWeatherHTML(weatherCode, isDay = true) {
        let cls = 'hot';
        let html = '<span class="sun"></span><span class="sunx"></span>';

        // 0: Clear sky
        if (weatherCode === 0) {
             if (!isDay) {
                cls = 'night';
                html = '<span class="moon"></span><span class="spot1"></span><span class="spot2"></span><ul><li></li><li></li><li></li><li></li><li></li></ul>';
            } else {
                cls = 'hot';
                html = '<span class="sun"></span><span class="sunx"></span>';
            }
        } 
        // 1-3: Mainly clear, partly cloudy, overcast
        else if (weatherCode >= 1 && weatherCode <= 3) {
             if (weatherCode <= 2 && isDay) {
                cls = 'partly';
                html = '<span class="p-sun"></span><span class="p-cloud"></span>';
            } else {
                cls = 'cloudy';
                html = '<span class="cloud"></span><span class="cloudx"></span>';
            }
        } 
        // 45, 48: Fog
        else if (weatherCode >= 45 && weatherCode <= 48) {
            cls = 'foggy';
            html = '<span></span><span></span><span></span>';
        }
        // 51-67: Drizzle, Rain
        else if (weatherCode >= 51 && weatherCode <= 67) {
            cls = 'rainy';
            html = '<span class="rain-cloud"></span><ul><li></li><li></li><li></li><li></li><li></li></ul>';
        } 
        // 71-77: Snow
        else if (weatherCode >= 71 && weatherCode <= 77) {
             cls = 'stormy';
             html = '<ul><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li></ul><span class="snowe"></span><span class="snowex"></span><span class="stick"></span><span class="stick2"></span>';
        }
        // 80-82: Rain showers
        else if (weatherCode >= 80 && weatherCode <= 82) {
            cls = 'rainy';
            html = '<span class="rain-cloud"></span><ul><li></li><li></li><li></li><li></li><li></li></ul>';
        } 
        // 85-86: Snow showers
        else if (weatherCode >= 85 && weatherCode <= 86) {
             cls = 'stormy';
             html = '<ul><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li></ul><span class="snowe"></span><span class="snowex"></span><span class="stick"></span><span class="stick2"></span>';
        }
        // 95-99: Thunderstorm
        else if (weatherCode >= 95 && weatherCode <= 99) {
            cls = 'stormy';
            html = '<ul><li></li><li></li><li></li><li></li><li></li><li></li><li></li><li></li></ul><span class="cloud"></span>';
        }

        return { class: cls, html: html };
    }

    function loadData(widget) {
        const currentUrl = buildUrl(widget.dataset.currentUrl, widget);
        fetch(currentUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(payload => {
                if (payload.status === 'success' && payload.data) {
                    const temp = widget.querySelector('[data-role="temperature"]');
                    const iconContainer = widget.querySelector('[data-role="icon-container"]');
                    const iconLegacy = widget.querySelector('[data-role="icon"]');
                    
                    if (temp) temp.textContent = (payload.data.temperature ?? '--') + '°';
                    
                    const weatherCode = payload.data.weather_code ?? 0;
                    const isDay = payload.data.is_day !== undefined ? payload.data.is_day : true;
                    
                    if (iconContainer) {
                        const animated = getAnimatedWeatherHTML(weatherCode, isDay);
                        iconContainer.className = 'meteo-widget-compact__icon-container weather-icon-container ' + animated.class;
                        iconContainer.innerHTML = animated.html;
                    } else if (iconLegacy) {
                        iconLegacy.textContent = '🌤️'; 
                    }
                } else {
                    console.error('Meteo API error:', payload.message || 'Unknown error');
                }
            })
            .catch(error => {
                console.error('Meteo fetch error:', error);
            });
    }

    function initWidgets() {
        document.querySelectorAll('.meteo-widget-compact:not([data-init="1"])').forEach(widget => {
            widget.dataset.init = '1';
            loadData(widget);
        });
    }

    // Listen for global geolocation updates
    window.addEventListener('user-location-updated', function(e) {
        const coords = e.detail;
        if (!coords || !coords.lat || !coords.lon) return;

        document.querySelectorAll('.meteo-widget-compact').forEach(widget => {
            console.log('Meteo: Updating location from geolocation event', coords);
            widget.dataset.latitude = coords.lat;
            widget.dataset.longitude = coords.lon;
            // Clear city to favor coordinates
            widget.dataset.defaultCity = ''; 
            loadData(widget);
        });
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWidgets);
    } else {
        initWidgets();
    }
})();
</script>

