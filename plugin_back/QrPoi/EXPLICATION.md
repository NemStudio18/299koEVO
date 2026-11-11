# Plugin QR-POI - Explication du fonctionnement

## 🎯 But du plugin

Le plugin `qr-poi` permet aux **propriétaires de POI** (qui ont réclamé une fiche via le plugin `claim`) de :
1. **Générer des QR codes** pour leurs POI réclamés
2. **Télécharger** ces QR codes en image PNG
3. **Afficher le QR code** sur la page publique de leur POI (optionnel)
4. **Rediriger** les utilisateurs qui scannent le QR code vers la page de détail du POI

## 📍 Comment y accéder ?

### Pour les propriétaires (Admin)

1. **Menu Admin** → Cliquez sur "QR Code POI" dans le menu latéral
   - Route : `/admin/qr-poi`
   - Affiche la liste de tous vos POI réclamés avec leurs QR codes

2. **Actions disponibles** :
   - **Voir le QR code** : Cliquez sur un POI pour voir le QR code en grand
   - **Télécharger** : Bouton "Télécharger" pour obtenir le QR code en PNG
   - **Afficher sur la page publique** : Checkbox pour activer/désactiver l'affichage sur la page de détail du POI

### Pour les visiteurs (Public)

1. **Page de détail d'un POI** : Si le propriétaire a activé l'affichage, le QR code apparaît sur la page
   - Route : `/nom-du-poi` (page de détail)
   - Section : "QR Code" (visible uniquement si activé par le propriétaire)

2. **Scan du QR code** : 
   - Route : `/qr/poi/[poi_id]`
   - Redirige automatiquement vers la page de détail du POI

## 🔧 Fonctionnement technique

### 1. Génération des QR codes

- **Service** : `QrCodeService::generatePoiQrCodeUrl()`
- **API externe** : `https://api.qrserver.com/v1/create-qr-code/`
- **URL générée** : `/qr/poi/[poi_id]` qui redirige vers la page de détail
- **Taille par défaut** : 300px (configurable)

### 2. Stockage des préférences

- **Base de données** : SQLite (`data/plugin/qr-poi/qr_display_settings.sqlite3`)
- **Table** : `qr_display_settings`
- **Colonnes** : `poi_id`, `user_id`, `show_qr_code`, `updated_at`
- **Service** : `QrDisplayService` gère cette table

### 3. Vérification de propriété

- **Service** : `ClaimService::isOwner()`
- **Vérifie** :
  1. Table `poi` (colonne `claimed_by`)
  2. Table `poi_claims` (status = 'approved')
- **Nécessite** : L'utilisateur doit avoir réclamé le POI via le plugin `claim`

### 4. Intégration avec searchEVO

- **Page de détail** : `searchEVO/template/detail.tpl`
- **Condition d'affichage** :
  - Plugin `qr-poi` actif
  - Utilisateur connecté
  - Utilisateur est propriétaire du POI (`user_is_owner = true`)
  - Affichage activé dans les préférences (`show_qr_code = true`)

## 📋 Routes disponibles

### Routes Admin

| Route | Méthode | Contrôleur | Description |
|-------|---------|-----------|-------------|
| `/admin/qr-poi` | GET | `QrPoiAdminController#list` | Liste des POI avec QR codes |
| `/admin/qr-poi/generate/[poi_id]` | GET | `QrPoiAdminController#generate` | Affiche le QR code en grand |
| `/admin/qr-poi/download/[poi_id]` | GET | `QrPoiAdminController#download` | Télécharge le QR code en PNG |
| `/admin/qr-poi/toggle-display/[poi_id]` | POST | `QrPoiAdminController#toggleDisplay` | Active/désactive l'affichage public |

### Routes Publiques

| Route | Méthode | Contrôleur | Description |
|-------|---------|-----------|-------------|
| `/qr/poi/[poi_id]` | GET | `QrPoiPublicController#redirect` | Redirige vers la page de détail |

## 🔗 Dépendances

Le plugin `qr-poi` dépend de :

1. **Plugin `claim`** :
   - Pour vérifier si un utilisateur est propriétaire
   - Pour récupérer la liste des POI réclamés
   - Pour obtenir les informations des POI

2. **Plugin `searchEVO`** :
   - Pour afficher le QR code sur la page de détail
   - Pour générer les URLs de redirection

3. **Base de données MySQL** :
   - Tables : `poi`, `poi_claims`, `users`
   - Configuration : `data/plugin/searchEVO/search_conf.json` ou `data/plugin/search/search_conf.json`

## 🎨 Interface utilisateur

### Page Admin - Liste des QR codes

- **Affiche** : Tous les POI réclamés par l'utilisateur
- **Pour chaque POI** :
  - Nom et adresse
  - QR code (aperçu)
  - Bouton "Télécharger"
  - Checkbox "Afficher sur la page publique"

### Page Admin - Détail d'un QR code

- **Affiche** : Le QR code en grande taille
- **Informations** : Nom du POI, URL de redirection
- **Action** : Bouton de téléchargement

### Page Publique - Affichage du QR code

- **Emplacement** : Section "QR Code" sur la page de détail
- **Style** : Intégré au thème Lumiere
- **Contenu** : QR code + texte explicatif

## 🔐 Sécurité

- **Vérification de propriété** : Seuls les propriétaires peuvent voir/gérer leurs QR codes
- **Validation CSRF** : Pour les actions POST (toggle display)
- **Vérification d'authentification** : Toutes les routes admin nécessitent une connexion

## 📝 Exemple d'utilisation

1. **Un propriétaire réclame un POI** via le plugin `claim`
2. **Il accède à `/admin/qr-poi`** et voit son POI dans la liste
3. **Il peut télécharger le QR code** pour l'imprimer ou l'afficher physiquement
4. **Il active l'affichage public** via la checkbox
5. **Le QR code apparaît** sur la page publique du POI
6. **Un visiteur scanne le QR code** avec son smartphone
7. **Il est redirigé** automatiquement vers la page de détail du POI

## 🛠️ Configuration

- **Fichier de config** : `data/plugin/claim/claim_conf.json` (pour la connexion MySQL)
- **Base SQLite** : Créée automatiquement à `data/plugin/qr-poi/qr_display_settings.sqlite3`
- **Pas de configuration spécifique** : Le plugin fonctionne dès qu'il est activé


