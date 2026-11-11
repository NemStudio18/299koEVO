# 🏠 Module HomeBuilder — Composez une page d’accueil sur-mesure

Combinez un hero, des listes d’articles, des CTA ou des formulaires en quelques glisser-déposer. HomeBuilder fusionne l’ancien module « Home » et le constructeur « Constructor » pour offrir une landing page pilotée depuis le back-office, sans toucher au code.

## 🌟 Ce que le module apporte
- 🧩 Bibliothèque de blocs (texte, bouton, tableau, formulaire, image, HTML, container, derniers articles, sondages actifs, CTA livre d’or…).
- 🧱 Structure hiérarchique : créez des conteneurs et des sous-blocs pour assembler des sections complètes.
- 🎚️ Editeur de styles intégré (couleurs, gradients, ombres, marges, rayons, bouton…) par bloc.
- 🔁 Réorganisation rapide (drag & drop côté JS + endpoint `reorder`) et duplication.
- 🔗 Blocs dynamiques interconnectés avec d’autres plugins (ex : blog, sondage, guestbook).

## ⚙️ Vous personnalisez
- Label/titre de la page d’accueil, descriptions, hero title/subtitle.
- Contenu et options de chaque bloc (URL, texte du bouton, nombre d’articles, champs de formulaire, etc.).
- Styles par bloc (container/title/content/button/links) enregistrés dans `blocks.json`.

## 🔒 Pensé pour rester fiable
- Tous les blocs sont stockés dans `DATA_PLUGIN/homebuilder/blocks.json` (préchargé depuis `blocks.default.json`).
- Les opérations critiques (ajout, édition, styles, suppression, reorder) nécessitent un utilisateur autorisé + token.
- Le système évite les cycles lors du déplacement de blocs parents/enfants.

## 🚀 Idéal pour
- Les sites vitrines qui changent régulièrement de campagne.
- Les projets qui veulent relier blog, sondages, guestbook dans une seule landing page.
- Les intégrateurs qui livrent un « builder » simple sans builder externe.

---

## HomeBuilder — Documentation du module

### 1. Présentation
- **Slug** : `homebuilder`
- **Version** : 1.0.0
- **Entrées publiques** : `/home`, `/homebuilder`
- **Entrée admin** : `/admin/homebuilder`
- **Stockage** : `DATA_PLUGIN/homebuilder/blocks.json` (+ `param/blocks.default.json` comme seed)

### 2. Fonctionnalités
- Listing des blocs (actifs/inactifs) avec aperçu, ordre et accès direct aux actions (éditer, styles, supprimer).
- Formulaire d’ajout/édition complet (type, contenu, options, champs spécifiques selon le type).
- Styles granulaire (section container/title/content/links/button) avec choix de gradient, bordures, ombres, etc.
- Reorder AJAX (`/admin/homebuilder/reorder`) + gestion des parents/enfants.
- Routes publiques `/home` et `/homebuilder` servant la page composées des blocs actifs.

### 3. Configuration (`param/config.json`)

| Clé | Description | Défaut |
| --- | --- | --- |
| `priority` | Position dans le menu | `1` |
| `label` | Libellé utilisé (menu/admin) | `Accueil` |
| `description` | Description de la page | `Page d'accueil dynamique` |
| `heroTitle` | Titre affiché dans le hero | `Bienvenue sur 299Ko` |
| `heroSubtitle` | Sous-titre du hero | `Créez votre landing page...` |

### 4. Routes (`param/routes.php`)

| Méthode | URI | Action |
| --- | --- | --- |
| GET | `/home` | `HomeBuilderController#home` |
| GET | `/homebuilder` | `HomeBuilderController#home` |
| GET | `/admin/homebuilder` | `HomeBuilderAdminController#index` |
| GET | `/admin/homebuilder/add` | `HomeBuilderAdminController#add` |
| GET | `/admin/homebuilder/edit/[id]` | `HomeBuilderAdminController#edit` |
| POST | `/admin/homebuilder/add/send` | `HomeBuilderAdminController#addSend` |
| POST | `/admin/homebuilder/edit/send/[id]` | `HomeBuilderAdminController#editSend` |
| POST | `/admin/homebuilder/delete/[id]` | `HomeBuilderAdminController#delete` |
| POST | `/admin/homebuilder/reorder` | `HomeBuilderAdminController#reorder` |
| GET | `/admin/homebuilder/styles/[id]` | `HomeBuilderAdminController#styles` |
| POST | `/admin/homebuilder/styles/send/[id]` | `HomeBuilderAdminController#stylesSend` |

### 5. Sécurité
- `isAuthorized()` partout côté admin, redirection + flash `Show::msg` en cas d’échec.
- Fichier `blocks.json` créé automatiquement ; vérifier ses permissions (écriture serveur).
- Reorder/Styles utilisent JSON depuis `php://input` : validez l’origine côté proxy/WAF.
- Pas de hook public : la page `/home` reste un contrôleur classique ⇒ suivre les règles habituelles (cache, SEO).

### 6. Tests rapides
- Ajouter un bloc « Texte simple » + un bloc « Derniers articles » → vérifier `/home`.
- Modifier les styles (gradient, padding) → recharger la page pour constater le changement.
- Désactiver un bloc ou le déplacer dans un conteneur → vérifier l’ordre rendue côté public.
- Tester la suppression avec un mauvais token → le bloc doit rester intact.

### 7. Références
- `homebuilder/homebuilder.php`
- `controllers/HomeBuilderController.php`
- `controllers/HomeBuilderAdminController.php`
- `entities/Block.php`, `entities/BlockManager.php`
- `template/admin/*.tpl`, `template/public/*.tpl`
- `param/config.json`, `param/routes.php`, `param/infos.json`

