<?php if (empty($timeline['items'])): ?>
    <div class="localfeed-empty-state">
        <div class="empty-icon"><i class="fa-solid fa-newspaper"></i></div>
        <h3>Aucune actualité pour le moment</h3>
        <p>Les commerçants de Bourges publieront bientôt leurs dernières nouvelles et offres exclusives ici !</p>
    </div>
<?php else: ?>
    <?php foreach ($timeline['items'] as $item): ?>
        <?php if ($item['type'] === 'post'): ?>
            <article class="feed-card post-card <?= $item['is_favorite_poi'] ? 'card-favorite' : '' ?>" id="feed-post-<?= $item['id'] ?>">
                <div class="feed-card-header">
                    <div class="feed-author-avatar">
                        <i class="fa-solid fa-store"></i>
                    </div>
                    <div class="feed-author-meta">
                        <div class="feed-author-row">
                            <a href="/p-<?= $item['poi_id'] ?>" class="feed-author-name"><?= htmlspecialchars($item['poi_name']) ?></a>
                            <?php if ($item['is_favorite_poi']): ?>
                                <span class="badge-favorite" title="Commerce dans vos favoris"><i class="fa-solid fa-heart"></i> Favori</span>
                            <?php endif; ?>
                        </div>
                        <span class="feed-post-time">
                            <i class="fa-regular fa-clock"></i> <?= htmlspecialchars($item['time_ago']) ?>
                            <?php if (!empty($item['poi_location'])): ?>
                                • <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($item['poi_location']) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <?php if (!empty($item['title'])): ?>
                    <h3 class="feed-card-title"><?= htmlspecialchars($item['title']) ?></h3>
                <?php endif; ?>

                <div class="feed-card-body">
                    <p><?= nl2br(htmlspecialchars($item['content'])) ?></p>
                </div>

                <?php if (!empty($item['media_url'])): ?>
                    <div class="feed-card-media">
                        <img src="<?= htmlspecialchars($item['media_url']) ?>" alt="Photo publication" loading="lazy" referrerpolicy="no-referrer" />
                    </div>
                <?php endif; ?>

                <div class="feed-card-footer">
                    <div class="feed-actions">
                        <button class="feed-action-btn btn-like <?= (!empty($item['has_liked']) || ($item['user_reaction'] ?? '') === 'like') ? 'active' : '' ?>" 
                                data-type="post"
                                data-id="<?= $item['id'] ?>"
                                data-reaction="like"
                                onclick="LocalFeedUI.react(this, 'post', <?= $item['id'] ?>, 'like')">
                            <i class="<?= (!empty($item['has_liked']) || ($item['user_reaction'] ?? '') === 'like') ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                            <span class="like-label">J'aime</span>
                            <span class="like-count count-val">(<?= (int)($item['reactions']['like'] ?? $item['likes_count'] ?? 0) ?>)</span>
                        </button>

                        <button class="feed-action-btn btn-share" 
                                onclick="LocalFeedUI.sharePost(<?= $item['id'] ?>, '<?= addslashes(htmlspecialchars($item['poi_name'])) ?>', '<?= addslashes(htmlspecialchars(substr($item['content'], 0, 100))) ?>')">
                            <i class="fa-solid fa-share-nodes"></i>
                            <span>Partager</span>
                            <?php if ($item['shares_count'] > 0): ?>
                                <span class="share-count">(<?= $item['shares_count'] ?>)</span>
                            <?php endif; ?>
                        </button>

                        <button type="button" class="feed-action-btn btn-copy-link" 
                                onclick="vbCopyLink('https://vivre-bourges.fr/localfeed/feed#feed-post-<?= $item['id'] ?>', this)"
                                title="Copier le lien direct de cette publication">
                            <i class="fa-solid fa-link"></i>
                            <span>Copier</span>
                        </button>

                        <?php $postFbUrl = 'https://vivre-bourges.fr/p-' . $item['poi_id']; ?>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($postFbUrl) ?>" 
                           target="_blank" 
                           rel="noopener noreferrer" 
                           class="feed-action-btn btn-fb-share" 
                           title="Partager cette publication sur Facebook"
                           aria-label="Partager cette publication sur Facebook">
                            <i class="fa-brands fa-facebook"></i>
                            <span>Facebook</span>
                        </a>

                        <a href="/p-<?= $item['poi_id'] ?>" class="feed-action-btn btn-visit" aria-label="Voir la fiche : <?= htmlspecialchars($item['title'] ?? 'Commerce') ?>">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            <span>Voir le commerce</span>
                        </a>
                    </div>
                </div>
            </article>

        <?php elseif ($item['type'] === 'event'): ?>
            <article class="feed-card event-card <?= !empty($item['is_cgr']) ? 'cgr-feed-card' : '' ?>" id="feed-event-<?= $item['id'] ?>">
                <div class="feed-card-badge event-badge <?= !empty($item['is_cgr']) ? 'is-cgr' : '' ?>">
                    <i class="fa-solid <?= !empty($item['is_cgr']) ? 'fa-film' : 'fa-calendar-check' ?>"></i> 
                    <?= !empty($item['is_cgr']) ? 'À l\'Affiche • Méga CGR Bourges' : 'Sortie & Événement Ville de Bourges' ?>
                </div>
                <div class="feed-card-header">
                    <div class="feed-author-avatar event-avatar <?= !empty($item['is_cgr']) ? 'is-cgr' : '' ?>">
                        <i class="fa-solid <?= !empty($item['is_cgr']) ? 'fa-film' : 'fa-champagne-glasses' ?>"></i>
                    </div>
                    <div class="feed-author-meta">
                        <h3 class="feed-event-title"><a href="/agenda/event/<?= $item['id'] ?>"><?= htmlspecialchars($item['title']) ?></a></h3>
                        <span class="feed-post-time">
                            <i class="fa-regular fa-calendar"></i> <?= htmlspecialchars($item['time_display']) ?>
                            • <i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($item['location']) ?>
                        </span>
                    </div>
                </div>

                <div class="feed-card-body">
                    <?php if (!empty($item['is_cgr']) && !empty($item['cgr_movies'])): ?>
                        <div class="feed-cgr-movies-carousel">
                            <?php foreach (array_slice($item['cgr_movies'], 0, 10) as $cm): ?>
                                <a href="/agenda/event/<?= $item['id'] ?>" class="feed-cgr-movie-link" title="<?= htmlspecialchars($cm['title']) ?>">
                                    <div class="feed-cgr-poster">
                                        <img src="<?= htmlspecialchars($cm['image']) ?>" alt="<?= htmlspecialchars($cm['title']) ?>" width="95" height="135" loading="lazy" decoding="async" referrerpolicy="no-referrer">
                                        <?php if (!empty($cm['tag'])): ?>
                                            <span class="feed-cgr-tag"><?= htmlspecialchars($cm['tag']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="feed-cgr-title"><?= htmlspecialchars($cm['title']) ?></div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif (!empty($item['media_url'])): ?>
                        <div class="feed-card-media event-card-media">
                            <a href="/agenda/event/<?= $item['id'] ?>" title="<?= htmlspecialchars($item['title']) ?>">
                                <img src="<?= htmlspecialchars($item['media_url']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" width="600" height="300" loading="lazy" decoding="async" referrerpolicy="no-referrer" />
                            </a>
                        </div>
                    <?php endif; ?>
                    <p><?= nl2br(htmlspecialchars($item['content'])) ?></p>
                </div>

                <div class="feed-card-footer">
                    <div class="feed-actions">
                        <!-- Bouton J'y participe -->
                        <button class="feed-action-btn btn-participate <?= (($item['user_reaction'] ?? '') === 'like') ? 'active' : '' ?>"
                                data-type="event"
                                data-id="<?= $item['id'] ?>"
                                data-reaction="like"
                                onclick="LocalFeedUI.react(this, 'event', <?= $item['id'] ?>, 'like')"
                                title="Je participe à cet événement">
                            <i class="<?= (($item['user_reaction'] ?? '') === 'like') ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                            <span>J'y participe</span>
                            <span class="count-val">(<?= (int)($item['reactions']['like'] ?? 0) ?>)</span>
                        </button>

                        <!-- Bouton Intéressé -->
                        <button class="feed-action-btn btn-interest <?= (($item['user_reaction'] ?? '') === 'interest') ? 'active' : '' ?>"
                                data-type="event"
                                data-id="<?= $item['id'] ?>"
                                data-reaction="interest"
                                onclick="LocalFeedUI.react(this, 'event', <?= $item['id'] ?>, 'interest')"
                                title="Cet événement m'intéresse">
                            <i class="<?= (($item['user_reaction'] ?? '') === 'interest') ? 'fa-solid' : 'fa-regular' ?> fa-star"></i>
                            <span>Intéressé</span>
                            <span class="count-val">(<?= (int)($item['reactions']['interest'] ?? 0) ?>)</span>
                        </button>

                        <a href="/agenda/event/<?= $item['id'] ?>" class="feed-action-btn btn-visit" aria-label="Voir dans l'Agenda : <?= htmlspecialchars($item['title']) ?>">
                            <i class="fa-solid fa-calendar-day"></i> Voir dans l'Agenda
                        </a>
                        <?php $eventFbUrl = 'https://vivre-bourges.fr/agenda/event/' . $item['id']; ?>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($eventFbUrl) ?>" 
                           target="_blank" 
                           rel="noopener noreferrer" 
                           class="feed-action-btn btn-fb-share" 
                           title="Partager cet événement sur Facebook"
                           aria-label="Partager cet événement sur Facebook : <?= htmlspecialchars($item['title']) ?>">
                            <i class="fa-brands fa-facebook"></i>
                            <span>Facebook</span>
                        </a>

                        <button type="button" class="feed-action-btn btn-copy-link" 
                                onclick="vbCopyLink('https://vivre-bourges.fr/agenda/event/<?= $item['id'] ?>', this)"
                                title="Copier le lien de cet événement">
                            <i class="fa-solid fa-link"></i>
                            <span>Copier</span>
                        </button>
                    </div>
                </div>
            </article>

        <?php elseif ($item['type'] === 'offer'): ?>
            <article class="feed-card offer-card" id="feed-offer-<?= $item['id'] ?>">
                <div class="feed-card-badge promo-badge">
                    <i class="fa-solid fa-tag"></i> Bon Plan & Promo Exclusif
                </div>
                <div class="feed-card-header">
                    <div class="feed-author-avatar offer-avatar">
                        <i class="fa-solid fa-percent"></i>
                    </div>
                    <div class="feed-author-meta">
                        <a href="/p-<?= $item['poi_id'] ?>" class="feed-author-name"><?= htmlspecialchars($item['poi_name']) ?></a>
                        <h4 class="feed-offer-title"><?= htmlspecialchars($item['title']) ?></h4>
                    </div>
                </div>

                <div class="feed-card-body">
                    <p><?= nl2br(htmlspecialchars($item['content'])) ?></p>
                </div>

                <div class="feed-card-footer">
                    <a href="/p-<?= $item['poi_id'] ?>" class="feed-action-btn btn-offer">
                        <i class="fa-solid fa-gift"></i> Découvrir l'offre
                    </a>
                    <?php $offerFbUrl = 'https://vivre-bourges.fr/p-' . $item['poi_id']; ?>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($offerFbUrl) ?>" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="feed-action-btn btn-fb-share" 
                       title="Partager ce bon plan sur Facebook"
                       aria-label="Partager ce bon plan sur Facebook">
                        <i class="fa-brands fa-facebook"></i>
                        <span>Facebook</span>
                    </a>

                    <button type="button" class="feed-action-btn btn-copy-link" 
                            onclick="vbCopyLink('https://vivre-bourges.fr/p-<?= $item['poi_id'] ?>', this)"
                            title="Copier le lien de ce bon plan">
                        <i class="fa-solid fa-link"></i>
                        <span>Copier</span>
                    </button>
                </div>
            </article>

        <?php elseif ($item['type'] === 'blog'): ?>
            <article class="feed-card blog-card" id="feed-blog-<?= $item['id'] ?>">
                <div class="feed-card-badge blog-badge">
                    <i class="fa-solid fa-newspaper"></i> Le Journal de Vivre-Bourges
                </div>
                <div class="feed-card-header">
                    <div class="feed-author-avatar blog-avatar">
                        <i class="fa-solid fa-feather-pointed"></i>
                    </div>
                    <div class="feed-author-meta">
                        <h3 class="feed-card-title">
                            <a href="<?= htmlspecialchars($item['url']) ?>">
                                <?= htmlspecialchars($item['title']) ?>
                            </a>
                        </h3>
                        <span class="feed-post-time">
                            <i class="fa-regular fa-clock"></i> <?= htmlspecialchars($item['time_ago']) ?>
                            • Rédaction Vivre-Bourges
                        </span>
                    </div>
                </div>

                <?php if (!empty($item['media_url'])): ?>
                    <div class="feed-card-media">
                        <a href="<?= htmlspecialchars($item['url']) ?>">
                            <img src="<?= htmlspecialchars($item['media_url']) ?>" alt="Illustration article" width="650" height="320" loading="lazy" decoding="async" />
                        </a>
                    </div>
                <?php endif; ?>

                <div class="feed-card-body">
                    <p><?= nl2br(htmlspecialchars($item['content'])) ?></p>
                </div>

                <div class="feed-card-footer">
                    <div class="blog-card-footer-left">
                        <a href="<?= htmlspecialchars($item['url']) ?>" class="feed-action-btn btn-visit" aria-label="Lire l'article complet : <?= htmlspecialchars($item['title']) ?>">
                            <i class="fa-solid fa-book-open-reader"></i> Lire l'article complet
                        </a>
                        <button class="feed-action-btn btn-like <?= (($item['user_reaction'] ?? '') === 'like') ? 'active' : '' ?>"
                                data-type="blog"
                                data-id="<?= $item['id'] ?>"
                                data-reaction="like"
                                onclick="LocalFeedUI.react(this, 'blog', <?= $item['id'] ?>, 'like')"
                                title="Aimer cet article">
                            <i class="<?= (($item['user_reaction'] ?? '') === 'like') ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                            <span class="like-label">J'aime</span>
                            <span class="like-count count-val">(<?= (int)($item['reactions']['like'] ?? $item['likes_count'] ?? 0) ?>)</span>
                        </button>
                    </div>
                    <?php $blogFullUrl = 'https://vivre-bourges.fr' . $item['url']; ?>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= rawurlencode($blogFullUrl) ?>" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="feed-action-btn btn-fb-share" 
                       title="Partager cet article sur Facebook"
                       aria-label="Partager cet article sur Facebook">
                        <i class="fa-brands fa-facebook"></i>
                        <span>Facebook</span>
                    </a>
                    <button type="button" class="feed-action-btn btn-copy-link" 
                            onclick="vbCopyLink('<?= htmlspecialchars($blogFullUrl) ?>', this)"
                            title="Copier le lien de cet article">
                        <i class="fa-solid fa-link"></i>
                        <span>Copier</span>
                    </button>
                </div>
            </article>

        <?php elseif ($item['type'] === 'weather'): $wb = $item['bulletin']; ?>
            <article class="feed-card weather-card <?= $wb['is_morning'] ? 'weather-morning' : 'weather-evening' ?>" id="feed-weather-<?= (int)$item['id'] ?>">
                <div class="weather-card-top" onclick="toggleWeatherAccordion(<?= (int)$item['id'] ?>)">
                    <div class="weather-card-badge">
                        <i class="fa-solid fa-cloud-sun"></i> Bulletin météo de <?= $wb['is_morning'] ? '6h00' : '20h00' ?>
                        <?php if (!empty($item['is_latest'])): ?><span class="weather-live-dot" title="Bulletin en cours"></span><?php endif; ?>
                    </div>
                    <div class="weather-card-top-meta">
                        <span class="feed-post-time"><i class="fa-regular fa-clock"></i> <?= htmlspecialchars($item['time_ago']) ?></span>
                        <button type="button" class="weather-toggle-pill" id="weather-toggle-btn-<?= (int)$item['id'] ?>" title="Réduire ou afficher les détails de la météo" aria-label="Réduire ou afficher les détails de la météo">
                            <i class="fa-solid fa-chevron-up"></i>
                            <span class="wth-toggle-text">Réduire</span>
                        </button>
                    </div>
                </div>
                <div class="weather-card-hero" onclick="toggleWeatherAccordion(<?= (int)$item['id'] ?>)">
                    <?php
                    $mascot = $wb['mascot'] ?? [
                        'webp' => '/images/mascot/what_ensoleille.webp',
                        'png'  => '/images/mascot/what_ensoleille.png',
                        'alt'  => 'What vous présente la météo'
                    ];
                    ?>
                    <div class="weather-card-mascot" title="<?= htmlspecialchars($mascot['alt']) ?>">
                        <picture>
                            <source srcset="<?= htmlspecialchars($mascot['webp']) ?>" type="image/webp">
                            <img src="<?= htmlspecialchars($mascot['png']) ?>" alt="<?= htmlspecialchars($mascot['alt']) ?>" width="68" height="68" class="weather-mascot-img" loading="lazy">
                        </picture>
                    </div>
                    <div>
                        <h3 class="weather-card-title"><?= htmlspecialchars($item['title']) ?></h3>
                        <p class="weather-card-summary"><?= htmlspecialchars($item['content']) ?></p>
                    </div>
                    <div class="weather-card-range">
                        <span class="wmax"><?= (int)$wb['tmax'] ?>°</span>
                        <span class="wmin"><?= (int)$wb['tmin'] ?>°</span>
                        <span class="weather-card-icon"><?= $wb['main_icon'] ?></span>
                    </div>
                </div>
                <div class="weather-collapsible-body" id="weather-body-<?= (int)$item['id'] ?>">
                    <?php if (!empty($wb['periods'])): ?>
                    <div class="weather-periods">
                        <?php foreach ($wb['periods'] as $p): ?>
                            <div class="weather-period" title="<?= htmlspecialchars($p['desc']) ?>">
                                <span class="wp-label"><?= htmlspecialchars($p['label']) ?></span>
                                <span class="wp-icon"><?= $p['icon'] ?></span>
                                <span class="wp-temp"><?= (int)$p['temp'] ?>°C</span>
                                <span class="wp-pop"><i class="fa-solid fa-droplet"></i> <?= (int)$p['pop'] ?>%</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <ul class="weather-tips">
                        <?php foreach ($wb['tips'] as $tip): ?>
                            <li><?= htmlspecialchars($tip) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="feed-card-footer">
                        <a href="/meteo-widget" class="feed-action-btn btn-visit"><i class="fa-solid fa-temperature-half"></i> Prévisions détaillées</a>
                    </div>
                </div>
                <script>
                if (localStorage.getItem('vb_weather_collapsed') === '1') {
                    (function() {
                        var b = document.getElementById('weather-body-<?= (int)$item['id'] ?>');
                        if (b) b.classList.add('is-collapsed');
                        var btn = document.getElementById('weather-toggle-btn-<?= (int)$item['id'] ?>');
                        if (btn) btn.innerHTML = '<i class="fa-solid fa-chevron-down"></i> <span class="wth-toggle-text">Détails</span>';
                    })();
                }
                </script>
            </article>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if (empty($isLandingPreview)): ?>
        <?php if ($timeline['has_more']): ?>
            <div class="feed-load-more" data-next="/localfeed/feed?page=<?= (int)$timeline['page'] + 1 ?>&amp;filter=<?= urlencode($filter ?? 'all') ?>">
                <div class="feed-spinner">
                    <i class="fa-solid fa-circle-notch fa-spin"></i> Chargement des actualités de Bourges...
                </div>
            </div>
        <?php else: ?>
            <div class="feed-end">
                <i class="fa-solid fa-circle-check"></i> Vous êtes à jour ! Revenez plus tard pour de nouvelles actualités.
            </div>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
