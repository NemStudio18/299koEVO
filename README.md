<div align="center">

# 299koEVO

**Lightweight, database-optional PHP 8+ CMS — fast, modular, and privacy-first.**

[🇫🇷 Version française](README.fr.md) • [🌐 Official Site](https://299ko.flexcb.fr/) • [📦 Releases](https://github.com/NemStudio18/299koEVO/releases) • [🧩 Plugins Repository](https://github.com/NemStudio18/Plugins_299koEVO)

</div>

---

## ✨ Overview

**299koEVO** is a modern evolution of the flat-file / lightweight PHP CMS **299ko**.  
Designed for speed, simplicity, and full data ownership, it runs out of the box with **JSON storage** (or optional SQLite / MySQL) and requires zero external tracking or third-party SaaS dependencies.

## 🚀 Key Features

- **Flexible Storage**: Works out of the box with JSON files, with optional SQLite or MySQL support.
- **Modular Plugin Architecture**: Blog, Forum, Gallery, Polls (`sondage`), Contact, Newsletter, RSS, Search, SEO, and more.
- **Built-in Marketplace Hub**: Install and update plugins, themes, and core releases directly from the admin panel.
- **Modern Admin & Themes**: Responsive Light & Dark mode UI (`Declic` and `default` themes) powered by CSS design tokens (`--evo-*`).
- **Local Antispam (`299nobot`)**: Privacy-friendly form protection using server-signed HMAC-SHA256 challenges, adaptive browser SHA-256 Proof-of-Work, SQLite rate limiting, honeypot, and an accessible `<noscript>` fallback — **no Google reCAPTCHA or third-party tracking**.
- **Built-in Security**: CSRF tokens, role/group permissions (`AccessPolicyEvaluator`), session hardening, and HTML sanitization.

## 📋 Requirements

- **PHP 8.1+** (recommended: PHP 8.2 / 8.3 / 8.4)
- Extensions: `json`, `mbstring`, `gd` (or `imagick`), `zip`, `pdo_sqlite` (recommended for antispam rate-limiting / SQLite storage)
- Web server: Apache (`.htaccess` included) or Nginx

## 🛠️ Quick Installation

1. Download the latest archive from the [Releases page](https://github.com/NemStudio18/299koEVO/releases) (or clone this repository).
2. Upload the files to your web server root.
3. Open your site URL in a browser and follow the guided installer (`install.php`).
4. Remove `install.php` once installation is complete (the admin dashboard will remind you).

## 🧩 Ecosystem

- **Core CMS**: [`NemStudio18/299koEVO`](https://github.com/NemStudio18/299koEVO)
- **Official Plugins**: [`NemStudio18/Plugins_299koEVO`](https://github.com/NemStudio18/Plugins_299koEVO)
- **Themes / Skins**: [`NemStudio18/Skins_299koEVO`](https://github.com/NemStudio18/Skins_299koEVO)

## 📄 License

Distributed under the **GNU General Public License v3.0 (GPLv3)**.
