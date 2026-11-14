<?php

namespace CssEditor\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Core\Auth\AccessPolicyEvaluator;
use Core\Lang;
use Utils\Show;

defined('ROOT') or exit('Access denied!');

class CssEditorAdminController extends AdminController {
    
    private function checkAdminAccess(): bool {
        if (class_exists(AccessPolicyEvaluator::class)) {
            $evaluator = AccessPolicyEvaluator::fromLoadedPlugins();
            $decision = $evaluator->evaluate($this->user, 'csseditor:admin');
            if (!$decision->allowed) {
                Show::msg(Lang::get('core-not-authorized'), 'error');
                $this->core->redirect($this->router->generate('admin-home'));
                return false;
            }
        }
        return true;
    }

    public function home() {
        if (!$this->checkAdminAccess()) return null;
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('csseditor', 'admin');

        // Récupérer le CSS du thème actuel
        $currentTheme = $this->core->getConfigVal('theme');
        $themeCss = $this->extractThemeCss($currentTheme);
        
        // Extraire les variables CSS du thème
        $themeVars = $this->extractThemeVars($themeCss);
        
        // Récupérer le CSS custom existant
        $customCss = $this->runPlugin->getConfigVal('cssContent') ?: '';
        
        // Récupérer le CSS manuel
        $manualCss = $this->runPlugin->getConfigVal('manualCss') ?: '';
        
        // Récupérer les variables CSS sauvegardées
        $savedVars = $this->runPlugin->getConfigVal('cssVars') ?: [];
        if (is_string($savedVars)) {
            $savedVars = json_decode($savedVars, true) ?: [];
        }
        
        // Fusionner avec les variables du thème
        $themeVars = $this->mergeThemeVarsWithSaved($themeVars, $savedVars);
        
        $tpl->set('currentTheme', $currentTheme);
        $tpl->set('themeCss', $themeCss);
        $tpl->set('customCss', $customCss);
        $tpl->set('manualCss', $manualCss);
        $tpl->set('enabled', $this->runPlugin->getConfigVal('enabled'));
        $tpl->set('themeVars', $themeVars);

        $response->addTemplate($tpl);
        return $response;
    }

    public function save() {
        if (!$this->checkAdminAccess()) return null;
        if (!$this->user->isAuthorized()) {
            return $this->home();
        }

        $manualCss = trim($_POST['manualCss'] ?? '');
        $enabled = isset($_POST['enabled']) ? '1' : '0';
        
        // Récupérer les variables CSS du tableau
        $cssVars = $_POST['cssVars'] ?? [];
        $cssVarsText = $_POST['cssVarsText'] ?? [];
        
        // Fusionner les variables (priorité au color picker)
        $mergedVars = [];
        foreach ($cssVars as $varName => $colorValue) {
            $mergedVars[$varName] = $colorValue;
        }
        foreach ($cssVarsText as $varName => $textValue) {
            if (!isset($mergedVars[$varName])) {
                $mergedVars[$varName] = $textValue;
            }
        }
        
        // Générer le CSS à partir des variables
        $generatedCss = $this->generateCssFromVars($mergedVars);
        
        // Combiner avec le CSS manuel
        $finalCss = $generatedCss . "\n" . $manualCss;
        
        // Sauvegarder le CSS dans le fichier
        $this->saveCssToFile($finalCss);
        
        // Sauvegarder les données
        $this->runPlugin->setConfigVal('cssContent', $finalCss);
        $this->runPlugin->setConfigVal('manualCss', $manualCss);
        $this->runPlugin->setConfigVal('cssVars', json_encode($mergedVars));
        $this->runPlugin->setConfigVal('enabled', $enabled);
        $this->runPlugin->setConfigVal('lastModified', date('Y-m-d H:i:s'));

        // Créer une sauvegarde si du CSS existe
        if (!empty($finalCss)) {
            $this->createBackup($finalCss);
        }

        if ($this->pluginsManager->savePluginConfig($this->runPlugin)) {
            Show::msg(Lang::get('core-changes-saved'), 'success');
        } else {
            Show::msg(Lang::get('core-changes-not-saved'), 'error');
        }
        $this->core->redirect($this->router->generate('csseditor-admin-home'));
    }

