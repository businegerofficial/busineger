<?php
session_start();
if (!isset($_SESSION['username'])) {
  http_response_code(401);
  echo 'Unauthorized';
  exit();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../backend/db.php'; // $pdo (PDO)

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid ID']);
  exit();
}

$stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
$ok = $stmt->execute([':id' => $id]);

if ($ok) echo json_encode(['ok' => true]);
else {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Delete failed']);
}
