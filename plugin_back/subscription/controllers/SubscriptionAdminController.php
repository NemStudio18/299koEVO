<?php

namespace Subscription\Controllers;

use Core\Controllers\AdminController;
use Core\Responses\AdminResponse;
use Subscription\Services\SubscriptionService;
use Subscription\Entities\PromoCodeManager;
use PDO;

defined('ROOT') or exit('Access denied!');

class SubscriptionAdminController extends AdminController
{
    public function dashboard()
    {
        try {
            $service = $this->getSubscriptionService();
            if ($service === null) {
                \Utils\Show::msg('Impossible de se connecter à la base de données. Veuillez configurer la connexion MySQL dans les paramètres du plugin SearchEVO.', 'error');
                $response = new AdminResponse();
                $tpl = $response->createPluginTemplate('subscription', 'admin');
                $tpl->set('stats', []);
                $tpl->set('subscribers', []);
                $tpl->set('payments', []);
                $tpl->set('addons', []);
                $tpl->set('promocodes', []);
                $tpl->set('config', ['enabled' => false]);
                $response->setTitle('Gestion des abonnements');
                $response->addTemplate($tpl);
                return $response;
            }
            
            $stats = $service->getStats();
            $subscribers = $service->getAllSubscribers(10, 0);
            $payments = $service->getPayments(10, 0);
            $addons = $service->getAvailableAddons();
            $config = $service->getConfig();
            
            $promoManager = new PromoCodeManager($this->getPdo(), $this->getTablePrefix());
            $promocodes = $promoManager->getAll();

            $response = new AdminResponse();
            $tpl = $response->createPluginTemplate('subscription', 'admin');
            $tpl->set('stats', $stats);
            $tpl->set('subscribers', $subscribers);
            $tpl->set('payments', $payments);
            $tpl->set('addons', $addons);
            $tpl->set('promocodes', $promocodes);
            $tpl->set('config', $config);
            $response->setTitle('Gestion des abonnements');
            $response->addTemplate($tpl);
            
            return $response;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('SubscriptionAdminController::dashboard - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue: ' . $e->getMessage(), 'error');
            return new AdminResponse();
        }
    }

