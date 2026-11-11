<section class="seo-optimizations-preview">
    <header>
        <h1>{{ Lang.seo.optimizations.test }}</h1>
        <p>{{ Lang.seo.optimizations.current }}</p>
        <ul>
            <li>{{ Lang.seo.optimizations.lazy }} : {{ optimizations.lazyLoading ? Lang.seo.enabled : Lang.seo.disabled }}</li>
            <li>{{ Lang.seo.optimizations.alt }} : {{ optimizations.autoAlt ? Lang.seo.enabled : Lang.seo.disabled }}</li>
            <li>{{ Lang.seo.optimizations.minify }} : {{ optimizations.minifyHtml ? Lang.seo.enabled : Lang.seo.disabled }}</li>
        </ul>
    </header>
    <article class="seo-preview">
        {{ content|raw }}
    </article>
</section>

<style>
.seo-optimizations-preview {
    padding: 2rem;
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e0e0e0;
}
.seo-optimizations-preview header ul {
    list-style: disc;
    margin-left: 1.5rem;
}
.seo-preview {
    margin-top: 2rem;
    padding: 1.5rem;
    border: 1px solid #f0f0f0;
    border-radius: 10px;
    background: #fafafa;
}
.seo-preview img,
.seo-preview iframe {
    display: block;
    margin-bottom: 1rem;
}
</style>

