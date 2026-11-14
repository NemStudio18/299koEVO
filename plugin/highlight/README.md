# ✨ Module HighLight — Rendez vos snippets irrésistibles

Un tutoriel, une documentation ou un article technique gagne immédiatement en clarté quand les blocs de code sont mis en valeur. HighLight active Highlight.js sur toutes vos pages publiques, propose une palette de thèmes modernes et s’installe en quelques secondes.

## 🌟 Ce que le module apporte
- 🎨 Palette de thèmes (GitHub, Monokai, VS…) sélectionnée depuis l’admin.
- ⚡ Chargement automatique de Highlight.js depuis CDNJS.
- 🧠 Initialisation universelle (`hljs.highlightAll()`) sans retouche template.
- 🛠️ Hooks propres (`endFrontHead`, `endFrontBody`) pour garder votre front léger.

## ⚙️ Vous personnalisez
- Thème CSS appliqué au `<head>` via la config.
- Possibilité d’étendre la liste des thèmes (`highlightGetThemes`).
- CSS additionnel ou inline si vous souhaitez un rendu sur-mesure.

## 🔒 Pensé pour rester fiable
- Routes admin protégées (`isAuthorized()`).
- Script distant unique (évite les doublons) + recommandation CSP (`cdnjs.cloudflare.com`).
- Ne touche pas le contenu des utilisateurs : pensez néanmoins à échapper vos `<code>` côté template.

## 🚀 Idéal pour
- Blogs techniques, docs développeurs, cours en ligne.
- Toute page affichant du Markdown converti en HTML.
- Sites qui souhaitent unifier visuellement leurs snippets.

---

## HighLight — Documentation du module

### 1. Présentation
- **Slug** : `highlight`
- **Version** : 2.0.0
- **Entrée admin** : `/admin/highlight`
- **Description** : intégration Highlight.js + choix de thème.

### 2. Fonctionnalités
- Hook `endFrontHead` : injecte CSS + script `highlight.min.js`.
- Hook `endFrontBody` : appelle `hljs.highlightAll()`.
- Interface admin (`template/config.tpl`) pour sélectionner le thème.

### 3. Configuration (`param/config.json`)

| Clé | Description | Défaut |
| --- | --- | --- |
| `priority` | Ordre d’affichage | `9` |
| `theme` | Thème Highlight.js | `default` |

### 4. Routes

| Méthode | URI | Action |
| --- | --- | --- |
| GET | `/admin/highlight` | `HighlightAdminController#home` |
| POST | `/admin/highlight/saveconf` | `HighlightAdminController#save` |

### 5. Hooks (`param/hooks.json`)
- `endFrontHead` → `highlightEndFrontHead`
- `endFrontBody` → `highlightEndFrontBody`

### 6. Sécurité
- `isAuthorized()` sur toute action admin.
- Ajouter un token CSRF si vous ouvrez la route POST sur un domaine externe.
- CSP recommandée : autoriser `https://cdnjs.cloudflare.com`.
- Échapper les blocs `<code>` avant qu’ils soient stylés.

### 7. Tests rapides
- Activer le plugin, choisir un thème, vérifier la présence de `<link rel="stylesheet" ...>` dans `<head>`.
- Ajouter `<pre><code class="language-php">...</code></pre>` → vérifier la coloration.
- Contrôler la console pour s’assurer qu’un seul script Highlight.js est chargé.

### 8. Références
- `highlight/highlight.php`
- `controllers/HighlightAdminController.php`
- `template/config.tpl`
- `param/hooks.json`, `param/routes.php`, `param/config.json`

