<?php

namespace Annonces\Controllers;

use Annonces\Entities\Annonce;
use Annonces\Entities\AnnonceReservation;
use Core\Auth\User;
use Core\Controllers\PublicController;
use Core\Lang;
use Core\Navigation\AccessPolicyEvaluator;
use Core\Navigation\ConditionContext;
use Core\Plugin\PluginsManager;
use Core\Responses\PublicResponse;
use Core\Security\Csrf;
use Utils\Show;
use Utils\Util;

defined('ROOT') OR exit('Access denied!');

class AnnoncesController extends PublicController
{
    private function pluginCfg(string $key, $default = '')
    {
        $plugin = $this->runPlugin ?? PluginsManager::getInstance()->getPlugin('annonces');
        if ($plugin) {
            $val = $plugin->getConfigVal($key);
            if ($val !== null && $val !== false) {
                return $val;
            }
        }
        return $default;
    }

    public function home()
    {
        $categoryFilter = trim((string) ($_GET['cat'] ?? ''));
        $statusFilter = trim((string) ($_GET['status'] ?? ''));
        $queryFilter = mb_strtolower(trim((string) ($_GET['q'] ?? '')));

        $allItems = Annonce::all();
        $categories = [];
        $items = [];

        foreach ($allItems as $item) {
            if (!$item->isPublic()) {
                continue;
            }
            $cat = trim((string) ($item->attributes['category'] ?? ''));
            if ($cat !== '') {
                $categories[$cat] = $cat;
            }
        }
        ksort($categories);

        // Tri du plus récent / mis en avant au plus ancien
        usort($allItems, static function (Annonce $a, Annonce $b): int {
            $fa = (int) ($a->attributes['is_featured'] ?? 0);
            $fb = (int) ($b->attributes['is_featured'] ?? 0);
            if ($fa !== $fb) {
                return $fb <=> $fa;
            }
            return strcmp((string) ($b->attributes['created_at'] ?? ''), (string) ($a->attributes['created_at'] ?? ''));
        });

        foreach ($allItems as $item) {
            if (!$item->isPublic()) {
                continue;
            }
            $attrs = $item->attributes;
            if ($categoryFilter !== '' && ($attrs['category'] ?? '') !== $categoryFilter) {
                continue;
            }
            if ($statusFilter !== '' && ($attrs['status'] ?? 'available') !== $statusFilter) {
                continue;
            }
            if ($queryFilter !== '') {
                $haystack = mb_strtolower(
                    ($attrs['title'] ?? '') . ' ' .
                    strip_tags((string) ($attrs['description'] ?? '')) . ' ' .
                    ($attrs['location'] ?? '') . ' ' .
                    ($attrs['category'] ?? '')
                );
                if (mb_strpos($haystack, $queryFilter) === false) {
                    continue;
                }
            }

            $descPlain = trim(strip_tags((string) ($attrs['description'] ?? '')));
            $excerpt = mb_strlen($descPlain) > 130 ? mb_substr($descPlain, 0, 127) . '…' : $descPlain;
            $images = $item->getImages();

            $items[] = [
                'id'             => $item->getId(),
                'title'          => (string) ($attrs['title'] ?? ''),
                'category'       => (string) ($attrs['category'] ?? ''),
                'price_label'    => trim((string) ($attrs['price_label'] ?? '')) ?: 'Prix non précisé',
                'location'       => (string) ($attrs['location'] ?? ''),
                'item_condition' => (string) ($attrs['item_condition'] ?? ''),
                'image'          => $item->getMainImage(),
                'images_count'   => count($images),
                'excerpt'        => $excerpt,
                'status'         => (string) ($attrs['status'] ?? 'available'),
                'status_label'   => $item->getStatusLabel(),
                'is_available'   => $item->isAvailable(),
                'is_reserved'    => $item->isReserved(),
                'is_featured'    => !empty($attrs['is_featured']),
                'created_at'     => (string) ($attrs['created_at'] ?? ''),
                'url'            => $item->getPublicUrl(),
            ];
        }

        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('annonces', 'public');
        $response->setTitle($this->pluginCfg('label', 'Petites Annonces') ?: 'Petites Annonces');
        $tpl->set('items', $items);
        $tpl->set('categories', array_values($categories));
        $tpl->set('categoryFilter', $categoryFilter);
        $tpl->set('statusFilter', $statusFilter);
        $tpl->set('queryFilter', trim((string) ($_GET['q'] ?? '')));
        $tpl->set('introText', (string) $this->pluginCfg('introText', ''));
        $tpl->set('listUrl', $this->router->generate('annonces-home'));
        $response->addTemplate($tpl);
        return $response;
    }