    /**
     * Sauvegarder la configuration
     * Les tarifs vont dans prices.json et la config Mollie dans mollie.json
     */
    public function saveConfig()
    {
        try {
            $dataDir = DATA_PLUGIN . 'subscription' . DS;
            if (!is_dir($dataDir)) {
                @mkdir($dataDir, 0755, true);
            }

            // Sauvegarder les tarifs dans prices.json
            $pricesFile = $dataDir . 'prices.json';
            // Charger les valeurs existantes pour préserver les titres/descriptions si non modifiés
            $existingPrices = [];
            if (file_exists($pricesFile)) {
                $existingPrices = json_decode(file_get_contents($pricesFile), true) ?: [];
            }
            $prices = [
                'monthly_price' => (float)($_POST['monthly_price'] ?? 9.99),
                'annual_price' => (float)($_POST['annual_price'] ?? (($_POST['monthly_price'] ?? 9.99) * 12)),
                'annual_discount' => (float)($_POST['annual_discount'] ?? 0),
                'monthly_title' => trim($_POST['monthly_title'] ?? $existingPrices['monthly_title'] ?? 'Abonnement mensuel'),
                'monthly_description' => trim($_POST['monthly_description'] ?? $existingPrices['monthly_description'] ?? ''),
                'annual_title' => trim($_POST['annual_title'] ?? $existingPrices['annual_title'] ?? 'Abonnement annuel'),
                'annual_description' => trim($_POST['annual_description'] ?? $existingPrices['annual_description'] ?? ''),
                'enabled' => isset($_POST['subscriptions_enabled']) && $_POST['subscriptions_enabled'] === '1',
                'disabled_message' => trim($_POST['disabled_message'] ?? $existingPrices['disabled_message'] ?? 'Les abonnements sont temporairement indisponibles.'),
            ];
            file_put_contents($pricesFile, json_encode($prices, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // Sauvegarder la config Mollie dans mollie.json
            $mollieFile = $dataDir . 'mollie.json';
            $mollie = [
                'mollie_api_key' => trim($_POST['mollie_api_key'] ?? ''),
                'mollie_sandbox' => isset($_POST['mollie_sandbox']) && $_POST['mollie_sandbox'] === '1',
            ];
            file_put_contents($mollieFile, json_encode($mollie, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            \Utils\Show::msg('Configuration enregistrée avec succès.', 'success');
            $this->core->redirect($this->router->generate('admin-subscription'));
            return;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('SubscriptionAdminController::saveConfig - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            $this->core->redirect($this->router->generate('admin-subscription'));
            return;
        }
    }

    /**
     * Liste des abonnés
     */
    public function subscribers()
    {
        try {
            $service = $this->getSubscriptionService();
            $subscribers = $service->getAllSubscribers(100, 0);

            $response = new AdminResponse();
            $tpl = $response->createPluginTemplate('subscription', 'admin');
            $tpl->set('subscribers', $subscribers);
            $response->setTitle('Liste des abonnés');
            $response->addTemplate($tpl);
            
            return $response;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('SubscriptionAdminController::subscribers - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            return new AdminResponse();
        }
    }

    /**
     * Historique des paiements
     */
    public function payments()
    {
        try {
            $service = $this->getSubscriptionService();
            $payments = $service->getPayments(100, 0);

            $response = new AdminResponse();
            $tpl = $response->createPluginTemplate('subscription', 'admin');
            $tpl->set('payments', $payments);
            $response->setTitle('Historique des paiements');
            $response->addTemplate($tpl);
            
            return $response;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('SubscriptionAdminController::payments - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            return new AdminResponse();
        }
    }

    /**
     * Gestion des add-ons
     */
    public function addons()
    {
        try {
            $service = $this->getSubscriptionService();
            $addons = $service->getAvailableAddons();

            $response = new AdminResponse();
            $tpl = $response->createPluginTemplate('subscription', 'admin');
            $tpl->set('addons', $addons);
            $response->setTitle('Gestion des add-ons');
            $response->addTemplate($tpl);
            
            return $response;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('SubscriptionAdminController::addons - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            return new AdminResponse();
        }
    }

    /**
     * Sauvegarder un add-on
     */
    public function saveAddon()
    {
        try {
            $pdo = $this->getPdo();
            
            $addonId = isset($_POST['addon_id']) ? (int)$_POST['addon_id'] : 0;
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $monthlyPrice = (float)($_POST['monthly_price'] ?? 0);
            $active = isset($_POST['active']) && $_POST['active'] === '1' ? 1 : 0;

            if (empty($name) || $monthlyPrice <= 0) {
                \Utils\Show::msg('Données invalides.', 'error');
                $this->core->redirect($this->router->generate('admin-subscription-addons'));
                return;
            }

            if ($addonId > 0) {
                // Mise à jour
                $sql = "UPDATE {$this->getTablePrefix()}subscription_addons 
                        SET name = ?, description = ?, monthly_price = ?, active = ? 
                        WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $description, $monthlyPrice, $active, $addonId]);
            } else {
                // Création
                $sql = "INSERT INTO {$this->getTablePrefix()}subscription_addons (name, description, monthly_price, active) 
                        VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$name, $description, $monthlyPrice, $active]);
            }

            \Utils\Show::msg('Add-on enregistré avec succès.', 'success');
            $this->core->redirect($this->router->generate('admin-subscription-addons'));
            return;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('SubscriptionAdminController::saveAddon - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            $this->core->redirect($this->router->generate('admin-subscription-addons'));
            return;
        }
    }

    /**
     * Supprimer un add-on
     */
    public function deleteAddon(int $addonId)
    {
        try {
            $pdo = $this->getPdo();
            
            $sql = "DELETE FROM {$this->getTablePrefix()}subscription_addons WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$addonId]);

            \Utils\Show::msg('Add-on supprimé avec succès.', 'success');
            $this->core->redirect($this->router->generate('admin-subscription-addons'));
            return;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('SubscriptionAdminController::deleteAddon - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            $this->core->redirect($this->router->generate('admin-subscription-addons'));
            return;
        }
    }

    /**
     * API ENDPOINTS FOR PROMO CODES
     */
    
    public function savePromoCode()
    {
        try {
            $code = trim($_POST['code'] ?? '');
            $discount = (int)($_POST['discount_percentage'] ?? 0);
            $duration = trim($_POST['duration'] ?? 'infinite');
            $maxUses = isset($_POST['max_uses']) && trim($_POST['max_uses']) !== '' ? (int)$_POST['max_uses'] : null;

            if (empty($code) || $discount <= 0 || $discount > 100) {
                \Utils\Show::msg('Données de code promotionnel invalides.', 'error');
                $this->core->redirect($this->router->generate('admin-subscription'));
                return;
            }

            $promoManager = new PromoCodeManager($this->getPdo(), $this->getTablePrefix());
            
            // Check if code already exists
            if ($promoManager->getByCode($code)) {
                \Utils\Show::msg('Ce code promotionnel existe déjà.', 'error');
                $this->core->redirect($this->router->generate('admin-subscription'));
                return;
            }

            if ($promoManager->create($code, $discount, $duration, $maxUses)) {
                \Utils\Show::msg('Code promotionnel généré avec succès.', 'success');
            } else {
                \Utils\Show::msg('Erreur lors de la génération du code promotionnel.', 'error');
            }

            $this->core->redirect($this->router->generate('admin-subscription'));
            return;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('SubscriptionAdminController::savePromoCode - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            $this->core->redirect($this->router->generate('admin-subscription'));
            return;
        }
    }

    public function togglePromoCode(int $codeId)
    {
        try {
            $promoManager = new PromoCodeManager($this->getPdo(), $this->getTablePrefix());
            
            // On veut récupérer ce code promo pour inverser son statut
            $stmt = $this->getPdo()->prepare("SELECT is_active FROM {$this->getTablePrefix()}subscription_promo_codes WHERE id = ?");
            $stmt->execute([$codeId]);
            $currentStatus = $stmt->fetchColumn();

            if ($currentStatus !== false) {
                $newStatus = !(bool)$currentStatus;
                $promoManager->toggleActive($codeId, $newStatus);
                $msg = $newStatus ? 'Code promotionnel activé.' : 'Code promotionnel désactivé.';
                \Utils\Show::msg($msg, 'success');
            } else {
                \Utils\Show::msg('Code promotionnel introuvable.', 'error');
            }

            $this->core->redirect($this->router->generate('admin-subscription'));
            return;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('SubscriptionAdminController::togglePromoCode - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            $this->core->redirect($this->router->generate('admin-subscription'));
            return;
        }
    }

    public function deletePromoCode(int $codeId)
    {
        try {
            $promoManager = new PromoCodeManager($this->getPdo(), $this->getTablePrefix());
            
            if ($promoManager->delete($codeId)) {
                \Utils\Show::msg('Code promotionnel supprimé avec succès.', 'success');
            } else {
                \Utils\Show::msg('Erreur lors de la suppression du code promotionnel.', 'error');
            }

            $this->core->redirect($this->router->generate('admin-subscription'));
            return;
        } catch (\Exception $e) {
            $this->core->getLogger()->error('SubscriptionAdminController::deletePromoCode - ' . $e->getMessage());
            \Utils\Show::msg('Une erreur est survenue.', 'error');
            $this->core->redirect($this->router->generate('admin-subscription'));
            return;
        }
    }

    private function getDbConfig(): array
    {
        // Essayer d'abord search_conf.json (searchEVO)
        $configFile = DATA_PLUGIN . 'search' . DS . 'search_conf.json';
        if (!file_exists($configFile)) {
            // Fallback sur claim_conf.json si search_conf.json n'existe pas
            $configFile = DATA_PLUGIN . 'claim' . DS . 'claim_conf.json';
            if (!file_exists($configFile)) {
                // Retourner une config par défaut au lieu de lever une exception
                $this->core->getLogger()->warning('SubscriptionAdminController::getDbConfig - Aucun fichier de configuration trouvé, utilisation des valeurs par défaut');
                return [
                    'sql_host' => 'localhost',
                    'sql_db' => 'vivrebourges',
                    'sql_user' => '',
                    'sql_pass' => '',
                ];
            }
        }
        $cfg = json_decode(file_get_contents($configFile), true);
        if (!$cfg || !is_array($cfg)) {
            $this->core->getLogger()->warning('SubscriptionAdminController::getDbConfig - Configuration invalide, utilisation des valeurs par défaut');
            return [
                'sql_host' => 'localhost',
                'sql_db' => 'vivrebourges',
                'sql_user' => '',
                'sql_pass' => '',
            ];
        }
        return $cfg;
    }

    private function getPdo(): ?PDO
    {
        $cfg = $this->getDbConfig();
        
        // Essayer d'abord avec les identifiants de la config
        $host = $cfg['sql_host'] ?? '127.0.0.1';
        $db = $cfg['sql_db'] ?? 'vivrebourges';
        $user = $cfg['sql_user'] ?? '';
        $pass = $cfg['sql_pass'] ?? '';
        
        // Si pas de configuration valide, retourner null
        if (empty($host) || empty($db)) {
            $this->core->getLogger()->warning('SubscriptionAdminController::getPdo - Configuration incomplète (host ou db manquant)');
            return null;
        }
        
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $db);
        
        try {
            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (\PDOException $e) {
            // Si l'authentification échoue, essayer avec les identifiants par défaut
            if (strpos($e->getMessage(), 'Access denied') !== false) {
                $defaultUser = 'my_webapp';
                $defaultPass = 'Production18#';
                $defaultDb = 'vivrebourges';
                
                // Ne réessayer que si l'utilisateur est différent
                if ($user !== $defaultUser || $db !== $defaultDb) {
                    $defaultDsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $defaultDb);
                    try {
                        return new PDO($defaultDsn, $defaultUser, $defaultPass, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        ]);
                    } catch (\PDOException $e2) {
                        // Si ça échoue encore, logger l'erreur et retourner null
                        $this->core->getLogger()->warning('SubscriptionAdminController::getPdo - Erreur de connexion: ' . $e->getMessage());
                        return null;
                    }
                }
            }
            // Pour toute autre erreur, logger et retourner null
            $this->core->getLogger()->warning('SubscriptionAdminController::getPdo - Erreur de connexion: ' . $e->getMessage());
            return null;
        }
    }

    private function getTablePrefix(): string
    {
        return '';
    }

    private function getSubscriptionService(): ?SubscriptionService
    {
        if (!class_exists('\Subscription\Services\SubscriptionService')) {
            require_once PLUGINS . 'subscription/services/SubscriptionService.php';
        }
        $pdo = $this->getPdo();
        if ($pdo === null) {
            return null;
        }
        return new SubscriptionService($this->core);
    }
}

