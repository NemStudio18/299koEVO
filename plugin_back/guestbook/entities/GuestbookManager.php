<?php
namespace Guestbook\Entities;

use Guestbook\Entities\GuestbookEntry;
use Utils\Util;

/**
 * @copyright (C) 2024, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('No direct script access allowed');

class GuestbookManager
{
    private $items = [];

    public function __construct()
    {
        $this->loadItems();
    }

    private function loadItems()
    {
        $items = GuestbookEntry::query()->orderBy('date', 'DESC')->get();
        foreach ($items as $item) {
            /** @var GuestbookEntry $item */
        }
        $this->items = $items;
    }

    /**
     * @return GuestbookEntry[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return GuestbookEntry[]
     */
    public function getApprovedItems()
    {
        return array_filter($this->items, function($item) {
            return $item->isApproved();
        });
    }

    /**
     * @return GuestbookEntry[]
     */
    public function getPendingItems()
    {
        return array_filter($this->items, function($item) {
            return !$item->isApproved();
        });
    }

    /**
     * @param mixed $id
     * @return GuestbookEntry|null
     */
    public function create($id)
    {
        return GuestbookEntry::findPK($id);
    }

    /**
     * @param GuestbookEntry $obj
     * @return bool
     */
    public function saveEntry($obj)
    {
        $saved = $obj->save();
        if ($saved) {
            $this->loadItems();
        }
        return $saved;
    }

    /**
     * @param GuestbookEntry $obj
     * @return bool
     */
    public function delEntry($obj)
    {
        if ($obj->delete()) {
            $this->loadItems();
            return true;
        }
        return false;
    }

    public function count()
    {
        return GuestbookEntry::query()->count();
    }

    public function countApproved()
    {
        return GuestbookEntry::query()->where('approved', '=', 1)->count();
    }

    public function countPending()
    {
        return GuestbookEntry::query()->where('approved', '=', 0)->count();
    }
}
