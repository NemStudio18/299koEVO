<section class="newsletter-subscribe-page">
    <header class="content-panel">
        <h1>{{ Lang.newsletter.name }}</h1>
    </header>
    
    {{ SHOW.displayMsg }}
    
    <form method="post" action="{{ subscribeUrl }}">
        {{ SHOW.tokenField }}
        <label for="email">{{ Lang.newsletter.email }}</label>
        <input type="email" name="email" id="email" placeholder="{{ Lang.newsletter.email-placeholder }}" required />
        
        <button type="submit" class="button">{{ Lang.newsletter.subscribe }}</button>
    </form>
</section>

