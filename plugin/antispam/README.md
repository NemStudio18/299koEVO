# 🛡️ Module Antispam — Bloquez les robots sans frustrer vos visiteurs

Le module Antispam ajoute un rempart intelligent à chaque formulaire 299Ko : contact, dons, FAQ, livre d’or… Choisissez votre expérience (question/réponse texte, sélection d’icône ou Google reCAPTCHA) et changez de stratégie en un clic. L’utilisateur bénéficie d’un CAPTCHA clair, vous gardez des conversations propres.

## 🌟 Ce que le module apporte
- 🎭 Trois protections complémentaires : texte personnalisé, puzzle d’icônes, reCAPTCHA officiel.
- 🔌 Service centralisé : un seul `new antispam()` pour sécuriser tous vos formulaires.
- ⚡ Expérience homogène : le champ s’intègre à vos templates existants sans retouche.
- 🔔 Messages clairs : l’utilisateur comprend immédiatement quoi faire en cas d’erreur.

## ⚙️ Vous personnalisez
- Type de CAPTCHA actif (`useText`, `useIcon`, `useRecaptcha`).
- Libellé affiché dans l’administration.
- Clés reCAPTCHA publiques/privées.

## 🔒 Pensé pour rester fiable
- Nettoyage serveur de chaque paramètre, validation stricte des clés Google.
- Compatible HTTPS + CSP restrictives (scripts Google).
- Fonctionne avec tous les modules consommateurs via l’autoloader FlexyLoad.

## 🚀 Idéal pour
- Sites vitrines soumis aux spams.
- Formulaires transactionnels (dons, newsletter) qui exigent confiance.
- Projet 299Ko nécessitant une bascule CAPTCHA rapide.

---

## Antispam — Documentation du module

### 1. Présentation
- **Slug** : `antispam`
- **Version** : 2.0
- **Entrée admin** : `/admin/antispam`
- **Description** : service CAPTCHA multi-moteur accessible depuis tous les plugins.

### 2. Fonctionnalités
- Sélection du moteur texte / icône / reCAPTCHA.
- Classe instanciable `antispam` avec méthodes `show()` et `isValid()`.
- Templates d’administration et d’affichage prêts à l’emploi.

### 3. Configuration (`param/config.json`)
| Clé | Rôle | Défaut |
| --- | --- | --- |
| `priority` | Position dans le menu | `2` |
| `label` | Nom affiché | `Antispam` |
| `type` | CAPTCHA actif | `useText` |
| `recaptchaPublicKey` | Clé site Google | `""` |
| `recaptchaSecretKey` | Clé secrète Google | `""` |

### 4. Routes (`param/routes.php`)
| Méthode | URI | Action |
| --- | --- | --- |
| GET | `/admin/antispam` | `AntispamAdminController#home` |
| POST | `/admin/antispam/saveconf` | `AntispamAdminController#save` |

### 5. Sécurité
- `trim`/`filter_input` sur toutes les entrées.
- Obligation d’avoir les deux clés reCAPTCHA avant activation.
- Recommandations : HTTPS, entêtes CSP (Google), audit régulier des questions texte.

### 6. Tests rapides
- Basculer chaque mode depuis l’admin et vérifier le rendu front.
- Activer reCAPTCHA sans clés → message d’erreur attendu.
- Soumettre un formulaire consommateur avec mauvaise réponse → `antispam.invalid-captcha`.

### 7. Références fichiers
- `Plugins_299koEVO/antispam/antispam.php`
- `controllers/AntispamAdminController.php`
- `lib/Antispam*.php`
- `template/config.tpl`, `template/captcha-icon.tpl`
- `param/config.json`, `param/routes.php`

