<?php
/**
 * Script de nettoyage de l'historique des pages wiki
 * Supprime les doublons et corrige les numéros de version
 */

define('ROOT', dirname(__DIR__) . '/');

require_once ROOT . 'common/common.php';

use Utils\Util;

$historyFile = DATA_PLUGIN . 'docs/history.json';

if (!file_exists($historyFile)) {
    echo "Fichier d'historique non trouvé.\n";
    exit;
}

$history = Util::readJsonFile($historyFile);

if (!is_array($history)) {
    echo "Erreur: Impossible de lire l'historique.\n";
    exit;
}

echo "Nettoyage de l'historique...\n";
echo "Entrées avant nettoyage: " . count($history) . "\n";

// Grouper par pageId
$pagesHistory = [];
foreach ($history as $entry) {
    $pageId = $entry['pageId'];
    if (!isset($pagesHistory[$pageId])) {
        $pagesHistory[$pageId] = [];
    }
    $pagesHistory[$pageId][] = $entry;
}

echo "Pages trouvées: " . count($pagesHistory) . "\n";

// Pour chaque page, corriger les versions
$cleanedHistory = [];
foreach ($pagesHistory as $pageId => $entries) {
    echo "Traitement page $pageId: " . count($entries) . " entrées\n";
    
    // Trier par date de modification
    usort($entries, function($a, $b) {
        return strtotime($a['modifiedAt']) - strtotime($b['modifiedAt']);
    });
    
    // Renumérotage des versions
    $version = 1;
    foreach ($entries as $entry) {
        $oldVersion = $entry['version'];
        $entry['version'] = $version;
        echo "  Page $pageId: version $oldVersion -> $version\n";
        $cleanedHistory[] = $entry;
        $version++;
    }
}

echo "Entrées après nettoyage: " . count($cleanedHistory) . "\n";

// Sauvegarder l'historique nettoyé
if (Util::writeJsonFile($historyFile, $cleanedHistory)) {
    echo "Historique nettoyé avec succès !\n";
    echo "Versions corrigées pour chaque page.\n";
} else {
    echo "Erreur lors de la sauvegarde de l'historique nettoyé.\n";
} 