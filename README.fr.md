<div align="center">

# 299koEVO

**CMS PHP 8+ léger, modulaire et respectueux de la vie privée — sans base de données obligatoire.**

[🇬🇧 English version](README.md) • [🌐 Site officiel](https://299ko.flexcb.fr/) • [📦 Releases](https://github.com/NemStudio18/299koEVO/releases) • [🧩 Dépôt des plugins](https://github.com/NemStudio18/Plugins_299koEVO)

</div>

---

## ✨ Présentation

**299koEVO** est une évolution moderne du CMS léger **299ko**.  
Conçu pour la rapidité, la simplicité et la maîtrise totale de vos données, il fonctionne immédiatement en **stockage JSON** (avec support optionnel SQLite / MySQL) et sans aucune dépendance ni pistage tiers.

## 🚀 Fonctionnalités principales

- **Stockage flexible** : fonctionne par défaut sur fichiers JSON, avec prise en charge optionnelle de SQLite et MySQL.
- **Architecture modulaire par plugins** : Blog, Forum, Galerie, Sondages, Contact, Newsletter, RSS, Recherche, SEO, etc.
- **Marketplace Hub intégré** : installez et mettez à jour vos plugins, thèmes et le cœur du CMS directement depuis l'administration.
- **Interface moderne & Thèmes** : prise en charge native du mode Clair / Sombre (`Declic` et `default`) avec harmonisation visuelle automatique.
- **Antispam local (`299nobot`)** : protection des formulaires par challenge signé HMAC-SHA256 côté serveur, preuve de travail (PoW) SHA-256 adaptative dans le navigateur, rate-limiting SQLite, honeypot et fallback accessible `<noscript>` — **sans Google reCAPTCHA ni suivi tiers**.
- **Sécurité intégrée** : jetons CSRF, gestion fine des rôles et permissions (`AccessPolicyEvaluator`), durcissement des sessions et filtrage HTML.

## 📋 Prérequis

- **PHP 8.1+** (recommandé : PHP 8.2 / 8.3 / 8.4)
- Extensions : `json`, `mbstring`, `gd` (ou `imagick`), `zip`, `pdo_sqlite` (recommandée pour le rate-limiting antispam / stockage SQLite)
- Serveur web : Apache (`.htaccess` fourni) ou Nginx

## 🛠️ Installation rapide

1. Téléchargez la dernière archive depuis la [page des Releases](https://github.com/NemStudio18/299koEVO/releases) (ou clonez ce dépôt).
2. Déposez les fichiers à la racine de votre hébergement web.
3. Ouvrez l'URL de votre site dans un navigateur et suivez l'assistant d'installation (`install.php`).
4. Supprimez `install.php` une fois l'installation terminée (un rappel s'affiche dans l'administration).

## 🧩 Écosystème

- **Cœur du CMS** : [`NemStudio18/299koEVO`](https://github.com/NemStudio18/299koEVO)
- **Plugins officiels** : [`NemStudio18/Plugins_299koEVO`](https://github.com/NemStudio18/Plugins_299koEVO)
- **Thèmes / Skins** : [`NemStudio18/Skins_299koEVO`](https://github.com/NemStudio18/Skins_299koEVO)

## 📄 Licence

Distribué sous licence **GNU General Public License v3.0 (GPLv3)**.

