<div class="weather-living-scene weather-state-sunny" id="meteo_living_scene" data-api-url="{{apiCurrentUrl}}" data-city="{{defaultCity}}" data-country="{{defaultCountry}}">
    <!-- 1. Couche du Ciel dynamique -->
    <div class="ws-sky-layer" aria-hidden="true"></div>

    <!-- 2. Scène Vectorielle Vivante Unique -->
    <svg class="ws-svg-canvas" viewBox="0 0 1440 320" preserveAspectRatio="none" aria-hidden="true">
        <defs>
            <radialGradient id="wsSunGlow" cx="50%" cy="50%" r="50%">
                <stop offset="0%" stop-color="#fffbeb" stop-opacity="1"/>
                <stop offset="40%" stop-color="#fde047" stop-opacity="0.9"/>
                <stop offset="70%" stop-color="#f59e0b" stop-opacity="0.5"/>
                <stop offset="100%" stop-color="#ea580c" stop-opacity="0"/>
            </radialGradient>
            <radialGradient id="wsMoonGlow" cx="50%" cy="50%" r="50%">
                <stop offset="0%" stop-color="#ffffff" stop-opacity="1"/>
                <stop offset="60%" stop-color="#e0e7ff" stop-opacity="0.75"/>
                <stop offset="100%" stop-color="#818cf8" stop-opacity="0"/>
            </radialGradient>
            <linearGradient id="wsTerrainBack" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" stop-color="var(--ws-hill-back)" stop-opacity="0.85"/>
                <stop offset="100%" stop-color="var(--ws-hill-back)" stop-opacity="1"/>
            </linearGradient>
            <linearGradient id="wsTerrainFront" x1="0%" y1="0%" x2="0%" y2="100%">
                <stop offset="0%" stop-color="var(--ws-hill-front)" stop-opacity="0.95"/>
                <stop offset="100%" stop-color="var(--ws-hill-front)" stop-opacity="1"/>
            </linearGradient>
        </defs>

        <!-- Nuit : Constellation d'étoiles scintillantes -->
        <g class="ws-stars-group">
            <circle cx="120" cy="40" r="1.5" class="ws-star ws-star-1"/>
            <circle cx="280" cy="70" r="1.2" class="ws-star ws-star-2"/>
            <circle cx="450" cy="30" r="2.0" class="ws-star ws-star-3"/>
            <circle cx="620" cy="80" r="1.4" class="ws-star ws-star-4"/>
            <circle cx="850" cy="45" r="1.8" class="ws-star ws-star-1"/>
            <circle cx="1020" cy="65" r="1.3" class="ws-star ws-star-2"/>
            <circle cx="1180" cy="35" r="2.2" class="ws-star ws-star-3"/>
            <circle cx="1340" cy="75" r="1.5" class="ws-star ws-star-4"/>
        </g>

        <!-- Astre Solaire (Jour) -->
        <g class="ws-sun-group">
            <circle cx="220" cy="90" r="80" fill="url(#wsSunGlow)" class="ws-sun-halo"/>
            <circle cx="220" cy="90" r="32" fill="#fef08a" class="ws-sun-disc"/>
            <g class="ws-sun-rays">
                <line x1="220" y1="40" x2="220" y2="20" stroke="#fef08a" stroke-width="3" stroke-linecap="round"/>
                <line x1="220" y1="140" x2="220" y2="160" stroke="#fef08a" stroke-width="3" stroke-linecap="round"/>
                <line x1="170" y1="90" x2="150" y2="90" stroke="#fef08a" stroke-width="3" stroke-linecap="round"/>
                <line x1="270" y1="90" x2="290" y2="90" stroke="#fef08a" stroke-width="3" stroke-linecap="round"/>
                <line x1="185" y1="55" x2="170" y2="40" stroke="#fef08a" stroke-width="3" stroke-linecap="round"/>
                <line x1="255" y1="125" x2="270" y2="140" stroke="#fef08a" stroke-width="3" stroke-linecap="round"/>
                <line x1="185" y1="125" x2="170" y2="140" stroke="#fef08a" stroke-width="3" stroke-linecap="round"/>
                <line x1="255" y1="55" x2="270" y2="40" stroke="#fef08a" stroke-width="3" stroke-linecap="round"/>
            </g>
        </g>

        <!-- Astre Lunaire (Nuit) -->
        <g class="ws-moon-group">
            <circle cx="220" cy="90" r="60" fill="url(#wsMoonGlow)" class="ws-moon-halo"/>
            <path d="M210,65 C228,65 242,79 242,97 C242,115 228,129 210,129 C205,129 200,128 195,126 C206,121 214,110 214,97 C214,84 206,73 195,68 C200,66 205,65 210,65 Z" fill="#ffffff" class="ws-moon-crescent"/>
        </g>

        <!-- Nuages en dérive Parallaxe arrière -->
        <g class="ws-clouds-back">
            <path class="ws-cloud" d="M100,110 Q140,80 180,105 Q220,75 270,105 Q300,95 320,115 L100,115 Z"/>
            <path class="ws-cloud" d="M680,100 Q720,70 760,95 Q800,65 850,95 Q880,85 900,105 L680,105 Z"/>
            <path class="ws-cloud" d="M1200,105 Q1240,75 1280,100 Q1320,70 1370,100 Q1400,90 1420,110 L1200,110 Z"/>
        </g>

        <!-- LA SCÈNE FIXE UNIQUE (Collines, silhouette intemporelle de village & arbres) -->
        <g class="ws-permanent-landscape">
            <path class="ws-hill-back" d="M0,320 L0,210 Q280,140 560,195 Q840,250 1120,175 Q1280,135 1440,180 L1440,320 Z" fill="url(#wsTerrainBack)"/>
            <path class="ws-hill-front" d="M0,320 L0,240 Q220,190 440,225 Q660,260 880,210 Q1100,160 1320,230 Q1380,250 1440,235 L1440,320 Z" fill="url(#wsTerrainFront)"/>
            
            <g class="ws-skyline-accents">
                <circle cx="380" cy="215" r="14" class="ws-tree-crown"/>
                <rect x="378" y="215" width="4" height="15" class="ws-tree-trunk"/>
                <circle cx="410" cy="220" r="11" class="ws-tree-crown"/>
                <rect x="408" y="220" width="4" height="14" class="ws-tree-trunk"/>
                
                <polygon points="860,210 875,190 890,210" class="ws-roof-accent"/>
                <rect x="863" y="200" width="24" height="15" class="ws-roof-accent"/>
                <polygon points="873,195 875,170 877,195" class="ws-roof-accent"/>
                
                <circle cx="1060" cy="180" r="13" class="ws-tree-crown"/>
                <rect x="1058" y="180" width="4" height="14" class="ws-tree-trunk"/>
                <circle cx="1085" cy="185" r="16" class="ws-tree-crown"/>
                <rect x="1083" y="185" width="4" height="16" class="ws-tree-trunk"/>
            </g>
        </g>

        <!-- Nuages en dérive Parallaxe avant -->
        <g class="ws-clouds-front">
            <path class="ws-cloud" d="M220,130 Q270,95 320,125 Q370,85 430,120 Q470,110 500,135 L220,135 Z"/>
            <path class="ws-cloud" d="M920,120 Q970,85 1020,115 Q1070,75 1130,110 Q1170,100 1200,125 L920,125 Z"/>
        </g>

        <!-- Éclair d'Orage -->
        <g class="ws-storm-lightning">
            <path class="ws-bolt" d="M720,40 L695,110 L725,120 L680,190 L710,180 L670,240" stroke="#fef08a" stroke-width="3.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
        </g>

        <!-- Gouttes de Pluie -->
        <g class="ws-rain-drops">
            <line x1="140" y1="60" x2="125" y2="105" class="ws-drop ws-drop-1"/>
            <line x1="280" y1="40" x2="265" y2="85" class="ws-drop ws-drop-2"/>
            <line x1="420" y1="80" x2="405" y2="125" class="ws-drop ws-drop-3"/>
            <line x1="560" y1="50" x2="545" y2="95" class="ws-drop ws-drop-1"/>
            <line x1="700" y1="70" x2="685" y2="115" class="ws-drop ws-drop-2"/>
            <line x1="840" y1="45" x2="825" y2="90" class="ws-drop ws-drop-3"/>
            <line x1="980" y1="65" x2="965" y2="110" class="ws-drop ws-drop-1"/>
            <line x1="1120" y1="35" x2="1105" y2="80" class="ws-drop ws-drop-2"/>
            <line x1="1260" y1="75" x2="1245" y2="120" class="ws-drop ws-drop-3"/>
            <line x1="1380" y1="55" x2="1365" y2="100" class="ws-drop ws-drop-1"/>
        </g>

        <!-- Flocons de Neige -->
        <g class="ws-snow-flakes">
            <circle cx="160" cy="50" r="3.5" class="ws-flake ws-flake-1"/>
            <circle cx="340" cy="70" r="2.5" class="ws-flake ws-flake-2"/>
            <circle cx="520" cy="40" r="4.0" class="ws-flake ws-flake-3"/>
            <circle cx="700" cy="80" r="3.0" class="ws-flake ws-flake-4"/>
            <circle cx="880" cy="60" r="2.8" class="ws-flake ws-flake-1"/>
            <circle cx="1060" cy="75" r="3.5" class="ws-flake ws-flake-2"/>
            <circle cx="1240" cy="45" r="4.2" class="ws-flake ws-flake-3"/>
            <circle cx="1380" cy="65" r="2.5" class="ws-flake ws-flake-4"/>
        </g>

        <!-- Nappe de Brouillard -->
        <g class="ws-mist-group">
            <path class="ws-mist-wave ws-mist-1" d="M-100,280 Q300,260 700,285 Q1100,310 1540,280 L1540,320 L-100,320 Z"/>
            <path class="ws-mist-wave ws-mist-2" d="M-50,295 Q350,315 750,290 Q1150,265 1500,295 L1500,320 L-50,320 Z"/>
        </g>
    </svg>

    <!-- Superposition Carte Météo Frosted Glass -->
    <div class="ws-overlay-card">
        <div class="ws-card-badge">
            <span class="ws-live-dot"></span>
            <span class="ws-card-city" data-role="ws-city">{{defaultCity}}</span>
        </div>
        <div class="ws-card-main">
            <div class="ws-temp-wrap">
                <span class="ws-temp-value" data-role="ws-temp">--</span>
                <span class="ws-temp-unit">°C</span>
            </div>
            <div class="ws-condition-wrap">
                <span class="ws-condition-text" data-role="ws-condition">Chargement...</span>
                <span class="ws-condition-sub" data-role="ws-sub">En direct</span>
            </div>
        </div>
        <!-- Sélecteur d'ambiances de prévisualisation -->
        <div class="ws-scene-picker" title="Tester les ambiances météo">
            <button type="button" class="ws-pick-btn is-active" data-scene="auto" title="Météo réelle en direct">⚡ Réel</button>
            <button type="button" class="ws-pick-btn" data-scene="sunny" title="Soleil">☀️</button>
            <button type="button" class="ws-pick-btn" data-scene="night" title="Nuit étoilée">🌙</button>
            <button type="button" class="ws-pick-btn" data-scene="rain" title="Pluie">🌧️</button>
            <button type="button" class="ws-pick-btn" data-scene="storm" title="Orage">⛈️</button>
            <button type="button" class="ws-pick-btn" data-scene="snow" title="Neige">❄️</button>
            <button type="button" class="ws-pick-btn" data-scene="fog" title="Brume">🌫️</button>
        </div>
    </div>
</div>
