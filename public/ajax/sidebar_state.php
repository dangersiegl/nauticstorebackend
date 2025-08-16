<?php
// Kleiner AJAX-Endpunkt zur Speicherung des Sidebar-Zustands in der Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Einfach nur POST akzeptieren
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$collapsed = isset($_POST['collapsed']) ? (int)$_POST['collapsed'] : null;
if ($collapsed === null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing parameter']);
    exit;
}

// speichern
$_SESSION['sidebar_collapsed'] = $collapsed ? 1 : 0;

header('Content-Type: application/json');
echo json_encode(['ok' => true, 'collapsed' => (int)$_SESSION['sidebar_collapsed']]);
exit;
