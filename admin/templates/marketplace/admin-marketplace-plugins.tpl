<section>
    <header>
    <a href="{{ ROUTER.generate("admin-marketplace") }}" class="button"><i class="fa-solid fa-store"></i> {{ Lang.marketplace.home }}</a>
    <a href="{{ ROUTER.generate("marketplace-themes") }}" class="button"><i class="fa-solid fa-panorama"></i> {{ Lang.marketplace.themes }}</a>
    </header>
    {{ PLUGINS_TPL}}
</section>
