<section>
    <header>{{ Lang.faq.name }}</header>
    
    {% if categories|length > 0 %}
        <div class="faq-categories">
            <a href="{{ ROUTER.generate("faq-home") }}" class="button{% if selectedCategory == "" %} active{% endif %}">{{ Lang.faq.all-categories }}</a>
            {% for cat in categories %}
                <a href="{{ ROUTER.generate("faq-home") }}?category={{ cat }}" class="button{% if selectedCategory == cat %} active{% endif %}">{{ cat }}</a>
            {% endfor %}
        </div>
    {% endif %}
    
    {% if count(questions) > 0 %}
        <div class="faq-questions">
            {% for q in questions %}
                <div class="faq-item">
                    <div class="faq-question-header">
                        <h3>{{ q.question }}</h3>
                        <div class="faq-vote">
                            <form method="post" action="{{ q.voteUrl }}" style="display: inline;">
                                {{ SHOW.tokenField }}
                                <button type="submit" class="faq-vote-btn{% if q.hasVoted %} voted{% endif %}"{% if q.hasVoted %} disabled{% endif %}>
                                    <i class="fa-solid fa-thumbs-up"></i> {{ q.votes }}
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="faq-answer">{{ htmlspecialchars_decode(q.answer) }}</div>
                </div>
            {% endfor %}
        </div>
    {% else %}
        <p>{{ Lang.faq.no-questions }}</p>
    {% endif %}
    
    <div class="faq-ask">
        <h3>{{ Lang.faq.ask-question }}</h3>
        <form method="post" action="{{ askUrl }}">
            {{ SHOW.tokenField }}
            <input type="text" name="_name" style="display: none;" tabindex="-1" autocomplete="off" />
            
            <label for="question">{{ Lang.faq.your-question }}</label>
            <textarea name="question" id="question" rows="3" required></textarea>
            
            <label for="email">{{ Lang.faq.email }} ({{ Lang.faq.optional }})</label>
            <input type="email" name="email" id="email" />
            
            <div class="faq-submit-container">
                {% if antispam %}
                    <div class="faq-captcha">{{ antispamField }}</div>
                {% endif %}
                <button type="submit" class="button">{{ Lang.faq.send-question }}</button>
            </div>
        </form>
    </div>
</section>

