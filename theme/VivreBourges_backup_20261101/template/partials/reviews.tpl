<div class="reviews-container">
    <div class="reviews-header">
        <h3><i class="fa-solid fa-star"></i> Avis des utilisateurs</h3>
        {% if CAN_REVIEW and addReviewUrl %}
            <a href="{{ addReviewUrl }}" class="button primary small"><i class="fa-solid fa-plus"></i> Laisser un avis</a>
        {% elseif not IS_LOGGED %}
            <a href="{{ loginUrl }}" class="button secondary small"><i class="fa-solid fa-lock"></i> Connectez-vous pour donner votre avis</a>
        {% endif %}
    </div>

    {% if reviews and reviews|length > 0 %}
        <div class="reviews-list">
            {% for review in reviews %}
                <div class="review-card">
                    <div class="review-meta">
                        <span class="review-author"><i class="fa-solid fa-user"></i> {{ review.username }}</span>
                        <span class="review-date">{{ review.created_at|date('d/m/Y') }}</span>
                    </div>
                    <div class="review-rating">
                        {% for i in 1..5 %}
                            <i class="fa-star {% if i <= review.rating %}fa-solid{% else %}fa-regular{% endif %}"></i>
                        {% endfor %}
                    </div>
                    {% if review.comment %}
                        <div class="review-comment">
                            {{ review.comment|nl2br }}
                        </div>
                    {% endif %}
                </div>
            {% endfor %}
        </div>
    {% else %}
        <div class="reviews-empty">
            <p>Il n'y a pas encore d'avis pour ce lieu.</p>
        </div>
    {% endif %}
</div>

<style>
.reviews-container { padding: 20px 0; }
.reviews-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.reviews-list { display: flex; flex-direction: column; gap: 15px; }
.review-card { background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #eee; }
.review-meta { display: flex; justify-content: space-between; font-size: 0.85rem; color: #666; margin-bottom: 5px; }
.review-rating { color: #ffc107; margin-bottom: 10px; }
.review-comment { font-style: italic; color: #333; }
.reviews-empty { text-align: center; padding: 40px; color: #888; background: #fafafa; border-radius: 8px; }
</style>
