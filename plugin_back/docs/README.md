# 📚 Module Docs — Transformez votre site en base de connaissances

Construisez un wiki complet directement dans 299Ko. Arborescences fines, historique des versions, sommaires automatiques et shortcodes pour lier n’importe quelle page : le module Docs est l’allié des équipes support, produit ou formation.

## 🌟 Ce que le module apporte
- 🧭 Navigation confortable : catégories, pagination, arbre latéral, activité récente.
- 📝 Pages riches avec TOC, liens internes et contenu édité dans votre éditeur favori.
- 🕒 Historique prêt à restaurer : comparez et revenez à une version antérieure.
- 🔗 Shortcode `[docsLink]` pour référencer vos articles depuis un blog, une FAQ, etc.
- 🛠️ Scripts front/back dédiés pour une expérience utilisateur moderne.

## ⚙️ Vous personnalisez
- Titre et texte d’accueil, nombre d’items par page.
- Sommaire automatique (`displayTOC`), widgets (last activity, tree).
- Activation du versioning et des liens internes.

## 🔒 Pensé pour rester fiable
- Droits d’accès stricts pour la création/modification.
- Historique stocké côté serveur (JSON) et réversibilité totale.
- Assets séparés (JS/CSS) pour éviter les conflits front.

## 🚀 Idéal pour
- Bases de connaissances clients.
- Documentations internes ou wiki d’équipe.
- Projets open source souhaitant héberger leur doc sans service externe.

---

## Docs — Documentation du module

### 1. Présentation
- **Slug** : `docs`
- **Version** : 1.0
- **Entrées** : `/docs`, `/admin/docs`
- **Domaines** : pages (`WikiPage*`), catégories (`WikiCategory*`), historique (`WikiHistory*`), activité (`WikiActivityManager`)

### 2. Fonctionnalités
- Listing public paginé, filtre par catégorie, page de lecture avec TOC.
- CRUD pages/catégories, éditeur riche, versioning complet (vue, restauration).
- Shortcode `docsLink` enregistré dans `docsBeforeRunPlugin`.
- Scripts front (`public.js`) et back (`admin.js`) injectés via hooks.

### 3. Configuration (`param/config.json`)
| Clé | Rôle | Défaut |
| --- | --- | --- |
| `priority` | Position menu | `2` |
| `label` | Titre principal | `Documentation` |
| `homeText` | Texte d’accueil | Phrase par défaut |
| `displayTOC` | Emplacement du sommaire | `content` |
| `hideContent` | Masquer le corps dans les listes | `0` |
| `itemsByPage` | Pagination | `10` |
| `enableVersioning` | Historique | `1` |
| `enableInternalLinks` | Shortcodes automatiques | `1` |
| `showLastActivity` / `showCategoryTree` | Widgets | `1` |

### 4. Routes clés (`param/routes.php`)
- **Public** : `/docs`, `/docs/[page]`, `/docs/cat-*`, `/docs/[name]-[id].html`
- **Admin** : `/admin/docs`, `/admin/docs/savePage`, `/admin/docs/editPage/[id]`, `/admin/docs/addCategory`, `/admin/docs/history/[id]`, `/admin/docs/version/[id]/[version]`, `/admin/docs/restoreVersion`, etc.

### 5. Sécurité
- Toutes les actions admin exigent `isAuthorized()`.
- Shortcode renvoie une chaîne vide si l’ID n’existe pas (pas d’erreur fatale).
- Vérifier les appels AJAX dans `template/admin.js` (tokens CSRF côté routes).
- Conseillé : placer `DATA_PLUGIN/docs/` hors webroot & permissions restreintes.

### 6. Tests rapides
- Créer une page, modifier-la, restaurer une version précédente.
- Supprimer une catégorie utilisée → vérifier la protection côté manager.
- Insérer `[docsLink id="X"]` dans un article de blog et tester le rendu.

### 7. Références fichiers
- `docs/docs.php`
- `controllers/Docs*.php`
- `entities/Wiki*.php`
- `template/*.tpl`, `template/public.js`, `template/admin.js`
- `param/config.json`, `param/routes.php`, `param/hooks.json`
