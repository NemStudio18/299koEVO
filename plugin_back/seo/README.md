# 🚀 Module SEO — Rendez votre site visible, partageable et mesurable

Centralisez tout ce qui touche à votre référencement : script d’analyse, vérification Search Console, icônes sociales, liens vers vos communautés. Le module SEO s’occupe d’injecter proprement les balises nécessaires et d’afficher vos réseaux où vous le souhaitez (footer flottant, navigation, etc.).

## 🌟 Ce que le module apporte
- 📊 Intégration Google Analytics (script `analytics.js`) + balise Search Console (`wt`).
- 🌐 Liste complète des réseaux sociaux (FontAwesome 6) pour diriger vos visiteurs.
- 🧱 Hooks multiples (`seoEndFrontHead`, `seoFooter`, `seoMainNavigation`, etc.) pour placer vos icônes où vous voulez.
- ⚙️ Interface admin unique pour gérer tracking, vérification et URLs.

## ⚙️ Vous personnalisez
- Positionnement des icônes (`float`, `footer`, navigation…).
- Ensemble des URLs sociales (Facebook, Mastodon, GitHub, etc.).
- ID Analytics et clé Search Console.

## 🔒 Pensé pour rester fiable
- Pas d’injection de script si `trackingId` est vide.
- Compatible CSP (pensez à autoriser `https://www.google-analytics.com`).
- Recommandation : stocker les IDs sensibles dans un coffre/config sécurisé.

## 🚀 Idéal pour
- Sites vitrines, blogs, e-commerces qui veulent une présence sociale cohérente.
- Projets nécessitant rapidement un suivi Analytics sans recoder la balise.
- Portails qui souhaitent pousser leurs réseaux dans le footer flottant.

---

## SEO — Documentation du module

### 1. Présentation
- **Slug** : `seo`
- **Version** : 2.0
- **Entrée admin** : `/admin/seo`
- **Description** : injection GA + vérification Search Console + icônes sociales.

### 2. Fonctionnalités
- Hook `seoEndFrontHead` : script Google Analytics + meta Search Console.
- Fonctions `seoFooter`, `seoEndFrontBody`, `seoMainNavigation` pour afficher les icônes.
- Formulaire admin unique pour tous les liens + keys.
- Icônes FontAwesome 6 (`fa-brands`).

### 3. Configuration (`param/config.json`)

| Clé | Description |
| --- | --- |
| `priority` | Position dans le menu |
| `position` | Mode d’affichage des icônes (`float`, `footer`, etc.) |
| `trackingId` | ID Google Analytics (UA-XXXXX…) |
| `wt` | Jeton Search Console (`google-site-verification`) |
| `facebook` … `tumblr` | URLs de chaque réseau social |

### 4. Routes (`param/routes.php`)

| Méthode | URI | Action |
| --- | --- | --- |
| GET | `/admin/seo` | `SEOAdminController#home` |
| POST | `/admin/seo/save` | `SEOAdminController#save` |

### 5. Hooks
- `endFrontHead` → `seoEndFrontHead`
- `footer` → `seoFooter`
- Autres (dans `seo.php`) : `seoEndFrontBody`, `seoMainNavigation`

### 6. Sécurité & conformité
- Script GA = Universal Analytics : envisager une migration GA4/`gtag.js`.
- Obtenir le consentement avant de charger Analytics (CMP recommandé).
- Vérifier que toutes les URLs sociales sont en `https://`.
- Stocker les identifiants sensibles dans `.env` si possible.

### 7. Tests rapides
- Définir un `trackingId` → vérifier le script dans `<head>`.
- Laisser `trackingId` vide → aucun script injecté.
- Ajouter une URL Mastodon → vérifier l’icône dans le footer flottant.

### 8. Références
- `seo/seo.php`
- `controllers/SEOAdminController.php`
- `template/*.tpl`, `template/public.css`
- `param/config.json`, `param/routes.php`, `param/hooks.json`

