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

if (!$data || !isset($data['items'])) {
    echo json_encode(['ok' => false, 'erreur' => 'Données invalides']);
    exit;
}

$fichier = __DIR__ . '/garde-manger.json';

if (file_put_contents($fichier, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
    echo json_encode(['ok' => false, 'erreur' => 'Impossible d\'écrire le fichier']);
    exit;
}

// Push automatique vers GitHub
$repo = '/volume1/cuisine';
$config = @file_get_contents('/volume1/cuisine/.github-token');
$token = $config ? trim($config) : '';
if ($token) {
    $remote = "https://zigoxaz:{$token}@github.com/zigoxaz/cuisine.git";
    shell_exec("cd {$repo} && git add pwa/garde-manger.json && git commit -m 'Garde-manger : mise à jour automatique' && git push {$remote} master 2>&1");
}

echo json_encode(['ok' => true]);
