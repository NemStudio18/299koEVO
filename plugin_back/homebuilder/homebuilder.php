<?php

/**
 * Plugin HomeBuilder – fusion Home + HomePageConstructor
 *
 * @copyright (C)
 */

use Utils\Util;

defined('ROOT') or exit('Access denied!');

require_once PLUGINS . 'homebuilder/entities/Block.php';
require_once PLUGINS . 'homebuilder/entities/BlockManager.php';
foreach (glob(PLUGINS . 'homebuilder/controllers/*.php') as $controllerFile) {
    require_once $controllerFile;
}

/**
 * Installation du plugin HomeBuilder.
 */
function homebuilderInstall(): void
{
    $dataDir = DATA_PLUGIN . 'homebuilder/';
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0755, true);
    }

    $defaultBlocksFile = PLUGINS . 'homebuilder/param/blocks.default.json';
    $targetFile = $dataDir . 'blocks.json';

    if (!file_exists($targetFile) && file_exists($defaultBlocksFile)) {
        copy($defaultBlocksFile, $targetFile);
    }

    // Migration automatique depuis l'ancien plugin HomePageConstructor si besoin
    $legacyFile = DATA_PLUGIN . 'homepageconstructor/blocks.json';
    if (file_exists($legacyFile) && !file_exists($targetFile)) {
        copy($legacyFile, $targetFile);
    }
}

/**
 * Hook exécuté avant l'initialisation du plugin.
 */
function homebuilderBeforeRunPlugin(): void
{
    // Rien de spécifique pour l'instant.
}

/**
 * Désinstallation : suppression des données générées.
 */
function homebuilderUninstall(): void
{
    $dir = DATA_PLUGIN . 'homebuilder';
    if (!is_dir($dir)) {
        return;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileInfo) {
        $action = $fileInfo->isDir() ? 'rmdir' : 'unlink';
        $action($fileInfo->getRealPath());
    }

    rmdir($dir);
}