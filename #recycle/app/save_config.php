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

$themes = ['sombre', 'creme', 'mediterraneen', 'naturel'];
$navs   = ['classique', 'minimaliste', 'epure', 'hierarchie'];

if (!$data || !isset($data['theme']) || !in_array($data['theme'], $themes)) {
    echo json_encode(['ok' => false, 'erreur' => 'Thème invalide']);
    exit;
}

$config = ['theme' => $data['theme']];

if (isset($data['nav']) && in_array($data['nav'], $navs)) {
    $config['nav'] = $data['nav'];
}

$fichier = __DIR__ . '/config.json';

if (file_put_contents($fichier, json_encode($config, JSON_PRETTY_PRINT)) === false) {
    echo json_encode(['ok' => false, 'erreur' => 'Impossible d\'écrire le fichier']);
    exit;
}

echo json_encode(['ok' => true]);
