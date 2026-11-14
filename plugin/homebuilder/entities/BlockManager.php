<?php

namespace HomeBuilder\Entities;

use Utils\Util;

defined('ROOT') or exit('Access denied!');

class BlockManager
{
    private $configFile;
    private $blocks = [];

    public function __construct()
    {
        $this->configFile = DATA_PLUGIN . 'homebuilder/blocks.json';
        if (!file_exists($this->configFile)) {
            $default = PLUGINS . 'homebuilder/param/blocks.default.json';
            if (file_exists($default)) {
                @copy($default, $this->configFile);
            } else {
                Util::writeJsonFile($this->configFile, ['blocks' => []]);
            }
        }
        $this->loadBlocks();
    }

    /**
     * Charge les blocs depuis le fichier de configuration
     */
    private function loadBlocks()
    {
        $config = Util::readJsonFile($this->configFile, true);
        $this->blocks = [];
        
        
        if (isset($config['blocks']) && is_array($config['blocks'])) {
            foreach ($config['blocks'] as $blockData) {
                $this->blocks[] = new Block($blockData);
            }
        }
        
    }

    /**
     * Sauvegarde les blocs dans le fichier de configuration
     */
    private function saveBlocks()
    {
        // Debug: log avant sauvegarde
        foreach ($this->blocks as $block) {
        }
        
        // Créer la structure de base si le fichier n'existe pas
        $config = ['blocks' => []];
        
        // Essayer de lire le fichier existant
        $existingConfig = Util::readJsonFile($this->configFile, true);
        if (is_array($existingConfig)) {
            $config = $existingConfig;
        }
        
        // Mettre à jour les blocs
        $config['blocks'] = [];
        
        foreach ($this->blocks as $block) {
            $config['blocks'][] = $block->toArray();
        }
        
        // Debug: log avant écriture
        
        $result = Util::writeJsonFile($this->configFile, $config);
        
        return $result;
    }

    /**
     * Retourne tous les blocs actifs triés par ordre
     */
    public function getActiveBlocks()
    {
        $activeBlocks = array_filter($this->blocks, function($block) {
            return $block->isActive();
        });
        
        usort($activeBlocks, function($a, $b) {
            return $a->getOrder() - $b->getOrder();
        });
        
        return $activeBlocks;
    }

    /**
     * Retourne tous les blocs (actifs et inactifs)
     */
    public function getAllBlocks()
    {
        usort($this->blocks, function($a, $b) {
            return $a->getOrder() - $b->getOrder();
        });
        
        return $this->blocks;
    }

    /**
     * Retourne un bloc par son ID
     */
    public function getBlock($id)
    {
        foreach ($this->blocks as $block) {
            if ($block->getId() === $id) {
                return $block;
            }
        }
        return null;
    }

    /**
     * Ajoute un nouveau bloc
     */
    public function addBlock($blockData)
    {
        $block = new Block($blockData);
        $this->blocks[] = $block;
        return $this->saveBlocks();
    }

    /**
     * Met à jour un bloc existant
     */
    public function updateBlock($id, $blockData)
    {
        // Debug: Afficher les données reçues
        
        foreach ($this->blocks as $key => $block) {
            if ($block->getId() === $id) {
                $this->blocks[$key] = new Block($blockData);
                
                // Debug: Vérifier les styles après création
                $newBlock = $this->blocks[$key];
                
                return $this->saveBlocks();
            }
        }
        return false;
    }

    /**
     * Supprime un bloc
     */
    public function deleteBlock($id)
    {
        // Debug: Afficher l'ID à supprimer
        
        foreach ($this->blocks as $key => $block) {
            if ($block->getId() === $id) {
                unset($this->blocks[$key]);
                $this->blocks = array_values($this->blocks); // Réindexer
                return $this->saveBlocks();
            }
        }
        
        return false;
    }

    /**
     * Active/désactive un bloc
     */
    public function toggleBlock($id)
    {
        foreach ($this->blocks as $block) {
            if ($block->getId() === $id) {
                $block->setActive(!$block->isActive());
                return $this->saveBlocks();
            }
        }
        return false;
    }

    /**
     * Change l'ordre des blocs
     */
    public function reorderBlocks($blockIds)
    {
        // Mettre à jour l'ordre des blocs spécifiés
        foreach ($blockIds as $index => $id) {
            $block = $this->getBlock($id);
            if ($block) {
                $block->setOrder($index + 1);
            }
        }
        
        // Préserver tous les blocs existants, pas seulement ceux dans blockIds
        return $this->saveBlocks();
    }

