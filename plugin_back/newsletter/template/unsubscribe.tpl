<section class="newsletter-unsubscribe-page">
    <header class="content-panel">
        <h1>{{ Lang.newsletter.unsubscribe-title }}</h1>
    </header>
    
    {{ SHOW.displayMsg }}
    
    <p>{{ Lang.newsletter.email }}: <strong>{{ email }}</strong></p>
    
    <form method="post" action="{{ unsubscribeUrl }}">
        {{ SHOW.tokenField }}
        
        <button type="submit" class="button alert">{{ Lang.newsletter.unsubscribe }}</button>
    </form>
</section>

