<?php
// admin/dashboard.php
session_start();
require_once __DIR__ . '/../backend/db.php'; // provides $pdo (PDO)

// ---- Auth gate (same as prompts.php)
if (!isset($_SESSION['user'])) { header("Location: ../mandilogin.php"); exit(); }
$user = $_SESSION['user'];
if (!isset($user['role']) || $user['role'] !== 'admin') {
  http_response_code(403); echo "Forbidden (admin only)"; exit();
}

// ---- Tiny helper
function q(PDO $pdo, string $sql, array $params=[]){ $st=$pdo->prepare($sql); $st->execute($params); return $st; }
function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ---- KPIs
$totalUsers      = (int) q($pdo,"SELECT COUNT(*) FROM users")->fetchColumn();
$totalCategories = (int) q($pdo,"SELECT COUNT(*) FROM categories WHERE is_active=1")->fetchColumn();
$totalTopics     = (int) q($pdo,"SELECT COUNT(*) FROM topics WHERE is_active=1")->fetchColumn();
$totalPrompts    = (int) q($pdo,"SELECT COUNT(*) FROM prompts")->fetchColumn();
$freePrompts     = (int) q($pdo,"SELECT COUNT(*) FROM prompts WHERE type='free'")->fetchColumn();
$paidPrompts     = (int) q($pdo,"SELECT COUNT(*) FROM prompts WHERE type='paid'")->fetchColumn();