    public function view(string $id, string $slug = '')
    {
        $item = Annonce::findPK($id);
        if ($item === null || !$item->isPublic()) {
            $this->core->error404();
            return null;
        }

        $antispamField = '';
        $requireAntispam = (string) $this->pluginCfg('requireAntispam', '1') === '1';
        if ($requireAntispam && PluginsManager::isActivePlugin('antispam')) {
            $hookRes = $this->core->callHook('antispam:get-captcha', ['field_html' => '']);
            $antispamField = (string) ($hookRes['field_html'] ?? '');
        }

        $attrs = $item->attributes;
        $images = $item->getImages();
        $annonce = [
            'id'             => $item->getId(),
            'title'          => (string) ($attrs['title'] ?? ''),
            'category'       => (string) ($attrs['category'] ?? ''),
            'price_label'    => trim((string) ($attrs['price_label'] ?? '')) ?: 'Prix non précisé',
            'location'       => (string) ($attrs['location'] ?? ''),
            'item_condition' => (string) ($attrs['item_condition'] ?? ''),
            'image'          => $item->getMainImage(),
            'images'         => $images,
            'images_count'   => count($images),
            'description'    => (string) ($attrs['description'] ?? ''),
            'contact_info'   => (string) ($attrs['contact_info'] ?? ''),
            'status'         => (string) ($attrs['status'] ?? 'available'),
            'status_label'   => $item->getStatusLabel(),
            'is_available'   => $item->isAvailable(),
            'is_reserved'    => $item->isReserved(),
            'is_closed'      => (($attrs['status'] ?? '') === 'closed'),
            'created_at'     => (string) ($attrs['created_at'] ?? ''),
            'url'            => $item->getPublicUrl(),
        ];

        $response = new PublicResponse();
        $tpl = $response->createPluginTemplate('annonces', 'view');
        $response->setTitle($annonce['title']);
        $tpl->set('annonce', $annonce);
        $tpl->set('backUrl', $this->router->generate('annonces-home'));
        $tpl->set('reserveUrl', $this->router->generate('annonces-reserve', ['id' => $item->getId()]));
        $tpl->set('antispam', $antispamField !== '');
        $tpl->set('antispamField', $antispamField);
        $tpl->set('reservationNotice', (string) $this->pluginCfg('reservationNotice', ''));
        $tpl->set('_csrfToken', Csrf::token());
        $response->addTemplate($tpl);
        return $response;
    }

