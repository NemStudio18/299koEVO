<section>
    <header>
    <a href="{{ ROUTER.generate("admin-marketplace") }}" class="button"><i class="fa-solid fa-store"></i> {{ Lang.marketplace.home }}</a>
    <a href="{{ ROUTER.generate("marketplace-plugins") }}" class="button"><i class="fa-solid fa-puzzle-piece"></i> {{ Lang.marketplace.plugins }}</a>
    </header>
    {{ THEMES_TPL}}
</section>
