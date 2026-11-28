# Guide Développeur 299koEVO — APIs Cœur (Phases 1 à 4)

Ce guide présente les APIs transversales ajoutées au cœur de **299koEVO** dans `common/Core/`, utilisables par les plugins et les thèmes sans dépendances externes (PHP 8.4+, JSON / SQLite / MySQL, RBAC/ABAC).

---

## 1. Types de contenus & Champs personnalisés (`Core\Content`)

### Déclarer un type de contenu depuis un plugin

Écoutez le hook `content:register-types` (ou appelez `ContentTypeManager::register()` au démarrage du plugin) :

```php
use Core\Content\ContentTypeManager;

ContentTypeManager::register([
    'id' => 'association',
    'label' => 'Association',
    'description' => 'Annuaire des associations locales',
    'target' => 'custom', // 'custom' ou 'page' pour enrichir les pages existantes
    'translatable' => true,
    'has_blocks' => true,
    'api_exposed_fields' => ['titre', 'email_contact'], // liste blanche facultative en API REST
    'permissions' => [
        'view' => 'public',
        'create' => 'content.create',
        'edit' => 'content.edit',
        'edit_others' => 'content.edit_others',
        'delete' => 'content.delete',
        'publish' => 'content.publish',
    ],
    'fields' => [
        [
            'name' => 'titre',
            'label' => 'Nom de l’association',
            'type' => 'text',
            'required' => true,
            'order' => 1,
            'translatable' => true,
            'rules' => ['minLength' => 3, 'maxLength' => 150],
        ],
        [
            'name' => 'email_contact',
            'label' => 'Email de contact',
            'type' => 'email',
            'required' => false,
            'order' => 2,
            'translatable' => false,
            'read_permission' => 'content.view',
            'write_permission' => 'content.edit',
            'api_readable' => true,
            'api_writable' => true,
        ],
    ],
]);
```

### Interface d'administration & persistance JSON (`/admin/content`)

- Le module cœur `content` (`Core\Content\Controllers\ContentAdminController`) fournit le back-office complet sous `/admin/content` : création/édition/suppression des types personnalisés, constructeur dynamique des 12 types de champs, et CRUD complet des entrées avec workflow, révisions, multilingue et constructeur visuel de blocs.
- Les types créés en back-office sont persistés dans `DATA_CORE_CONTENT/custom_types.json` via `saveCustomType()` / `deleteCustomType()` et rechargés automatiquement au démarrage de `ContentTypeManager` (les types enregistrés par code PHP coexistent sans être écrasés).

### Les 12 types de champs natifs (`FieldManager`)

`text`, `textarea`, `integer`, `decimal`, `date`, `datetime`, `boolean`, `email`, `url`, `media`, `select`, `geo`.

Un plugin peut enregistrer un type de champ supplémentaire via `FieldManager::getInstance()->registerType(string $type, callable $validator, ?callable $renderer = null)`.

### CRUD, filtrage et rattachement aux pages

```php
$ctm = \Core\Content\ContentTypeManager::getInstance();

// Création (retourne ['success' => bool, 'entry' => ?ContentEntry, 'errors' => array])
$res = $ctm->createEntry('association', [
    'title' => 'Club Cycliste',
    'status' => 'draft', // 'draft' | 'pending' | 'published' | 'archived'
    'fields' => ['titre' => 'Club Cycliste'],
], $user);

// Recherche & comptage (respecte les permissions et l'état publié)
$entries = $ctm->findEntries('association', ['status' => 'published'], ['created_at' => 'desc'], 20);
$count   = $ctm->countEntries('association', ['status' => 'published']);

// Champs attachés à une page existante (cible 'page')
$ctm->attachFieldsToType('page', [...]);
$ctm->saveTargetFields('page', $pageId, $fieldValues, $user);
$values = $ctm->getTargetFields('page', $pageId, $user);
```

### Hooks Phase 1

- `content:register-types` (paramètre : `ContentTypeManager`)
- `content:before-save` (`['type' => ContentTypeDefinition, 'entry' => ContentEntry, 'isNew' => bool]`)
- `content:after-save` (`['type' => ContentTypeDefinition, 'entry' => ContentEntry]`)
- `content:before-delete` (`['type' => ContentTypeDefinition, 'entry' => ContentEntry]`)
- `content:after-delete` (`['type' => ContentTypeDefinition, 'id' => int|string]`)

---

## 2. Moteur de blocs (`Core\Blocks`)

Le cœur fournit `Core\Blocks\BlockManager` (namespace distinct de `HomeBuilder\Entities\BlockManager` pour éviter toute collision) et **9 blocs natifs** :

