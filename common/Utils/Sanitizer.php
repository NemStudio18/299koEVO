<?php

namespace Utils;

defined('ROOT') or exit('Access denied!');

/**
 * Classe utilitaire pour nettoyer les entrées HTML (Anti-XSS léger)
 * Remplace HTMLPurifier qui est absent.
 */
class Sanitizer
{
    /**
     * Tags autorisés pour TinyMCE / Contenu riche
     */
    private const ALLOWED_TAGS = '<p><br><b><strong><i><em><u><s><blockquote><a><ul><ol><li><h1><h2><h3><h4><h5><h6><img><span><div><table><thead><tbody><tr><th><td>';

    /**
     * Nettoie une chaîne HTML
     * 
     * @param string $html Contenu à nettoyer
     * @return string Contenu nettoyé
     */
    public static function cleanHtml(string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // 1. strip_tags pour ne garder que les balises safe
        $clean = strip_tags($html, self::ALLOWED_TAGS);

        // 2. Supprimer les attributs dangereux (on*, javascript:) via Regex
        // Attention : ce n'est pas parfait mais filtre le plus gros des attaques script kids

        // Supprimer les événements on* (onclick, onerror, etc.)
        $clean = preg_replace('/\s+on[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]*)/i', '', $clean);

        // Supprimer javascript: dans les href/src
        $clean = preg_replace('/\s+(href|src)\s*=\s*("javascript:[^"]*"|\'javascript:[^\']*\')/i', ' $1="#"', $clean);

        // Supprimer les iframes (au cas où strip_tags laisse passer qqch)
        $clean = preg_replace('/<iframe.*?>.*?<\/iframe>/is', '', $clean);

        return $clean;
    }

    /**
     * Nettoie une chaîne texte simple (pas de HTML)
     */
    public static function cleanText(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
