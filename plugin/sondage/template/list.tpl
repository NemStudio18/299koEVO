<section>
    {% if count(sondages) > 0 %}
        <div class="sondages-list">
            {% for sondage in sondages %}
                <div class="sondage-item">
                    <h3><a href="{{ sondage.url }}">{{ sondage.title }}</a></h3>
                    <p>{{ sondage.votesCountText }}</p>
                    {% if sondage.isClosed %}
                        <div class="sondage-status-closed">
                            <span class="closed-badge">{{ Lang.sondage.closed }}</span>
                        </div>
                    {% elseif sondage.hasVoted %}
                        <div class="already-voted-notice">
                            <i class="fa-solid fa-check"></i>
                            <span>{{ Lang.sondage.already-voted }}</span>
                        </div>
                    {% endif %}
                </div>
            {% endfor %}
        </div>
    {% else %}
        <p>{{ Lang.sondage.no-sondage }}</p>
    {% endif %}
</section>
