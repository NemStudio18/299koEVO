# ❓ Module FAQ — Donnez instantanément les réponses que vos utilisateurs cherchent

Le module FAQ vous permet d’afficher vos questions fréquentes de manière claire, triée, et interactive. Vos visiteurs peuvent voter pour indiquer si une réponse leur a été utile, et même envoyer directement leurs propres questions.

Simple à installer. Facile à gérer. Totalement intégré au CMS.

## 🌟 Ce que le module apporte

- 🎯 Organisation par catégories : vos questions sont regroupées pour une navigation fluide.
- 👍 Votes d’utilité : un bouton « Utile » améliore la pertinence de vos réponses.
- 🛡️ Anti-fraude intégré : le système empêche les votes abusifs.
- ✉️ Réception de nouvelles questions : un formulaire simple et protégé contre le spam.
- 🛠️ Back-office complet : activez, modifiez, triez et créez vos questions en quelques clics.
- 🔔 Notification instantanée : vous recevez un email dès qu’une question est soumise.

## ⚙️ C’est personnalisable

Vous définissez :

- le titre de la page FAQ ;
- l’ordre d’apparition dans le menu ;
- les catégories disponibles.

## 🔒 Pensé pour rester fiable

Le module intègre un système de protection anti-spam, gère les droits d’accès administrateur et suit les bonnes pratiques email pour assurer la délivrabilité.

## 🚀 Idéal pour…

- Les sites vitrines
- Les boutiques
- Les projets nécessitant un support simplifié
- Toute installation 299Ko cherchant un module FAQ léger et efficace

---

## FAQ — Documentation du module

### 1. Présentation

- **Slug** : `faq`
- **Version** : 1.0
- **Entrées** : `/faq`, `/admin/faq`
- **Données** : `FaqQuestion`, `FaqManager`
- **Description** : gestion complète des questions/réponses avec votes d’utilité et formulaire public.

### 2. Fonctionnalités

- Listing par catégories avec ancres et compteur de votes.
- Bouton « Utile » avec protection fingerprint (`VoteProtection`).
- Formulaire public avec honeypot `_name` + intégration Antispam.
- Actions admin : activer, créer, éditer, supprimer, trier (champ `order`).
- Email de notification dès qu’une question est soumise.

### 3. Configuration (`param/config.json`)

| Clé | Rôle | Défaut |
| --- | --- | --- |
| `priority` | Position du module dans le menu | `5` |
| `pageTitle` | Titre de la page `/faq` | `Questions fréquemment posées` |
| `categories` | Catégories disponibles | `[]` |

### 4. Routes

| Méthode | URI | Action |
| --- | --- | --- |
| GET | `/faq` | `FaqController#home` |
| POST | `/faq/vote/[id]` | `FaqController#vote` |
| POST | `/faq/ask` | `FaqController#ask` |
| GET | `/admin/faq` | `FaqAdminController#list` |
| GET | `/admin/faq/edit/[id]?` | `FaqAdminController#edit` |
| POST | `/admin/faq/save` | `FaqAdminController#save` |
| POST | `/admin/faq/delete/[id]` | `FaqAdminController#delete` |

### 5. Sécurité

- Empreinte unique par question (VoteProtection), purge recommandée pour la vie privée.
- Validation stricte du formulaire `ask`, possibilité d’ajouter un throttling IP.
- Actions admin réservées aux utilisateurs autorisés.
- Email envoyé via `mail()` : configurer SPF/DKIM pour une meilleure délivrabilité.

### 6. Tests rapides

- Question vide → `faq.empty-question`.
- Double vote → `faq.already-voted`.
- Désactiver Antispam → vérifier que le formulaire reste fonctionnel.

### 7. Références fichiers

