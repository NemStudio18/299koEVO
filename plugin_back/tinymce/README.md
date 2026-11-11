# ✒️ Module TinyMCE — L’éditeur WYSIWYG complet prêt à l’emploi

Profitez de toute la puissance de TinyMCE 6 directement dans votre back-office 299Ko : toolbar moderne, upload d’images intégré, palettes de styles, insertion de snippets et compatibilité FontAwesome. Le plugin fournit tout le bundle en local et se charge de l’initialisation pour chaque champ `textarea.editor`.

## 🌟 Ce que le module apporte
- 🛠️ Toolbar riche (blocks, couleurs, media, codesample, charmap, etc.).
- 🖼️ Upload direct via le filemanager 299Ko (token utilisateur automatiquement injecté).
- 🎨 CSS personnalisés (`template/editor.css`) + FontIcon pour rester aligné avec votre front.
- 💬 Bouton custom « dialog-add-icon » pour insérer rapidement un pictogramme FontAwesome.
- 🔌 Hooks dédiés pour ajouter du script avant le premier éditeur (gestion des images).

## ⚙️ Vous personnalisez
- Options TinyMCE dans `tinymceAdminHead()` (plugins, toolbar, formats, languages).
- CSS additionnels (liste `content_css`) et boutons custom.
- Interaction avec votre gestionnaire d’images via `tinymceInsertScriptBeforeEditor`.

## 🔒 Pensé pour rester fiable
- Upload sécurisé via `filemanager-upload-api` + token utilisateur.
- Configurations `addslashes()` pour éviter les injections dans le JS inline.
- Recommandation : ajuster `valid_elements` si vos auteurs ne sont pas de confiance.
- À l’installation, TinyMCE cohabite avec MDEditor grâce au paramètre `priority`.

## 🚀 Idéal pour
- Équipes éditoriales habituées au WYSIWYG.
- Sites qui veulent insérer facilement images, tableaux, blocs d’alertes.
- Projets nécessitant des styles personnalisés accessibles depuis l’éditeur.

---

## TinyMCE — Documentation du module

### 1. Présentation
- **Slug** : `tinymce`
- **Version** : 2.0
- **Entrées** : pas de route publique (hooks uniquement)
- **Assets** : bundle TinyMCE local (`lib/tinymce/`), `template/editor.css`

### 2. Fonctionnalités
- Hook `endAdminHead` → `tinymceAdminHead` (chargement TinyMCE + configuration).
- Hook `insertCodeBeforeFirstEditor` → `tinymceInsertScriptBeforeEditor` (helpers JS).
- Bouton personnalisé « dialog-add-icon ».
- Upload d’images via `filemanager-upload-api` + token utilisateur courant.

### 3. Configuration (`param/config.json`)

| Clé | Description | Défaut |
| --- | --- | --- |
| `priority` | Ordre d’initialisation des hooks (utile avec MDEditor) | `2` |

### 4. Hooks (`param/hooks.json`)
- `endAdminHead` → `tinymceAdminHead`
- `insertCodeBeforeFirstEditor` → `tinymceInsertScriptBeforeEditor`

### 5. Sécurité
- S’assurer que `/filemanager-upload-api` vérifie bien les permissions + MIME.
- TinyMCE permet l’insertion de HTML riche : ajuster `valid_elements`/`content_css` selon vos politiques.
- Scripts chargés localement : mettre à jour le bundle TinyMCE si vulnérabilité.
- Eviter d’activer simultanément TinyMCE et MDEditor sur les mêmes champs pour prévenir les conflits.

### 6. Tests rapides
- Ouvrir un formulaire contenant `textarea.editor` → vérifier l’initialisation TinyMCE.
- Utiliser le bouton image → confirmer que `processInsertImgInEditor` insère bien l’URL.
- Cliquer sur « dialog-add-icon », insérer un snippet `<span class="...">`.

### 7. Références
- `tinymce/tinymce.php`
- `param/config.json`, `param/hooks.json`
- `lib/tinymce/`
- `template/editor.css`
