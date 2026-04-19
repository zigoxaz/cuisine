<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'erreur' => 'Méthode non autorisée']);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['recettes']) || !isset($data['categories'])) {
    echo json_encode(['ok' => false, 'erreur' => 'Données invalides']);
    exit;
}

$fichier = __DIR__ . '/recettes.json';

if (file_put_contents($fichier, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
    echo json_encode(['ok' => false, 'erreur' => 'Impossible d\'écrire le fichier']);
    exit;
}

echo json_encode(['ok' => true]);