- `controllers/FaqController.php`
- `controllers/FaqAdminController.php`
- `entities/FaqQuestion.php`
- `entities/FaqManager.php`
- `template/list.tpl`, `template/admin-*.tpl`
-# ❓ Module FAQ — Donnez instantanément les réponses que vos utilisateurs cherchent
-
-Le module FAQ vous permet d’afficher vos questions fréquentes de manière claire, triée, et interactive. Vos visiteurs peuvent voter pour indiquer si une réponse leur a été utile, et même envoyer directement leurs propres questions.
-
-Simple à installer. Facile à gérer. Totalement intégré au CMS.
-
-## 🌟 Ce que le module apporte
-
-🎯 Organisation par catégories : vos questions sont regroupées pour une navigation fluide.
-
-👍 Votes d’utilité : un bouton « Utile » permet d’améliorer la pertinence de vos réponses.
-
-🛡️ Anti-fraude intégré : le système empêche les votes abusifs.
-
-✉️ Réception de nouvelles questions : un formulaire simple et protégé contre le spam.
-
-🛠️ Back-office complet : activez, modifiez, triez et créez vos questions en quelques clics.
-
-🔔 Notification instantanée : vous recevez un email dès qu’une question est soumise.
-
-## ⚙️ C’est personnalisable
-
-Vous pouvez définir :
-
-le titre de la page FAQ
-
-l’ordre d’apparition dans le menu
-
-les catégories disponibles
-
-## 🔒 Pensé pour rester fiable
-
-Le module intègre un système de protection anti-spam, gère les droits d’accès administrateur et suit les bonnes pratiques email pour assurer la délivrabilité.
-
-## 🚀 Idéal pour…
-
-Les sites vitrines
-
-Les boutiques
-
-Les projets nécessitant un support simplifié
-
-Toute installation 299Ko cherchant un module FAQ léger et efficace
-
----
-
-## FAQ — Documentation du module
-
-### 1. Présentation
-
-Le module FAQ fournit une gestion complète des questions/réponses : affichage par catégorie, votes d’utilité, réception de nouvelles questions et interface d’administration.
-
-Slug : faq
-
-Version : 1.0
-
-Entrées : /faq, /admin/faq
-
-Données : FaqQuestion, FaqManager
-
-### 2. Fonctionnalités
-
-Listing par catégories avec ancre et compteur de votes
-
-Bouton « Utile » avec protection fingerprint
-
-Formulaire public avec honeypot + antispam
-
-Actions admin : activer, créer, éditer, supprimer, trier (order)
-
-Email de notification lors d’une question envoyée
-
-### 3. Configuration (param/config.json)
-
-| Clé |	Rôle |	Défaut |
-| `priority` |	Position du module dans le menu |	5 |
-| `pageTitle` |	Titre de la page /faq |	Questions fréquemment posées |
-| `categories` |	Catégories disponibles |	[] |
-
-### 4. Routes
-
-| Méthode |	URI |	Action |
-| GET |	/faq |	FaqController#home |
-| POST |	/faq/vote/[id] |	FaqController#vote |
-| POST |	/faq/ask |	FaqController#ask |
-| GET |	/admin/faq |	FaqAdminController#list |
-| GET |	/admin/faq/edit/[id]? |	FaqAdminController#edit |
-| POST |	/admin/faq/save |	FaqAdminController#save |
-| POST |	/admin/faq/delete/[id] |	FaqAdminController#delete |
-
-### 5. Sécurité
-
-Fingerprint unique par question (VoteProtection)
-
-Purge conseillée des empreintes pour vie privée
-
-Validation stricte du formulaire ask
-
-Possibilité d’ajouter un throttling IP
-
-Toutes les actions admin requièrent une autorisation
-
-Envoi email basique (mail()), prévoir SPF/DKIM
-
-### 6. Tests rapides
-
-Question vide → faq.empty-question
-
-Double vote → faq.already-voted
-
-Désactivation antispam → formulaire fonctionnel
-
-### 7. Références fichiers
-
-controllers/FaqController.php
-
-controllers/FaqAdminController.php
-
-entities/FaqQuestion.php
-
-entities/FaqManager.php
-
-Templates : template/list.tpl, template/admin-*.tpl

