{# Page d'accueil personnalisable #}

<div class="homepage-container">
    {% IF blocks %}
        {% FOR block IN blocks %}
            {{ block.render() }}
        {% ENDFOR %}
    {% ELSE %}
        <div class="homepage-empty">
            <h1>Bienvenue sur notre site</h1>
            <p>Cette page d'accueil peut être entièrement personnalisée depuis l'administration.</p>
            <p>Connectez-vous à l'administration pour ajouter des blocs de contenu.</p>
        </div>
    {% ENDIF %}
</div>

<style>
.homepage-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.homepage-block {
    margin-bottom: 30px;
    padding: 20px;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.homepage-block h2 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
    font-size: 1.5em;
}

.homepage-block .content {
    margin-bottom: 15px;
    line-height: 1.6;
}

.homepage-block-button .btn {
    display: inline-block;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 4px;
    font-weight: bold;
    transition: all 0.3s ease;
}

.homepage-block-button .btn-primary { background: #007bff; color: white; }
.homepage-block-button .btn-secondary { background: #6c757d; color: white; }
.homepage-block-button .btn-success { background: #28a745; color: white; }
.homepage-block-button .btn-danger { background: #dc3545; color: white; }
.homepage-block-button .btn-warning { background: #ffc107; color: black; }
.homepage-block-button .btn-info { background: #17a2b8; color: white; }
.homepage-block-button .btn-light { background: #f8f9fa; color: black; }
.homepage-block-button .btn-dark { background: #343a40; color: white; }

.homepage-block-button .btn-small { padding: 5px 10px; font-size: 0.875em; }
.homepage-block-button .btn-medium { padding: 10px 20px; font-size: 1em; }
.homepage-block-button .btn-large { padding: 15px 30px; font-size: 1.125em; }

.homepage-block-table .table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.homepage-block-table .table th,
.homepage-block-table .table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.homepage-block-table .table th {
    background-color: #f8f9fa;
    font-weight: bold;
}

.homepage-block-form .form-group {
    margin-bottom: 15px;
}

.homepage-block-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.homepage-block-form input,
.homepage-block-form textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1em;
}

.homepage-block-form textarea {
    min-height: 100px;
    resize: vertical;
}

.homepage-block-form button {
    background: #007bff;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1em;
}

.homepage-block-form button:hover {
    background: #0056b3;
}

.homepage-block-image img {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
    margin-top: 15px;
}

.homepage-empty {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.homepage-empty h1 {
    color: #333;
    margin-bottom: 20px;
}

/* HomeBuilder specific helpers */
.homebuilder-hero {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.homebuilder-hero .kicker {
    text-transform: uppercase;
    letter-spacing: 2px;
    opacity: 0.8;
}
.homebuilder-hero .subtitle {
    font-size: 1.1rem;
    color: rgba(255,255,255,0.85);
}
.homebuilder-hero .cta-group {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}
.homebuilder-hero .btn {
    padding: 0.75rem 1.5rem;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 600;
}
.homebuilder-hero .btn.primary {
    background: #ffb347;
    color: #0b1f3a;
}
.homebuilder-hero .btn.ghost {
    border: 1px solid rgba(255,255,255,0.4);
    color: #fff;
}
.latest-articles-list,
.latest-sondages-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}
.latest-articles-list li,
.latest-sondages-list li {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    padding: 1.2rem;
    transition: box-shadow 0.3s ease;
}
.latest-articles-list li:hover,
.latest-sondages-list li:hover {
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}
.latest-articles-list a,
.latest-sondages-list a {
    text-decoration: none;
    color: inherit;
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}
.latest-articles-list .article-title,
.latest-sondages-list .sondage-title {
    font-weight: 600;
}
.homebuilder-info {
    padding: 1rem;
    border-radius: 8px;
    background: #f5f7fb;
    border: 1px solid #dbe3f4;
    color: #4a5b7a;
    margin-top: 1rem;
}
</style> 