    /**
     * Sauvegarde le CSS dans le fichier data/plugin/csseditor/custom.css
     */
    public function saveCss() {
        if (!$this->checkAdminAccess() || !$this->user->isAuthorized()) {
            echo 'error:unauthorized';
            exit;
        }

        $action = $_POST['action'] ?? '';
        
        if ($action === 'save_css') {
            $cssContent = $_POST['css_content'] ?? '';
            
            // Sauvegarder dans le fichier
            $result = $this->saveCssToFile($cssContent);
            
            if ($result) {
                echo 'success';
            } else {
                echo 'error:save_failed';
            }
            exit;
        }
        
        return $this->home();
    }

    /**
     * Sauvegarde le CSS dans le fichier data/plugin/csseditor/custom.css
     */
    private function saveCssToFile($cssContent) {
        $cssDir = DATA_PLUGIN . 'csseditor/';
        $cssFile = $cssDir . 'custom.css';
        
        // Créer le dossier s'il n'existe pas
        if (!is_dir($cssDir)) {
            if (!mkdir($cssDir, 0755, true)) {
                return false;
            }
        }
        
        // Vérifier les permissions
        if (!is_writable($cssDir)) {
            return false;
        }
        
        // Préparer le contenu du fichier - seulement les variables personnalisées
        $fileContent = "/* CSS Editor - Fichier de personnalisation */\n";
        $fileContent .= "/* Ce fichier est généré automatiquement par le plugin CSS Editor */\n";
        $fileContent .= "/* Les modifications apportées ici auront la priorité sur le CSS du thème */\n\n";
        
        // Nettoyer le contenu CSS reçu pour ne garder que les variables personnalisées
        $lines = explode("\n", $cssContent);
        $customVars = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            // Ne garder que les lignes qui contiennent des variables CSS personnalisées
            if (preg_match('/^:root\s*\{/', $line) || 
                preg_match('/^\s*--[a-zA-Z0-9\-_]+:\s*[^;]+;/', $line) ||
                preg_match('/^\s*\}\s*$/', $line)) {
                $customVars[] = $line;
            }
        }
        
        // Ajouter les variables personnalisées
        if (!empty($customVars)) {
            $fileContent .= implode("\n", $customVars) . "\n\n";
        }
        
        // Sauvegarder le fichier
        $result = file_put_contents($cssFile, $fileContent);
        
        if ($result !== false) {
            return true;
        }
        
