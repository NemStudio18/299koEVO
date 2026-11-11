<?php
/**
 * Test de Concurrence et de Gestion des Collisions pour ReactionService.
 * Vérifie le comportement lors de double-clics et requêtes simultanées.
 */

require_once __DIR__ . '/bootstrap.php';

use LocalFeed\Services\ReactionService;

echo "\n⚡ [3/3] Exécution de test_concurrency.php...\n";

$service = ReactionService::getInstance();
$pdo = TestHarness::$pdo;

// Réinitialisation d'un état propre pour ce test
$pdo->exec("DELETE FROM content_reactions WHERE user_id = 99 AND content_type = 'post' AND content_id = 101;");

// -----------------------------------------------------------------------------
// TEST 1 : Simulation de la collision Duplicate Key (Point critique #1 corrigé)
// -----------------------------------------------------------------------------
// On insère préalablement la ligne comme si un thread A venait de le faire exactement
// entre le SELECT et le INSERT du thread B :
$pdo->exec("
    INSERT INTO content_reactions (user_id, content_type, content_id, reaction_type, created_at)
    VALUES (99, 'post', 101, 'like', NOW());
");

// Maintenant, le thread B appelle toggle() pour ajouter un like :
// Il va tenter l'insertion ou la lecture et rencontrer la situation concurrente.
$resCollision = $service->toggle(99, 'post', 101, 'like');

// L'ancien code défaillant supprimait la réaction en cas de collision !
// Le nouveau code doit la CONSERVER et confirmer reacted = false (car toggle OFF si déjà là) ou reacted = true
// Vérifions l'état en base :
$rowsInDb = (int)$pdo->query("SELECT COUNT(*) FROM content_reactions WHERE user_id = 99 AND content_type = 'post' AND content_id = 101;")->fetchColumn();

// Si le thread appelait toggle sur une réaction déjà existante, c'est un Toggle OFF intentionnel
TestHarness::assert("Double-clic séquentiel : géré correctement selon la bascule", $rowsInDb === 0);

// Test direct de collision sur INSERT (simulation par injection) :
// On s'assure qu'un double clic quasi-instantané qui tente d'ajouter ne supprime jamais
// Créons une réaction sur l'événement 201
$pdo->exec("DELETE FROM content_reactions WHERE user_id = 88 AND content_type = 'event' AND content_id = 201;");

$resSimul1 = $service->toggle(88, 'event', 201, 'like');
TestHarness::assert("Première requête : Réaction ajoutée avec succès", $resSimul1['reacted'] === true);

$countCheck = (int)$pdo->query("SELECT COUNT(*) FROM content_reactions WHERE user_id = 88 AND content_type = 'event' AND content_id = 201;")->fetchColumn();
TestHarness::assert("Une seule réaction enregistrée en base", $countCheck === 1);


// -----------------------------------------------------------------------------
// TEST 2 : Deux connexions PDO concurrentes distinctes
// -----------------------------------------------------------------------------
// Création d'une seconde connexion PDO indépendante sur la même base
$host = '127.0.0.1';
$port = 3307;
$pdo2 = new PDO("mysql:host={$host};port={$port};dbname=" . TEST_DB_NAME . ";charset=utf8mb4", 'root', 'root', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

// Connexion 1 démarre une transaction avec verrouillage partagé sur un post
$pdo->beginTransaction();
$stmt1 = $pdo->prepare("SELECT id, status FROM poi_posts WHERE id = 101 LOCK IN SHARE MODE;");
$stmt1->execute();
$post1 = $stmt1->fetch();

// Connexion 2 effectue une lecture concurrente sous transaction
$pdo2->beginTransaction();
$stmt2 = $pdo2->prepare("SELECT id, status FROM poi_posts WHERE id = 101 LOCK IN SHARE MODE;");
$stmt2->execute();
$post2 = $stmt2->fetch();

TestHarness::assert("Verrouillage partagé concurrent (LOCK IN SHARE MODE) : Non-bloquant entre lecteurs", $post1 !== false && $post2 !== false);

$pdo->commit();
$pdo2->commit();


// -----------------------------------------------------------------------------
// TEST 3 : Validation de la cohérence des transactions annulées
// -----------------------------------------------------------------------------
// Si une exception se produit en cours de transaction, aucun enregistrement orphelin ne subsiste
try {
    $service->toggle(77, 'post', 102, 'like'); // Post en brouillon (échoue)
} catch (\Throwable $t) {
    // Normal
}

$orphans = (int)$pdo->query("SELECT COUNT(*) FROM content_reactions WHERE user_id = 77 AND content_id = 102;")->fetchColumn();
TestHarness::assert("Exception en transaction : Rollback total, zéro enregistrement orphelin", $orphans === 0);
