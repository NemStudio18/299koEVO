<div class="search299-wrapper" id="search299Wrapper">
    <form action="/search" method="GET" class="search299-pill-form" role="search" id="search299Form">
        <button type="submit" class="search299-icon-btn" aria-label="Rechercher sur le site">
            <i class="fa-solid fa-magnifying-glass"></i>
        </button>
        <input 
            type="text" 
            name="q" 
            id="search299Input"
            class="search299-input" 
            placeholder="Rechercher sur le site…" 
            value="{{q}}" 
            autocomplete="off"
            aria-label="Rechercher sur tout le site"
        />
    </form>
</div>
<script>
(function() {
    var wrapper = document.getElementById('search299Wrapper');
    var input   = document.getElementById('search299Input');
    var header  = document.getElementById('header');
    var form    = document.getElementById('search299Form');
    if (!wrapper || !header) return;

    function activate() {
        header.classList.add('search-active');
    }

    function deactivate() {
        if (input && document.activeElement === input) {
            return; // garder actif tant que l'input a le focus
        }
        header.classList.remove('search-active');
    }

    // Déclenchement au SURVOL de la loupe / du wrapper
    wrapper.addEventListener('mouseenter', activate);
    wrapper.addEventListener('mouseleave', deactivate);

    // Déclenchement au FOCUS de l'input
    if (input) {
        input.addEventListener('focus', activate);
        input.addEventListener('blur', function() {
            setTimeout(deactivate, 150);
        });
    }

    // Si on clique sur la loupe sans texte saisi, focus le champ au lieu de soumettre
    if (form && input) {
        form.addEventListener('submit', function(e) {
            if (!input.value.trim()) {
                e.preventDefault();
                input.focus();
            }
        });
    }
})();
</script>
