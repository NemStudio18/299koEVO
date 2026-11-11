<?php
namespace LocalFeed\Services;

use LocalFeed\Services\Providers\ContentProviderInterface;
use LocalFeed\Services\Providers\PostProvider;
use LocalFeed\Services\Providers\EventProvider;
use LocalFeed\Services\Providers\OfferProvider;
use LocalFeed\Services\Providers\BlogProvider;

class ContentProviderRegistry {

    private static array $providers = [];
    private static bool $initialized = false;

    private static function initDefaults(): void {
        if (!self::$initialized) {
            self::register(new PostProvider());
            self::register(new EventProvider());
            self::register(new OfferProvider());
            self::register(new BlogProvider());
            self::$initialized = true;
        }
    }

    public static function register(ContentProviderInterface $provider): void {
        self::$providers[$provider->getContentType()] = $provider;
    }

    public static function get(string $contentType): ?ContentProviderInterface {
        self::initDefaults();
        return self::$providers[$contentType] ?? null;
    }

    public static function has(string $contentType): bool {
        self::initDefaults();
        return isset(self::$providers[$contentType]);
    }

    /**
     * Pour les tests unitaires : réinitialise l'état du registre
     */
    public static function reset(): void {
        self::$providers = [];
        self::$initialized = false;
    }
}
