<div class="poi-reviews-container">
    <div class="poi-reviews-header">
        {% IF poiDetailUrl %}
        <a href="{{ poiDetailUrl }}" class="button secondary back-to-detail-btn">
            <i class="fa-solid fa-arrow-left"></i> Retour à la fiche détail
        </a>
        {% ENDIF %}
        <h1 class="reviews-title">
            <i class="fa-solid fa-star"></i> Avis et notes de {{ poiName }}
        </h1>
        <div class="poi-reviews-rating-summary">
            <div class="poi-reviews-average">{{ averageRating }}</div>
            <div>
                <div class="poi-reviews-stars">
                    {% for star in stars %}
                        {% if star.isFull %}
                            <i class="fa-solid fa-star"></i>
                        {% elseif star.isHalf %}
                            <i class="fa-solid fa-star-half-stroke"></i>
                        {% else %}
                            <i class="fa-regular fa-star"></i>
                        {% endif %}
                    {% endfor %}
                </div>
                <div class="poi-reviews-count">{{ ratingCount }} avis</div>
            </div>
        </div>
    </div>

    {% if isLogged %}
        {% if userReview == null or not userReview %}
        <div class="poi-reviews-form" id="add-review">
            <h3>Laisser un avis</h3>
            <form method="POST" action="{{ ROUTER.generate("poi-reviews-add") }}">
                <input type="hidden" name="token" value="{{ csrfToken }}">
                <input type="hidden" name="poi_id" value="{{ poiId }}">
                
                <div class="poi-reviews-rating-input">
                    <input type="radio" id="rating5" name="rating" value="5" required>
                    <label for="rating5"><i class="fa-solid fa-star"></i></label>
                    <input type="radio" id="rating4" name="rating" value="4">
                    <label for="rating4"><i class="fa-solid fa-star"></i></label>
                    <input type="radio" id="rating3" name="rating" value="3">
                    <label for="rating3"><i class="fa-solid fa-star"></i></label>
                    <input type="radio" id="rating2" name="rating" value="2">
                    <label for="rating2"><i class="fa-solid fa-star"></i></label>
                    <input type="radio" id="rating1" name="rating" value="1">
                    <label for="rating1"><i class="fa-solid fa-star"></i></label>
                </div>
                
                <textarea name="comment" class="poi-reviews-comment-input" placeholder="Votre commentaire (optionnel)"></textarea>
                
                <button type="submit" class="button primary">Envoyer mon avis</button>
            </form>
        </div>
        {% else %}
        <div class="review-already-posted">
            <p><i class="fa-solid fa-info-circle"></i> Vous avez déjà laissé un avis pour ce POI.</p>
        </div>
        {% endif %}
    {% else %}
        <div class="review-login-required">
            <p><a href="{{ loginUrl }}">Connectez-vous</a> pour laisser un avis et noter ce POI.</p>
        </div>
    {% endif %}

    <div class="poi-reviews-list">
        {% if reviews %}
            {% for review in reviews %}
            <div class="poi-review-item {% if review.status == "moderated" %}poi-review-moderated{% endif %}">
                <div class="poi-review-header">
                    <div class="poi-review-header-left">
                        {% if review.has_avatar %}
                        <img src="{{ review.author_avatar }}" alt="{{ review.author_name }}" class="poi-reviews-avatar">
                        {% else %}
                        <div class="review-avatar-placeholder" style="background-color: {{ review.avatar_color }};">
                            {{ review.avatar_initials }}
                        </div>
                        {% endif %}
                        <div>
                            <span class="poi-review-author">{{ review.author_name }}</span>
                            <span class="poi-review-date">{{ review.created_at_formatted }}</span>
                            {% if review.status == "moderated" %}
                            <span class="poi-review-moderated-badge">Modéré</span>
                            {% endif %}
                        </div>
                    </div>
                    <div class="poi-review-header-right">
                        <div class="poi-review-rating">
                            {% for star in review.stars %}
                                {% if star.isFull %}
                                    <i class="fa-solid fa-star"></i>
                                {% else %}
                                    <i class="fa-regular fa-star"></i>
                                {% endif %}
                            {% endfor %}
                        </div>
                        {% if isOwner and review.status == "active" and review.moderateUrl %}
                        <form method="POST" action="{{ review.moderateUrl }}" class="poi-review-moderated-btn" onsubmit="return confirm('Êtes-vous sûr de vouloir modérer cet avis ?');">
                            <input type="hidden" name="token" value="{{ csrfToken }}">
                            <input type="hidden" name="reason" value="Modéré par le propriétaire">
                            <button type="submit" class="button small poi-review-moderate-action-btn">
                                <i class="fa-solid fa-gavel"></i> Modérer
                            </button>
                        </form>
                        {% endif %}
                    </div>
                </div>
                {% if review.comment %}
                <div class="poi-review-comment">{{ review.comment }}</div>
                {% endif %}
            </div>
            {% endfor %}
        {% else %}
            <p class="poi-reviews-empty">Aucun avis pour le moment.</p>
        {% endif %}
    </div>
</div>

