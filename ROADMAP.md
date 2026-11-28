# Feuille de Route / Roadmap — 299koEVO

[🇫🇷 Français](#-feuille-de-route-2026---2027) • [🇬🇧 English](#-roadmap-2026---2027)

---

## 🇫🇷 Feuille de Route (2026 - 2027)

### 🧱 1. Le Socle Acquis du CMS (Ne pas réinventer ce qui existe)

| Composant | État & Rôle |
| :--- | :--- |
| **Multi-stockage JSON / SQLite / MySQL** | ✅ **Opérationnel** avec bascule transparente (`StorageManager`). |
| **`JsonActiveRecord`** | 🔄 **Actif**, à renforcer (transactions, verrous concurrents). |
| **Sécurité CSRF** | ✅ **Déployée à 100%** sur l'ensemble des formulaires et contrôleurs POST de l'écosystème. |
| **Antispam souverain (`299nobot`)** | ✅ **Opérationnel** (PoW navigateur, HMAC-SHA256, zéro reCAPTCHA). |
| **Politiques d'accès ABAC** | ✅ **Opérationnel** (`AccessPolicyEvaluator`, `ConditionContext`). |
| **Gestionnaire d'événements (`HookManager`)**| ✅ **Opérationnel** (`addHook`, `callHook`, `hooks.json`). |
| **Gestionnaire d'API (`ApiManager`)** | ✅ **Opérationnel** (`Core\Api\ApiManager`, routes API). |
| **Passerelle de Paiement centrale** | ✅ **Opérationnelle** (`PaymentDriverInterface`, hub central `paiement`). |
| **Marketplace Hub intégré** | ✅ **Opérationnel** (catalogue officiel et installation 1-clic). |
| **Mode Test & Bac à sable (`AdminTestMode`)** | ✅ **Opérationnel** (simulation en mémoire de session). |
| **Traductions & Localisation** | ✅ **Opérationnel** (fichiers `.ini` modulaires). |

---

### 🏛️ 2. L'Architecture Cible en 5 Piliers

```mermaid
flowchart TD
    CORE["⚡ NOYAU DU CMS (Core)\nStockage · Sécurité · Hooks · API · Permissions · Routage"]
    INFRA["🛠️ INFRASTRUCTURE\nTaskScheduler · BackupManager · SystemMonitor · DataInspector"]
    DEV["💻 DÉVELOPPEURS\nPluginInspector · PluginSandbox · Marketplace · Workflow"]
    FEATURES["🧩 FONCTIONNALITÉS\nBlog · Commerce · Social · Agenda · POI & Territoire"]
    EXP["🎨 EXPÉRIENCE\nAdministration · Thèmes · Traductions · MediaManager"]

    CORE --> INFRA
    CORE --> DEV
    CORE --> FEATURES
    CORE --> EXP
```

---

### 🚀 3. Les Trois Chantiers Prioritaires

#### 1. `PluginInspector & Pipeline Communautaire` (Priorité Haute) — Sécurité, Audit & Soumission d'Extensions
- [x] **Moteur d'audit statique automatique** : Analyse des ZIP soumis (détection AST de fonctions proscrites `eval`, `exec`, `shell_exec`, fuites de chemins `../..`, vérification CSRF et validation syntaxique `php -l`).
- [x] **Portail Web de Soumission Développeurs** : Formulaire public pour soumettre un plugin ou un thème avec validation d'intégrité en temps réel.
- [x] **Gestion des Mises à Jour & Jeton Auteur** : Authentification par *Developer Secret Key* garantissant que seul l'auteur d'origine peut pousser une version supérieure.
- [x] **Automatisation GitHub & Arbitrage NemStudio18** : Création automatique d'une Pull Request isolée sur le dépôt dédié `NemStudio18/Community_Plugins_299koEVO` avec rapport d'audit, laissant le contrôle final du merge au mainteneur unique.
- [x] **Distinction Claire dans la Marketplace** :
  - Métadonnées API avec canaux distincts (`official` vs `community` vs `exclusive`).
  - Badges visuels explicites dans l'administration (`🛡️ Officiel 299ko` vs `👥 Communauté`).
  - Modale de confirmation transparente informant l'utilisateur à l'installation d'un module tiers.
- [x] Graphe des dépendances, détection des hooks manquants et rapport de santé pré-installation.

#### 2. `TaskScheduler` (Priorité Haute) — Autonomie d'exécution en arrière-plan
- [x] Ordonnanceur natif pour tâches périodiques et exécutions différées (`TaskScheduler`).
- [x] File d'attente persistante avec relance automatique après échec.
- [x] Verrouillage anti-concurrence contre les exécutions simultanées.
- [x] Historique et métriques d'exécution.
- [x] Déblocage des capacités asynchrones pour `newsletter`, `LocalFeed`, `BackupManager` et `Workflow`.

#### 3. `DataInspector` (Priorité Haute) — Cohérence des données multi-moteurs
- [x] Détection des entités orphelines et références croisées cassées (liens de menus, catégories et commentaires de blog).
- [x] Nettoyage des doublons de clés primaires et validation de syntaxe de 100% des fichiers JSON.
- [x] Comparateur d'intégrité croisée multi-stockage (Flat-File JSON vs base SQL SQLite/MySQL).
- [x] Outil d'auto-réparation en 1 clic intégré dans la gestion du stockage.

---

### 🎯 4. Modules d'Infrastructure & Évolutions Complémentaires

#### Infrastructure & Système
- [x] **`SystemMonitor`** : Dashboard technique en temps réel (`/admin/monitor`) mesurant la latence du Core en ms, la mémoire vive RAM PHP, le chronométrage unitaire des Hooks et le volume d'opérations d'I/O (JSON & SQL) avec rafraîchissement direct Fetch.
- [x] **`BackupManager` & `SiteExporter`** : Sauvegarde 1-clic et migration intégrale (code + data + médias) en ZIP sécurisé, snapshots automatiques pré-mise à jour (Core & Marketplace) et restauration d'urgence.
- [x] **`MediaManager`** : Médiathèque transversale avec détection des fichiers inutilisés (`MediaInspector`), conversion WebP par lot et gestion des droits.

#### Outils Développeurs & Automatisation
- [x] **`Workflow`** : Orchestrateur d'événements sans code (triggers, conditions logiques, actions Webhooks HTTP, e-mails, logs et exécution asynchrone différée via `TaskScheduler`).
- [ ] **`TranslationManager`** : Interface visuelle de comparaison multilingue avec détection des chaînes manquantes.
- [ ] **`PluginSandbox`** : Environnement de test éphémère pour exécuter un plugin sans impacter le site réel.

#### Évolutions des Extensions & Plugins
- [x] **`Design Tokens Universels (--evo-*)`** : Migration des feuilles de style `public.css` de l'ensemble des plugins vers les variables CSS standardisées (`--evo-bg`, `--evo-card-bg`, `--evo-text`, `--evo-border`, `--evo-accent`) pour garantir une adaptation caméléon native et automatique sur 100% des thèmes sombres et clairs.
- [x] **`Blog` (Gestion temporelle et éditoriale fine & Modernisation PSR-4)** :
  - **Séparation des dates** : distinction claire entre date de création (`created_at`), date de parution/publication effective (`published_at`) et date de dernière modification (`updated_at`).
  - **Précision horaire** : passage du format jour simple (`Y-m-d`) à l'horodatage complet (`Y-m-d H:i:s`) avec heure et minute.
  - **Publication programmée / différée** : l'article reste masqué aux visiteurs jusqu'à ce que `published_at <= NOW()`.
  - **Affichage éditorial conditionnel** : mention publique *« Publié le [date] à [heure] »* et, en cas de révision ultérieure, *« Mis à jour le [date] à [heure] »*.
  - **Suppression intégrale du legacy** : éradication du dossier `entities/` et suppression de tous les alias pour un modèle 100% PSR-4 propre.

---

## 🇬🇧 Roadmap (2026 - 2027)

### 🧱 1. Established Foundation (Building Upon Existing Assets)

- **Multi-Storage Engine**: JSON flat-file, SQLite, MySQL/MariaDB ready.
- **CSRF Protection**: 100% enforced across all public and exclusive POST forms and controllers.
- **Sovereign Antispam (`299nobot`)**: Browser PoW + HMAC-SHA256 challenges active.
- **ABAC Engine**: `AccessPolicyEvaluator` and `ConditionContext` operational.
- **Core Event Bus**: `HookManager` and event hooks active.
- **Payment Hub**: Centralized `PaymentDriverInterface` abstraction.

### 🚀 2. Top Three Priority Pillars

1. **`PluginInspector & Community Pipeline` (High Priority)**: Automated static security screening for extension ZIPs, developer submission portal, author update tokens, automated GitHub PR workflow on `Community_Plugins_299koEVO` for lead maintainer review, and marketplace badge distinctions (`Official` vs `Community`).
2. **`TaskScheduler` (High Priority)**: Background job queue, recurring tasks, concurrency locks, and retry mechanism.
3. **`DataInspector` (High Priority)**: Cross-storage consistency checks (JSON vs SQL), broken reference and orphan detection, and 1-click auto-repair.

### 🎯 3. Next Milestones

- [x] **`SystemMonitor`**: Sovereign real-time telemetry dashboard (`/admin/monitor`): latency in ms, peak PHP RAM, unit hook profiler, and JSON/SQL I/O counter with live Fetch auto-refresh.
- [x] **`BackupManager` & `SiteExporter`**: 1-click snapshot export and seamless migration between servers.
- **`MediaManager`**: Centralized asset library with orphan detection and WebP processing.
- [x] **`Workflow`**: Sovereign no-code event orchestrator (triggers, conditions, HTTP webhooks, emails, and TaskScheduler async jobs).
- **`TranslationManager`**: Graphical dictionary diff and missing keys detector.
- [x] **`Universal CSS Tokens (--evo-*)`**: Modernize all plugin stylesheets to use standardized CSS custom properties (`--evo-bg`, `--evo-card-bg`, `--evo-text`, `--evo-border`, `--evo-accent`) ensuring native chameleon adaptation on 100% of dark and light themes without requiring theme-specific CSS.
- [x] **`Blog` (Advanced Editorial Scheduling & Timestamps)**: Distinct creation date (`created_at`), scheduled publication date (`published_at`), and revision date (`updated_at`) with full hour/minute precision (`Y-m-d H:i:s`), plus conditional "Updated on..." display.
