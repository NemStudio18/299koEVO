# Lexique des Design Tokens & Architecture CSS (`--evo-*`)

Ce document détaille les variables CSS natives (**Design Tokens**) du socle **299ko EVO** (`common/css/evo-core.css`), leur rôle, leur comportement en mode clair/sombre, ainsi que la méthode pour styliser et surcharger les plugins sans jamais recourir à `!important`.

---

## 1. Hiérarchie des Cascades Layers (`@layer`)

Le socle repose sur les **CSS Cascade Layers** qui garantissent un ordre de priorité immuable :

```css
@layer evo-reset, evo-tokens, evo-base, evo-components, evo-plugins, evo-theme;
```

| Layer | Rôle |
|---|---|
| `evo-reset` | Reset universel moderne (`box-sizing`, `margin: 0`, images responsives). |
| `evo-tokens` | Définition des variables `--evo-*` par défaut (clair, sombre, et auto). |
| `evo-base` | Typographie de base, structure du `body`, liens, conteneur principal. |
| `evo-components` | Composants universels : boutons, formulaires, tables, modales, tiroir mobile nav. |
| `evo-plugins` | Styles par défaut fournis par les plugins (`299search`, `notifications`, etc.). |
| `evo-theme` | **Priorité maximale**. Le thème actif (`Vegetal`, `Declic`, `default`, etc.) a le dernier mot sans `!important`. |

---

## 2. Dictionnaire des Tokens `--evo-*`

### A. Typographie
| Token | Rôle & Usage | Valeur par défaut (Clair) |
|---|---|---|
| `--evo-font` | Police principale de texte de l'interface et du contenu. | `system-ui, -apple-system, Roboto, sans-serif` |
| `--evo-font-serif` | Police avec empattements pour citations ou titres éditoriaux. | `Georgia, Cambria, "Times New Roman", serif` |
| `--evo-font-mono` | Police à chasse fixe pour code source, balises `<pre>`, `<code>`. | `ui-monospace, Menlo, Monaco, Consolas, monospace` |

---

### B. Couleurs de Fond & Surfaces
| Token | Rôle & Usage | Mode Clair | Mode Sombre |
|---|---|---|---|
| `--evo-bg` | Fond global de la page (`body`). | `#f8fafc` (gris très clair) | `#0b1118` (sombre profond) |
| `--evo-surface` | Fond des barres d'en-tête, pieds de page, modales et menus déroulants. | `#ffffff` | `#131c26` |
| `--evo-surface-alt` | Fond des éléments secondaires, survols, zébrures de tableaux. | `#f1f5f9` | `#1a2533` |
| `--evo-card-bg` | Fond des cartes d'articles, blocs de contenu, panneaux widget. | `#ffffff` | `#141e2a` |

---

### C. Textes & Contraste
| Token | Rôle & Usage | Mode Clair | Mode Sombre |
|---|---|---|---|
| `--evo-text` | Couleur du texte courant principal (titres, paragraphes). | `#1e293b` (slate 800) | `#e2e8f0` (slate 200) |
| `--evo-text-muted` | Couleur des métadonnées, dates, sous-titres, placeholders. | `#64748b` (slate 500) | `#94a3b8` (slate 400) |

---

### D. Bordures & Formulaires
| Token | Rôle & Usage | Mode Clair | Mode Sombre |
|---|---|---|---|
| `--evo-border` | Bordure subtile de séparation (séparateurs, cartes, listes). | `#e2e8f0` | `#233142` |
| `--evo-border-strong` | Bordure plus marquée pour contours de contrôles ou sélecteurs. | `#cbd5e1` | `#334458` |
| `--evo-input-bg` | Fond des champs `<input>`, `<select>`, `<textarea>`. | `#ffffff` | `#0f1620` |
| `--evo-input-border` | Bordure des champs de formulaire au repos. | `#cbd5e1` | `#334458` |

---

