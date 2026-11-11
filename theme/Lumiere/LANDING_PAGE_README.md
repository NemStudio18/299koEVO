# Landing Page Personnalisée - Thème Lumiere

## Vue d'ensemble

Cette landing page personnalisée suit la structure UX recommandée :
1. **Hero** : Promesse + intention + CTA
2. **Recherche** : Formulaire de recherche (après le hero)
3. **Rappel collaboratif** : Explication de la source des données
4. **Résultats** : Zone pour afficher les résultats de recherche

## Structure des fichiers

- `landing.php` : Code PHP exécuté avant le template pour préparer les données
- `landing.tpl` : Template Twig pour le rendu de la page

## Comment utiliser

### 1. Créer une page dans "Page & Menu"

1. Aller dans l'admin → **Page & Menu**
2. Créer une nouvelle page ou modifier la page d'accueil
3. Dans le champ **Fichier template**, entrer : `landing.tpl`
4. Laisser le contenu vide (le template gère tout)
5. Sauvegarder

### 2. Configuration

La landing page utilise automatiquement :
- Les valeurs de `hero_title` et `hero_subtitle` depuis la configuration du plugin search (`data/plugin/search/search_conf.json`)
- Les hooks du plugin search pour le formulaire de recherche
- Les stats de l'agglomération si le plugin search est actif

### 3. Personnalisation

#### Modifier les textes

Éditer `landing.php` pour changer :
- `hero_title` : Titre principal
- `hero_subtitle` : Sous-titre
- `cta_primary_text` : Texte du CTA principal
- `cta_secondary_text` : Texte du CTA secondaire
- `search_placeholder` : Placeholder de la recherche
- `collaborative_text` : Texte sur la collaboration
- `contribute_text` : Texte d'invitation à contribuer

#### Modifier les styles

Éditer `landing.tpl` dans la section `<style>` pour personnaliser :
- Couleurs du hero
- Tailles de police
- Espacements
- Responsive design

#### Utiliser les données du plugin search

Le template a accès à :
- `landing` : Toutes les données préparées dans `landing.php`
- `homeStats` : Statistiques de l'agglomération (si plugin search actif)
- `cities` : Liste des villes de l'agglomération (si plugin search actif)
- `isLoggedIn` : État de connexion de l'utilisateur

## Structure UX

### Hero Section
- **Objectif** : Expliquer ce qu'est le site et pourquoi il existe
- **Contenu** : Titre, sous-titre, CTA (seulement si non connecté)
- **Position** : Au-dessus de la ligne de flottaison

### Search Section
- **Objectif** : Permettre la recherche après avoir compris le contexte
- **Contenu** : Formulaire de recherche via hook `searchHeroForm`
- **Position** : Juste après le hero

### Collaborative Section
- **Objectif** : Expliquer la source des données et inviter à contribuer
- **Contenu** : Texte explicatif + lien vers inscription
- **Position** : Avant les résultats

### Results Section
- **Objectif** : Afficher les résultats de recherche
- **Contenu** : Chargé dynamiquement via JavaScript/AJAX
- **Position** : Après la section collaborative

## Intégration avec le plugin Search

La landing page utilise les hooks suivants du plugin search :
- `searchHeroForm` : Pour injecter le formulaire de recherche
- Les styles CSS du plugin search sont automatiquement chargés via `searchEndFrontHead`

## Notes importantes

1. **Page d'accueil** : Pour utiliser cette landing comme page d'accueil, cocher "Page d'accueil" dans les options de la page
2. **Template requis** : Le fichier `landing.tpl` doit être dans le dossier du thème actif
3. **Plugin search** : Fonctionne même si le plugin search n'est pas actif (formulaire basique)
4. **Responsive** : Le template inclut des styles responsive pour mobile

## Prochaines étapes possibles

- Ajouter une section de catégories populaires
- Intégrer une carte Leaflet pour la visualisation géographique
- Ajouter des statistiques visuelles (graphiques, badges)
- Personnaliser les CTA selon le type d'utilisateur

