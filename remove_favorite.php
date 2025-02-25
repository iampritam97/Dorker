<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $index = $data['index'] ?? -1;
    
    if ($index >= 0 && isset($_SESSION['favorites'][$index])) {
        array_splice($_SESSION['favorites'], $index, 1);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid index']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>