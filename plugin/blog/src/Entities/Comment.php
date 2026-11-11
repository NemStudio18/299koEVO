<?php

namespace Blog\Entities;

use DateTime;
use Template\Template;

defined('ROOT') or exit('Access denied!');

class Comment
{

    private $id;
    private $idNews;
    private $author;
    private $authorEmail;
    private $authorWebsite;
    private $date;
    private $content;

    /**
     * Array with ID of children comments
     */
    public $repliesId;

    public $replies = [];

    public function __construct($val = array())
    {
        if (count($val) > 0) {
            $this->id = $val['id'];
            $this->idNews = $val['idNews'];
            $this->content = $val['content'];
            $this->date = $val['date'];
            $this->author = $val['author'];
            $this->authorEmail = $val['authorEmail'];
            $this->authorWebsite = $val['authorWebsite'];
            $this->repliesId = $val['replies'] ?? [];
        } else {
            $this->id = time();
        }
    }

    public function hasReplies(): bool
    {
        return (!empty($this->replies));
    }

    public function show()
    {
        // Attention: Template file path might need adjustment if logic changes
        $tpl = new Template(PLUGINS . 'blog/template/comment.tpl');
        $tpl->set('comment', $this);
        // Pass formatted date directly to template to avoid "util::getNaturalDate" string issue
        $tpl->set('formattedDate', \Utils\Util::getNaturalDate($this->date));
        echo $tpl->output();
    }

    public function setId($val)
    {
        $this->id = intval($val);
    }

    public function setIdNews($val)
    {
        $this->idNews = intval($val);
    }

    public function setAuthor($val)
    {
        $this->author = trim(htmlEntities($val, ENT_QUOTES));
    }

    public function setAuthorEmail($val)
    {
        $this->authorEmail = trim(htmlEntities($val, ENT_QUOTES));
    }

    public function setAuthorWebsite($val)
    {
        $this->authorWebsite = trim(htmlEntities($val, ENT_QUOTES));
    }

    public function setDate($val)
    {
        $val = trim($val);
        if ($val == '')
            $val = new DateTime();
        $this->date = $val->getTimestamp();
    }

    public function setContent($val)
    {
        $this->content = trim(htmlEntities($val, ENT_QUOTES));
    }

    public function getId()
    {
        return $this->id;
    }

    public function getIdNews()
    {
        return $this->idNews;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function getAuthorEmail()
    {
        return $this->authorEmail;
    }

    public function getAuthorWebsite()
    {
        return $this->authorWebsite;
    }

    public function getAuthorAvatar()
    {
        // Try to find user by email to get their custom avatar
        if (!empty($this->authorEmail)) {
            // Check if User class exists logic
            if (class_exists('Core\Auth\User')) {
                $user = \Core\Auth\User::find('email', $this->authorEmail);
                if ($user && !empty($user->avatar)) {
                    $avatar = trim($user->avatar);
                    // Validate avatar URL (same logic as ForumController)
                    if ($this->isValidAvatarUrl($avatar)) {
                        return $avatar;
                    }
                }
            }
        }

        // If no valid avatar found (and we don't want Libravatar fallback if user requested "CSS avatar"), 
        // we return null to trigger the CSS fallback in the template.
        // However, if we want Libravatar as a secondary option before CSS, we could keep it.
        // The user said: "if the User don't have avatar detected ... then use the avatar CSS generated".
        // This suggests skipping Libravatar if it's not the user's explicit avatar.
        // But for consistency with standard behavior, usually Libravatar IS the detected avatar.
        // Let's assume Libravatar is NOT wanted if user specifically asked for "CSS generated avatar" when "no avatar detected".
        // Or maybe Libravatar counts as "detected". 
        // Logic in ForumController: 
        // if (isset($topicData['author']['avatar'])) ... check validity ... else null.
        // Forum user enrichment likely pulls from MySQL.

        // Let's check if Libravatar is used in Forum?
        // ForumController doesn't seem to use Libravatar explicitly, it relies on UserEnrichmentService.
        // If UserEnrichmentService uses Libravatar, then we should too.
        // But the user's request "use the avatar CSS generated" implies we should FALLBACK to it.
        // So return NULL here if no custom avatar is found.

        return null;
    }

    public function hasValidAvatar(): bool
    {
        $avatar = $this->getAuthorAvatar();
        return !empty($avatar);
    }

    public function getAvatarColor(): string
    {
        // Use author name for color generation
        return $this->generateAvatarColor($this->author ?? 'Anonymous');
    }

    public function getAvatarInitials(): string
    {
        return $this->generateAvatarInitials($this->author ?? 'Anonymous');
    }

    private function isValidAvatarUrl(?string $avatarUrl): bool
    {
        if (empty($avatarUrl)) {
            return false;
        }

        $normalized = trim($avatarUrl);
        $normalizedLower = strtolower($normalized);
        $invalidValues = ['/avatar', 'avatar', '/', ''];
        $invalidPatterns = ['/^\/avatar/', '/^avatar$/i', '/^\/$/'];

        if (in_array($normalized, $invalidValues, true) || in_array($normalizedLower, $invalidValues, true)) {
            return false;
        }

        foreach ($invalidPatterns as $pattern) {
            if (preg_match($pattern, $normalized)) {
                return false;
            }
        }

        // Basic Check
        if (preg_match('/^https?:\/\//', $normalized))
            return true;
        if ($normalized[0] === '/' && strlen($normalized) > 1)
            return true;

        return false;
    }

    private function generateAvatarColor(string $username): string
    {
        $colors = [
            '#1abc9c',
            '#2ecc71',
            '#3498db',
            '#9b59b6',
            '#e74c3c',
            '#f39c12',
            '#16a085',
            '#27ae60',
            '#2980b9',
            '#8e44ad',
            '#c0392b',
            '#d35400',
            '#34495e',
            '#7f8c8d',
            '#95a5a6'
        ];
        $hash = crc32($username);
        $index = abs($hash) % count($colors);
        return $colors[$index];
    }

    private function generateAvatarInitials(string $username): string
    {
        $username = trim($username);
        if (empty($username)) {
            return '?';
        }
        $words = preg_split('/[\s\-_]+/', $username);
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        } else {
            $firstWord = $words[0];
            if (mb_strlen($firstWord) >= 2) {
                return strtoupper(mb_substr($firstWord, 0, 2));
            } else {
                return strtoupper($firstWord);
            }
        }
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getContent()
    {
        return $this->content;
    }
}
