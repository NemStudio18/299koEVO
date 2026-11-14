# ✍️ Module MDEditor — Offrez à vos contenus le confort du Markdown

Si vous préférez la simplicité du Markdown à celle d’un WYSIWYG massif, MDEditor déploie EasyMDE côté administration : barre d’outils intuitive, aperçu live, raccourcis clavier, tout y est. Les contenus restent stockés en HTML grâce à ParsedownExtra, vous gardez donc une compatibilité totale avec vos templates.

## 🌟 Ce que le module apporte
- 🧠 Expérience auteur moderne (EasyMDE) sur chaque `textarea.editor`.
- 🔁 Conversion automatique HTML ⇄ Markdown (Markdownify / ParsedownExtra).
- 🧹 Désactivation de TinyMCE à l’installation pour éviter les conflits.
- ⚙️ Hooks centralisés pour intercepter l’édition et la sauvegarde.

## ⚙️ Vous personnalisez
- Barre d’outils EasyMDE (modifiez `mdeditorAdmin` si besoin).
- CSS/JS supplémentaires pour vos éditeurs.
- Possibilité de garder TinyMCE pour certains écrans (en ajustant les hooks).

## 🔒 Pensé pour rester fiable
- Conversion serveur (ParsedownExtra) → surveiller les mises à jour de sécurité.
- Scripts chargés via `cdn.jsdelivr.net` : déclarer le domaine dans votre CSP.
- Contenu final stocké en HTML nettoyé (toujours possible d’ajouter une couche `HTML Purifier`).

## 🚀 Idéal pour
- Rédacteurs familiers du Markdown.
- Documentations techniques, changelogs, FAQ longues.
- Installations qui veulent alléger l’admin.

---

## MDEditor — Documentation du module

### 1. Présentation
- **Slug** : `mdeditor`
- **Version** : 1.3.1
- **Entrées** : pas de route (hooks uniquement)
- **Bibliothèques** : `Markdownify`, `ParsedownExtra`, EasyMDE (CDN)

### 2. Fonctionnalités
- `mdeditorInstall()` désactive TinyMCE si actif.
- Hook `endAdminBody` → injection CSS/JS EasyMDE + initialisation des `textarea.editor`.
- Hook `beforeEditEditor` → conversion HTML → Markdown (Markdownify) lors du chargement.
- Hook `beforeSaveEditor` → conversion Markdown → HTML (ParsedownExtra) à l’enregistrement.

### 3. Configuration (`param/config.json`)

| Clé | Description | Défaut |
| --- | --- | --- |
| `priority` | Ordre d’exécution des hooks | `2` |

### 4. Hooks (`param/hooks.json`)
- `endAdminBody` → `mdeditorAdmin`
- `beforeEditEditor` → `mdeditorBeforeEdit`
- `beforeSaveEditor` → `mdeditorBeforeSave`

### 5. Sécurité
- Tenir ParsedownExtra et Markdownify à jour (veille CVE).
- Ajouter une étape de sanitization si les auteurs peuvent coller du HTML sensible.
- CSP : autoriser `https://cdn.jsdelivr.net`.
- Pensez à restreindre l’accès au bouton d’activation/désactivation de TinyMCE.

### 6. Tests rapides
- Activer le plugin, éditer un contenu existant → vérifier la conversion Markdown.
- Ajouter du code/tableaux/listes → valider le rendu HTML après sauvegarde.
- Réactiver TinyMCE manuellement pour confirmer que MDEditor se désactive proprement.

### 7. Références
- `mdeditor/mdeditor.php`
- `Markdownify.php`
- `Parsedown.php`
- `param/config.json`, `param/hooks.json`

