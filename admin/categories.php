<?php
// admin/categories.php
session_start();
require_once __DIR__ . '/../backend/db.php';

if (!isset($_SESSION['user'])) { header("Location: ../mandilogin.php"); exit(); }
if (($_SESSION['user']['role'] ?? 'user') !== 'admin') { http_response_code(403); exit("Forbidden"); }

function q(PDO $pdo, $sql, $p=[]){ $st=$pdo->prepare($sql); $st->execute($p); return $st; }
function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16));
$csrf=$_SESSION['csrf'];
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) die("Bad CSRF");
  $action=$_POST['action']??'';
  if ($action==='create') {
    $name=trim($_POST['name']??'');
    $sort=(int)($_POST['sort_order']??10);
    if ($name!=='') q($pdo,"INSERT INTO categories(name,sort_order,is_active) VALUES(?,?,1)",[$name,$sort]);
  } elseif ($action==='update') {
    $id=(int)($_POST['id']??0);
    $name=trim($_POST['name']??'');
    $sort=(int)($_POST['sort_order']??10);
    $active=isset($_POST['is_active'])?1:0;
    if ($id && $name!=='') q($pdo,"UPDATE categories SET name=?,sort_order=?,is_active=? WHERE id=?",[$name,$sort,$active,$id]);
  } elseif ($action==='delete') {
    $id=(int)($_POST['id']??0);
    if ($id) q($pdo,"DELETE FROM categories WHERE id=?",[$id]); // ON DELETE CASCADE will clear mapping if FK set
  }
  header("Location: categories.php"); exit();
}

$list=q($pdo,"SELECT id,name,sort_order,is_active FROM categories ORDER BY sort_order,name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin · Categories</title>
<style>
body{font-family:Inter,system-ui,Segoe UI,Roboto,Arial;background:#f5f8ff;color:#0b1220;margin:20px}
h1{margin:0 0 12px}
.card{background:#fff;border:1px solid #e6edff;border-radius:12px;box-shadow:0 6px 18px rgba(2,6,23,.06);padding:14px;max-width:900px}
.tbl{width:100%;border-collapse:collapse}
.tbl th,.tbl td{border-bottom:1px solid #e6edff;padding:8px;text-align:left}
.btn{padding:8px 12px;border:1px solid #d7e3ff;border-radius:10px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;font-weight:800;cursor:pointer}
.btn.ghost{background:#fff;color:#1e3a8a}
input[type=text],input[type=number]{padding:8px;border:1px solid #d7e3ff;border-radius:10px;width:100%}
.row{display:grid;grid-template-columns:2fr 100px 100px 160px;gap:8px;align-items:center}
</style></head><body>
<h1>Admin · Categories</h1>
<div class="card" style="margin-bottom:12px;">
  <form method="post" class="row">
    <input type="hidden" name="csrf" value="<?=esc($csrf)?>">
    <input type="hidden" name="action" value="create">
    <input type="text" name="name" placeholder="New category name" required>
    <input type="number" name="sort_order" value="10" min="0" step="5">
    <div></div>
    <button class="btn" type="submit">Add Category</button>
  </form>
</div>

<div class="card">
  <table class="tbl">
    <thead><tr><th>Name</th><th>Order</th><th>Active</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach($list as $c): ?>
        <tr>
          <td style="width:60%">
            <form method="post" class="row">
              <input type="hidden" name="csrf" value="<?=esc($csrf)?>">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?=$c['id']?>">
              <input type="text" name="name" value="<?=esc($c['name'])?>">
              <input type="number" name="sort_order" value="<?=$c['sort_order']?>" min="0" step="5">
              <label><input type="checkbox" name="is_active" <?=$c['is_active']?'checked':''?>> active</label>
              <div style="display:flex;gap:6px;">
                <button class="btn" type="submit">Save</button>
            </form>
                <form method="post" onsubmit="return confirm('Delete this category?');">
                  <input type="hidden" name="csrf" value="<?=esc($csrf)?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?=$c['id']?>">
                  <button class="btn ghost" type="submit">Delete</button>
                </form>
              </div>
          </td>
          <td colspan="3"></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<p style="margin-top:8px"><a href="dashboard.php">← Back to Dashboard</a> · <a href="prompts.php">Open Prompts Manager</a></p>
</body></html>
