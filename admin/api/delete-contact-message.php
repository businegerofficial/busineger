<?php
session_start();
if (!isset($_SESSION['username'])) { http_response_code(401); echo 'Unauthorized'; exit(); }

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../backend/db.php'; // $pdo

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Invalid ID']); exit; }

$stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = :id");
$ok = $stmt->execute([':id' => $id]);

echo json_encode(['ok' => $ok]);
