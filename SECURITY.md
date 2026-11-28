# Politique de Sécurité / Security Policy

[🇫🇷 Français](#-politique-de-sécurité-français) • [🇬🇧 English](#-security-policy-english)

---

## 🇫🇷 Politique de Sécurité (Français)

La sécurité, l'intégrité des données et la confidentialité sont au cœur de la conception de **299koEVO**. Nous prenons très au sérieux tout signalement de faille ou de comportement inattendu.

### Versions prises en charge

Seules les versions les plus récentes de la branche principale bénéficient activement de correctifs de sécurité prioritaires :

| Version | Supportée | Statut |
| :--- | :---: | :--- |
| **0.4.x (actuelle)** | ✅ | Correctifs prioritaires & mises à jour actives |
| < 0.4.0 | ❌ | Obsolète (mise à niveau vivement recommandée) |

---

### Signaler une vulnérabilité

> [!IMPORTANT]
> **Ne divulguez jamais publiquement une vulnérabilité de sécurité non corrigée dans les Issues GitHub publiques.**

Si vous découvrez une faille potentielle (XSS, CSRF, contournement d'authentification, injection SQL/JSON, traversée de répertoires, etc.) :

1. **Contact confidentiel** :
   - Envoyez un email détaillé à : **`maximeblanc@flexcb.fr`** (ou via le formulaire privé du site officiel [299ko.flexcb.fr](https://299ko.flexcb.fr)).
   - Objet : `[SECURITY 299koEVO] Description brève de la vulnérabilité`
2. **Contenu recommandé du rapport** :
   - Description précise de la vulnérabilité et de son impact potentiel.
   - Étapes claires et reproductibles (Proof of Concept, capture d'écran ou extrait de requête).
   - Version testée du CMS et environnement (PHP, Apache/Nginx, type de stockage JSON/SQLite/MySQL).
   - Votre proposition de correctif si vous en avez une.

### Notre engagement de réponse

- **Accusé de réception** : Sous **48 heures ouvrées**.
- **Analyse & Validation** : Reproduction et évaluation du risque sous 3 à 5 jours.
- **Déploiement du patch** : Publication d'une version corrective sur GitHub et le Hub API Marketplace dans les plus brefs délais.
- **Remerciements** : Avec votre accord, vous serez crédité dans les notes de version de la publication corrective.

---

## 🇬🇧 Security Policy (English)

Security, data integrity, and privacy are core architectural pillars of **299koEVO**. We treat every security vulnerability report with the highest priority.

### Supported Versions

Only the latest active minor releases receive official security patches:

| Version | Supported | Status |
| :--- | :---: | :--- |
| **0.4.x (latest)** | ✅ | Priority patches & active support |
| < 0.4.0 | ❌ | Deprecated (upgrade strongly recommended) |

---

### Reporting a Vulnerability

> [!IMPORTANT]
> **Please do not report security vulnerabilities through public GitHub Issues.**

If you discover a potential vulnerability (XSS, CSRF, authentication bypass, SQL/JSON injection, path traversal, etc.):

1. **Private Disclosure**:
   - Send an email to: **`maximeblanc@flexcb.fr`** (or via the contact form on [299ko.flexcb.fr](https://299ko.flexcb.fr)).
   - Subject: `[SECURITY 299koEVO] Brief vulnerability description`
2. **Please include in your report**:
   - Clear description of the vulnerability and its potential impact.
   - Step-by-step reproduction guide (Proof of Concept, cURL command, or screenshots).
   - CMS version and environment (PHP version, Web Server, Storage driver).
   - Suggested remediation or patch if available.

### Response Timeline

- **Initial acknowledgment**: Within **48 business hours**.
- **Assessment**: Severity evaluation and reproduction within 3–5 business days.
- **Fix release**: Coordinated patch released via GitHub and the Marketplace Hub.
- **Credit**: With your permission, you will be credited in the release notes.

