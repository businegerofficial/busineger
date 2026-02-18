<?php
session_start();
require_once __DIR__ . '/../backend/db.php'; // must expose $pdo (PDO). If you use mysqli, tell me & I'll convert.

if (!isset($_SESSION['user'])) {
  header("Location: ../mandilogin.php");
  exit();
}
$user = $_SESSION['user'];
if (!isset($user['role']) || $user['role'] !== 'admin') {
  http_response_code(403);
  echo "Forbidden (admin only)";
  exit();
}

/* ---------------- CSRF (tiny) ---------------- */
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
function check_csrf() {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
      http_response_code(400); die("Bad CSRF token");
    }
  }
}

/* ---------------- Helpers ---------------- */
function q(PDO $pdo, string $sql, array $params = []) {
  $st = $pdo->prepare($sql); $st->execute($params); return $st;
}
function esc($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$csrf = $_SESSION['csrf'];

/* ------- Load base lists ------- */
$cats = q($pdo, "SELECT id, name FROM categories WHERE is_active=1 ORDER BY sort_order, name")->fetchAll(PDO::FETCH_ASSOC);
$topicsAll = q($pdo, "SELECT id, name FROM topics WHERE is_active=1 ORDER BY sort_order, name")->fetchAll(PDO::FETCH_ASSOC);

/* ------- Filters ------- */
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0; // 0 = Global
$topic_id    = isset($_GET['topic_id']) ? (int)$_GET['topic_id'] : 0;
$typeFilter  = isset($_GET['type']) ? ($_GET['type']==='paid'?'paid':'free') : 'free';

/* ------- Actions ------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();

  $action = $_POST['action'] ?? '';
  if ($action === 'add_prompt') {
    $catId = (int)($_POST['category_id'] ?? 0);
    $topicId = (int)($_POST['topic_id'] ?? 0);
    $type = ($_POST['type'] ?? 'free') === 'paid' ? 'paid' : 'free';
    $label = trim($_POST['label'] ?? '');
    $prompt_text = trim($_POST['prompt_text'] ?? '');
    $prompt_text = ($prompt_text === '') ? null : $prompt_text;
    $sort = (int)($_POST['sort_order'] ?? 10);
    if ($topicId && $label !== '') {
    q($pdo, "INSERT INTO prompts (category_id, topic_id, label, prompt_text, type, sort_order, is_active)
         VALUES (?, ?, ?, ?, ?, ?, 1)", [
  $catId ? $catId : null,
  $topicId,
  $label,
  ($prompt_text !== '' ? $prompt_text : null),
  $type,
  $sort
]);

    }
    header("Location: ?category_id=$catId&topic_id=$topicId&type=$type");
    exit();
  }

 if ($action === 'update_prompt') {
  $id = (int)($_POST['id'] ?? 0);
  $label = trim($_POST['label'] ?? '');
  $prompt_text = trim($_POST['prompt_text'] ?? '');
  $sort = (int)($_POST['sort_order'] ?? 10);
  $active = isset($_POST['is_active']) ? 1 : 0;

  if ($id && $label !== '') {
    q($pdo, "UPDATE prompts SET label=?, prompt_text=?, sort_order=?, is_active=? WHERE id=?",
      [$label, ($prompt_text !== '' ? $prompt_text : null), $sort, $active, $id]
    );
    }
    $catId = (int)($_POST['category_id'] ?? 0);
    $topicId = (int)($_POST['topic_id'] ?? 0);
    $type = ($_POST['type'] ?? 'free')==='paid'?'paid':'free';
    header("Location: ?category_id=$catId&topic_id=$topicId&type=$type");
    exit();
  }

  if ($action === 'delete_prompt') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) q($pdo, "DELETE FROM prompts WHERE id=?", [$id]);
    $catId = (int)($_POST['category_id'] ?? 0);
    $topicId = (int)($_POST['topic_id'] ?? 0);
    $type = ($_POST['type'] ?? 'free')==='paid'?'paid':'free';
    header("Location: ?category_id=$catId&topic_id=$topicId&type=$type");
    exit();
  }

  if ($action === 'save_mapping') {
    // Map topics checked to selected category
    $catId = (int)($_POST['map_category_id'] ?? 0);
    if ($catId > 0) {
      // wipe then insert selected
      q($pdo, "DELETE FROM category_topics WHERE category_id=?", [$catId]);
      $checked = $_POST['topics'] ?? [];
      $sort = 10;
      foreach ($checked as $tid) {
        $tid = (int)$tid;
        if ($tid) q($pdo, "INSERT INTO category_topics (category_id, topic_id, sort_order) VALUES (?,?,?)", [$catId, $tid, $sort]);
        $sort += 10;
      }
    }
    header("Location: ?category_id=$catId");
    exit();
  }

  if ($action === 'bulk_sort') {
    // Update many sort_order quickly
    $rows = $_POST['rows'] ?? [];
    foreach ($rows as $id => $sort) {
      q($pdo, "UPDATE prompts SET sort_order=? WHERE id=?", [(int)$sort, (int)$id]);
    }
    $catId = (int)($_POST['category_id'] ?? 0);
    $topicId = (int)($_POST['topic_id'] ?? 0);
    $type = ($_POST['type'] ?? 'free')==='paid'?'paid':'free';
    header("Location: ?category_id=$catId&topic_id=$topicId&type=$type");
    exit();
  }
}

/* ------- Load mapped topics for selected category (for UI) ------- */
if ($category_id > 0) {
  $mapped = q($pdo, "SELECT topic_id FROM category_topics WHERE category_id=? ORDER BY sort_order", [$category_id])->fetchAll(PDO::FETCH_COLUMN);
} else {
  $mapped = array_column($topicsAll, 'id'); // Global: show all topics
}

/* ------- If a topic not chosen yet, pick the first mapped one ------- */
if (!$topic_id && $mapped) $topic_id = (int)$mapped[0];

/* ------- Load prompts for current filter ------- */
$params = [];
$sql = "SELECT p.id, p.label, p.prompt_text, p.type, p.sort_order, p.is_active, p.category_id,
        t.name AS topic_name, c.name AS category_name
        FROM prompts p
        JOIN topics t ON t.id = p.topic_id
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.type = ? ";
$params[] = $typeFilter;

if ($topic_id) { $sql .= " AND p.topic_id = ? "; $params[] = $topic_id; }
if ($category_id > 0) { $sql .= " AND p.category_id = ? "; $params[] = $category_id; }
else { $sql .= " AND p.category_id IS NULL "; }

$sql .= " ORDER BY p.sort_order, p.id";
$list = q($pdo, $sql, $params)->fetchAll(PDO::FETCH_ASSOC);

/* ------- For mapping form ------- */
$topicIdsMapped = array_flip($mapped);
?>
<!DOCTYPE html>
<html lang="en" data-theme="auto">
<head>
  <meta charset="utf-8">
  <title>Admin · Prompts</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:Inter,system-ui,Segoe UI,Roboto,Arial;margin:20px;color:#0b1220;background:#f7f9ff}
    h1{margin:0 0 12px}
    .row{display:flex;gap:16px;flex-wrap:wrap}
    .card{background:#fff;border:1px solid #e5ecff;border-radius:12px;box-shadow:0 6px 18px rgba(2,6,23,.06);padding:14px;flex:1;min-width:280px}
    label{font-weight:700;font-size:14px}
    select, input[type="text"], input[type="number"], textarea{width:100%;padding:8px;border:1px solid #d7e3ff;border-radius:10px;margin-top:6px}
    .grid{display:grid;grid-template-columns: 1fr 140px 90px 90px 110px;gap:8px;align-items:start}
.grid textarea{grid-column: 1 / -1; margin-top:6px;}

    .grid.head{font-weight:800;border-bottom:1px solid #e9efff;padding-bottom:6px;margin-bottom:6px}
    .pill{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid #d7e3ff;border-radius:999px;background:#fff;font-weight:800;cursor:pointer}
    .btn{padding:10px 12px;border:1px solid #d7e3ff;border-radius:10px;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;font-weight:800;cursor:pointer}
    .btn.ghost{background:#fff;color:#1e3a8a}
    .muted{color:#6b7ca6}
    .row .card.small{max-width:420px}
    .warn{background:#fff8e6;border-color:#ffe1a3}
    .flex{display:flex;gap:10px;align-items:center}
    .mt8{margin-top:8px}.mt12{margin-top:12px}.mt16{margin-top:16px}
  </style>
</head>
<body>
  <h1>Admin · Prompts Manager</h1>

  <!-- Filters -->
  <form method="get" class="card small">
    <input type="hidden" name="csrf" value="<?php echo esc($csrf); ?>">
    <label>Category (Global = None)</label>
    <select name="category_id">
      <option value="0"<?php if($category_id===0) echo ' selected'; ?>>Global (All Categories)</option>
      <?php foreach($cats as $c): ?>
        <option value="<?php echo (int)$c['id']; ?>"<?php if($category_id===(int)$c['id']) echo ' selected'; ?>>
          <?php echo esc($c['name']); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label class="mt8">Topic</label>
    <select name="topic_id">
      <?php foreach($topicsAll as $t): ?>
        <?php if($category_id===0 || isset($topicIdsMapped[$t['id']])): ?>
          <option value="<?php echo (int)$t['id']; ?>"<?php if($topic_id===(int)$t['id']) echo ' selected'; ?>>
            <?php echo esc($t['name']); ?>
          </option>
        <?php endif; ?>
      <?php endforeach; ?>
    </select>

    <label class="mt8">Type</label>
    <select name="type">
      <option value="free"<?php if($typeFilter==='free') echo ' selected'; ?>>Free</option>
      <option value="paid"<?php if($typeFilter==='paid') echo ' selected'; ?>>Paid</option>
    </select>

    <div class="mt12"><button class="btn" type="submit">Apply</button></div>
  </form>

  <!-- Category ↔ Topic Mapping -->
  <form method="post" class="card">
    <input type="hidden" name="csrf" value="<?php echo esc($csrf); ?>">
    <input type="hidden" name="action" value="save_mapping">
    <label>Map Topics to Category</label>
    <div class="flex mt8">
      <select name="map_category_id">
        <?php foreach($cats as $c): ?>
          <option value="<?php echo (int)$c['id']; ?>"<?php if($category_id===(int)$c['id']) echo ' selected'; ?>>
            <?php echo esc($c['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <span class="muted">Select the category, then tick topics below:</span>
    </div>
    <div class="row mt12" style="gap:10px;">
      <?php foreach($topicsAll as $t): ?>
        <label class="pill">
          <input type="checkbox" name="topics[]" value="<?php echo (int)$t['id']; ?>"
            <?php if($category_id>0 && isset($topicIdsMapped[$t['id']])) echo ' checked'; ?>>
          <?php echo esc($t['name']); ?>
        </label>
      <?php endforeach; ?>
    </div>
    <div class="mt12"><button class="btn" type="submit">Save Mapping</button></div>
    <div class="muted mt8">Tip: Order is by save sequence (left→right). Edit later by re-saving.</div>
  </form>

  <!-- Add Prompt -->
  <form method="post" class="card small">
    <input type="hidden" name="csrf" value="<?php echo esc($csrf); ?>">
    <input type="hidden" name="action" value="add_prompt">
    <label>Scope</label>
    <div class="flex">
      <select name="category_id" title="Category (0 = Global)">
        <option value="0"<?php if($category_id===0) echo ' selected'; ?>>Global</option>
        <?php foreach($cats as $c): ?>
          <option value="<?php echo (int)$c['id']; ?>"<?php if($category_id===(int)$c['id']) echo ' selected'; ?>>
            <?php echo esc($c['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <select name="topic_id" title="Topic" required>
        <?php foreach($topicsAll as $t): ?>
          <option value="<?php echo (int)$t['id']; ?>"<?php if($topic_id===(int)$t['id']) echo ' selected'; ?>>
            <?php echo esc($t['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <select name="type" title="Type">
        <option value="free"<?php if($typeFilter==='free') echo ' selected'; ?>>Free</option>
        <option value="paid"<?php if($typeFilter==='paid') echo ' selected'; ?>>Paid</option>
      </select>
    </div>
    <label class="mt8">Label</label>
    <input type="text" name="label" placeholder="e.g., Pet parent engagement post ideas" required>
   <label class="mt8">Backend Full Prompt (prompt_text)</label>
<textarea name="prompt_text" rows="8" placeholder="Paste full backend prompt/template here..."></textarea>
    <label class="mt8">Sort Order</label>
    <input type="number" name="sort_order" value="10" min="0" step="5">
    <div class="mt12"><button class="btn" type="submit">Add Prompt</button></div>
  </form>

  <!-- Prompts List -->
  <div class="card">
    <div class="flex" style="justify-content:space-between;">
      <div><strong>Prompts</strong> ·
        <?php echo $category_id ? esc("Category #$category_id") : "Global"; ?>
        · Topic #<?php echo (int)$topic_id; ?> · <?php echo esc(strtoupper($typeFilter)); ?>
      </div>
      <form method="post">
        <input type="hidden" name="csrf" value="<?php echo esc($csrf); ?>">
        <input type="hidden" name="action" value="bulk_sort">
        <input type="hidden" name="category_id" value="<?php echo (int)$category_id; ?>">
        <input type="hidden" name="topic_id" value="<?php echo (int)$topic_id; ?>">
        <input type="hidden" name="type" value="<?php echo esc($typeFilter); ?>">
        <button class="btn ghost" type="submit" title="Save all sort orders in this list">Save All Sort</button>
      </form>
    </div>

    <div class="grid head mt12">
      <div>Label</div><div>Type</div><div>Order</div><div>Active</div><div>Actions</div>
    </div>

    <?php if(!$list): ?>
      <div class="warn pill">No prompts found for this filter.</div>
    <?php endif; ?>

    <?php foreach($list as $row): ?>
      <form method="post" class="grid mt8" style="align-items:center;">
        <input type="hidden" name="csrf" value="<?php echo esc($csrf); ?>">
        <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
        <input type="hidden" name="category_id" value="<?php echo (int)$category_id; ?>">
        <input type="hidden" name="topic_id" value="<?php echo (int)$topic_id; ?>">
        <input type="hidden" name="type" value="<?php echo esc($typeFilter); ?>">

        <input type="text" name="label" value="<?php echo esc($row['label']); ?>">
        <textarea name="prompt_text" rows="5"><?php echo esc($row['prompt_text'] ?? ''); ?></textarea>

        <div class="muted"><?php echo esc($row['type']); ?></div>

        <input type="number" name="sort_order" value="<?php echo (int)$row['sort_order']; ?>" min="0" step="5">

        <label class="flex">
          <input type="checkbox" name="is_active" <?php echo $row['is_active'] ? 'checked' : ''; ?>>
          <span class="muted">active</span>
        </label>

        <div class="flex">
          <button class="btn" name="action" value="update_prompt">Save</button>
          <button class="btn ghost" name="action" value="delete_prompt" onclick="return confirm('Delete this prompt?')">Delete</button>
        </div>
      </form>
      <!-- For bulk sort -->
      <form method="post" style="display:none">
        <input type="hidden" name="csrf" value="<?php echo esc($csrf); ?>">
        <input type="hidden" name="action" value="bulk_sort">
        <input type="hidden" name="category_id" value="<?php echo (int)$category_id; ?>">
        <input type="hidden" name="topic_id" value="<?php echo (int)$topic_id; ?>">
        <input type="hidden" name="type" value="<?php echo esc($typeFilter); ?>">
        <input type="hidden" name="rows[<?php echo (int)$row['id']; ?>]" value="<?php echo (int)$row['sort_order']; ?>">
      </form>
    <?php endforeach; ?>
  </div>

  <p class="muted mt16">
    Tip: Use <strong>Global</strong> scope to add default prompts.  
    When you add **Category-specific** prompts for the same topic, your frontend will show those first and top-up from Global up to 5 items.
  </p>
   <!-- 🧭 Admin navigation links -->
  <p style="margin-top:8px">
    <a href="dashboard.php">← Back to Dashboard</a> ·
    <a href="categories.php">Manage Categories</a> ·
    <a href="topics.php">Manage Topics</a>
  </p>
</body>
</html>
