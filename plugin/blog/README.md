# 📰 Module Blog — Publiez, engagez, convertissez

Partagez vos actualités, guides ou retours d’expérience depuis une interface qui respire la simplicité. Le module Blog fournit un véritable magazine en ligne : catégories claires, navigation fluide, bloc auteur, fil RSS, commentaires modérés et même un shortcode pour relier vos contenus entre eux.

## 🌟 Ce que le module apporte
- 🧭 Liste paginée avec filtres par catégorie et fil d’Ariane automatique.
- 🗞️ Pages article élégantes : images, bloc auteur, table des matières optionnelle.
- 💬 Commentaires natifs avec modération rapide.
- 📡 Flux RSS prêt pour vos lecteurs et agrégateurs.
- 🔗 Shortcode `[blogLink]` pour interconnecter vos contenus partout ailleurs.

## ⚙️ Vous personnalisez
- Libellé du module, nombre d’articles par page, affichage du contenu tronqué.
- Activation des commentaires, du TOC, et du bloc auteur (nom, avatar, bio).
- Catégories, ordre d’affichage, et styles via les templates fournis.

## 🔒 Pensé pour rester fiable
- Contrôles d’accès stricts (`isAuthorized()`), validation des inputs et hook antispam sur les commentaires.
- Shortcode enregistré via hook `beforeRunPlugin`.
- Flux RSS déclaré automatiquement dans `<head>` (SEO friendly).

## 🚀 Idéal pour
- Sites vitrines qui souhaitent partager des actualités.
- Associations et collectivités qui publient régulièrement.
- Projets SEO cherchant à relier documentation, FAQ, news…

---

## Blog — Documentation du module

### 1. Présentation
- **Slug** : `blog`
- **Version** : 2.0
- **Entrées** : `/blog`, `/admin/blog`
- **Données** : `news`, `newsComment`, `newsManager`, `BlogCategory`, `BlogCategoriesManager`

### 2. Fonctionnalités
- Liste, pagination et filtres /cat-[name]-[id].
- Lecture article avec envoi de commentaires (`BlogReadController#read/#send`).
- Admin : CRUD articles + catégories Ajax, configuration avancée.
- Shortcode `blogLink` ajouté via `blogBeforeRunPlugin`.

### 3. Configuration (`param/config.json`)
| Clé | Rôle | Défaut |
| --- | --- | --- |
| `priority` | Position menu | `2` |
| `label` | Titre | `Blog` |
| `itemsByPage` | Articles listés | `5` |
| `displayTOC` | Table des matières | `no` |
| `hideContent` | Tronquer le contenu | `0` |
| `comments` | Autoriser les commentaires | `1` |
| `authorName`, `authorAvatar`, `authorBio` | Bloc auteur | `""` |
| `displayAuthor` | Afficher le bloc auteur | `false` |

### 4. Routes principales (`param/routes.php`)
- **Public** : `/blog`, `/blog/[page]`, `/blog/cat-*`, `/blog/[name]-[id].html`, `/blog/send.html`, `/blog/rss.html`
- **Admin** : `/admin/blog`, `/admin/blog/savePost`, `/admin/blog/deletePost`, `/admin/blog/listComments`, `/admin/blog/addCategory`, `/admin/blog/saveConfig`, etc.

### 5. Sécurité
- Vérification `isAuthorized()` sur toutes les actions admin.
- Contenus passés par `beforeSaveEditor` → respecter les filtres HTML.
- Recommandé : activer l’antispam sur le formulaire de commentaires.
- Limiter le HTML affiché dans les commentaires côté template.

### 6. Tests rapides
- Créer un article + catégorie, vérifier pagination et flux RSS.
- Ouvrir la popin de catégories depuis la barre d’outils admin.
- Poster un commentaire et valider le workflow de modération.

### 7. Références fichiers
- `blog/blog.php`
- `controllers/Blog*.php`
- `entities/*.php`
- `template/admin-*.tpl`, `template/list.tpl`, `template/read.tpl`
- `param/config.json`, `param/routes.php`, `param/hooks.json`

