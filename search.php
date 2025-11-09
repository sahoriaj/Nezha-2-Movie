<?php
require_once 'config.php';

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

if(strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, title, slug, synopsis, poster FROM anime WHERE title LIKE ? OR zh_name LIKE ? LIMIT 10");
$searchTerm = '%' . $query . '%';
$stmt->execute([$searchTerm, $searchTerm]);
$results = $stmt->fetchAll();

echo json_encode($results);
?>