### E. Couleur d'Accentuation Principale
| Token | Rôle & Usage | Mode Clair | Mode Sombre |
|---|---|---|---|
| `--evo-accent` | Couleur de marque, boutons primaires, liens actifs, focus ring. | `#1d4ed8` | `#60a5fa` |
| `--evo-accent-hover` | Teinte au survol ou à l'état actif des boutons et liens. | `#1e40af` | `#93c5fd` |
| `--evo-accent-soft` | Teinte très translucide pour fonds de badges actifs, halos de focus. | `rgba(29, 78, 216, 0.08)` | `rgba(96, 165, 250, 0.15)` |
| `--evo-accent-text` | Couleur de texte sur fond `--evo-accent` (lisibilité optimale). | `#ffffff` | `#0b1118` |

---

### F. États & Alertes (Succès, Attention, Erreur, Info)
| Token | Rôle & Usage |
|---|---|
| `--evo-success` / `--evo-success-bg` / `--evo-success-border` | Notifications de confirmation, badges « Publié », validation. |
| `--evo-warning` / `--evo-warning-bg` / `--evo-warning-border` | Alertes de vigilance, avertissements de sécurité, modération. |
| `--evo-danger` / `--evo-danger-bg` / `--evo-danger-border` | Erreurs, boutons de suppression, messages d'échec. |
| `--evo-info` / `--evo-info-bg` / `--evo-info-border` | Informations système, bulles d'aide, astuces. |

---

### G. Rayons d'Arrondi (`border-radius`)
| Token | Rôle & Recommandation | Valeur |
|---|---|---|
| `--evo-radius-xs` | Petits badges, tags compacts, coches. | `4px` |
| `--evo-radius-sm` | Boutons compacts, champs d'entrée, infobulles. | `8px` |
| `--evo-radius-md` | Boutons standards, cartes simples, menus déroulants. | `12px` |
| `--evo-radius-lg` | Grandes cartes, conteneurs modaux, bannières. | `16px` |
| `--evo-radius-full` | Pastilles (« pills »), avatars ronds, boutons flottants circulaires. | `9999px` |

---

### H. Ombres Portées (`box-shadow`)
| Token | Rôle & Usage |
|---|---|
| `--evo-shadow-sm` | Boutons légers, cartes au repos, délimitation douce. |
| `--evo-shadow-md` | Menus déroulants, barres fixes, cartes au survol. |
| `--evo-shadow-lg` | Modales d'administration, tiroir de navigation mobile, popups. |

---

### I. Dimensions de Gabarit
| Token | Usage | Valeur |
|---|---|---|
| `--evo-container-width` | Largeur maximale du conteneur central de page. | `1240px` |
| `--evo-header-height` | Hauteur de référence de la barre d'en-tête (navigation). | `68px` |
| `--evo-sidebar-width` | Largeur type de la barre latérale ou du panneau d'administration. | `320px` |

---

## 3. Personnalisation des Plugins par Thème (Sans `!important`)

### Comment ça marche ?
Les plugins sont rangés dans `@layer evo-plugins`.  
Les thèmes sont déclarés dans `@layer evo-theme`.  
En CSS standard, **toute règle présente dans un layer supérieur l'emporte toujours sur les layers inférieurs**, même si le sélecteur du plugin est plus long ou plus complexe !

### Exemple pratique : Adapter la loupe `299search`

#### 1. Cas d'un thème rond / pilule douce (ex: Thème Végétal)
```css
@layer evo-theme {
    .search299-pill-form {
        border-radius: var(--vg-radius-full, 9999px);
        background: var(--vg-surface);
        border: 1.5px solid var(--vg-border-strong);
    }
}
```

#### 2. Cas d'un thème rectangulaire / géométrique (ex: Thème Tech ou Minimaliste)
```css
@layer evo-theme {
    .search299-pill-form {
        border-radius: 4px; /* Forme rectangulaire */
        background: var(--dc-surface);
        border: 1px solid var(--dc-border);
    }
}
```

#### 3. Cas d'un thème sans cadre (icône seule sans boîte)
```css
@layer evo-theme {
    .search299-pill-form {
        border: none;
        background: transparent;
        box-shadow: none;
    }
}
```
**Résultat :** Zéro hack, zéro `!important`, et chaque thème conserve une maîtrise totale sur l'apparence des composants tiers.

