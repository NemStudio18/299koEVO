# 📊 Module Sondage — Mesurez l’avis de votre audience en temps réel

Diffusez des sondages attractifs, récoltez les votes en quelques clics et affichez des résultats clairs pour vos visiteurs. Le module Sondage vous permet de créer, activer et analyser vos questionnaires depuis l’admin sans dépendance externe.

## 🌟 Ce que le module apporte
- 🗳️ Votes à choix unique ou multiple selon votre configuration.
- 📋 Page publique dédiée (`/sondage`, `/sondage/[id]`) avec pourcentages en direct.
- ⚙️ Workflow complet : création, duplication, activation, suppression, verrouillage.
- 🔐 Restrictions optionnelles : connexion obligatoire, limite par empreinte.

## ⚙️ Vous personnalisez
- Position dans le menu, obligation de login, multi-vote.
- Contenu des questions, nombre d’options, ordre d’affichage.
- Templates front (`template/*.tpl`) pour adapter votre charte.

## 🔒 Pensé pour rester fiable
- Protection par empreinte (`allowMultipleVotes`) + option `requireLogin`.
- Suppressions et actions critiques protégées par token `[a:token]`.
- Résultats stockés dans `DATA_PLUGIN` : appliquez des ACL strictes.

## 🚀 Idéal pour
- Collecter des insights sur un site média ou associatif.
- Gérer des votes internes (intranet, communauté).
- Écouter rapidement votre audience sans outil tiers.

---

## Sondage — Documentation du module

### 1. Présentation
- **Slug** : `sondage`
- **Version** : 1.0
- **Entrées** : `/sondage`, `/admin/sondage`
- **Données** : `Sondage`, `SondageManager`, `SondageVote`

### 2. Fonctionnalités
- Liste des sondages actifs + page détail `/sondage/[id]`.
- Vote POST `/sondage/vote/[id]` (choix simple ou multiple).
- Interface admin : création/édition/suppression, duplication, activation.
- Affichage des résultats (pourcentage par option) dans les templates publics.

### 3. Configuration (`param/config.json`)

| Clé | Description | Défaut |
| --- | --- | --- |
| `priority` | Position menu | `2` |
| `allowMultipleVotes` | Autoriser plusieurs votes / empreinte | `0` |
| `requireLogin` | Vote réservé aux utilisateurs connectés | `0` |

### 4. Routes (`param/routes.php`)

| Méthode | URI | Action |
| --- | --- | --- |
| GET | `/sondage` | `SondageController#home` |
| GET | `/sondage/[id]` | `SondageController#read` |
| POST | `/sondage/vote/[id]` | `SondageController#vote` |
| GET | `/admin/sondage` | `SondageAdminController#list` |
| GET | `/admin/sondage/edit/[id]?` | `SondageAdminController#edit` |
| POST | `/admin/sondage/save` | `SondageAdminController#save` |
| GET | `/admin/sondage/delete/[id]/[token]` | `SondageAdminController#delete` |

### 5. Sécurité
- Votes limités via empreinte IP/session (pensez à ajouter un throttling si nécessaire).
- `requireLogin` s’appuie sur `Core\Auth\User` : sécurisez vos cookies (HttpOnly, SameSite).
- Token obligatoire pour toute suppression admin → forcer HTTPS.
- Protéger `DATA_PLUGIN` contre l’accès direct.

### 6. Tests rapides
- Créer un sondage multi-options → vérifier la page `/sondage`.
- Supprimer un sondage avec un token invalide → refus attendu.
- Activer `requireLogin` et tenter de voter déconnecté → redirection vers login/erreur.

### 7. Références
- `controllers/SondageController.php`
- `controllers/SondageAdminController.php`
- `entities/Sondage.php`, `SondageVote.php`, `SondageManager.php`
- `template/*.tpl`
- `param/config.json`, `param/routes.php`

