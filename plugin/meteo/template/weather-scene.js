/**
 * weather-scene.js — Contrôleur de Scène Météo Vectorielle Vivante
 * Gère la météo en temps réel et les transitions fluides entre ambiances.
 */
document.addEventListener("DOMContentLoaded", function () {
    var scene = document.getElementById("meteo_living_scene");
    if (!scene) return;

    var apiUrl = scene.getAttribute("data-api-url") || "/meteo/api/current";
    var defaultCity = scene.getAttribute("data-city") || "Paris";
    var defaultCountry = scene.getAttribute("data-country") || "FR";

    var tempEl = scene.querySelector('[data-role="ws-temp"]');
    var condEl = scene.querySelector('[data-role="ws-condition"]');
    var cityEl = scene.querySelector('[data-role="ws-city"]');
    var subEl = scene.querySelector('[data-role="ws-sub"]');
    var pickerBtns = scene.querySelectorAll(".ws-pick-btn");

    var realWeatherState = "weather-state-sunny";

    function setSceneState(stateClass) {
        var states = [
            "weather-state-sunny",
            "weather-state-night",
            "weather-state-cloudy",
            "weather-state-rain",
            "weather-state-storm",
            "weather-state-snow",
            "weather-state-fog"
        ];
        states.forEach(function (st) {
            scene.classList.remove(st);
        });
        scene.classList.add(stateClass);
    }

    function determineState(weatherCode, isDay) {
        // Nuit claire
        if (!isDay && (weatherCode === 0 || weatherCode === 1)) {
            return "weather-state-night";
        }
        // Soleil
        if (weatherCode === 0 || weatherCode === 1) {
            return "weather-state-sunny";
        }
        // Nuageux
        if (weatherCode === 2 || weatherCode === 3) {
            return isDay ? "weather-state-cloudy" : "weather-state-night";
        }
        // Brouillard
        if (weatherCode >= 45 && weatherCode <= 48) {
            return "weather-state-fog";
        }
        // Pluie & Bruine
        if ((weatherCode >= 51 && weatherCode <= 67) || (weatherCode >= 80 && weatherCode <= 82)) {
            return "weather-state-rain";
        }
        // Neige
        if ((weatherCode >= 71 && weatherCode <= 77) || (weatherCode >= 85 && weatherCode <= 86)) {
            return "weather-state-snow";
        }
        // Orage
        if (weatherCode >= 95 && weatherCode <= 99) {
            return "weather-state-storm";
        }
        return isDay ? "weather-state-sunny" : "weather-state-night";
    }

    function getWeatherDesc(weatherCode, isDay) {
        var map = {
            0: isDay ? "Ensoleillé" : "Nuit claire",
            1: isDay ? "Peu nuageux" : "Nuit dégagée",
            2: "Partiellement nuageux",
            3: "Couvert",
            45: "Brouillard",
            48: "Brouillard givrant",
            51: "Bruine légère",
            53: "Bruine modérée",
            55: "Bruine dense",
            61: "Pluie faible",
            63: "Pluie modérée",
            65: "Forte pluie",
            71: "Chute de neige faible",
            73: "Chute de neige modérée",
            75: "Forte chute de neige",
            80: "Averses de pluie",
            81: "Fortes averses",
            82: "Averses violentes",
            95: "Orage modéré",
            96: "Orage violent",
            99: "Orage avec grêle"
        };
        return map[weatherCode] || "Ciel variable";
    }

    // Chargement des données météo réelles
    fetch(apiUrl + "?city=" + encodeURIComponent(defaultCity) + "&country=" + encodeURIComponent(defaultCountry))
        .then(function (res) { return res.ok ? res.json() : null; })
        .then(function (payload) {
            if (!payload || !payload.data) {
                fallbackDefault();
                return;
            }
            var d = payload.data;
            var temp = Math.round(d.temperature);
            var isDay = (typeof d.is_day !== "undefined") ? Boolean(d.is_day) : ((typeof d.isDay !== "undefined") ? Boolean(d.isDay) : true);
            var code = (typeof d.weather_code !== "undefined") ? parseInt(d.weather_code, 10) : ((typeof d.weatherCode !== "undefined") ? parseInt(d.weatherCode, 10) : 0);

            if (tempEl) tempEl.textContent = temp;
            if (condEl) condEl.textContent = getWeatherDesc(code, isDay);
            if (cityEl) cityEl.textContent = d.city || defaultCity;
            if (subEl) subEl.textContent = isDay ? "Observations du jour" : "Observations nocturnes";

            realWeatherState = determineState(code, isDay);
            setSceneState(realWeatherState);
        })
        .catch(function () {
            fallbackDefault();
        });

    function fallbackDefault() {
        var hour = new Date().getHours();
        var isDay = hour >= 7 && hour < 20;
        if (tempEl) tempEl.textContent = isDay ? "16" : "11";
        if (condEl) condEl.textContent = isDay ? "Ensoleillé" : "Nuit claire";
        if (subEl) subEl.textContent = "Prévisions estimées";
        realWeatherState = isDay ? "weather-state-sunny" : "weather-state-night";
        setSceneState(realWeatherState);
    }

    // Gestion du sélecteur d'ambiances manuel
    pickerBtns.forEach(function (btn) {
        btn.addEventListener("click", function () {
            pickerBtns.forEach(function (b) { b.classList.remove("is-active"); });
            btn.classList.add("is-active");

            var targetScene = btn.getAttribute("data-scene");
            if (targetScene === "auto") {
                setSceneState(realWeatherState);
            } else if (targetScene === "sunny") {
                setSceneState("weather-state-sunny");
            } else if (targetScene === "night") {
                setSceneState("weather-state-night");
            } else if (targetScene === "rain") {
                setSceneState("weather-state-rain");
            } else if (targetScene === "storm") {
                setSceneState("weather-state-storm");
            } else if (targetScene === "snow") {
                setSceneState("weather-state-snow");
            } else if (targetScene === "fog") {
                setSceneState("weather-state-fog");
            }
        });
    });
});
