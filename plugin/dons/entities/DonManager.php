<?php

namespace Dons\Entities;

use Dons\Entities\Don;

/**
 * @copyright (C) 2025, 299Ko
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPLv3
 * @author Maxence Cauderlier <mx.koder@gmail.com>
 * 
 * @package 299Ko https://github.com/299Ko/299ko
 */
defined('ROOT') OR exit('No direct script access allowed');

class DonManager
{
    public function __construct()
    {
    }

    public function getDons()
    {
        return Don::query()->get();
    }

    public function getCompletedDons()
    {
        return Don::query()->where('status', '=', 'completed')->get();
    }

    public function getTotalAmount()
    {
        $dons = $this->getCompletedDons();
        $total = 0;
        foreach ($dons as $don) {
            $total += $don->getAmount();
        }
        return $total;
    }

    public function create($id = null)
    {
        if ($id === null) {
            return new Don();
        }
        return Don::findPK($id);
    }

    public function findByTransactionId($transactionId)
    {
        return Don::query()->where('transactionId', '=', $transactionId)->first();
    }

    public function saveDon($don)
    {
        if ($don->getId() === null) {
            $don->date = date('Y-m-d H:i:s');
        }
        return $don->save();
    }

    public function getDailyDonations($days = 30)
    {
        $dons = $this->getCompletedDons();
        $dailyTotals = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dailyTotals[$date] = 0;
        }
        
        foreach ($dons as $don) {
            $donDate = date('Y-m-d', strtotime($don->date));
            if (isset($dailyTotals[$donDate])) {
                $dailyTotals[$donDate] += (float)$don->amount;
            }
        }
        
        $result = [];
        foreach ($dailyTotals as $date => $amount) {
            $result[] = [
                'date' => $date,
                'amount' => $amount
            ];
        }
        
        return $result;
    }
}



