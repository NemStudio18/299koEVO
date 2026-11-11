<?php
/**
 * Test unitaire de ReactionService.
 * Vérifie le comportement des réactions, retraits, bascules et correspondance des compteurs.
 */

require_once __DIR__ . '/bootstrap.php';

use LocalFeed\Services\ReactionService;

echo "\n❤️  [2/3] Exécution de test_reactions.php...\n";

$service = ReactionService::getInstance();
$pdo = TestHarness::$pdo;

// 1. Utilisateur non autorisé (userId <= 0)
try {
    $service->toggle(0, 'post', 101, 'like');
    TestHarness::assert("Utilisateur non autorisé (id=0) : Accès refusé", false);
} catch (\InvalidArgumentException $e) {
    TestHarness::assert("Utilisateur non autorisé (id=0) : Accès refusé", true);
}

try {
    $service->toggle(-5, 'post', 101, 'like');
    TestHarness::assert("Utilisateur non autorisé (id<0) : Accès refusé", false);
} catch (\InvalidArgumentException $e) {
    TestHarness::assert("Utilisateur non autorisé (id<0) : Accès refusé", true);
}


// 2. Ajout d'une réaction sur post publié
$res1 = $service->toggle(10, 'post', 101, 'like');
TestHarness::assert("Ajout réaction sur post publié : Succès", $res1['success'] === true && $res1['reacted'] === true);
TestHarness::assert("Compteur retourné = 1", $res1['reaction_count'] === 1);

// Vérification en base SQL
$countSql = (int)$pdo->query("SELECT COUNT(*) FROM content_reactions WHERE content_type = 'post' AND content_id = 101 AND reaction_type = 'like'")->fetchColumn();
TestHarness::assert("Vérification SQL : 1 ligne présente dans content_reactions", $countSql === 1);


// 3. Retrait d'une réaction (Toggle OFF par clic identique)
$res2 = $service->toggle(10, 'post', 101, 'like');
TestHarness::assert("Retrait d'une réaction : Réaction supprimée", $res2['success'] === true && $res2['reacted'] === false);
TestHarness::assert("Compteur après retrait = 0", $res2['reaction_count'] === 0);

$countSqlAfterDel = (int)$pdo->query("SELECT COUNT(*) FROM content_reactions WHERE content_type = 'post' AND content_id = 101 AND reaction_type = 'like'")->fetchColumn();
TestHarness::assert("Vérification SQL : 0 ligne présente après retrait", $countSqlAfterDel === 0);


// 4. Changement like -> interest sur un événement
// A. Ajout d'un like sur l'événement 201 par l'utilisateur 10
$service->toggle(10, 'event', 201, 'like');
// B. Changement vers 'interest' par le même utilisateur
$resChange = $service->toggle(10, 'event', 201, 'interest');
TestHarness::assert("Changement like -> interest : Succès", $resChange['success'] === true && $resChange['reacted'] === true && $resChange['reaction_type'] === 'interest');

// Vérifier qu'il n'y a pas de doublon en base pour cet utilisateur
$totalUserRows = (int)$pdo->query("SELECT COUNT(*) FROM content_reactions WHERE user_id = 10 AND content_type = 'event' AND content_id = 201")->fetchColumn();
TestHarness::assert("Changement de type : Aucun doublon créé en base (exactement 1 ligne)", $totalUserRows === 1);

$curType = $pdo->query("SELECT reaction_type FROM content_reactions WHERE user_id = 10 AND content_type = 'event' AND content_id = 201")->fetchColumn();
TestHarness::assert("Type de réaction mis à jour à 'interest'", $curType === 'interest');


// 5. Multiples utilisateurs et cohérence stricte des compteurs
// Ajout de 3 réactions par 3 utilisateurs distincts sur l'offre 301
$service->toggle(10, 'offer', 301, 'interest');
$service->toggle(20, 'offer', 301, 'interest');
$resOffer3 = $service->toggle(30, 'offer', 301, 'interest');

TestHarness::assert("3 utilisateurs réagissent sur l'offre 301 : Compteur = 3", $resOffer3['reaction_count'] === 3);

$sqlCountOffer = (int)$pdo->query("SELECT COUNT(*) FROM content_reactions WHERE content_type = 'offer' AND content_id = 301 AND reaction_type = 'interest'")->fetchColumn();
TestHarness::assert("Le compteur correspond exactement aux lignes réelles de content_reactions", $resOffer3['reaction_count'] === $sqlCountOffer);


// 6. Chargement ciblé des réactions utilisateur via getUserReactionsMap
$itemsToDisplay = [
    ['type' => 'event', 'id' => 201],
    ['type' => 'offer', 'id' => 301],
    ['type' => 'post',  'id' => 101]
];

$userMap10 = $service->getUserReactionsMap(10, $itemsToDisplay);
TestHarness::assert("User 10 a réagi sur l'event 201 (interest)", isset($userMap10['event_201']) && $userMap10['event_201'] === 'interest');
TestHarness::assert("User 10 a réagi sur l'offer 301 (interest)", isset($userMap10['offer_301']) && $userMap10['offer_301'] === 'interest');
TestHarness::assert("User 10 n'a pas réagi sur le post 101", !isset($userMap10['post_101']));

// Test d'isolation : les contenus non présents dans $items ne doivent PAS être retournés
$restrictedItems = [
    ['type' => 'event', 'id' => 201]
];
$restrictedMap = $service->getUserReactionsMap(10, $restrictedItems);
TestHarness::assert("Filtrage strict sur items : seuls les éléments demandés sont retournés", count($restrictedMap) === 1 && isset($restrictedMap['event_201']));
TestHarness::assert("L'offre 301 n'est pas chargée si non présente dans items", !isset($restrictedMap['offer_301']));

// Test avec items vide
$emptyMap = $service->getUserReactionsMap(10, []);
TestHarness::assert("Liste d'items vide : retourne tableau vide immédiatement", $emptyMap === []);