    /**
     * Génère l'ID unique pour un nouveau bloc
     */
    public function generateBlockId()
    {
        $existingIds = [];
        foreach ($this->blocks as $block) {
            $existingIds[] = $block->getId();
        }
        
        $counter = 1;
        $id = 'block_' . $counter;
        
        while (in_array($id, $existingIds)) {
            $counter++;
            $id = 'block_' . $counter;
        }
        
        return $id;
    }

    /**
     * Retourne les blocs parents (sans parent)
     */
    public function getParentBlocks()
    {
        return array_filter($this->blocks, function($block) {
            return $block->getParentId() === null;
        });
    }

    /**
     * Retourne les blocs enfants d'un bloc parent
     */
    public function getChildBlocks($parentId)
    {
        return array_filter($this->blocks, function($block) use ($parentId) {
            return $block->getParentId() === $parentId;
        });
    }

    /**
     * Retourne tous les blocs disponibles comme parents potentiels
     */
    public function getAvailableParents($excludeId = null)
    {
        $parents = [];
        foreach ($this->blocks as $block) {
            if ($block->isContainer() && $block->getId() !== $excludeId) {
                $parents[] = [
                    'id' => $block->getId(),
                    'title' => $block->getTitle(),
                    'type' => $block->getType()
                ];
            }
        }
        return $parents;
    }

    /**
     * Vérifie si un bloc peut être déplacé dans un autre bloc
     */
    public function canMoveBlock($blockId, $newParentId)
    {
        // Un bloc ne peut pas être son propre parent
        if ($blockId === $newParentId) {
            return false;
        }
        
        // Vérifier qu'on ne crée pas de cycle
        $currentParent = $newParentId;
        while ($currentParent) {
            $parentBlock = $this->getBlock($currentParent);
            if (!$parentBlock) {
                break;
            }
            if ($parentBlock->getParentId() === $blockId) {
                return false; // Cycle détecté
            }
            $currentParent = $parentBlock->getParentId();
        }
        
        return true;
    }

    /**
     * Déplace un bloc dans un autre bloc
     */
    public function moveBlock($blockId, $newParentId)
    {
        if (!$this->canMoveBlock($blockId, $newParentId)) {
            return false;
        }
        
        $block = $this->getBlock($blockId);
        if ($block) {
            $block->setParentId($newParentId);
            return $this->saveBlocks();
        }
        
        return false;
    }

    /**
     * Retourne les types de blocs disponibles
     */
    public function getAvailableBlockTypes()
    {
        return [
            'text' => 'Texte simple',
            'button' => 'Bouton',
            'table' => 'Tableau',
            'form' => 'Formulaire',
            'image' => 'Image',
            'html' => 'HTML personnalisé',
            'container' => 'Conteneur',
            'latest_articles' => 'Derniers articles',
            'latest_sondages' => 'Sondages actifs',
            'guestbook_cta' => 'Bouton livre d\'or'
        ];
    }

    /**
     * Retourne les couleurs disponibles pour les boutons
     */
    public function getAvailableColors()
    {
        return [
            'primary' => 'Primaire',
            'secondary' => 'Secondaire',
            'success' => 'Succès',
            'danger' => 'Danger',
            'warning' => 'Attention',
            'info' => 'Information',
            'light' => 'Clair',
            'dark' => 'Sombre'
        ];
    }

    /**
     * Retourne les tailles disponibles pour les boutons
     */
    public function getAvailableSizes()
    {
        return [
            'small' => 'Petit',
            'medium' => 'Moyen',
            'large' => 'Grand'
        ];
    }

    /**
     * Valide les données d'un bloc
     */
    public function validateBlockData($data)
    {
        $errors = [];
        
        if (empty($data['id'])) {
            $errors[] = 'ID du bloc requis';
        }
        
        if (empty($data['type'])) {
            $errors[] = 'Type de bloc requis';
        }
        
        if (!in_array($data['type'], array_keys($this->getAvailableBlockTypes()))) {
            $errors[] = 'Type de bloc invalide';
        }
        
        if (empty($data['title'])) {
            $errors[] = 'Titre requis';
        }
        
        return $errors;
    }
} 
