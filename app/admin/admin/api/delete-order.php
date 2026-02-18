<?php
session_start();
if (!isset($_SESSION['username'])) {
  http_response_code(401);
  echo 'Unauthorized';
  exit();
}

require_once(__DIR__ . '/../../DB/conn.php');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
  http_response_code(400);
  echo 'Invalid ID';
  exit();
}

$stmt = $conn->prepare("DELETE FROM button_clicks WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
  echo 'OK';
} else {
  http_response_code(500);
  echo 'Delete failed';
}

$stmt->close();
$conn->close();
