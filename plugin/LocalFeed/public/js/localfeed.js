/**
 * LocalFeed UI & Interactions (Vivre-Bourges)
 */
const LocalFeedUI = {
    /**
     * Réaction universelle (like, interest) avec micro-interaction instantanée
     */
    async react(buttonEl, contentType, contentId, reactionType = 'like') {
        if (!buttonEl || buttonEl.disabled) return;
        
        buttonEl.disabled = true;
        const icon = buttonEl.querySelector('i');
        const countEl = buttonEl.querySelector('.count-val') || buttonEl.querySelector('.like-count');
        
        // Micro-animation immédiate au clic
        if (icon) {
            icon.classList.add('lf-icon-bounce');
            setTimeout(() => icon.classList.remove('lf-icon-bounce'), 400);
        }

        try {
            const resp = await fetch('/localfeed/react', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    type: contentType,
                    id: contentId,
                    reaction: reactionType
                })
            });

            if (resp.status === 401) {
                const returnUrl = encodeURIComponent(window.location.pathname + window.location.search);
                let msgType = 'like';
                if (contentType === 'event') {
                    msgType = (reactionType === 'interest') ? 'interest' : 'participate';
                }
                window.location.href = '/users/login?msg=' + msgType + '&return_url=' + returnUrl;
                return;
            }

            const data = await resp.json();
            if (data.success) {
                const isReacted = data.reacted;
                
                // 1. Mise à jour du bouton cliqué
                if (isReacted) {
                    buttonEl.classList.add('active');
                    if (icon) {
                        if (reactionType === 'like') {
                            icon.className = 'fa-solid fa-heart';
                        } else if (reactionType === 'interest') {
                            icon.className = 'fa-solid fa-star';
                        }
                    }
                } else {
                    buttonEl.classList.remove('active');
                    if (icon) {
                        if (reactionType === 'like') {
                            icon.className = 'fa-regular fa-heart';
                        } else if (reactionType === 'interest') {
                            icon.className = 'fa-regular fa-star';
                        }
                    }
                }

                if (countEl) {
                    countEl.textContent = `(${data.reaction_count})`;
                }

                // 2. Gestion des boutons alternatifs sur la même carte (ex: événement switch like <-> interest)
                const card = buttonEl.closest('.feed-card');
                if (card && isReacted) {
                    const altButtons = card.querySelectorAll(`.feed-action-btn[data-id="${contentId}"]:not([data-reaction="${reactionType}"])`);
                    altButtons.forEach(altBtn => {
                        altBtn.classList.remove('active');
                        const altIcon = altBtn.querySelector('i');
                        const altReaction = altBtn.dataset.reaction;
                        if (altIcon) {
                            if (altReaction === 'like') altIcon.className = 'fa-regular fa-heart';
                            if (altReaction === 'interest') altIcon.className = 'fa-regular fa-star';
                        }
                        const altCountEl = altBtn.querySelector('.count-val');
                        if (altCountEl && data.counts && typeof data.counts[altReaction] !== 'undefined') {
                            altCountEl.textContent = `(${data.counts[altReaction]})`;
                        }
                    });
                }
            } else if (data.error) {
                alert(data.error);
            }
        } catch (e) {
            console.error('Erreur réaction LocalFeed:', e);
        } finally {
            buttonEl.disabled = false;
        }
    },

    async toggleLike(buttonEl, postId) {
        return this.react(buttonEl, 'post', postId, 'like');
    },

    async sharePost(postId, title, snippet) {
        const postUrl = `${window.location.origin}/localfeed/feed#feed-post-${postId}`;

        if (navigator.share) {
            try {
                await navigator.share({
                    title: `${title} sur Vivre-Bourges`,
                    text: snippet,
                    url: postUrl
                });

                // Trace analytique
                fetch(`/localfeed/post/${postId}/share`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'channel=native'
                });
            } catch (err) {
                // Annulé par l'utilisateur
            }
        } else {
            // Fallback presse-papier
            try {
                await navigator.clipboard.writeText(postUrl);
                this.showToast('Lien de la publication copié dans le presse-papier !');

                fetch(`/localfeed/post/${postId}/share`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'channel=copy_link'
                });
            } catch (err) {
                prompt('Copiez ce lien :', postUrl);
            }
        }
    },

    toggleComments(postId) {
        const box = document.getElementById(`comments-box-${postId}`);
        if (!box) return;
        const isHidden = box.style.display === 'none' || box.classList.contains('hidden');
        if (isHidden) {
            box.style.display = 'block';
            box.classList.remove('hidden');
            const input = box.querySelector('textarea, input[type="text"]');
            if (input) input.focus();
        } else {
            box.style.display = 'none';
            box.classList.add('hidden');
        }
    },

    async submitComment(event, postId) {
        if (event) event.preventDefault();
        const form = document.getElementById(`comment-form-${postId}`);
        if (!form) return;

        const textarea = form.querySelector('textarea[name="content"], input[name="content"]');
        const content = textarea ? textarea.value.trim() : '';
        if (!content) {
            this.showToast('Veuillez écrire un commentaire.');
            return;
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        try {
            const resp = await fetch(`/localfeed/post/${postId}/comment`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ content: content })
            });

            if (resp.status === 401) {
                const returnUrl = encodeURIComponent(window.location.pathname + window.location.search);
                window.location.href = '/users/login?msg=comment&return_url=' + returnUrl;
                return;
            }

            const data = await resp.json();
            if (data.success && data.comment) {
                if (textarea) textarea.value = '';
                
                // Mettre à jour le compteur sur le bouton
                const countEls = document.querySelectorAll(`.post-${postId}-comment-count`);
                countEls.forEach(el => el.textContent = `(${data.comment.comments_count})`);

                // Ajouter le commentaire dans la liste
                const list = document.getElementById(`comments-list-${postId}`);
                if (list) {
                    const noCommentsNotice = list.querySelector('.no-comments-yet');
                    if (noCommentsNotice) noCommentsNotice.remove();

                    const newEl = document.createElement('div');
                    newEl.className = 'poi-comment-item';
                    newEl.id = `comment-item-${data.comment.id}`;
                    newEl.innerHTML = `
                        <div class="comment-author-avatar">
                            <i class="fa-solid fa-user-circle"></i>
                        </div>
                        <div class="comment-bubble">
                            <div class="comment-header-row">
                                <span class="comment-author-name">${this.escapeHtml(data.comment.display_name)}</span>
                                <span class="comment-time">${data.comment.time_ago}</span>
                            </div>
                            <div class="comment-body">${this.escapeHtml(data.comment.content)}</div>
                        </div>
                    `;
                    list.appendChild(newEl);
                }

                this.showToast('Commentaire publié avec succès !');
            } else {
                this.showToast(data.error || 'Erreur lors de la publication du commentaire.');
            }
        } catch (e) {
            console.error('Erreur submitComment:', e);
            this.showToast('Une erreur est survenue.');
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    },

    escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    },

    showToast(message) {
        let toast = document.getElementById('localfeed-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'localfeed-toast';
            toast.className = 'localfeed-toast';
            document.body.appendChild(toast);
        }
        toast.textContent = message;
        toast.classList.add('visible');
        setTimeout(() => toast.classList.remove('visible'), 3000);
    }
};

