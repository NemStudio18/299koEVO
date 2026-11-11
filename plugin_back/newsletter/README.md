# 📬 Module Newsletter — Captez vos visiteurs au bon moment

Déployez une modale d’abonnement élégante sur toutes vos pages, capturez les emails consentis et envoyez vos campagnes directement depuis 299Ko. Le module Newsletter s’occupe de l’inscription, de la désinscription sécurisée et du stockage des abonnés.

## 🌟 Ce que le module apporte
- 📨 Formulaire natif (page dédiée + modale `newsletterEndFrontBody`).
- 🧠 Gestion RGPD : message personnalisé, consentement explicite, lien de désinscription par token.
- 🗂️ Tableau de bord admin : liste, suppression, envoi manuel d’une campagne.
- 🔄 Requêtes AJAX légères pour s’intégrer à toutes vos pages.

## ⚙️ Vous personnalisez
- Titre de page, textes de confirmation/désinscription/doublon.
- Contenu de la modale (`template/modal.tpl`) et style CSS.
- Contenu des emails envoyés via l’admin (copier/coller HTML).

## 🔒 Pensé pour rester fiable
- Validation email côté serveur (`filter_var`) + token unique pour se désabonner.
- Données stockées dans `DATA_PLUGIN/newsletter/subscribers.json` (pensez à restreindre l’accès).
- Recommandation : activer Antispam ou throttling pour éviter les bots.

## 🚀 Idéal pour
- Sites vitrines, blogs, médias qui veulent fidéliser.
- Projets sans outil email externe mais qui souhaitent communiquer régulièrement.
- Installations cherchant un module « ready » avant d’intégrer un ESP.

---

## Newsletter — Documentation du module

### 1. Présentation
- **Slug** : `newsletter`
- **Version** : 1.0
- **Entrées** : `/newsletter`, `/admin/newsletter`
- **Stockage** : `DATA_PLUGIN/newsletter/subscribers.json` via `NewsletterManager`

### 2. Fonctionnalités
- Page `/newsletter` + modale sur toutes les pages (`newsletterEndFrontBody`).
- Abonnement/désabonnement via token, message personnalisable.
- Liste des abonnés dans l’admin, suppression individuelle, envoi d’email groupé.

### 3. Configuration (`param/config.json`)

| Clé | Description | Défaut |
| --- | --- | --- |
| `priority` | Position menu | `5` |
| `pageTitle` | Titre page publique | `Newsletter` |
| `subscriptionMessage` | Message succès | `Merci pour votre inscription...` |
| `unsubscriptionMessage` | Message désinscription | `Vous avez été désabonné...` |
| `alreadySubscribedMessage` | Message doublon | `Cette adresse email est déjà inscrite...` |

### 4. Routes (`param/routes.php`)
- Public : `/newsletter` (GET), `/newsletter/subscribe` (POST), `/newsletter/unsubscribe/[token]` (GET), `/newsletter/unsubscribe` (POST formulaire).
- Admin : `/admin/newsletter`, `/admin/newsletter/delete/[id]`, `/admin/newsletter/send`, `/admin/newsletter/params`, `/admin/newsletter/save-params`.

### 5. Hooks
- `endFrontBody` → `newsletterEndFrontBody` (injection de la modale).

### 6. Sécurité
- Emails stockés en clair → envisager hash/chiffrement selon vos contraintes RGPD.
- Token de désinscription unique (penser à vérifier l’entropie et la durée de vie).
- Ajouter Antispam/throttling sur `/newsletter/subscribe` pour éviter l’abus.
- Lors des envois, filtrer le HTML collé pour éviter l’injection d’éléments dangereux.

### 7. Tests rapides
- Soumettre deux fois la même adresse → recevoir `alreadySubscribedMessage`.
- Cliquer sur un lien de désinscription envoyé → l’adresse doit disparaître de la liste.
- Vérifier que la modale s’injecte sur une page publique.

### 8. Références
- `newsletter/newsletter.php`
- `controllers/NewsletterController.php`, `NewsletterAdminController.php`
- `entities/NewsletterSubscriber.php`, `NewsletterManager.php`
- `template/modal.tpl`
- `param/config.json`, `param/routes.php`, `param/hooks.json`

