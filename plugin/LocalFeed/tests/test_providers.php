<?php
/**
 * Test unitaire des Fournisseurs de Contenu (ContentProviderRegistry & Providers).
 * Vérifie les règles d'accessibilité métier et les types de réactions supportés.
 */

require_once __DIR__ . '/bootstrap.php';

use LocalFeed\Services\ContentProviderRegistry;

echo "\n📦 [1/3] Exécution de test_providers.php...\n";

$pdo = TestHarness::$pdo;

// 1. PostProvider
$postProvider = ContentProviderRegistry::get('post');
TestHarness::assert("PostProvider est bien résolu par le registre", $postProvider !== null);
TestHarness::assert("PostProvider supporte la réaction 'like'", $postProvider->supportsReaction('like'));
TestHarness::assert("PostProvider refuse la réaction 'interest'", !$postProvider->supportsReaction('interest'));

// Post publié (ID: 101)
try {
    $postProvider->assertAccessible($pdo, 101, 1, 'like');
    TestHarness::assert("Post publié : Réaction autorisée", true);
} catch (\Throwable $t) {
    TestHarness::assert("Post publié : Réaction autorisée", false, $t->getMessage());
}

// Post en brouillon (ID: 102)
try {
    $postProvider->assertAccessible($pdo, 102, 1, 'like');
    TestHarness::assert("Post en brouillon : Réaction refusée", false, "Une exception aurait dû être levée");
} catch (\RuntimeException $e) {
    TestHarness::assert("Post en brouillon : Réaction refusée", true);
}

// Post archivé (ID: 103)
try {
    $postProvider->assertAccessible($pdo, 103, 1, 'like');
    TestHarness::assert("Post archivé : Réaction refusée", false, "Une exception aurait dû être levée");
} catch (\RuntimeException $e) {
    TestHarness::assert("Post archivé : Réaction refusée", true);
}

// Post inexistant (ID: 999)
try {
    $postProvider->assertAccessible($pdo, 999, 1, 'like');
    TestHarness::assert("Post inexistant : Rejeté", false);
} catch (\RuntimeException $e) {
    TestHarness::assert("Post inexistant : Rejeté", true);
}


// 2. EventProvider
$eventProvider = ContentProviderRegistry::get('event');
TestHarness::assert("EventProvider est bien résolu par le registre", $eventProvider !== null);
TestHarness::assert("EventProvider supporte 'like'", $eventProvider->supportsReaction('like'));
TestHarness::assert("EventProvider supporte 'interest'", $eventProvider->supportsReaction('interest'));

// Événement approuvé (ID: 201)
try {
    $eventProvider->assertAccessible($pdo, 201, 1, 'like');
    $eventProvider->assertAccessible($pdo, 201, 1, 'interest');
    TestHarness::assert("Événement approuvé : like et interest autorisés", true);
} catch (\Throwable $t) {
    TestHarness::assert("Événement approuvé : like et interest autorisés", false, $t->getMessage());
}

// Événement en attente (ID: 202)
try {
    $eventProvider->assertAccessible($pdo, 202, 1, 'like');
    TestHarness::assert("Événement non approuvé : Réaction refusée", false);
} catch (\RuntimeException $e) {
    TestHarness::assert("Événement non approuvé : Réaction refusée", true);
}


// 3. OfferProvider
$offerProvider = ContentProviderRegistry::get('offer');
TestHarness::assert("OfferProvider est bien résolu par le registre", $offerProvider !== null);
TestHarness::assert("OfferProvider supporte 'like' et 'interest'", $offerProvider->supportsReaction('like') && $offerProvider->supportsReaction('interest'));

// Offre active et valide (ID: 301)
try {
    $offerProvider->assertAccessible($pdo, 301, 1, 'like');
    TestHarness::assert("Offre active et valide : Réaction autorisée", true);
} catch (\Throwable $t) {
    TestHarness::assert("Offre active et valide : Réaction autorisée", false, $t->getMessage());
}

// Offre expirée (ID: 302)
try {
    $offerProvider->assertAccessible($pdo, 302, 1, 'like');
    TestHarness::assert("Offre expirée : Réaction refusée", false);
} catch (\RuntimeException $e) {
    TestHarness::assert("Offre expirée : Réaction refusée", true);
}

// Offre inactive (ID: 303)
try {
    $offerProvider->assertAccessible($pdo, 303, 1, 'like');
    TestHarness::assert("Offre inactive : Réaction refusée", false);
} catch (\RuntimeException $e) {
    TestHarness::assert("Offre inactive : Réaction refusée", true);
}


// 4. BlogProvider
$blogProvider = ContentProviderRegistry::get('blog');
TestHarness::assert("BlogProvider est bien résolu par le registre", $blogProvider !== null);
TestHarness::assert("BlogProvider supporte 'like'", $blogProvider->supportsReaction('like'));

// Article de blog publié (ID: 401)
try {
    $blogProvider->assertAccessible($pdo, 401, 1, 'like');
    TestHarness::assert("Article de blog publié : Réaction autorisée", true);
} catch (\Throwable $t) {
    TestHarness::assert("Article de blog publié : Réaction autorisée", false, $t->getMessage());
}

// Article de blog en brouillon (ID: 402)
try {
    $blogProvider->assertAccessible($pdo, 402, 1, 'like');
    TestHarness::assert("Article en brouillon : Réaction refusée", false);
} catch (\RuntimeException $e) {
    TestHarness::assert("Article en brouillon : Réaction refusée", true);
}
