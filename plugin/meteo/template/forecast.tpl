{% set HIDE_PAGE_TITLE = true %}
﻿<section class="meteo-forecast-page">
    <!-- En-tête Météo avec photo de Bourges -->
    <header class="meteo-hero-banner">
        <div class="meteo-hero-inner">
            <div class="meteo-hero-topline">
                <span class="meteo-hero-badge"><i class="fa-solid fa-cloud-sun"></i> Météo en direct • Bourges &amp; Berry</span>
            </div>
            <h1 class="meteo-hero-title">Météo à Bourges</h1>
            <p class="meteo-hero-subtitle">Observations en temps réel, prévisions détaillées heure par heure et tendances sur 5 jours.</p>
        </div>
    </header>
    
    <div class="meteo-widget {{extraClass}}"
         data-current-url="{{apiCurrentUrl}}"
         data-forecast-url="{{apiForecastUrl}}"
         data-show-forecast="{{showForecastFlag}}"
         data-default-city="{{defaultCity}}"
         data-default-country="{{defaultCountry}}"
         data-latitude="{{latitude}}"
         data-longitude="{{longitude}}"
         data-language="{{language}}"
         data-units="{{units}}">
        <header class="meteo-widget__header">
            <div class="meteo-widget__location" data-role="location">{{defaultCity}}, {{defaultCountry}}</div>
        </header>

        <div class="meteo-widget__current">
            <div class="meteo-widget__temperature">
                <span data-role="temperature">--°</span>
                <div data-role="icon-container" class="weather-icon-container">
                    <div data-role="icon" class="meteo-widget__icon">🌤️</div>
                </div>
            </div>
            <div class="meteo-widget__summary" data-role="description">{{Lang.meteo.loading}}</div>
            <div class="meteo-widget__details">
                <div>
                    <span>{{Lang.meteo.feels-like}}</span>
                    <strong data-role="feels">--°</strong>
                </div>
                <div>
                    <span>{{Lang.meteo.humidity}}</span>
                    <strong data-role="humidity">--%</strong>
                </div>
                <div>
                    <span>{{Lang.meteo.wind-speed}}</span>
                    <strong data-role="wind">-- km/h</strong>
                </div>
            </div>
        </div>

        <div class="meteo-widget__hourly" data-role="hourly">
            <h2>{{Lang.meteo.hourly-forecast}}</h2>
            <div class="meteo-hourly-list" data-role="hourly-list"></div>
        </div>

        <div class="meteo-widget__forecast" data-role="forecast">
            <h2>Prévisions sur 5 jours</h2>
            <div class="meteo-forecast-list" data-role="forecast-list"></div>
        </div>
    </div>
</section>

