<?php

namespace Newsletter\Entities;

defined('ROOT') or exit('No direct script access allowed');

class NewsletterManager
{
    public function __construct()
    {
    }

    public function getSubscribers()
    {
        return NewsletterSubscriber::query()->get();
    }

    public function getActiveSubscribers()
    {
        return NewsletterSubscriber::query()->where('active', '=', '1')->get();
    }

    /**
     * @return NewsletterSubscriber|null
     */
    public function findByEmail($email)
    {
        return NewsletterSubscriber::query()->where('email', '=', $email)->first();
    }

    /**
     * @return NewsletterSubscriber|null
     */
    public function findByToken($token)
    {
        return NewsletterSubscriber::query()->where('token', '=', $token)->first();
    }

    public function isUserSubscribed($email)
    {
        $subscriber = $this->findByEmail($email);
        return $subscriber && $subscriber->isActive();
    }

    public function create($id = null)
    {
        if ($id === null) {
            return new NewsletterSubscriber();
        }
        return NewsletterSubscriber::findPK($id);
    }

    public function saveSubscriber($subscriber)
    {
        if ($subscriber->getId() === null) {
            $subscriber->setDate(date('Y-m-d H:i:s'));
        }
        return $subscriber->save();
    }

    public function deleteSubscriber($id)
    {
        $subscriber = NewsletterSubscriber::findPK($id);
        if ($subscriber) {
            return $subscriber->delete();
        }
        return false;
    }
}


