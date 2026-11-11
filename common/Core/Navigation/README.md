# Système de Navigation 299koEVO

## Vue d'ensemble

Le nouveau système de navigation remplace l'ancien système limité à 2 niveaux par une architecture complète basée sur un fichier JSON et un arbre hiérarchique récursif.

## Architecture

```
Core/Navigation/
├── NavigationItem.php      # Modèle de données avec validation
├── NavigationTree.php      # Arbre hiérarchique récursif
├── NavigationService.php   # Service principal (chargement, fusion, lifecycle)
├── NavigationRenderer.php  # Rendu sécurisé HTML
├── LegacyAdapter.php      # Compatibilité avec ancien système
└── hooks.php              # Documentation hooks
```

## Fonctionnalités

### ✅ Support multi-niveaux illimité
- Arbre récursif sans limitation de profondeur
- Gestion automatique des relations parent/enfant

### ✅ Namespace obligatoire pour les IDs
- Format : `core.home`, `plugin.search.main`, `core.page.123`
- Évite les collisions entre plugins
- Validation automatique

### ✅ Gestion du cycle de vie
- Désactivation automatique des items de plugins inactifs
- Nettoyage des orphelins
- Flag `enabled` pour contrôle manuel

### ✅ Permissions et visibilité
```php
'visibility' => [
    'public' => true,      // Visible en front
    'admin' => false,      // Visible en admin
    'logged' => false,     // Nécessite connexion
    'roles' => ['admin'],  // Rôles requis
    'conditions' => []     // Conditions personnalisées
]
```

### ✅ Positions relatives
```php
'positionRelative' => [
    'after' => 'core.home'  // Après un item
]
// ou
'positionRelative' => [
    'before' => 'core.about'  // Avant un item
]
```

### ✅ Sécurité XSS
- Échappement HTML par défaut
- Validation des attributs
- Protection contre injection

### ✅ Compatibilité legacy
- Migration automatique depuis `Page::addToNavigation()`
- Support des plugins existants via `addToNavigation()`
- Fallback automatique en cas d'erreur

## Utilisation

### Pour les plugins

#### Méthode 1 : Via hook (recommandé)

```php
// Dans votre plugin (ex: plugin/monplugin/monplugin.php)
$core = Core::getInstance();
$core->addHook('navigation:build', function($tree) {
    $navigationService = \Core\Navigation\NavigationService::getInstance();
    
    // Item principal
    $navigationService->register('plugin.monplugin.main', [
        'label' => 'Mon Plugin',
        'target' => '/monplugin',
        'position' => 50,
        'source' => 'plugin:monplugin',
        'visibility' => ['public' => true],
        'icon' => 'fa-solid fa-star'
    ]);
    
    // Sous-menu
    $navigationService->register('plugin.monplugin.sub1', [
        'label' => 'Sous-menu 1',
        'target' => '/monplugin/sub1',
        'parent' => 'plugin.monplugin.main',
        'position' => 10,
        'source' => 'plugin:monplugin',
        'visibility' => ['public' => true]
    ]);
    
    // Sous-menu de niveau 2
    $navigationService->register('plugin.monplugin.sub1.1', [
        'label' => 'Sous-sous-menu',
        'target' => '/monplugin/sub1/1',
        'parent' => 'plugin.monplugin.sub1',
        'position' => 5,
        'source' => 'plugin:monplugin',
        'visibility' => ['public' => true]
    ]);
});
```

#### Méthode 2 : Legacy (toujours supporté)

```php
// Dans votre plugin
$this->addToNavigation('Mon Plugin', '/monplugin', '_self', 1, 0, '');
$this->addToNavigation('Sous-menu', '/monplugin/sub', '_self', 2, 1, '');
```

### Pour le thème

Le template peut utiliser `Show::mainNavigation()` avec un format personnalisé :

```php
// Format par défaut
Show::mainNavigation();

// Format personnalisé
Show::mainNavigation('<li class="nav-item"><a href="[target]">[icon][label]</a>[childrens]</li>');
```

Placeholders disponibles :
- `[id]` : ID de l'item
- `[label]` : Label (échappé)
- `[target]` : URL (échappée)
- `[targetAttribut]` : Attribut target (échappé)
- `[cssClass]` : Classes CSS (échappées)
- `[icon]` : Icône formatée
- `[childrens]` : Enfants rendus

## Format JSON

Le fichier `data/core/navigation.json` stocke les items core :

```json
{
  "version": "1.0",
  "items": [
    {
      "id": "core.home",
      "label": "Accueil",
      "target": "/",
      "targetAttr": "_self",
      "cssClass": "",
      "icon": "fa-home",
      "position": 10,
      "source": "core",
      "parentId": null,
      "visibility": {
        "public": true,
        "admin": false,
        "logged": false,
        "roles": [],
        "conditions": []
      },
      "enabled": true,
      "children": []
    }
  ]
}
```

## Migration depuis l'ancien système

Le système migre automatiquement les pages existantes. Pour forcer la migration :

```php
\Core\Navigation\LegacyAdapter::migratePagesToNavigation();
```

## Règles importantes

1. **IDs avec namespace obligatoire** : `core.xxx`, `plugin.name.xxx`
2. **Les plugins n'injectent jamais de HTML** : Seulement des données
3. **Le thème décide du rendu** : Via `NavigationRenderer`
4. **Échappement par défaut** : Protection XSS automatique
5. **Lifecycle géré automatiquement** : Plugins inactifs = items désactivés

## Exemples avancés

### Item avec position relative

```php
$navigationService->register('plugin.monplugin.after', [
    'label' => 'Après Accueil',
    'target' => '/monplugin',
    'positionRelative' => ['after' => 'core.home'],
    'source' => 'plugin:monplugin',
    'visibility' => ['public' => true]
]);
```

### Item avec permissions

```php
$navigationService->register('plugin.monplugin.admin', [
    'label' => 'Admin',
    'target' => '/monplugin/admin',
    'source' => 'plugin:monplugin',
    'visibility' => [
        'public' => false,
        'admin' => true,
        'logged' => true,
        'roles' => ['admin', 'editor']
    ]
]);
```

### Item conditionnel

```php
$navigationService->register('plugin.monplugin.conditional', [
    'label' => 'Conditionnel',
    'target' => '/monplugin/cond',
    'source' => 'plugin:monplugin',
    'visibility' => [
        'public' => true,
        'conditions' => [
            'plugin_active' => 'otherplugin'
        ]
    ]
]);
```

## Dépannage

### Le menu ne s'affiche pas
- Vérifier que `data/core/navigation.json` existe
- Vérifier les logs pour erreurs
- Le système fait un fallback automatique sur l'ancien système

### Les plugins n'apparaissent pas
- Vérifier que le plugin est activé
- Vérifier `hideInPublicMenu` dans `infos.json`
- Vérifier les permissions de visibilité

### Items orphelins
- Le système nettoie automatiquement
- Vérifier que les `parentId` sont corrects
- Utiliser `NavigationService::reset()` pour forcer rebuild


