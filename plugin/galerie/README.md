# 🖼️ Module Galerie — Sublimez vos visuels en quelques clics

Exposez vos plus belles images dans une galerie responsive, légère et personnalisable. Téléversez une photo, laissez le module la redimensionner automatiquement et décidez comment l’exposer (ordre, catégories, titres, introduction riche). Résultat : une vitrine photo propre, élégante et simple à maintenir.

## 🌟 Ce que le module apporte
- 🗂️ Tri intelligent par date, nom ou ordre naturel.
- 🏷️ Catégories et filtre automatique pour guider vos visiteurs.
- 🖼️ Redimensionnement auto pour des visuels optimisés (PNG/JPG/GIF).
- 📝 Introduction éditable via votre éditeur préféré (TinyMCE / EasyMDE).
- 🎨 Templates et CSS prêts à adapter pour une intégration parfaite.

## ⚙️ Vous personnalisez
- Titre du module, introduction riche, taille max d’image (pixels).
- Mode d’affichage (avec/sans titre, ordre de tri, affichage uniquement visuel).
- Gestion des catégories, statut visible/masqué, description par image.

## 🔒 Pensé pour rester fiable
- Upload sécurisé via `Util::uploadFile` (whitelist extensions + renommage `uniqid`).
- Redimensionnement côté serveur (`galerieResize`) pour éviter les fichiers lourds.
- Actions sensibles protégées par token + `isAuthorized()`.

## 🚀 Idéal pour
- Portfolios (photographes, agences, artistes).
- Sites associatifs/vitrines présentant des événements.
- Showrooms produits en attendant un e-commerce complet.

---

## Galerie — Documentation du module

### 1. Présentation
- **Slug** : `galerie`
- **Version** : 2.0
- **Entrées** : `/galerie`, `/admin/galerie`
- **Stockage** : `DATA_PLUGIN/galerie/galerie.json` + fichiers dans `UPLOAD/galerie/`

### 2. Fonctionnalités
- Listing public triable par date/nom/naturel, filtre catégories.
- Upload + redimensionnement automatique, support PNG/JPG/GIF.
- Admin complet : ajout, édition, suppression, masquage, paramétrage global.
- Éditeur dédié via `galerieGenerateEditor()` pour rédiger l’introduction.

### 3. Configuration (`param/config.json`)

| Clé | Description | Défaut |
| --- | --- | --- |
| `priority` | Position menu | `2` |
| `label` | Titre affiché | `Galerie` |
| `order` | `byDate`, `byName`, `natural` | `byDate` |
| `onlyImg` | Mode « images seules » | `0` |
| `introduction` | Texte introductif | `""` |
| `showTitles` | Afficher les titres | `1` |
| `size` | Largeur max (px) | `1024` |

### 4. Routes (`param/routes.php`)

| Méthode | URI | Action |
| --- | --- | --- |
| GET | `/galerie` | `GalerieController#home` |
| GET | `/admin/galerie` | `GalerieAdminController#home` |
| GET | `/admin/galerie/edit/[id]?` | `GalerieAdminController#edit / #editId` |
| GET | `/admin/galerie/delete/[id]/[token]` | `GalerieAdminController#delete` |
| POST | `/admin/galerie/save` | `GalerieAdminController#save` |
| POST | `/admin/galerie/saveConf` | `GalerieAdminController#saveConf` |

### 5. Hooks
- `endFrontHead` (placeholder pour CSS/JS supplémentaires).
- `galerieGenerateEditor()` fournit un éditeur HTML dans le back-office.

### 6. Sécurité
- Upload via `Util::uploadFile` + renommage unique + resize serveur.
- Actions critiques : token `[a:token]` + `isAuthorized()`.
- Recommandé : limiter `upload_max_filesize` et scanner les fichiers.
- Stocker `UPLOAD/galerie` hors webroot si possible ou protéger via `.htaccess`.

### 7. Tests rapides
- Téléverser un fichier non image → l’upload doit échouer.
- Modifier l’ordre (ex. `byName`) → vérifier le tri front.
- Supprimer un item → vérifier que `galerie.json` reste cohérent.

### 8. Références fichiers
- `galerie/galerie.php`
- `controllers/GalerieController.php`
- `controllers/GalerieAdminController.php`
- `template/*.tpl`, `template/public.css`
- `param/config.json`, `param/routes.php`, `param/hooks.json`

