# ❤️ Module Dons — Faites grandir vos projets en ligne

Transformez votre site 299Ko en plateforme de soutien instantanée. Avec le module Dons, proposez PayPal Checkout et Stripe Checkout dans la même interface, affichez votre objectif en temps réel, rassurez vos donateurs et pilotez les contributions depuis un back-office dédié.

## 🌟 Ce que le module apporte
- 💳 Double passerelle PayPal + Stripe prête à l’emploi (modes test/live).
- 📊 Barre de progression dynamique (objectif vs montants collectés).
- 🧾 Historique détaillé des dons (montant, message, anonymat, statut).
- 📈 API de statistiques pour alimenter vos dashboards.
- 🔔 Notifications frontend claires (succès, annulation, erreurs).

## ⚙️ Vous personnalisez
- Titre/page de présentation, description, objectif financier.
- Modes sandbox/live pour PayPal et Stripe + clés API.
- Messages affichés, CSS du template `donate.tpl`.

## 🔒 Pensé pour rester fiable
- Validation serveur stricte (montant minimum, sanitization).
- Sauvegarde de l’état `pending/completed/failed` pour chaque passerelle.
- Mise à jour automatique de `currentAmount` après capture réussie.
- Actions admin sécurisées (autorisation + token).

## 🚀 Idéal pour
- Associations, campagnes solidaires, collectes ponctuelles.
- Sites médias/fans cherchant un soutien récurrent.
- Tout projet qui veut ajouter un bouton « Soutenez-nous » sans coder.

---

## Dons — Documentation du module

### 1. Présentation
- **Slug** : `dons`
- **Version** : 1.0
- **Entrées** : `/dons`, `/admin/dons`
- **Données** : `Don`, `DonManager` (JSON dans `DATA_PLUGIN/dons/`)

### 2. Fonctionnalités
- Page publique avec description, barre de progression, boutons PayPal/Stripe.
- Intégration PayPal : création/capture API REST.
- Intégration Stripe : sessions Checkout (success/cancel + vérification).
- Admin : liste, stats JSON, paramétrage complet (targets + clés).

### 3. Configuration (`param/config.json`)
| Clé | Description | Défaut |
| --- | --- | --- |
| `pageTitle` | titre de page | `Faire un don` |
| `description` | texte descriptif | message exemple |
| `targetAmount` | objectif | `10000` |
| `currentAmount` | cache du total | `0` (MAJ auto) |
| `paypalClientId`, `paypalSecret`, `paypalMode` | PayPal API | vides / `sandbox` |
| `stripePublishableKey`, `stripeSecretKey`, `stripeMode` | Stripe API | vides / `test` |

### 4. Routes principales
- **Public** : `/dons`, `/dons/paypal/create`, `/dons/paypal/capture`, `/dons/stripe/create`, `/dons/stripe/success`, `/dons/stripe/cancel`
- **Admin** : `/admin/dons`, `/admin/dons/stats`, `/admin/dons/params`, `/admin/dons/save-params`

### 5. Sécurité
- Autorisations admin (`isAuthorized()` + token) sur toutes les routes privées.
- `filter_var`/`floatval` sur les montants et champs texte.
- Recommandé : stocker les clés API hors repo (env/secret manager).
- Absence de webhooks : envisager un endpoint complémentaire pour confirmer côté serveur.
- Les logs contiennent potentiellement IP/User-Agent (penser RGPD).

### 6. Tests rapides
- Mode sandbox/test : effectuer un don PayPal puis Stripe → statut `completed`.
- Forcer un montant inférieur au minimum → message `dons.invalid-amount`.
- Appeler `/admin/dons/stats` sans authentification → vérifier l’erreur.

### 7. Références fichiers
- `controllers/DonController.php`
- `controllers/DonAdminController.php`
- `entities/Don.php`, `entities/DonManager.php`
- `template/donate.tpl`, `template/admin-list.tpl`, `template/param.tpl`
- `param/config.json`, `param/routes.php`, `param/hooks.json`

