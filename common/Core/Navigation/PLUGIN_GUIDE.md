# Guide : Comment un plugin se déclare dans la navigation

## Vue d'ensemble

Le nouveau système de navigation supporte **multi-niveaux illimités** et permet aux plugins de s'enregistrer via un hook.

## ✅ Multi-niveaux

Le système supporte **un nombre illimité de niveaux** :

```
Menu principal (niveau 1)
  └─ Sous-menu (niveau 2)
      └─ Sous-sous-menu (niveau 3)
          └─ Niveau 4...
```

La gestion est **récursive** dans `NavigationTree::build()` qui attache automatiquement les enfants à leurs parents.

## 📝 Méthode 1 : Via Hook (Recommandée)

### Exemple basique

```php
// Dans plugin/monplugin/monplugin.php
$core = Core::getInstance();
$core->addHook('navigation:build', function($tree) {
    $navigationService = \Core\Navigation\NavigationService::getInstance();
    
    // Item principal (niveau 1)
    $navigationService->register('plugin.monplugin.main', [
        'label' => 'Mon Plugin',
        'target' => '/monplugin',
        'position' => 50,
        'source' => 'plugin:monplugin',
        'visibility' => ['public' => true],
        'icon' => 'fa-solid fa-star'
    ]);
});
```

### Exemple avec sous-menu (niveau 2)

```php
$core->addHook('navigation:build', function($tree) {
    $navigationService = \Core\Navigation\NavigationService::getInstance();
    
    // Menu principal
    $navigationService->register('plugin.monplugin.main', [
        'label' => 'Mon Plugin',
        'target' => '/monplugin',
        'position' => 50,
        'source' => 'plugin:monplugin',
        'visibility' => ['public' => true]
    ]);
    
    // Sous-menu (parent = ID du menu principal)
    $navigationService->register('plugin.monplugin.settings', [
        'label' => 'Paramètres',
        'target' => '/monplugin/settings',
        'parent' => 'plugin.monplugin.main',  // ← Lien vers le parent
        'position' => 10,
        'source' => 'plugin:monplugin',
        'visibility' => ['public' => true]
    ]);
});
```

### Exemple multi-niveaux (3+ niveaux)

```php
$core->addHook('navigation:build', function($tree) {
    $navigationService = \Core\Navigation\NavigationService::getInstance();
    
    // Niveau 1
    $navigationService->register('plugin.monplugin.main', [
        'label' => 'Mon Plugin',
        'target' => '/monplugin',
        'position' => 50,
        'source' => 'plugin:monplugin',
        'visibility' => ['public' => true]
    ]);
    
    // Niveau 2
    $navigationService->register('plugin.monplugin.admin', [
        'label' => 'Administration',
        'target' => '/monplugin/admin',
        'parent' => 'plugin.monplugin.main',
        'position' => 10,
        'source' => 'plugin:monplugin',
        'visibility' => ['public' => true]
    ]);
    
    // Niveau 3
    $navigationService->register('plugin.monplugin.admin.users', [
        'label' => 'Utilisateurs',
        'target' => '/monplugin/admin/users',
        'parent' => 'plugin.monplugin.admin',  // ← Parent = niveau 2
        'position' => 5,
        'source' => 'plugin:monplugin',
        'visibility' => ['public' => true]
    ]);
    
    // Niveau 4 (possible !)
    $navigationService->register('plugin.monplugin.admin.users.add', [
        'label' => 'Ajouter',
        'target' => '/monplugin/admin/users/add',
        'parent' => 'plugin.monplugin.admin.users',  // ← Parent = niveau 3
        'position' => 1,
        'source' => 'plugin:monplugin',
        'visibility' => ['public' => true]
    ]);
});
```

## 📋 Paramètres disponibles

```php
$navigationService->register('plugin.monplugin.id', [
    'label' => 'Label affiché',           // Requis
    'target' => '/url',                   // Requis
    'position' => 50,                     // Requis (pour le tri)
    'source' => 'plugin:monplugin',       // Requis
    'parent' => 'plugin.autre.parent',    // Optionnel (pour sous-menu)
    'visibility' => [                     // Optionnel
        'public' => true,                 // Visible sur site public
        'admin' => false,                // Visible dans l'admin
        'logged' => false,               // Nécessite connexion
        'roles' => ['admin']             // Rôles requis
    ],
    'icon' => 'fa-solid fa-star',        // Optionnel (FontAwesome)
    'cssClass' => 'custom-class',        // Optionnel
    'targetAttr' => '_self',             // Optionnel (_self, _blank, etc.)
    'positionRelative' => [              // Optionnel (position relative)
        'after' => 'core.home'           // ou 'before' => 'core.about'
    ]
]);
```

## 🔄 Méthode 2 : Legacy (Toujours supportée)

L'ancienne méthode fonctionne toujours mais est limitée à 2 niveaux :

```php
// Dans votre plugin
$this->addToNavigation('Mon Plugin', '/monplugin', '_self', 1, 0, '');
$this->addToNavigation('Sous-menu', '/monplugin/sub', '_self', 2, 1, '');
```

**Note** : Cette méthode est dépréciée. Utilisez la méthode 1 pour le multi-niveaux.

## 🎯 Règles importantes

1. **ID avec namespace obligatoire** : Format `plugin.nomplugin.xxx`
2. **Parent doit exister** : L'ID du parent doit être enregistré avant l'enfant
3. **Position** : Plus petit = affiché en premier
4. **Source** : Toujours `plugin:nomplugin` pour les plugins

## 🔍 Dépannage

### Le menu n'apparaît pas
- Vérifier que le plugin est **activé**
- Vérifier `hideInPublicMenu: false` dans `infos.json`
- Vérifier la `visibility`

### Le sous-menu n'apparaît pas
- Vérifier que le `parent` existe et est enregistré avant
- Vérifier que le parent a `visibility: public: true`

### Ordre incorrect
- Vérifier les `position` (plus petit = en premier)
- Utiliser `positionRelative` pour position relative


