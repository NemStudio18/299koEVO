# 🎨 Module CSS Editor — Harmonisez votre thème sans toucher aux fichiers

Adaptez vos couleurs, variables et règles CSS depuis une interface dédiée : CSS Editor scanne votre thème, expose les variables disponibles, propose des color pickers et ajoute automatiquement vos surcharges en fin de `<head>`. Vous gardez la main sur votre identité visuelle sans ouvrir un éditeur externe.

## 🌟 Ce que le module apporte
- 🧬 Détection des variables du thème (et fallback sur les couleurs clés si aucune n’existe).
- 🎛️ Interface de réglage (color picker + champs texte) pour mettre à jour chaque variable.
- ✍️ Zone « CSS manuel » pour ajouter vos propres règles et snippets.
- 💾 Sauvegarde dans `DATA_PLUGIN/csseditor/custom.css` + système de backups (limite configurable).
- 🔁 Injection automatique via un hook `endFrontHead` et une route publique avec cache.

## ⚙️ Vous personnalisez
- Activation/désactivation globale du CSS custom.
- Nombre de sauvegardes conservées, contenu manuel, liste des variables à surcharger.
- Possibilité d’éditer directement le fichier généré (via le gestionnaire de fichiers) si besoin.

## 🔒 Pensé pour rester fiable
- Les fichiers générés vivent en dehors du thème : vous ne perdez rien lors d’une mise à jour.
- Le contrôleur public vérifie l’état du plugin, le timestamp et l’ETag pour limiter le trafic.
- Droits d’écriture contrôlés sur `DATA_PLUGIN/csseditor/` (échec clair si permissions insuffisantes).

## 🚀 Idéal pour
- Les intégrateurs qui veulent ajuster rapidement un thème livré.
- Les clients qui souhaitent modifier des couleurs sans FTP.
- Toute installation nécessitant plusieurs variantes CSS (événement, promo, etc.).

---

## CSS Editor — Documentation du module

### 1. Présentation
- **Slug** : `csseditor`
- **Version** : 1.0
- **Entrées** : `/csseditor/custom.css` (flux CSS), `/admin/csseditor`
- **Hooks** : `csseditorEndFrontHead`
- **Stockage** : `DATA_PLUGIN/csseditor/custom.css` + `backups/`

### 2. Fonctionnalités
- Analyse du thème courant pour extraire les variables CSS (`--color-primary`, etc.).
- Interface d’administration (onglet unique) avec :
  - tableau des variables + pickers ;
  - zone de CSS manuel ;
  - commutateur d’activation ;
  - indicateur `lastModified`.
- Génération du fichier `custom.css` + sauvegardes horodatées (limite `backupCount`).
- Route publique `csseditor/custom.css` avec gestion ETag/Last-Modified.
- Hook `endFrontHead` qui injecte le `<link>` seulement si le plugin est actif et le CSS non vide.

### 3. Configuration (`param/config.json`)

| Clé | Description | Défaut |
| --- | --- | --- |
| `priority` | Position du module dans le menu admin | `5` |
| `cssContent` | CSS généré (stocké pour l’interface) | `""` |
| `enabled` | Active l’injection du fichier custom | `0` |
| `lastModified` | Date/heure de la dernière sauvegarde | `""` |
| `backupCount` | Nb de backups conservés | `5` |
| `manualCss`* | (stocké dynamiquement) CSS saisi par l’admin | `""` |
| `cssVars`* | (JSON) variables personnalisées sauvegardées | `[]` |

### 4. Routes (`param/routes.php`)

| Méthode | URI | Action |
| --- | --- | --- |
| GET | `/admin/csseditor` | `CssEditorAdminController#home` |
| POST | `/admin/csseditor/save` | `CssEditorAdminController#save` |
| POST | `/admin/csseditor/save-css` | `CssEditorAdminController#saveCss` (AJAX) |
| GET | `/csseditor/custom.css` | `CssEditorPublicController#customCss` |

### 5. Hooks

| Hook | Callback | Rôle |
| --- | --- | --- |
| `endFrontHead` | `csseditorEndFrontHead` | Ajoute le `<link rel="stylesheet">` vers le CSS généré |

### 6. Sécurité
- Toutes les actions admin passent par `isAuthorized()`.
- Les fichiers sont écrits uniquement si `DATA_PLUGIN/csseditor/` est accessible en écriture.
- Le contrôleur public renvoie 404 si le plugin est désactivé ou si le fichier est vide.
- Pensez à restreindre les permissions du dossier `DATA_PLUGIN/csseditor/` (750 recommandé).

### 7. Tests rapides
- Activer le plugin, modifier une variable → vérifier que le `<link>` apparait dans le `<head>` avec un timestamp.
- Ajouter du CSS manuel (ex : modifier `body { font-size }`) → recharger la page publique.
- Désactiver le plugin → le fichier reste accessible mais n’est plus injecté.
- Consulter `/csseditor/custom.css` avec l’entête `If-Modified-Since` → attendre un `304 Not Modified`.

### 8. Références
- `csseditor/csseditor.php`
- `controllers/CssEditorAdminController.php`
- `controllers/CssEditorPublicController.php`
- `template/admin.tpl`
- `param/config.json`, `param/routes.php`, `param/infos.json`