    public function reserve(string $id)
    {
        $item = Annonce::findPK($id);
        if ($item === null || !$item->isPublic()) {
            $this->core->error404();
            return null;
        }

        $redirectUrl = $item->getPublicUrl();

        if (!Csrf::validate($_POST['_csrf'] ?? null, false)) {
            Show::msg(Lang::get('core-not-authorized') ?: 'Action non autorisée.', 'error');
            $this->core->redirect($redirectUrl);
            return null;
        }

        if (($_POST['_hp_website'] ?? '') !== '') {
            $this->core->redirect($redirectUrl);
            return null;
        }

        $requireAntispam = (string) $this->pluginCfg('requireAntispam', '1') === '1';
        if ($requireAntispam && PluginsManager::isActivePlugin('antispam')) {
            $res = $this->core->callHook('antispam:validate', ['is_valid' => true]);
            if (empty($res['is_valid'])) {
                Show::msg(Lang::get('antispam.invalid-captcha') ?: 'Antispam invalide, veuillez réessayer.', 'error');
                $this->core->redirect($redirectUrl);
                return null;
            }
        }

        $onReserveMode = (string) $this->pluginCfg('onReserveStatus', 'mark_reserved');
        if ($onReserveMode === '') {
            $onReserveMode = 'mark_reserved';
        }

        if (!$item->isAvailable()) {
            Show::msg(Lang::get('annonces.reserve-unavailable') ?: "Cette annonce n'est plus disponible à la réservation.", 'error');
            $this->core->redirect($redirectUrl);
            return null;
        }

        if ($onReserveMode === 'mark_reserved') {
            $itemId = (string) $item->getId();
            foreach (AnnonceReservation::all() as $existing) {
                $existingAid = (string) ($existing->attributes['annonce_id'] ?? '');
                $existingStatus = (string) ($existing->attributes['status'] ?? 'pending');
                if ($existingAid === $itemId && in_array($existingStatus, ['pending', 'confirmed'], true)) {
                    $item->status = 'reserved';
                    $item->updated_at = date('Y-m-d H:i:s');
                    $item->save();
                    Show::msg(Lang::get('annonces.reserve-unavailable') ?: "Cette annonce n'est plus disponible à la réservation.", 'error');
                    $this->core->redirect($redirectUrl);
                    return null;
                }
            }
        }

        $name = trim((string) ($_POST['requester_name'] ?? ''));
        $email = filter_var(trim((string) ($_POST['requester_email'] ?? '')), FILTER_VALIDATE_EMAIL) ?: '';
        $phone = trim((string) ($_POST['requester_phone'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        if ($name === '' || $email === '' || $message === '') {
            Show::msg(Lang::get('annonces.reserve-invalid') ?: 'Veuillez renseigner votre nom, une adresse email valide et un message.', 'error');
            $this->core->redirect($redirectUrl);
            return null;
        }

        $reservation = new AnnonceReservation();
        $reservation->annonce_id = (string) $item->getId();
        $reservation->annonce_title = (string) ($item->attributes['title'] ?? '');
        $reservation->requester_name = $name;
        $reservation->requester_email = $email;
        $reservation->requester_phone = $phone;
        $reservation->message = $message;
        $reservation->status = 'pending';
        $reservation->created_at = date('Y-m-d H:i:s');
        $reservation->save();

        // Par défaut, bloquer immédiatement l'annonce dès réception d'une demande
        if ($onReserveMode === 'mark_reserved') {
            $item->status = 'reserved';
            $item->updated_at = date('Y-m-d H:i:s');
            $item->save();
        }

        $recipients = $this->resolveAdminRecipients();
        $annonceTitle = (string) ($item->attributes['title'] ?? 'Annonce');
        $siteName = (string) ($this->core->getConfigVal('siteName') ?? 'Site');

        // Notification email vers l'administrateur
        if (!empty($recipients['emails'])) {
            $host = parse_url((string) ($this->core->getConfigVal('siteUrl') ?? ''), PHP_URL_HOST)
                ?: ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $host = preg_replace('/[^a-zA-Z0-9.-]/', '', (string) $host) ?: 'localhost';
            $from = 'no-reply@' . $host;
            $adminUrl = rtrim((string) ($this->core->getConfigVal('siteUrl') ?? ''), '/') . '/admin/annonces?tab=reservations';
            $subject = '[' . $siteName . '] Nouvelle réservation : ' . $annonceTitle;
            $body = "Nouvelle demande de réservation (sans paiement en ligne) pour l'annonce : {$annonceTitle}\n\n"
                . "Demandeur : {$name}\n"
                . "Email : {$email}\n"
                . "Téléphone : " . ($phone !== '' ? $phone : 'Non renseigné') . "\n\n"
                . "Message :\n{$message}\n\n"
                . "Gérer les réservations : {$adminUrl}\n";
            foreach ($recipients['emails'] as $toEmail) {
                try {
                    Util::sendEmail($from, $email, $toEmail, $subject, $body);
                } catch (\Throwable $e) {
                    $this->core->getLogger()->error('Annonces email notification failed: ' . $e->getMessage());
                }
            }
        }

        // Notification interne via le plugin notifications s'il est actif
        if (PluginsManager::isActivePlugin('notifications') && !empty($recipients['userIds'])) {
            try {
                $this->core->callHook('notifications:send', [
                    'user_id'       => $recipients['userIds'],
                    'type'          => 'annonces.reservation',
                    'source_plugin' => 'annonces',
                    'data'          => [
                        'key'            => 'annonces.reservation',
                        'params'         => [$name, $annonceTitle],
                        'fallback_title' => 'Nouvelle réservation de %s pour « %s »',
                        'url'            => '/admin/annonces?tab=reservations',
                        'actor'          => [
                            'name'  => $name,
                            'email' => $email,
                        ],
                        'entity'         => [
                            'type' => 'annonce',
                            'id'   => (string) $item->getId(),
                        ],
                    ],
                    'external_id' => 'annonces:reservation:' . (string) $reservation->getId(),
                ]);
            } catch (\Throwable $e) {
                $this->core->getLogger()->error('Annonces hook notifications:send failed: ' . $e->getMessage());
            }
        }

        Show::msg(Lang::get('annonces.reserve-success') ?: 'Votre demande de réservation a bien été enregistrée.', 'success');
        $this->core->redirect($redirectUrl);
        return null;
    }

    /**
     * Résout les emails et IDs administrateurs destinataires des alertes de réservation.
     *
     * @return array{emails: string[], userIds: int[]}
     */
    private function resolveAdminRecipients(): array
    {
        $emails = [];
        $userIds = [];

        $cfgEmail = trim((string) $this->pluginCfg('notifyEmail', ''));
        if ($cfgEmail !== '' && filter_var($cfgEmail, FILTER_VALIDATE_EMAIL)) {
            $emails[$cfgEmail] = $cfgEmail;
        }

        try {
            $evaluator = AccessPolicyEvaluator::getInstance();
            foreach (User::all() as $user) {
                if (!$evaluator->isAdmin(new ConditionContext($user))) {
                    continue;
                }
                $uid = (int) ($user->id ?? ($user->attributes['id'] ?? 0));
                if ($uid > 0) {
                    $userIds[] = $uid;
                }
                $uEmail = trim((string) ($user->email ?? ($user->attributes['email'] ?? '')));
                if (empty($emails) && $uEmail !== '' && filter_var($uEmail, FILTER_VALIDATE_EMAIL)) {
                    $emails[$uEmail] = $uEmail;
                }
            }
        } catch (\Throwable $e) {
            // Fallback géré ci-dessous
        }

        if (empty($emails)) {
            $siteEmail = trim((string) ($this->core->getConfigVal('siteEmail') ?? ''));
            if ($siteEmail !== '' && filter_var($siteEmail, FILTER_VALIDATE_EMAIL)) {
                $emails[$siteEmail] = $siteEmail;
            }
        }

        if (empty($userIds)) {
            $userIds = [1];
        }

        return [
            'emails'  => array_values($emails),
            'userIds' => array_values(array_unique($userIds)),
        ];
    }
}
