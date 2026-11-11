<?php

namespace Docs\Entities;

use Content\Categories\Category;
use Core\Responses\AdminResponse;
use Utils\Util;

/**
 * @copyright (C) 2023, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * @author Maxime Blanc <maximeblanc@flexcb.fr>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') or exit('Access denied!');

/**
 * Catégorie Wiki
 * 
 * Classe représentant une catégorie du plugin Wiki
 */
class WikiCategory extends Category
{

    protected string $pluginId = 'docs';
    protected string $name = 'categories';
    protected bool $nested = true;
    protected bool $chooseMany = true;
    public int $position = 0;

    /**
     * Constructeur de la catégorie Wiki
     * 
     * @param int $id ID de la catégorie
     */
    public function __construct(int $id = -1) {
        parent::__construct($id);
        if ($this->id !== -1 && file_exists(self::$file)) {
            $metas = Util::readJsonFile(self::$file);
            if (is_array($metas) && isset($metas[$this->id])) {
                $this->position = $metas[$this->id]['position'] ?? 0;
            }
        }
    }

    /**
     * Générer l'affichage de la catégorie dans une liste
     * 
     * @return string HTML de la catégorie
     */
    public function outputAsList() {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('docs', 'categories-list');
        
        $tpl->set('catDisplay', 'sub');
        $tpl->set('this', $this);
        
        return $tpl->output();
    }

    /**
     * Générer l'affichage de la catégorie dans un sélecteur de parent
     * 
     * @param int $selectedParentId ID du parent sélectionné
     * @return string HTML de la catégorie
     */
    public function outputAsParentSelect($selectedParentId) {
        $response = new AdminResponse();
        $tpl = $response->createPluginTemplate('docs', 'selectParentCategory');
        
        $tpl->set('catDisplay', 'sub');
        $tpl->set('this', $this);
        $tpl->set('selectedParentId', $selectedParentId);
        
        return $tpl->output();
    }

    /**
     * Calculer le nombre total d'éléments d'une catégorie incluant tous les enfants
     * 
     * @return int Nombre total d'éléments
     */
    public function getTotalItemsCountRecursive() {
        $total = count($this->items);
        
        if ($this->hasChildren) {
            foreach ($this->children as $child) {
                $total += $child->getTotalItemsCountRecursive();
            }
        }
        
        return $total;
    }

    /**
     * Définir la position de la catégorie
     * 
     * @param int $position Position de la catégorie
     * @return void
     */
    public function setPosition(int $position) {
        $this->position = $position;
    }

    /**
     * Obtenir la position de la catégorie
     * 
     * @return int Position de la catégorie
     */
    public function getPosition(): int {
        return $this->position;
    }

}