function initLocalFeedWeather() {
    if (localStorage.getItem('vb_weather_collapsed') === '1') {
        const bodies = document.querySelectorAll('.weather-collapsible-body');
        bodies.forEach(b => b.style.display = 'none');
        const btns = document.querySelectorAll('.weather-toggle-pill, .weather-toggle-btn');
        btns.forEach(btn => {
            btn.innerHTML = '<i class="fa-solid fa-chevron-down"></i> <span class="wth-toggle-text">Détails</span>';
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initLocalFeedWeather();
        const pills = document.querySelectorAll('.publish-type-selector .type-pill');
        pills.forEach(pill => {
            pill.addEventListener('click', () => {
                pills.forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
            });
        });
    });
} else {
    initLocalFeedWeather();
}

window.toggleWeatherAccordion = function(id) {
    const body = document.getElementById('weather-body-' + id);
    const btn = document.getElementById('weather-toggle-btn-' + id);
    if (!body) return;
    const isHidden = (body.style.display === 'none');
    body.style.display = isHidden ? 'block' : 'none';
    if (btn) {
        btn.innerHTML = isHidden 
            ? '<i class="fa-solid fa-chevron-up"></i> <span class="wth-toggle-text">Réduire</span>' 
            : '<i class="fa-solid fa-chevron-down"></i> <span class="wth-toggle-text">Détails</span>';
    }
    localStorage.setItem('vb_weather_collapsed', isHidden ? '0' : '1');
};