// recent items
$recentPrompts = q($pdo, "
  SELECT p.id, p.label, p.type, p.sort_order, p.is_active, t.name AS topic, c.name AS category
  FROM prompts p
  JOIN topics t ON t.id=p.topic_id
  LEFT JOIN categories c ON c.id=p.category_id
  ORDER BY p.id DESC
  LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

$recentUsers = q($pdo, "
  SELECT id, username, email, role, created_at
  FROM users
  ORDER BY id DESC
  LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// mapping health: how many topics are mapped per category
$mapStats = q($pdo, "
  SELECT c.id, c.name, COALESCE(COUNT(ct.topic_id),0) AS mapped_topics
  FROM categories c
  LEFT JOIN category_topics ct ON ct.category_id=c.id
  WHERE c.is_active=1
  GROUP BY c.id, c.name
  ORDER BY c.sort_order, c.name
")->fetchAll(PDO::FETCH_ASSOC);

// prompts per topic (quick glance)
$topicPromptStats = q($pdo, "
  SELECT t.id, t.name,
         SUM(CASE WHEN p.type='free' THEN 1 ELSE 0 END) AS free_count,
         SUM(CASE WHEN p.type='paid' THEN 1 ELSE 0 END) AS paid_count
  FROM topics t
  LEFT JOIN prompts p ON p.topic_id=t.id
  WHERE t.is_active=1
  GROUP BY t.id, t.name
  ORDER BY t.sort_order, t.name
  LIMIT 12
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin · Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  :root{--ink:#0b1220;--muted:#6b7ca6;--panel:#fff;--line:#e6edff;--brand:#2563eb;--brand2:#1d4ed8;--bg:#f5f8ff;}
  *{box-sizing:border-box} body{margin:0;font-family:Inter,system-ui,Segoe UI,Roboto,Arial;background:var(--bg);color:var(--ink)}
  .wrap{max-width:1180px;margin:28px auto;padding:0 16px}
  h1{margin:0 0 16px}
  .grid{display:grid;gap:14px}
  .kpis{grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}
  .card{background:var(--panel);border:1px solid var(--line);border-radius:14px;box-shadow:0 6px 18px rgba(2,6,23,.06);padding:14px}
  .kpi{display:flex;align-items:center;gap:12px}
  .kpi b{font-size:28px}
  .kpi span{color:var(--muted);font-weight:700}
  .row{display:grid;grid-template-columns:2fr 1fr;gap:14px}
  .tbl{width:100%;border-collapse:collapse;font-size:14px}
  .tbl th,.tbl td{border-bottom:1px solid var(--line);padding:8px 6px;text-align:left}
  .pill{display:inline-block;padding:4px 8px;border-radius:999px;font-weight:800;font-size:12px;border:1px solid var(--line)}
  .pill.free{background:#f3f8ff;color:#1d4ed8;border-color:#cfe0ff}
  .pill.paid{background:#fff7f0;color:#b45309;border-color:#ffe0bf}
  .muted{color:var(--muted)}
  .topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
  .btn{padding:10px 12px;border-radius:10px;border:1px solid var(--line);background:linear-gradient(135deg,var(--brand),var(--brand2));color:#fff;font-weight:800;text-decoration:none}
  .btn.ghost{background:#fff;color:#1e3a8a}
  .chips{display:flex;flex-wrap:wrap;gap:8px}
  .chip{border:1px solid var(--line);border-radius:999px;padding:6px 10px;background:#fff}
  .hdr{font-weight:800;margin-bottom:8px}
  @media (max-width:960px){.row{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">

  <div class="topbar">
    <h1>Admin · Dashboard</h1>
    <div class="chips">
      <a class="btn" href="./prompts.php">Open Prompts Manager</a>
      <a class="btn ghost" href="../index.php" target="_blank">Open Site</a>
      <a class="btn ghost" href="./categories.php">Categories</a>
<a class="btn ghost" href="./topics.php">Topics</a>

    </div>
  </div>

  <!-- KPIs -->
  <div class="grid kpis">
    <div class="card kpi"><b><?= $totalUsers ?></b><span>Users</span></div>
    <div class="card kpi"><b><?= $totalCategories ?></b><span>Categories</span></div>
    <div class="card kpi"><b><?= $totalTopics ?></b><span>Topics</span></div>
    <div class="card kpi"><b><?= $totalPrompts ?></b><span>Prompts (<?= $freePrompts ?> free / <?= $paidPrompts ?> paid)</span></div>
  </div>

  <div class="row" style="margin-top:14px;">
    <!-- Recent Prompts -->
    <div class="card">
      <div class="hdr">Recent Prompts</div>
      <table class="tbl">
        <thead><tr>
          <th>ID</th><th>Label</th><th>Topic</th><th>Category</th><th>Type</th><th>Order</th><th>Status</th>
        </tr></thead>
        <tbody>
        <?php if(!$recentPrompts): ?>
          <tr><td colspan="7" class="muted">No prompts yet.</td></tr>
        <?php else: foreach($recentPrompts as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= esc($r['label']) ?></td>
            <td><?= esc($r['topic']) ?></td>
            <td><?= $r['category'] ? esc($r['category']) : '<span class="muted">Global</span>' ?></td>
            <td><span class="pill <?= $r['type']==='paid'?'paid':'free' ?>"><?= esc($r['type']) ?></span></td>
            <td><?= (int)$r['sort_order'] ?></td>
            <td><?= $r['is_active'] ? '✅ Active' : '⏸ Disabled' ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Recent Users -->
    <div class="card">
      <div class="hdr">Recent Users</div>
      <table class="tbl">
        <thead><tr><th>ID</th><th>Name</th><th>Role</th></tr></thead>
        <tbody>
        <?php if(!$recentUsers): ?>
          <tr><td colspan="3" class="muted">No users.</td></tr>
        <?php else: foreach($recentUsers as $u): ?>
          <tr>
            <td><?= (int)$u['id'] ?></td>
            <td><?= esc($u['username']) ?><br><span class="muted" style="font-size:12px;"><?= esc($u['email']) ?></span></td>
            <td><?= esc($u['role'] ?? 'user') ?></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="row" style="margin-top:14px;">
    <!-- Mapping Health -->
    <div class="card">
      <div class="hdr">Category → Topics Mapping (health)</div>
      <table class="tbl">
        <thead><tr><th>Category</th><th>Mapped Topics</th></tr></thead>
        <tbody>
          <?php foreach($mapStats as $m): ?>
            <tr>
              <td><?= esc($m['name']) ?></td>
              <td><?= (int)$m['mapped_topics'] ?> / <?= $totalTopics ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="muted" style="margin-top:6px;">Tip: Manage mapping in <a href="./prompts.php">Prompts Manager</a>.</div>
    </div>

    <!-- Prompts per Topic -->
    <div class="card">
      <div class="hdr">Prompts by Topic (first 12)</div>
      <div class="chips">
        <?php foreach($topicPromptStats as $t): ?>
          <div class="chip">
            <b><?= esc($t['name']) ?></b>
            <div class="muted" style="font-size:12px;margin-left:6px;">
              <?= (int)$t['free_count'] ?> free · <?= (int)$t['paid_count'] ?> paid
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

</div>
</body>
</html>
