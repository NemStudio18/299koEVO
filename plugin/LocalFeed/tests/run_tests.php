<?php
/**
 * Lanceur de tests d'intégration LocalFeed (Environnement local isolé).
 * 
 * Usage : php run_tests.php
 */

echo "===========================================================================\n";
echo "🧪 SUITE DE TESTS D'INTÉGRATION - LOCALFEED & CONTENT_REACTIONS\n";
echo "===========================================================================\n";
echo "Environnement       : Base locale dédiée (127.0.0.1:3307 / localfeed_test_db)\n";
echo "Isolation           : STRICTE (Aucun accès à la base de production)\n";
echo "Schéma source       : 001_create_content_reactions.sql + Schémas réels\n";
echo "===========================================================================\n";

$startTime = microtime(true);

// 1. Initialisation
require_once __DIR__ . '/bootstrap.php';

// 2. Exécution des suites
require_once __DIR__ . '/test_providers.php';
require_once __DIR__ . '/test_reactions.php';
require_once __DIR__ . '/test_concurrency.php';

$elapsed = microtime(true) - $startTime;

// 3. Rapport final
echo "\n===========================================================================\n";
echo "📊 BILAN FINAL DE LA SUITE DE TESTS\n";
echo "===========================================================================\n";
echo "Tests exécutés       : " . TestHarness::$testsRun . "\n";
echo "Tests réussis        : " . TestHarness::$testsPassed . " ✅\n";
echo "Tests échoués        : " . TestHarness::$testsFailed . " " . (TestHarness::$testsFailed > 0 ? "❌" : "") . "\n";
echo "Erreurs SQL          : " . count(TestHarness::$sqlErrors) . "\n";
echo "Temps d'exécution    : " . round($elapsed * 1000, 2) . " ms\n";

if (TestHarness::$testsFailed > 0) {
    echo "\n❌ DÉTAIL DES ÉCHECS :\n";
    foreach (TestHarness::$failures as $failure) {
        echo "{$failure}\n";
    }
    echo "===========================================================================\n";
    exit(1);
} else {
    echo "\n🎉 TOUS LES TESTS SONT PASSÉS AVEC SUCCÈS !\n";
    echo "L'architecture des réactions et des 4 fournisseurs est 100% validée.\n";
    echo "===========================================================================\n";
    exit(0);
}