`text`, `image`, `gallery`, `video`, `button`, `columns`, `quote`, `list`, `plugin_content`.

### Enregistrer un bloc personnalisé

```php
use Core\Blocks\BlockManager;

BlockManager::register([
    'type' => 'meteo_card',
    'label' => 'Carte Météo',
    'icon' => 'fa-solid fa-cloud-sun',
    'category' => 'dynamic',
    'default_data' => ['city' => 'Paris'],
    'validator' => static function (array $data): array {
        $city = trim(strip_tags((string) ($data['city'] ?? '')));
        if ($city === '') {
            return ['valid' => false, 'data' => $data, 'errors' => ['Ville requise.']];
        }
        return ['valid' => true, 'data' => ['city' => $city], 'errors' => []];
    },
    'renderer' => static fn(array $data, array $context = []): string
        => '<div class="meteo-card">' . htmlspecialchars($data['city'], ENT_QUOTES, 'UTF-8') . '</div>',
]);
```

### Surcharge dans un thème, éditeur visuel & rendu `plugin_content`

- **Constructeur visuel typé** : `admin/templates/page/edit.tpl` et `admin/templates/content/edit_entry.tpl` utilisent tous deux un éditeur de blocs visuels à champs typés (`text`, `image`, `gallery`, `video`, `button`, `columns`, `quote`, `list`, `plugin_content`, avec repli JSON uniquement pour les blocs personnalisés tiers).
- **Surcharge thème** : créez `theme/<MonTheme>/template/blocks/<type>.tpl` ; le template reçoit `$block`, `$data`, `$settings` et `$context`.
- **Hook d'enregistrement** : `blocks:register` (reçoit `BlockManager`).
- **Hook d'adaptation plugin** : `blocks:render-plugin-content` (`['plugin' => string, 'data' => array, 'context' => array, 'html' => string]`).

---

## 3. Workflow éditorial & Révisions (`Core\Editorial`)

### Workflow (`WorkflowManager`)

États par défaut : `draft`, `pending`, `published`, `archived`.

- `WorkflowManager::getInstance()->canTransition($from, $to, $user, $publishPerm, $editPerm)`
- `WorkflowManager::getInstance()->registerStatus($status, $label, $transitions)`
- Hook émis après changement d'état : `editorial:workflow-transition`.

### Révisions (`RevisionManager`)

- Anti-doublon automatique par empreinte SHA-256 du snapshot.
- Enregistrement : `RevisionManager::getInstance()->recordRevision($entityType, $entityId, $snapshot, $status, $user, $summary)`.
- Historique & comparaison : `getHistory($entityType, $entityId, $limit)` et `compare($revIdA, $revIdB)`.
- Restauration : `restoreRevision($revisionId, $actor)` (supporte `page` et `content:<type>`).
- Intégration page : route POST `/admin/page/restore-revision` et onglet éditorial dans `admin/templates/page/edit.tpl`.
- Hook : `editorial:revision-recorded`.

---

## 4. API REST normalisée (`Core\Api`)

Toutes les réponses utilisent l'enveloppe normalisée fournie par `Core\Responses\ApiResponse::normalized()` / `success()` / `error()` :

```json
{
  "success": true,
  "data": {},
  "meta": {},
  "errors": []
}
```

### Endpoints REST cœur pré-enregistrés

