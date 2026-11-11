# ✉️ Module Contact — Transformez chaque message en opportunité

Offrez une porte d’entrée impeccable à vos visiteurs. Le module Contact embarque un formulaire clair, des contenus d’introduction personnalisables, un suivi des adresses reçues et une sécurité renforcée (honeypot + Antispam natif). Vous choisissez qui reçoit les messages, gardez une base propre et répondez plus vite.

## 🌟 Ce que le module apporte
- 🪪 Formulaire complet (nom, prénom, email, message, consentement, champ masqué).
- 🔄 Double contenu éditable avant/après le formulaire pour rassurer vos visiteurs.
- 🧾 Historique des emails collectés avec possibilité de purge.
- 🔔 Notification immédiate à l’utilisateur sélectionné et copie optionnelle.

## ⚙️ Vous personnalisez
- Titre de page, contenus `content1`/`content2`.
- Texte d’acceptation RGPD, email de copie, destinataire (userMailId).
- Position dans le menu, mise en page via les templates.

## 🔒 Pensé pour rester fiable
- Intégration directe avec le module Antispam + honeypot `_name`.
- Validation serveur (`filter_var`, `strip_tags`, temporisation 2s).
- Actions sensibles protégées par token utilisateur.

## 🚀 Idéal pour
- Sites vitrines et portfolios.
- Associations et collectivités souhaitant centraliser les demandes.
- Modules complémentaires (dons, FAQ) qui nécessitent un support simple.

---

## Contact — Documentation du module

### 1. Présentation
- **Slug** : `contact`
- **Version** : 2.0
- **Entrées** : `/contact`, `/admin/contact`
- **Description** : formulaire public relié aux utilisateurs du CMS, stockage des emails.

### 2. Fonctionnalités
- Formulaire public avec Antispam (si activé) et honeypot.
- Gestion des contenus contextuels via l’éditeur choisi (TinyMCE, MDEditor).
- Sélection du destinataire + copie email.
- Écran admin listant les adresses collectées avec bouton « vider ».

### 3. Configuration (`param/config.json`)
| Clé | Rôle | Défaut |
| --- | --- | --- |
| `priority` | Position | `2` |
| `content1`, `content2` | Blocs HTML | `""` |
| `label` | Titre du module | `Contact` |
| `copy` | Email de copie | `""` |
| `acceptation` | Texte RGPD | `""` (rempli à l’install) |
| `userMailId` | ID utilisateur recevant le mail | `1` |

### 4. Routes (`param/routes.php`)
| Méthode | URI | Action |
| --- | --- | --- |
| GET | `/contact` | `ContactController#home` |
| POST | `/contact/send.html` | `ContactController#send` |
| GET | `/admin/contact` | `ContactAdminController#home` |
| POST | `/admin/contact/saveParams` | `ContactAdminController#saveParams` |
| POST | `/admin/contact/saveConfig` | `ContactAdminController#saveConfig` |
| GET | `/admin/contact/emptyMails/[token]` | `ContactAdminController#emptyMails` |

### 5. Sécurité
- Honeypot `_name`, temporisation `sleep(2)`, validation `filter_var`.
- Antispam branché automatiquement si actif.
- Actions admin protégées par `isAuthorized()` + token utilisateur.
- Logs lors de la purge des emails.

### 6. Tests rapides
- Soumettre un message sans consentement → vérifier le message d’erreur.
- Activer Antispam et tenter un mauvais CAPTCHA → erreur attendue.
- Vider la base email avec un token invalide → opération refusée.

### 7. Références fichiers
- `contact/contact.php`
- `controllers/ContactController.php`
- `controllers/ContactAdminController.php`
- `template/contact.tpl`, `template/admin-contact.tpl`
- `param/config.json`, `param/routes.php`