<script>
(function(){
    function getWeatherIcon(weatherCode, isDay = true) {
        if (weatherCode === 0) return isDay ? '☀️' : '🌙';
        if (weatherCode >= 1 && weatherCode <= 3) return '⛅';
        if (weatherCode >= 45 && weatherCode <= 48) return '🌫️';
        if (weatherCode >= 51 && weatherCode <= 57) return '🌦️';
        if (weatherCode >= 61 && weatherCode <= 67) return '🌧️';
        if (weatherCode >= 71 && weatherCode <= 77) return '❄️';
        if (weatherCode >= 80 && weatherCode <= 82) return '🌧️';
        if (weatherCode >= 85 && weatherCode <= 86) return '❄️';
        if (weatherCode >= 95 && weatherCode <= 99) return '⛈️';
        return '🌤️';
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
        // 51-67, 80-82: Drizzle, Rain, Showers
        else if ((weatherCode >= 51 && weatherCode <= 67) || (weatherCode >= 80 && weatherCode <= 82)) {
            cls = 'rainy';
            html = '<span class="rain-cloud"></span><ul><li></li><li></li><li></li><li></li><li></li></ul>';
        } 
        // 71-77, 85-86: Snow
        else if ((weatherCode >= 71 && weatherCode <= 77) || (weatherCode >= 85 && weatherCode <= 86)) {
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

    function loadData(widget) {
        const currentUrl = buildUrl(widget.dataset.currentUrl, widget);
        const forecastUrl = buildUrl(widget.dataset.forecastUrl, widget);
        const windUnit = widget.dataset.units === 'imperial' ? 'mph' : 'km/h';
        
        // Charger les données actuelles
        fetch(currentUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(payload => {
                if (payload.status === 'success' && payload.data) {
                    renderCurrent(widget, payload.data, windUnit);
                } else {
                    console.error('Meteo API error:', payload.message || 'Unknown error');
                    widget.querySelector('[data-role="description"]').textContent = payload.message || '{{Lang.meteo.api-error}}';
                }
            })
            .catch(error => {
                console.error('Meteo fetch error:', error);
                widget.querySelector('[data-role="description"]').textContent = '{{Lang.meteo.api-error}}';
            });

        // Charger les prévisions
        fetch(forecastUrl + '&days=5')
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(payload => {
                if (payload.status === 'success' && payload.data) {
                    renderForecast(widget, payload.data.forecast);
                    renderHourly(widget, payload.data.hourly);
                } else {
                    console.error('Meteo Forecast API error:', payload.message || 'Unknown error');
                }
            })
            .catch(error => {
                console.error('Meteo Forecast fetch error:', error);
                // Erreur silencieuse pour les prévisions
            });
    }

    function renderCurrent(widget, data, windUnit) {
        const temp = widget.querySelector('[data-role="temperature"]');
        const desc = widget.querySelector('[data-role="description"]');
        const feels = widget.querySelector('[data-role="feels"]');
        const humidity = widget.querySelector('[data-role="humidity"]');
        const wind = widget.querySelector('[data-role="wind"]');
        const iconContainer = widget.querySelector('[data-role="icon-container"]');
        const iconLegacy = widget.querySelector('[data-role="icon"]');
        const location = widget.querySelector('[data-role="location"]');

        if (temp) temp.textContent = (data.temperature ?? '--') + '°';
        if (desc) desc.textContent = data.description || '{{Lang.meteo.loading}}';
        if (feels) feels.textContent = (data.feels_like ?? '--') + '°';
        if (humidity) humidity.textContent = (data.humidity ?? '--') + '%';
        if (wind) wind.textContent = (data.wind_speed ?? '--') + ' ' + windUnit;
        
        if (iconContainer) {
            const weatherCode = data.weather_code ?? 0;
            const isDay = data.is_day !== undefined ? data.is_day : true;
            const animated = getAnimatedWeatherHTML(weatherCode, isDay);
            iconContainer.className = 'weather-icon-container ' + animated.class;
            iconContainer.innerHTML = animated.html;
        } else if (iconLegacy) {
            const weatherCode = data.weather_code ?? 0;
            const isDay = data.is_day !== undefined ? data.is_day : true;
            // Fallback to simple emoij if animation depends on container
            iconLegacy.textContent = data.icon || '🌤️';
        }
        
        if (location) location.textContent = data.city && data.country ? (data.city + ', ' + data.country) : location.textContent;
    }

    function renderForecast(widget, forecast) {
        const container = widget.querySelector('[data-role="forecast-list"]');
        if (!container || !Array.isArray(forecast)) {
            return;
        }
        container.innerHTML = '';
        forecast.forEach(day => {
            const item = document.createElement('div');
            item.className = 'meteo-forecast-item';
            const weatherCode = day.weather_code ?? 0;
            const animated = getAnimatedWeatherHTML(weatherCode, true);
            
            item.innerHTML = `
                <div class="meteo-forecast-date">${formatDate(day.date)}</div>
                <div class="weather-icon-container is-mini ${animated.class}">
                    ${animated.html}
                </div>
                <div class="meteo-forecast-temp">
                    <span class="temp-max">${day.max ?? '--'}°</span>
                    <span class="temp-min">${day.min ?? '--'}°</span>
                </div>
                <div class="meteo-forecast-desc">${day.description || ''}</div>
            `;
            container.appendChild(item);
        });
    }

    function renderHourly(widget, hourly) {
        const container = widget.querySelector('[data-role="hourly-list"]');
        if (!container || !Array.isArray(hourly)) {
            return;
        }
        container.innerHTML = '';
        let lastDay = '';
        
        hourly.forEach(hour => {
            const date = new Date(hour.time);
            const currentDay = date.toLocaleDateString(undefined, { weekday: 'short', day: 'numeric' });
            
            if (currentDay !== lastDay) {
                const dayHeader = document.createElement('div');
                dayHeader.className = 'meteo-hourly-day-header';
                dayHeader.textContent = currentDay;
                container.appendChild(dayHeader);
                lastDay = currentDay;
            }

            const item = document.createElement('div');
            item.className = 'meteo-hourly-item';
            
            // Ajouter une classe selon la température pour colorer subtilement
            const temp = hour.temp ?? 0;
            if (temp > 25) item.classList.add('temp-hot');
            else if (temp < 5) item.classList.add('temp-cold');

            const prec = hour.precipitation > 0 ? `<div class="meteo-hourly-precip"><i class="fa-solid fa-droplet"></i> ${hour.precipitation}%</div>` : '';

            const weatherCode = hour.weather_code ?? 0;
            const isDay = hour.is_day !== undefined ? hour.is_day : true;
            const animated = getAnimatedWeatherHTML(weatherCode, isDay);

            item.innerHTML = `
                <div class="meteo-hourly-time">${hour.hour}</div>
                <div class="weather-icon-container is-mini ${animated.class}">
                    ${animated.html}
                </div>
                <div class="meteo-hourly-temp">${hour.temp ?? '--'}°</div>
                ${prec}
            `;
            container.appendChild(item);
        });
    }

    function formatDate(dateString) {
        try {
            const locale = document.documentElement.lang || '{{language}}';
            const date = new Date(dateString);
            return date.toLocaleDateString(locale, { weekday: 'long', day: 'numeric', month: 'long' });
        } catch (e) {
            return dateString;
        }
    }

    function initWidgets() {
        document.querySelectorAll('.meteo-widget:not([data-init="1"])').forEach(widget => {
            widget.dataset.init = '1';
            loadData(widget);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWidgets);
    } else {
        initWidgets();
    }
})();
</script>

<style>
.meteo-forecast-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
}

.meteo-forecast-item {
    background: var(--section-bg-color, rgba(255, 255, 255, 0.85));
    backdrop-filter: blur(10px);
    border: 1px solid var(--border-color, rgba(255, 255, 255, 0.3));
    border-radius: 16px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: var(--dc-shadow-sm, 0 4px 15px rgba(0, 0, 0, 0.05));
    transition: transform 0.2s ease;
}

.meteo-forecast-item:hover {
    transform: translateY(-5px);
}

.meteo-forecast-date {
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: var(--font-color, #1f2937);
    text-transform: capitalize;
}

.meteo-forecast-icon {
    font-size: 3rem;
    margin: 1rem 0;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
}

.temp-max {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--alert-color, #ef4444);
}

.temp-min {
    font-size: 1.1rem;
    color: var(--primary-color, #3b82f6);
    margin-left: 0.75rem;
    opacity: 0.85;
}

.meteo-hourly-list {
    display: flex;
    overflow-x: auto;
    gap: 1rem;
    padding: 1.5rem 0.5rem;
    margin-top: 1rem;
    scrollbar-width: thin;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
}

.meteo-hourly-item {
    flex: 0 0 90px;
    background: var(--section-bg-color, #ffffff);
    border-radius: 12px;
    padding: 1rem 0.5rem;
    text-align: center;
    box-shadow: var(--dc-shadow-sm, 0 2px 8px rgba(0, 0, 0, 0.04));
    border: 1px solid var(--border-color, #f3f4f6);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.meteo-hourly-item.temp-hot { background: var(--dc-warning-bg, #fff7ed); border-color: var(--warning-color, #ffedd5); }
.meteo-hourly-item.temp-cold { background: var(--dc-info-bg, #eff6ff); border-color: var(--primary-color, #dbeafe); }

.meteo-hourly-time {
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--font-secondary, #6b7280);
    margin-bottom: 0.5rem;
}

.meteo-hourly-icon {
    font-size: 1.8rem;
    margin-bottom: 0.5rem;
}

.meteo-hourly-temp {
    font-weight: 800;
    font-size: 1.1rem;
    color: var(--font-color, #111827);
}

.meteo-hourly-precip {
    font-size: 0.7rem;
    color: var(--primary-color, #3b82f6);
    margin-top: 0.25rem;
    font-weight: 600;
}

.meteo-hourly-day-header {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--section-head-bg-color, #f3f4f6);
    color: var(--font-secondary, #4b5563);
    font-weight: 700;
    font-size: 0.7rem;
    text-transform: uppercase;
    padding: 0 0.75rem;
    border-radius: 20px;
    height: 32px;
    align-self: center;
    margin-right: 0.5rem;
    position: sticky;
    left: 0;
    z-index: 2;
    box-shadow: 2px 0 5px rgba(0,0,0,0.05);
}
</style>