| Méthode | Route | Permission |
| :--- | :--- | :--- |
| `GET` | `/api/v1/routes` | `public` |
| `GET` | `/api/v1/content/[a:type]` | `public` (filtre les entrées selon lecture/statut) |
| `GET` | `/api/v1/content/[a:type]/[a:id]` | `public` (vérifie statut publié ou droit d'édition) |
| `POST` | `/api/v1/content/[a:type]` | `auth` (+ RBAC du type de contenu) |
| `PUT` / `PATCH` | `/api/v1/content/[a:type]/[a:id]` | `auth` (+ RBAC du type de contenu) |
| `DELETE` | `/api/v1/content/[a:type]/[a:id]` | `auth` (+ RBAC du type de contenu) |

### Sécurité intégrée dans `ApiManager`

1. **Authentification & tokens révocables** : jeton `Authorization: Bearer <token>`, en-tête `X-API-Token`, ou session utilisateur existante. Les nouveaux tokens émis via `ApiManager::getInstance()->issueToken($user, $label, $ttl)` sont stockés uniquement sous forme d'empreinte SHA-256 (`token_hash`) avec date d'expiration (`expires_at`) et support de révocation (`revokeToken()`).
2. **Protection CSRF** : obligatoire sur les méthodes mutantes (`POST`, `PUT`, `PATCH`, `DELETE`) en session cookie navigateur (`X-CSRF-Token` ou `_csrf`).
3. **RBAC/ABAC & Anti-IDOR** : évaluation par `AccessPolicyEvaluator` avec cache isolé par empreinte de politique. Sur `/api/v1/content/...`, les brouillons et entrées non publiées sont masqués en HTTP `404` aux non-propriétaires non privilégiés, et les mutations/suppressions sur l'entrée d'un autre auteur sont bloquées en HTTP `403` sans permission `content.edit_others` (ou droit `publish`).
4. **Validation & listes blanches d'entrée/sortie** : HTTP `422` automatique via `param_rules` et rejet des champs marqués `api_writable => false`. En lecture API, seuls les champs `api_readable => true` présents dans `api_exposed_fields` (si défini) sont renvoyés ; les routes personnalisées peuvent définir `allowed_output_keys` (`allowedOutputKeys`).
5. **Filtrage récursif élargi des secrets** : suppression automatique par clés exactes et motif regex (`password`, `passwd`, `pwd`, `secret`, `token`, `private`, `credential`, `signature`, `api_key`, `smtp_*`, `webhook_*`, `payment_conf*`, `internal_note*`).
6. **Rate limiting par route** : option `'rate_limit' => ['max' => 60, 'window' => 60]` renvoyant HTTP `429` en cas de dépassement.

### Déclarer une route API dans un plugin

Écoutez le hook `api:register` :

```php
use Core\Api\ApiManager;
use Core\Responses\ApiResponse;

ApiManager::register([
    'name' => 'monplugin-stats',
    'method' => 'GET',
    'pattern' => '/api/v1/monplugin/stats',
    'permission' => 'api.access', // ou 'public', 'auth', 'admin', ou policy ABAC array
    'auth_required' => true,
    'allowed_output_keys' => ['total'],
    'rate_limit' => ['max' => 30, 'window' => 60],
    'param_rules' => [
        'limit' => ['type' => 'int'],
    ],
    'handler' => static function (array $ctx): ApiResponse {
        return ApiResponse::success(['total' => 42]);
    },
]);
```

---

## 5. CLI léger (`cli.php` & `Core\Cli\CliApplication`)

Exécution strictement réservée au SAPI CLI (`php cli.php <commande>`) :

- `php cli.php help` — Liste des commandes.
- `php cli.php system:info [--json]` — Version CMS/PHP, mode de stockage, compteurs.
- `php cli.php plugin:list [--json]` — État actif/inactif et version de chaque plugin détecté.
- `php cli.php storage:check [--json]` — Vérifie les droits d'écriture et la connexion PDO.
- `php cli.php routes:list [--json]` — Inventorie les routes API et les routes HTTP AltoRouter.
- `php cli.php cache:clear [--dry-run]` — Nettoie `CACHE` en préservant `sessions`.
- `php cli.php test:run` — Lance `tests/run.php`.
- Option globale `--data-dir=/chemin` — Isole les données pour les tests ou l'exploitation multi-environnement.

---

## 6. Multilingue (`Core\Translation`)

`TranslationManager` gère la traduction de contenus indépendamment de la traduction de l'interface (`Core\Lang`).

- **Routage préfixé non destructif** (`Router.php` + `Core.php`) : extrait `/fr/...`, `/en/...` si la locale est activée dans `locales.enabled` tout en conservant les routes historiques sans préfixe.
- **Variables de template globales** : `ACTIVE_LOCALE`, `DEFAULT_LOCALE`, `ENABLED_LOCALES`.
- **Groupes de traduction** : `TranslationManager::getInstance()->linkEntities('content:product', ['fr' => 1, 'en' => 2])`.
- **Unicité des slugs par locale & suppression propre** : l'unicité d'un slug est vérifiée par couple `(content_type, locale, slug)` (`findBySlug($typeId, $slug, $locale)`, `Page::findBySlug($slug, $locale)`), permettant de réutiliser un même slug entre deux langues tout en rejetant les doublons dans la même langue. La suppression d'une page ou entrée nettoie son `TranslationLink` et promeut automatiquement la première variante restante en `is_default = 1`.
- **Champs communs vs traduisibles** : tout champ déclaré avec `'translatable' => false` est synchronisé automatiquement vers les autres versions du groupe via `syncCommonFields()`.
- **Résolution et fallback explicite** : `resolveContentEntry()` et `resolvePageItem()` retournent un objet `ResolvedTranslation` indiquant `fallbackUsed`, `requestedLocale` et `resolvedLocale`.
- **SEO `hreflang` & `canonical`** : injection automatique des balises `<link rel="canonical" ...>` (`TranslationManager::renderCanonicalTag()`) et `<link rel="alternate" hreflang="...">` sur les pages via `PageHooks`.