        return false;
    }

    /**
     * Extrait le CSS du thème actuel
     */
    private function extractThemeCss($themeName) {
        $themePath = THEMES . $themeName . '/';
        $cssFiles = glob($themePath . '*.css');
        
        $cssContent = '';
        foreach ($cssFiles as $cssFile) {
            // Exclure les fichiers minifiés
            if (strpos(basename($cssFile), '.min.css') !== false) {
                continue;
            }
            
            $content = file_get_contents($cssFile);
            if ($content !== false) {
                $cssContent .= "/* Fichier: " . basename($cssFile) . " */\n";
                $cssContent .= $content . "\n\n";
            }
        }
        
        return $cssContent;
    }

    /**
     * Extrait les variables CSS personnalisées du thème
     * @param string $themeCss
     * @return array
     */
    private function extractThemeVars($themeCss) {
        $vars = [];
        
        // Cherche les variables CSS natives : --var: valeur;
        if (preg_match_all('/--([a-zA-Z0-9\-_]+)\s*:\s*([^;]+);/', $themeCss, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $value = trim($m[2]);
                // Nettoyer la valeur (supprimer les commentaires, etc.)
                $value = preg_replace('/\/\*.*?\*\//', '', $value); // Supprimer les commentaires
                $value = trim($value);
                
                // Ignorer les valeurs vides ou trop longues
                if (empty($value) || strlen($value) > 100) {
                    continue;
                }
                
                // Détecter le type de valeur
                $type = $this->detectValueType($value);
                
                $vars[] = [
                    'label' => $this->beautifyVarName($m[1]),
                    'var' => '--' . $m[1],
                    'value' => $value,
                    'original' => $value,
                    'type' => $type
                ];
            }
        }
        
        // Si aucune variable CSS, proposer des couleurs de base
        if (empty($vars)) {
            $defaults = [
                ['label' => 'Fond principal', 'selector' => 'body', 'property' => 'background-color', 'default' => '#ffffff'],
                ['label' => 'Texte principal', 'selector' => 'body', 'property' => 'color', 'default' => '#333333'],
                ['label' => 'Lien navigation', 'selector' => 'a', 'property' => 'color', 'default' => '#007cba'],
                ['label' => 'Lien navigation (hover)', 'selector' => 'a:hover', 'property' => 'color', 'default' => '#005a87'],
                ['label' => 'Titre principal', 'selector' => 'h1', 'property' => 'color', 'default' => '#333333'],
                ['label' => 'Titre secondaire', 'selector' => 'h2', 'property' => 'color', 'default' => '#555555'],
            ];
            
            foreach ($defaults as $def) {
                $found = false;
                if (preg_match('/' . preg_quote($def['selector']) . '\s*\{[^}]*' . preg_quote($def['property']) . '\s*:\s*([^;]+);/i', $themeCss, $m)) {
                    $found = true;
                    $value = trim($m[1]);
                } else {
                    $value = $def['default'];
                }
                
                $vars[] = [
                    'label' => $def['label'],
                    'var' => $def['selector'] . ':' . $def['property'],
                    'value' => $value,
                    'original' => $value,
                    'type' => 'color'
                ];
            }
        }
        
        return $vars;
    }

    /**
     * Détecte le type de valeur CSS
     * @param string $value
     * @return string 'color', 'size', 'function', 'other'
     */
    private function detectValueType($value) {
        // Couleurs hexadécimales
        if (preg_match('/^#[0-9a-fA-F]{3,6}$/', $value)) {
            return 'color';
        }
        
        // Couleurs nommées
        $colorNames = ['red', 'green', 'blue', 'yellow', 'black', 'white', 'gray', 'grey', 'orange', 'purple', 'pink', 'brown', 'cyan', 'magenta', 'lime', 'navy', 'olive', 'teal', 'silver', 'maroon', 'transparent', 'currentcolor', 'inherit', 'initial', 'unset'];
        if (in_array(strtolower($value), $colorNames)) {
            return 'color';
        }
        
        // Couleurs RGB/RGBA
        if (preg_match('/^rgba?\([^)]+\)$/', $value)) {
            return 'color';
        }
        
        // Couleurs HSL/HSLA
        if (preg_match('/^hsla?\([^)]+\)$/', $value)) {
            return 'color';
        }
        
        // Variables CSS (var(--variable))
        if (preg_match('/^var\(--[^)]+\)$/', $value)) {
            return 'variable';
        }
        
        // Fonctions CSS (blur(), calc(), etc.)
        if (preg_match('/^[a-zA-Z-]+\([^)]*\)$/', $value)) {
            return 'function';
        }
        
        // Tailles avec unités (px, em, rem, %, etc.)
        if (preg_match('/^[\d.]+(px|em|rem|%|vh|vw|pt|pc|in|cm|mm|ch|ex|fr)$/', $value)) {
            return 'size';
        }
        
        // Nombres simples (sans unité)
        if (preg_match('/^[\d.]+$/', $value)) {
            return 'number';
        }
        
        // Valeurs de position (top, center, bottom, left, right)
        $positionValues = ['top', 'center', 'bottom', 'left', 'right', 'start', 'end', 'flex-start', 'flex-end', 'space-between', 'space-around', 'space-evenly', 'stretch'];
        if (in_array(strtolower($value), $positionValues)) {
            return 'position';
        }
        
        // Valeurs de display (flex, grid, block, inline, etc.)
        $displayValues = ['flex', 'grid', 'block', 'inline', 'inline-block', 'none', 'contents', 'table', 'table-row', 'table-cell', 'inline-flex', 'inline-grid'];
        if (in_array(strtolower($value), $displayValues)) {
            return 'display';
        }
        
        // Valeurs de flex-direction
        $flexDirectionValues = ['row', 'column', 'row-reverse', 'column-reverse'];
        if (in_array(strtolower($value), $flexDirectionValues)) {
            return 'flex-direction';
        }
        
        // Valeurs de flex-wrap
        $flexWrapValues = ['nowrap', 'wrap', 'wrap-reverse'];
        if (in_array(strtolower($value), $flexWrapValues)) {
            return 'flex-wrap';
        }
        
        // Valeurs de align-items
        $alignItemsValues = ['flex-start', 'flex-end', 'center', 'baseline', 'stretch'];
        if (in_array(strtolower($value), $alignItemsValues)) {
            return 'align-items';
        }
        
        // Valeurs de justify-content
        $justifyContentValues = ['flex-start', 'flex-end', 'center', 'space-between', 'space-around', 'space-evenly'];
        if (in_array(strtolower($value), $justifyContentValues)) {
            return 'justify-content';
        }
        
        // Valeurs de text-align
        $textAlignValues = ['left', 'right', 'center', 'justify', 'start', 'end'];
        if (in_array(strtolower($value), $textAlignValues)) {
            return 'text-align';
        }
        
        // Valeurs de font-weight
        $fontWeightValues = ['normal', 'bold', 'bolder', 'lighter', '100', '200', '300', '400', '500', '600', '700', '800', '900'];
        if (in_array(strtolower($value), $fontWeightValues)) {
            return 'font-weight';
        }
        
        // Valeurs de font-style
        $fontStyleValues = ['normal', 'italic', 'oblique'];
        if (in_array(strtolower($value), $fontStyleValues)) {
            return 'font-style';
        }
        
        // Valeurs de text-decoration
        $textDecorationValues = ['none', 'underline', 'overline', 'line-through', 'blink'];
        if (in_array(strtolower($value), $textDecorationValues)) {
            return 'text-decoration';
        }
        
        // Valeurs de border-style
        $borderStyleValues = ['none', 'solid', 'dashed', 'dotted', 'double', 'groove', 'ridge', 'inset', 'outset', 'hidden'];
        if (in_array(strtolower($value), $borderStyleValues)) {
            return 'border-style';
        }
        
        // Valeurs de overflow
        $overflowValues = ['visible', 'hidden', 'scroll', 'auto', 'clip'];
        if (in_array(strtolower($value), $overflowValues)) {
            return 'overflow';
        }
        
        // Valeurs de visibility
        $visibilityValues = ['visible', 'hidden', 'collapse'];
        if (in_array(strtolower($value), $visibilityValues)) {
            return 'visibility';
        }
        
        // Valeurs de position (CSS position)
        $positionValues = ['static', 'relative', 'absolute', 'fixed', 'sticky'];
        if (in_array(strtolower($value), $positionValues)) {
            return 'position';
        }
        
        // Valeurs de z-index
        if (preg_match('/^(auto|[\d-]+)$/', $value)) {
            return 'z-index';
        }
        
        // Valeurs de cursor
        $cursorValues = ['auto', 'default', 'pointer', 'crosshair', 'move', 'text', 'wait', 'help', 'progress', 'not-allowed', 'grab', 'grabbing'];
        if (in_array(strtolower($value), $cursorValues)) {
            return 'cursor';
        }
        
        // Valeurs de user-select
        $userSelectValues = ['auto', 'text', 'none', 'contain', 'all'];
        if (in_array(strtolower($value), $userSelectValues)) {
            return 'user-select';
        }
        
        // Valeurs de pointer-events
        $pointerEventsValues = ['auto', 'none', 'visiblePainted', 'visibleFill', 'visibleStroke', 'visible', 'painted', 'fill', 'stroke', 'all', 'inherit'];
        if (in_array(strtolower($value), $pointerEventsValues)) {
            return 'pointer-events';
        }
        
        // Valeurs de box-shadow (peut contenir 'none')
        if ($value === 'none' || preg_match('/^[\d.]+(px|em|rem|%)\s+[\d.]+(px|em|rem|%)\s+[\d.]+(px|em|rem|%)(\s+[\d.]+(px|em|rem|%))?(\s+rgba?\([^)]+\)|\s+#[0-9a-fA-F]{3,6}|\s+[a-zA-Z]+)?$/', $value)) {
            return 'box-shadow';
        }
        
        // Valeurs de text-shadow
        if ($value === 'none' || preg_match('/^[\d.]+(px|em|rem|%)\s+[\d.]+(px|em|rem|%)(\s+[\d.]+(px|em|rem|%))?(\s+rgba?\([^)]+\)|\s+#[0-9a-fA-F]{3,6}|\s+[a-zA-Z]+)?$/', $value)) {
            return 'text-shadow';
        }
        
        // Valeurs de border-radius
        if (preg_match('/^[\d.]+(px|em|rem|%)(\s+[\d.]+(px|em|rem|%)){0,3}$/', $value)) {
            return 'border-radius';
        }
        
        // Valeurs de margin/padding
        if (preg_match('/^[\d.]+(px|em|rem|%)(\s+[\d.]+(px|em|rem|%)){0,3}$/', $value)) {
            return 'spacing';
        }
        
        // Valeurs de border-width
        if (preg_match('/^[\d.]+(px|em|rem|%)(\s+[\d.]+(px|em|rem|%)){0,3}$/', $value)) {
            return 'border-width';
        }
        
        // Par défaut, considérer comme autre
        return 'other';
    }

    /**
     * Fusionne les variables du thème avec celles sauvegardées
     */
    private function mergeThemeVarsWithSaved($themeVars, $savedVars) {
        $merged = [];
        
        // Ajouter les variables du thème
        foreach ($themeVars as $var) {
            $merged[$var['var']] = $var;
        }
        
        // Ajouter les variables sauvegardées
        foreach ($savedVars as $varName => $value) {
            if (isset($merged[$varName])) {
                // Vérifier si la valeur sauvegardée est différente de l'original
                if ($value !== $merged[$varName]['original']) {
                    $merged[$varName]['value'] = $value;
                } else {
                    // Si la valeur sauvegardée est identique à l'original, 
                    // garder la valeur originale et ne pas considérer comme modifiée
                    $merged[$varName]['value'] = $merged[$varName]['original'];
                }
            } else {
                // Variable personnalisée ajoutée par l'utilisateur
                $merged[$varName] = [
                    'label' => $this->beautifyVarName(str_replace(['--', '-', '_'], ' ', $varName)),
                    'var' => $varName,
                    'value' => $value,
                    'original' => $value
                ];
            }
        }
        
        return array_values($merged);
    }

    /**
     * Génère le CSS à partir des variables
     */
    private function generateCssFromVars($vars) {
        $css = '';
        
        foreach ($vars as $varName => $value) {
            if ($value && $value !== '') {
                if (strpos($varName, '--') === 0) {
                    // Variable CSS native
                    $css .= ":root {\n    " . $varName . ": " . $value . ";\n}\n\n";
                } else {
                    // Sélecteur CSS classique
                    $parts = explode(':', $varName, 2);
                    if (count($parts) === 2) {
                        $css .= $parts[0] . " {\n    " . $parts[1] . ": " . $value . ";\n}\n\n";
                    }
                }
            }
        }
        
        return $css;
    }

    /**
     * Transforme un nom de variable CSS en label lisible
     */
    private function beautifyVarName($var) {
        $var = str_replace(['-', '_'], ' ', $var);
        $var = ucfirst($var);
        return $var;
    }

    /**
     * Crée une sauvegarde du CSS custom
     */
    private function createBackup($cssContent) {
        $backupDir = DATA_PLUGIN . 'csseditor/backups/';
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $backupDir . 'backup_' . $timestamp . '.css';
        
        if (file_put_contents($backupFile, $cssContent) !== false) {
            // Limiter le nombre de sauvegardes
            $this->cleanOldBackups();
        }
    }

    /**
     * Nettoie les anciennes sauvegardes
     */
    private function cleanOldBackups() {
        $backupDir = DATA_PLUGIN . 'csseditor/backups/';
        $maxBackups = (int)$this->runPlugin->getConfigVal('backupCount') ?: 5;
        
        $backupFiles = glob($backupDir . 'backup_*.css');
        if (count($backupFiles) > $maxBackups) {
            // Trier par date de modification (plus ancien en premier)
            usort($backupFiles, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Supprimer les plus anciens
            $filesToDelete = array_slice($backupFiles, 0, count($backupFiles) - $maxBackups);
            foreach ($filesToDelete as $file) {
                unlink($file);
            }
        }
    }
} 