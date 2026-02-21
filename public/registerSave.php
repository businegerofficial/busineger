<?php
// Public proxy endpoint for registration (POST only)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method Not Allowed');
}

require __DIR__ . '/../app/backend/registerSave.php';

