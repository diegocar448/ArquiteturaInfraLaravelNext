<?php

// Placeholder - sera substituido pelo Laravel real
// Este arquivo existe apenas para o Nginx ter algo para servir
// durante o build inicial do Docker

header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'message' => 'Orderly API - Placeholder. Run composer install to setup Laravel.',
    'version' => '0.0.1',
]);