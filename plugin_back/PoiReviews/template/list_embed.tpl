<div class="poi-reviews-embed">
    <div class="poi-reviews-header-embed">
        <h3 class="reviews-embed-title">Avis de la communauté</h3>
        <div class="poi-reviews-rating-summary-small">
            <div class="poi-reviews-stars stars-gold">
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
            <span class="reviews-average">{{ averageRating }}</span>
            <span class="reviews-count">({{ ratingCount }})</span>
        </div>
    </div>

    {% if isLogged %}
        {% if userReview == null or not userReview %}
        <div class="poi-reviews-form-embed" id="add-review-embed" style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid #e9ecef;">
            <h4 style="margin-top: 0; margin-bottom: 1rem;">Partagez votre expérience</h4>
            <form method="POST" action="{{ ROUTER.generate("poi-reviews-add") }}">
                <input type="hidden" name="token" value="{{ csrfToken }}">
                <input type="hidden" name="poi_id" value="{{ poiId }}">
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Votre note</label>
                    <div class="poi-reviews-rating-input" style="display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 5px;">
                        <input type="radio" id="rating5" name="rating" value="5" required style="display: none;"><label for="rating5" style="cursor: pointer; color: #ddd; font-size: 1.5rem;"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" id="rating4" name="rating" value="4" style="display: none;"><label for="rating4" style="cursor: pointer; color: #ddd; font-size: 1.5rem;"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" id="rating3" name="rating" value="3" style="display: none;"><label for="rating3" style="cursor: pointer; color: #ddd; font-size: 1.5rem;"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" id="rating2" name="rating" value="2" style="display: none;"><label for="rating2" style="cursor: pointer; color: #ddd; font-size: 1.5rem;"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" id="rating1" name="rating" value="1" style="display: none;"><label for="rating1" style="cursor: pointer; color: #ddd; font-size: 1.5rem;"><i class="fa-solid fa-star"></i></label>
                    </div>
                    <style>
                        .poi-reviews-rating-input input:checked ~ label,
                        .poi-reviews-rating-input label:hover,
                        .poi-reviews-rating-input label:hover ~ label { color: #ffc107 !important; }
                    </style>
                </div>
                
                <div class="form-group" style="margin-bottom: 1rem;">
                    <textarea name="comment" class="form-control" placeholder="Dites-nous ce que vous avez pensé de ce lieu..." style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; min-height: 80px; font-family: inherit;"></textarea>
                </div>
                
                <button type="submit" class="button primary" style="width: 100%;">Publier mon avis</button>
            </form>
        </div>
        {% else %}
        {% else %}
        <div class="review-already-posted-embed">
            <p><i class="fa-solid fa-check-circle"></i> Merci ! Vous avez déjà donné votre avis sur ce lieu.</p>
        </div>
        {% endif %}
    {% else %}
        <div class="review-login-required-embed">
            <p><a href="{{ loginUrl }}">Connectez-vous</a> pour laisser un avis.</p>
        </div>
    {% endif %}

    <div class="poi-reviews-list-embed">
        {% if reviews %}
            {% for review in reviews %}
            <div class="poi-review-item-embed" style="padding: 1.5rem 0; border-bottom: 1px solid #eee;">
                <div class="poi-review-header" style="display: flex; justify-content: space-between; margin-bottom: 0.8rem;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        {% if review.has_avatar %}
                        <img src="{{ review.author_avatar }}" alt="{{ review.author_name }}" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;">
                        {% else %}
                        <div class="review-avatar-placeholder-small" style="background-color: {{ review.avatar_color }};">
                            {{ review.avatar_initials }}
                        </div>
                        {% endif %}
                        <div>
                            <div style="font-weight: 600; color: #333; font-size: 0.95rem;">{{ review.author_name }}</div>
                            <div style="font-size: 0.8rem; color: #999;">{{ review.created_at_formatted }}</div>
                        </div>
                    </div>
                    <div class="poi-review-rating" style="color: #ffc107; font-size: 0.9rem;">
                        {% for star in review.stars %}
                            {% if star.isFull %}<i class="fa-solid fa-star"></i>{% else %}<i class="fa-regular fa-star"></i>{% endif %}
                        {% endfor %}
                    </div>
                </div>
                {% if review.comment %}
                <div class="poi-review-comment" style="color: #555; line-height: 1.5; font-size: 0.95rem;">{{ review.comment }}</div>
                {% endif %}
                
                {% if isOwner and review.status == "active" and review.moderateUrl %}
                <div style="margin-top: 0.5rem; text-align: right;">
                    <form method="POST" action="{{ review.moderateUrl }}" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir modérer cet avis ?');">
                        <input type="hidden" name="token" value="{{ csrfToken }}">
                        <input type="hidden" name="reason" value="Modéré par le propriétaire">
                        <button type="submit" class="button-text small" style="background: none; border: none; color: #dc3545; font-size: 0.8rem; cursor: pointer; padding: 0;">
                            <i class="fa-solid fa-flag"></i> Signaler / Modérer
                        </button>
                    </form>
                </div>
                {% endif %}
            </div>
            {% endfor %}
        {% else %}
            <div style="text-align: center; color: #999; padding: 2rem; font-style: italic;">
                Soyez le premier à donner votre avis !
            </div>
        {% endif %}
    </div>
</div>
