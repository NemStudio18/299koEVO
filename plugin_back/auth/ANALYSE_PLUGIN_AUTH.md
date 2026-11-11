# Analyse du Plugin Auth - Authentification OAuth (Facebook/Google)

## 📋 Vue d'ensemble

Le plugin `auth` permet aux utilisateurs de se connecter ou de s'inscrire sur le site en utilisant leur compte **Facebook** ou **Google** via OAuth 2.0, sans avoir besoin de créer un compte manuellement.

## ✨ Fonctionnalités

### 1. **Authentification OAuth**
- **Facebook OAuth** : Connexion via compte Facebook
- **Google OAuth** : Connexion via compte Google
- Les deux peuvent être activés simultanément ou indépendamment

### 2. **Inscription automatique**
- Si l'utilisateur n'existe pas, un compte est créé automatiquement
- Le nom d'utilisateur est généré à partir du nom fourni par le provider OAuth
- L'email est récupéré depuis le compte OAuth
- Aucun mot de passe n'est requis (authentification uniquement via OAuth)

### 3. **Connexion automatique**
- Si l'utilisateur existe déjà (même email), il est connecté automatiquement
- Les informations OAuth sont stockées dans les attributs de l'utilisateur

### 4. **Interface utilisateur**
- Boutons OAuth injectés automatiquement sur les pages `/users/login` et `/users/register`
- Design intégré avec le thème du site

## 🔧 Architecture technique

### Fichiers principaux
- `auth.php` : Point d'entrée, hooks, installation
- `controllers/AuthPublicController.php` : Gestion des flux OAuth (login/callback)
- `controllers/AuthAdminController.php` : Interface d'administration
- `param/routes.php` : Routes OAuth
- `data/plugin/auth/auth_conf.json` : Configuration (tokens, activation)

### Routes OAuth
- `/auth/facebook/login.php` : Démarre le flux Facebook
- `/auth/facebook/callback.php` : Callback Facebook
- `/auth/google/login.php` : Démarre le flux Google
- `/auth/google/callback.php` : Callback Google
- `/admin/auth` : Interface d'administration

### Flux OAuth (exemple Google)
1. Utilisateur clique sur "Se connecter avec Google"
2. Redirection vers Google OAuth
3. Utilisateur autorise l'application
4. Google redirige vers `/auth/google/callback.php` avec un code
5. Le plugin échange le code contre un token d'accès
6. Récupération des informations utilisateur (email, nom)
7. Création ou connexion de l'utilisateur
8. Redirection vers le profil

## 🐛 Problèmes identifiés et corrigés

### ❌ Bug critique corrigé
**Problème** : `AuthPublicController::getConfig()` lisait `config.json` au lieu de `auth_conf.json`
- **Impact** : Le plugin ne pouvait pas lire la configuration, rendant OAuth inopérant
- **Correction** : Fichier corrigé pour utiliser `auth_conf.json`

## ✅ État actuel

Le plugin est **fonctionnel** après correction du bug. Il nécessite :
1. Configuration des credentials OAuth (Client ID / Secret)
2. Configuration des URLs de callback dans les consoles OAuth

## 🔐 Configuration Google OAuth

### Étape 1 : Créer un projet dans Google Cloud Console

1. Aller sur [Google Cloud Console](https://console.cloud.google.com/)
2. Créer un nouveau projet ou sélectionner un projet existant
3. Activer l'API "Google+ API" (ou "Google Identity Services")

### Étape 2 : Créer les identifiants OAuth

1. Aller dans **APIs & Services** > **Credentials**
2. Cliquer sur **Create Credentials** > **OAuth client ID**
3. Choisir **Web application**
4. Configurer :
   - **Name** : Nom de votre application (ex: "299koEVO Auth")
   - **Authorized JavaScript origins** : 
     ```
     https://299koevo.test
     http://localhost (si développement local)
     ```
   - **Authorized redirect URIs** : 
     ```
     https://299koevo.test/auth/google/callback.php
     http://localhost/auth/google/callback.php (si développement local)
     ```
5. Cliquer sur **Create**
6. **Copier le Client ID et le Client Secret**

### Étape 3 : Configurer dans le plugin

1. Aller dans l'administration : `/admin/auth`
2. Cocher **Activer Google**
3. Remplir :
   - **Client ID** : Le Client ID copié depuis Google
   - **Client Secret** : Le Client Secret copié depuis Google
4. Cliquer sur **Sauvegarder**

### Étape 4 : Tester

1. Aller sur `/users/login`
2. Cliquer sur "Se connecter avec Google"
3. Autoriser l'application dans Google
4. Vérifier la redirection et la connexion

## 📝 URLs de callback à configurer

### Google
```
https://299koevo.test/auth/google/callback.php
```

### Facebook (si vous créez l'app plus tard)
```
https://299koevo.test/auth/facebook/callback.php
```

## ⚠️ Points d'attention

### Sécurité
- ✅ Protection CSRF via `state` parameter
- ✅ Validation des tokens OAuth
- ✅ Vérification de l'email (requis)
- ⚠️ Les secrets doivent être stockés de manière sécurisée (actuellement dans `auth_conf.json`)

### Limitations
- L'utilisateur doit avoir un email valide dans son compte OAuth
- Le nom d'utilisateur est généré automatiquement (peut être modifié après)
- Pas de mot de passe pour les comptes OAuth (connexion uniquement via OAuth)

### Compatibilité
- ✅ Facebook OAuth v24.0
- ✅ Google OAuth 2.0
- ✅ Compatible avec le système d'authentification du CMS

## 🧪 Test recommandé

1. **Configurer Google OAuth** (plus simple que Facebook)
2. **Tester la connexion** sur `/users/login`
3. **Vérifier la création de compte** si l'utilisateur n'existe pas
4. **Vérifier la connexion** si l'utilisateur existe déjà
5. **Vérifier les attributs OAuth** dans la base de données

## 📊 État du code

- ✅ Structure OOP correcte
- ✅ Séparation des responsabilités (Public/Admin controllers)
- ✅ Gestion d'erreurs basique
- ✅ Hooks CMS bien intégrés
- ⚠️ Gestion d'erreurs OAuth pourrait être améliorée (messages plus explicites)
- ⚠️ Pas de logs détaillés pour le débogage

## 🎯 Conclusion

Le plugin est **fonctionnel** et prêt à être utilisé avec Google OAuth. Le bug de configuration a été corrigé. Il suffit de :
1. Créer les credentials dans Google Cloud Console
2. Configurer les URLs de callback
3. Remplir les identifiants dans `/admin/auth`
4. Tester la connexion

Le plugin devrait fonctionner correctement pour Google. Pour Facebook, il faudra créer l'application Facebook (plus complexe que Google).

