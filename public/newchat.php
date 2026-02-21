<?php
session_start();
require __DIR__ . '/../app/backend/db.php';

/* ---------- Auth guard + user name / initial ---------- */
$isLoggedIn = isset($_SESSION['user']) && is_array($_SESSION['user']);
$user = $isLoggedIn ? $_SESSION['user'] : null;

$userIdPhp = $isLoggedIn ? (int)($user['user_id'] ?? ($user['id'] ?? 0)) : 0;

$userName = $isLoggedIn
  ? ($user['name'] ?? ($user['username'] ?? 'User'))
  : 'Guest';

$userInitial = strtoupper(substr($userName, 0, 1));


/* ---------- Helper: category-scoped paid checker ---------- */
if (!function_exists('userHasPaidForCategory')) {
  function userHasPaidForCategory(PDO $pdo, int $userId, int $categoryId): bool {
    $stmt = $pdo->prepare("
      SELECT has_paid_prompts,
             (COALESCE(expires_at, NOW()) >= NOW()) AS active
      FROM user_access
      WHERE user_id = ? AND category_id = ?
      LIMIT 1
    ");
    $stmt->execute([$userId, $categoryId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? ((int)$row['has_paid_prompts'] === 1 && (int)$row['active'] === 1) : false;
  }
}

/* ---------- Categories ---------- */
$categories = $pdo->query("
  SELECT id, name, icon, css_class
  FROM categories
  WHERE is_active = 1
  ORDER BY sort_order, name
")->fetchAll(PDO::FETCH_ASSOC);

/* ---------- Global price / currency (read once) ---------- */
$globalPrice = 499.00;
$globalCurr = 'INR';
try {
  $cfg = $pdo->query("
    SELECT cfg_key, cfg_val
    FROM site_config
    WHERE cfg_key IN ('premium_price','premium_currency')
  ")->fetchAll(PDO::FETCH_KEY_PAIR);

  if (!empty($cfg)) {
    if (isset($cfg['premium_price']))  $globalPrice = (float)$cfg['premium_price'];
    if (isset($cfg['premium_currency'])) $globalCurr = $cfg['premium_currency'];
  }
} catch (PDOException $e) {
  // Keep defaults on error
}

/* ---------- Topics (prepared) ---------- */
$getTopics = $pdo->prepare("
  SELECT t.id, t.name
  FROM category_topics ct
  JOIN topics t ON t.id = ct.topic_id
  WHERE ct.category_id = ? AND t.is_active = 1
  ORDER BY ct.sort_order, t.name
");

/* ---------- Prompts fetcher ---------- */
function fetchPrompts(PDO $pdo, int $categoryId, int $topicId, string $type, int $limit = 5): array {
  $s1 = $pdo->prepare("
    SELECT id, label
    FROM prompts
    WHERE is_active=1 AND type=? AND topic_id=? AND category_id=?
    ORDER BY sort_order, id
    LIMIT $limit
  ");
  $s1->execute([$type, $topicId, $categoryId]);
  $rows = $s1->fetchAll(PDO::FETCH_ASSOC);
  if (count($rows) >= $limit) return $rows;

  $params = [$type, $topicId];
  $excludeSql = '';
  if ($rows) {
    $placeholders = implode(',', array_fill(0, count($rows), '?'));
    $excludeSql = "AND label NOT IN ($placeholders)";
    foreach ($rows as $r) $params[] = $r['label'];
  }
  $missing = $limit - count($rows);
  $s2 = $pdo->prepare("
    SELECT id, label
    FROM prompts
    WHERE is_active=1 AND type=? AND topic_id=? AND category_id IS NULL
    $excludeSql
    ORDER BY sort_order, id
    LIMIT $missing
  ");
  $s2->execute($params);
  return array_merge($rows, $s2->fetchAll(PDO::FETCH_ASSOC));
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <title>Busineger</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="color-scheme" content="light dark">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

  <style>
    /* ===================== BASE + TOKENS ===================== */
    :root{
      --vh: 1vh; /* will be updated by JS to real viewport height */

      /* brand */
      --brand:#2563eb;
      --brand-700:#1d4ed8;
      --brand-800:#1e40af;
      --brand-glow:rgba(37,99,235,.35);

      /* dark default */
      --bg:#020617;
      --ink:#e5edff;
      --ink-2:#cbd5f5;
      --muted:#9ca3af;
      --muted-2:#6b7280;

      --panel:#020617;
      --panel-2:#020617;
      --glass:rgba(15,23,42,.9);
      --sidebar-start:#020617;
      --sidebar-end:#020617;
      --line:rgba(51,65,85,.9);

      --shadow-sm:0 6px 18px rgba(0,0,0,.7);
      --shadow-md:0 14px 40px rgba(0,0,0,.9);
      --shadow-lg:0 28px 80px rgba(0,0,0,1);

      --fs-xs: 11px;
      --fs-sm: 13px;
      --fs-md: 15px;
      --fs-lg: 17px;
      --fs-xl: 20px;

      --radius-sm: 10px;
      --radius-md: 12px;
      --radius-lg: 16px;
      --radius-xl: 22px;

      --depth:900px;
    }

    /* Light theme override */
    html[data-theme="light"]{
      --bg:#f3f4f6;
      --ink:#020617;
      --ink-2:#111827;
      --muted:#6b7280;
      --muted-2:#4b5563;

      --panel:#ffffff;
      --panel-2:#f9fafb;
      --glass:rgba(255,255,255,.9);
      --sidebar-start:#eff6ff;
      --sidebar-end:#e5e7eb;
      --line:rgba(209,213,219,1);

      --shadow-sm:0 4px 14px rgba(15,23,42,.1);
      --shadow-md:0 10px 30px rgba(15,23,42,.18);
      --shadow-lg:0 20px 60px rgba(15,23,42,.25);
    }

    *{box-sizing:border-box}

    html,body{
      height:100%;
      min-height:100%;
      text-size-adjust:100%;
      -webkit-text-size-adjust:100%;
    }

    body{
      margin:0;
      padding:0;
      background:var(--bg);
      color:var(--ink);
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
      font-size: var(--fs-md);
      line-height:1.45;
      overflow:hidden;           /* no page scroll – only inner sections scroll */
      height:calc(var(--vh) * 100); /* 🔥 use real viewport height */
    }

    a{color:inherit;text-decoration:none}
    img,video{max-width:100%;height:auto;display:block;}

    /* ========= FULLSCREEN APP SHELL (NO GAPS) ========= */
    .app-shell{
      width:100%;
      height:calc(var(--vh) * 100);
      max-height:calc(var(--vh) * 100);
      margin:0;
      padding:0;
      display:grid;
      grid-template-columns:84px minmax(0,1fr);
      gap:0;
    }

    /* ========= LEFT MAIN NAV BAR ========= */
    .side-nav{
      background:linear-gradient(180deg,#020617,#020617);
      box-shadow: 0 0 0 1px rgba(15,23,42,1), 12px 0 40px rgba(0,0,0,1);
      padding:14px 10px 16px;
      display:flex;
      flex-direction:column;
      justify-content:space-between;
      align-items:center;
      position:relative;
      overflow:hidden;
      flex-shrink:0;
      height:100%;
    }
    .side-nav::before{
      content:"";
      position:absolute;
      inset:-40% -40%;
      background:
        radial-gradient(260px 260px at 0% 0%, rgba(59,130,246,.5), transparent 60%),
        radial-gradient(260px 260px at 100% 100%, rgba(56,189,248,.4), transparent 60%);
      opacity:.7;
      filter:blur(18px);
      pointer-events:none;
    }
    .side-nav-inner{
      position:relative;
      z-index:1;
      width:100%;
      height:100%;
      display:flex;
      flex-direction:column;
      justify-content:space-between;
      align-items:center;
      gap:10px;
    }

    .side-avatar-wrap{
      width:48px;height:48px;
      border-radius:999px;
      padding:2px;
      background:conic-gradient(from 210deg, rgba(59,130,246,1), rgba(56,189,248,1), rgba(16,185,129,1), rgba(59,130,246,1));
      box-shadow:0 12px 30px rgba(0,0,0,1);
    }
    .side-avatar{
      width:100%;height:100%;
      border-radius:999px;
      background: radial-gradient(circle at 30% 0, #f9fafb 0, #cbd5f5 30%, #1f2937 100%);
      display:flex;
      align-items:center;
      justify-content:center;
      color:#0f172a;
      font-weight:900;
      font-size:1.1rem;
      box-shadow:inset 0 0 0 1px rgba(255,255,255,.65);
    }
    .side-user-name{
      margin-top:8px;
      font-size:var(--fs-sm);
      font-weight:900;
      text-align:center;
      color:#e5edff;
      max-width:70px;
      overflow:hidden;
      text-overflow:ellipsis;
      white-space:nowrap;
    }




    .side-light-mode,
.side-user-name{
  margin-top:2px;
  font-size:var(--fs-xs);
  font-weight:600;
  color:#ffffff;   /* always white */
  text-align:center;
  opacity:0.95;
}



    .side-menu{
      display:flex;
      flex-direction:column;
      gap:10px;
      margin-top:10px;
      flex:1;
    }
    .side-item{
      width:100%;
      border-radius:14px;
      padding:10px 6px;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      gap:4px;
      font-size:var(--fs-xs);
      font-weight:600;
      text-align:center;
      cursor:pointer;
      border:1px solid rgba(148,163,184,.45);
      background:
        radial-gradient(circle at 0% 0%, rgba(59,130,246,.25), transparent 55%),
        rgba(15,23,42,.98);
      color:#e5e7eb;
      box-shadow:0 8px 22px rgba(0,0,0,.9);
      transition:
        transform .15s ease,
        box-shadow .15s ease,
        border-color .15s ease,
        background .15s ease,
        color .15s ease;
    }
    .side-item span.icon{font-size:18px;}
    .side-item:hover{
      transform:translateY(-3px);
      box-shadow:0 14px 38px rgba(0,0,0,1);
      border-color:rgba(129,140,248,.9);
      background:
        radial-gradient(circle at 10% 0%, rgba(59,130,246,.45), transparent 55%),
        rgba(15,23,42,1);
    }
    .side-item.active{
      border-color:rgba(129,140,248,1);
      background:
        radial-gradient(circle at 20% 0%, rgba(59,130,246,.55), transparent 60%),
        radial-gradient(circle at 80% 100%, rgba(56,189,248,.4), transparent 60%),
        linear-gradient(145deg,#1e293b,#020617);
      color:#e5edff;
    }
    .side-item.danger{
      border-color:rgba(248,113,113,.6);
      color:rgba(254,202,202,.95);
    }
    .side-bottom{
      display:flex;
      flex-direction:column;
      gap:10px;
      width:100%;
    }

    /* MOBILE BEHAVIOUR FOR SIDE NAV (PROFILE BAR DRAWER) */
    @media (max-width:1024px){
      .app-shell{
        grid-template-columns:minmax(0,1fr);
      }
      .side-nav{
        position:fixed;
        left:0;top:0;bottom:0;
        height:calc(var(--vh) * 100);
        width:260px;
        transform:translateX(-105%);
        transition:transform .32s cubic-bezier(.22,.8,.25,1); /* smoother, no box-shadow animation */
        will-change:transform;
        z-index:60;
      }
      body.nav-open .side-nav{
        transform:translateX(0);
        box-shadow:0 0 0 9999px rgba(0,0,0,.55), 12px 0 40px rgba(0,0,0,1);
      }
    }

    /* ========= MAIN: LEFT INDUSTRY + RIGHT CHAT (FULL HEIGHT) ========= */
    .shell{
      width:100%;
      height:calc(var(--vh) * 100);
      max-height:calc(var(--vh) * 100);
      display:grid;
      grid-template-columns:minmax(260px, 340px) minmax(0,1fr);
      gap:0;
      background:
        radial-gradient(1200px 900px at 0% 0%, rgba(37,99,235,.18), transparent 60%),
        radial-gradient(1200px 900px at 100% 100%, rgba(8,47,73,.6), transparent 55%),
        #020617;
      overflow:hidden;
    }
    @media (max-width:1024px){
      .shell{
        grid-template-columns:1fr;
      }
    }

    /* ========= LEFT SIDEBAR / DRAWER (INDUSTRIES) ========= */
    .left{
      position:relative;
      background:
        radial-gradient(900px 900px at 0% 0%, rgba(30,64,175,.75), transparent 55%),
        radial-gradient(900px 900px at 100% 100%, rgba(8,47,73,.9), transparent 55%),
        #020617;
      border-right:1px solid rgba(15,23,42,1);
      padding:12px 12px 10px;
      display:flex;
      flex-direction:column;
      gap:8px;
      height:calc(var(--vh) * 100);
      min-height:0;
      max-height:calc(var(--vh) * 100);
      box-shadow: 0 0 0 1px rgba(15,23,42,1);
      overflow:hidden;
    }
    @media (max-width:1024px){
      .left{
        position:fixed;
        top:0;bottom:0;left:0;
        width:min(92vw,380px);
        max-width:92vw;
        height:calc(var(--vh) * 100);
        max-height:calc(var(--vh) * 100);
        transform:translateX(-105%);
        transition:transform .32s cubic-bezier(.22,.8,.25,1); /* smoother, single slider */
        will-change:transform;
        z-index:50;
      }
      body.drawer-open .left{
        transform:translateX(0);
        box-shadow:0 0 0 9999px rgba(0,0,0,.55), 0 24px 80px rgba(0,0,0,.95);
      }
    }

    .sidebar-head{display:flex;align-items:center;gap:8px;}
    .sidebar-title{font-size:var(--fs-lg);font-weight:900;color:#e5edff;}
    .sidebar-sub{font-size:var(--fs-sm);color:var(--muted);}

    .search{position:relative;margin-top:4px;}
    .search input{
      width:100%;
      padding:10px 34px 10px 10px;
      border-radius:var(--radius-md);
      border:1px solid rgba(148,163,184,.6);
      background:rgba(15,23,42,.9);
      color:#e5edff;
      outline:none;
      font-size:var(--fs-md);
      box-shadow:var(--shadow-sm);
    }
    .search input::placeholder{color:rgba(148,163,184,.9);}
    .search .icon{
      position:absolute;
      right:9px;
      top:50%;
      transform:translateY(-50%);
      font-size:16px;
      opacity:.8;
    }
    html[data-theme="light"] .search input{
      background:#f9fafb;
      color:#111827;
      border-color:#d1d5db;
    }

    .cats{
      flex:1 1 auto;
      min-height:0;
      margin-top:8px;
      overflow-y:auto;
      overflow-x:hidden;
      padding-right:4px;
    }
    .cats::-webkit-scrollbar{width:8px;}
    .cats::-webkit-scrollbar-thumb{
      background:linear-gradient(180deg,rgba(59,130,246,.85),rgba(30,64,175,.9));
      border-radius:999px;
    }

    .cat{
      background:
        radial-gradient(900px 400px at 0% 0%, rgba(37,99,235,.35), transparent 60%),
        rgba(15,23,42,.98);
      border-radius: var(--radius-md);
      padding:10px;
      margin-bottom:8px;
      border:1px solid rgba(148,163,184,.55);
      position:relative;
      transform-origin:top center;
      transition:
        transform .15s ease,
        box-shadow .15s ease,
        border-color .15s ease,
        background .15s ease;
      color:#e5edff;
    }
    .cat:hover{
      transform:translateY(-3px);
      box-shadow:0 16px 40px rgba(0,0,0,1);
      border-color:rgba(129,140,248,.9);
      background:
        radial-gradient(900px 400px at 0% 0%, rgba(59,130,246,.55), transparent 60%),
        rgba(15,23,42,1);
    }
    html[data-theme="light"] .cat{
      background:linear-gradient(135deg,#ffffff,#f9fafb);
      color:#111827;
      border-color:#e5e7eb;
    }
    html[data-theme="light"] .cat:hover{
      background:linear-gradient(135deg,#eff6ff,#ffffff);
      box-shadow:0 12px 30px rgba(15,23,42,.15);
      border-color:#bfdbfe;
    }

    .cat-head{display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;}
    .cat-left{display:flex;align-items:center;gap:10px;min-width:0;}

    .cat-ico{
      width:42px;height:42px;
      border-radius:12px;
      display:grid;
      place-items:center;
      font-size:18px;
      font-weight:800;
      color:#111827;
      flex:0 0 auto;
    }
    .ico-pet{ background: linear-gradient(135deg,#7dd3fc,#60a5fa); }
    .ico-salon{ background: linear-gradient(135deg,#fda4af,#fb7185); }
    .ico-car{ background: linear-gradient(135deg,#fef08a,#f59e0b); }
    .ico-computer{ background: linear-gradient(135deg,#bbf7d0,#34d399); }
    .ico-real{ background: linear-gradient(135deg,#c7a2ff,#7c3aed); color:#f9fafb; }
    .ico-beauty{ background: linear-gradient(135deg,#ffedd5,#fb923c); }
    .ico-interior{ background: linear-gradient(135deg,#cfe9ff,#60a5fa); }
    .ico-jewel{ background: linear-gradient(135deg,#fff1f2,#f472b6); }

    .cat-name{font-weight:800;font-size:var(--fs-md);}
    .cat-meta{font-size:var(--fs-xs);color:var(--muted);}

    .toggle-btn{
      border-radius:10px;
      padding:6px 11px;
      border:1px solid rgba(148,163,184,.7);
      background:rgba(15,23,42,.95);
      color:#e5edff;
      font-size:var(--fs-sm);
      font-weight:700;
      cursor:pointer;
      box-shadow:var(--shadow-sm);
      transition:
        transform .12s ease,
        background .12s ease,
        border-color .12s ease,
        color .12s ease;
      white-space:nowrap;
    }
    .toggle-btn:hover{
      transform:translateY(-2px);
      border-color:rgba(129,140,248,1);
      background:rgba(15,23,42,1);
    }
    html[data-theme="light"] .toggle-btn{
      background:#ffffff;
      color:#1f2937;
      border-color:#d1d5db;
    }
    html[data-theme="light"] .toggle-btn:hover{
      background:#eff6ff;
      border-color:#93c5fd;
    }

    .cat-body, .topic-body{ display:none; }
    .cat-body.open, .topic-body.open{ display:block; }

    .topic{
      border:1px dashed rgba(148,163,184,.6);
      border-radius:12px;
      padding:10px;
      margin-top:8px;
      background:rgba(15,23,42,.9);
      color:#e5edff;
    }
    .topic-head{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:8px;
      cursor:pointer;
      flex-wrap:wrap;
    }
    .topic-title{font-weight:900;font-size:var(--fs-md);}
    .mini-toggle{padding:5px 9px;font-size:var(--fs-xs);}

    html[data-theme="light"] .topic{
      background:#f9fafb;
      border-color:#e5e7eb;
      color:#111827;
    }

    .sub{
      background:linear-gradient(120deg,rgba(15,23,42,1),rgba(30,64,175,.95));
      border-radius:12px;
      padding:11px;
      margin-top:8px;
      border:1px solid rgba(96,165,250,.75);
      color:#e5edff;
    }
    html[data-theme="light"] .sub{
      background:linear-gradient(120deg,#eff6ff,#ffffff);
      border-color:#bfdbfe;
      color:#111827;
    }
    .sub-label{font-weight:700;margin-bottom:4px;}
    .sub-label span{display:block;line-height:1.5;}

    .sub-actions{
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      margin-top:6px;
    }
    .sub-action-btn{
      border-radius:10px;
      padding:8px 11px;
      border:1px solid rgba(148,163,184,.8);
      background:
        radial-gradient(circle at 0% 0%, rgba(59,130,246,.45), transparent 60%),
        rgba(15,23,42,.95);
      color:#e5edff;
      font-size:var(--fs-sm);
      font-weight:800;
      cursor:pointer;
      box-shadow:var(--shadow-sm);
      display:inline-flex;
      align-items:center;
      gap:6px;
      white-space:nowrap;
      transition:
        transform .12s ease,
        box-shadow .12s ease,
        filter .12s ease,
        border-color .12s ease;
    }
    .sub-action-btn:hover{
      transform:translateY(-2px);
      box-shadow:var(--shadow-md);
      border-color:rgba(129,140,248,1);
      filter:brightness(1.05);
    }
    html[data-theme="light"] .sub-action-btn{
      background:#ffffff;
      color:#111827;
      border-color:#d1d5db;
    }
    html[data-theme="light"] .sub-action-btn:hover{
      background:#eff6ff;
      border-color:#93c5fd;
    }

    .paid{
      margin-top:6px;
      padding:8px 10px;
      border-radius:12px;
      background:linear-gradient(90deg,#111827,#020617);
      border:1px solid rgba(250,204,21,.7);
      color:#fef3c7;
      display:flex;
      justify-content:space-between;
      align-items:center;
      font-size:var(--fs-sm);
      gap:8px;
    }
    .paid strong{color:#facc15;}
    html[data-theme="light"] .paid{
      background:linear-gradient(90deg,#fef9c3,#fef3c7);
      color:#854d0e;
      border-color:#facc15;
    }

    .left-footer{
      margin-top:8px;
      padding:8px 10px;
      border-radius:12px;
      border:1px solid rgba(148,163,184,.65);
      background:rgba(15,23,42,.95);
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:8px;
      font-size:var(--fs-xs);
      color:#e5edff;
      flex-shrink:0;
    }
    html[data-theme="light"] .left-footer{
      background:#ffffff;
      border-color:#e5e7eb;
      color:#111827;
    }

    .theme-toggle{
      display:flex;
      align-items:center;
      gap:7px;
      cursor:pointer;
      font-size:var(--fs-sm);
      font-weight:800;
    }
    .toggle{
      --w:46px;--h:24px;
      width:var(--w);height:var(--h);
      border-radius:999px;
    
      background:rgba(15,23,42,.95);
      position:relative;
    }
    .knob{
      position:absolute;
      top:2px;left:2px;
      width:20px;height:20px;
      border-radius:999px;
      background:radial-gradient(circle at 30% 0,#f9fafb 0,#e5e7eb 30%,#d1d5db 100%);
      box-shadow:var(--shadow-sm);
      transition:left .2s cubic-bezier(.2,.9,.2,1),background .2s;
    }
    .toggle.active .knob{
      left:calc(100% - 2px - 20px);
    }
    html[data-theme="light"] .toggle{
      background:#f9fafb;
      border-color:#d1d5db;
    }

    /* ========= RIGHT CHAT COLUMN ========= */
    .right{
      height:calc(var(--vh) * 100);
      max-height:calc(var(--vh) * 100);
      display:flex;
      align-items:stretch;
      justify-content:stretch;
      overflow:hidden;
      padding:0;
    }

    #chat-container{
      flex:1;
      width:100%;
      max-width:none;
      display:flex;
      flex-direction:column;
      background:
        radial-gradient(900px 900px at 0% 0%, rgba(37,99,235,.3), transparent 60%),
        radial-gradient(900px 900px at 100% 100%, rgba(8,47,73,.7), transparent 60%),
        #020617;
      border-left:1px solid rgba(15,23,42,1);
      box-shadow:
        0 0 0 1px rgba(15,23,42,1),
        0 24px 80px rgba(0,0,0,1);
      height:calc(var(--vh) * 100);
      max-height:calc(var(--vh) * 100);
      transform-style:preserve-3d;
      overflow:hidden; /* only messages scroll */
    }
    html[data-theme="light"] #chat-container{
      background:
        radial-gradient(900px 900px at 0% 0%, rgba(191,219,254,.9), transparent 60%),
        #ffffff;
      box-shadow: 0 0 0 1px #e5e7eb, 0 22px 60px rgba(15,23,42,.15);
    }

    .chat-header{
      padding:10px 12px;
      background:
        radial-gradient(260px 120px at 0% 0%, rgba(37,99,235,.9), transparent 65%),
        radial-gradient(260px 120px at 100% 0%, rgba(8,47,73,.9), transparent 65%),
        linear-gradient(90deg,#020617,#020617);
      color:#e5edff;
      display:flex;
      align-items:center;
      gap:8px;
      flex-wrap:wrap;
      font-size:var(--fs-lg);
      font-weight:900;
      letter-spacing:.2px;
      z-index:2;
      flex-shrink:0;
    }
    html[data-theme="light"] .chat-header{
      background:
        radial-gradient(260px 120px at 0% 0%, rgba(191,219,254,1), transparent 65%),
        #ffffff;
      color:#111827;
      border-bottom:1px solid #e5e7eb;
    }

    #drawerToggleBtn, #changeCatBtn{
  border-radius:10px;
  padding:0 12px;
  height:34px;
  border:1px solid rgba(148,163,184,.8);
  background:rgba(15,23,42,.95);
  color:#e5edff;
  font-size:var(--fs-sm);
  font-weight:800;
  cursor:pointer;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:6px;
  box-shadow:var(--shadow-sm);
  transition: transform .12s ease, box-shadow .12s ease, filter .12s ease, border-color .12s ease;
  white-space:nowrap;
  flex:0 0 auto;
}
#drawerToggleBtn:hover, #changeCatBtn:hover{
  transform:translateY(-2px);
  box-shadow:var(--shadow-md);
  filter:brightness(1.05);
  border-color:rgba(129,140,248,1);
}
html[data-theme="light"] #drawerToggleBtn,
html[data-theme="light"] #changeCatBtn{
  background:#ffffff;
  color:#111827;
  border-color:#d1d5db;
  box-shadow:0 6px 18px rgba(148,163,184,.35);
}
html[data-theme="light"] #drawerToggleBtn:hover,
html[data-theme="light"] #changeCatBtn:hover{
  background:#eff6ff;
  border-color:#93c5fd;
}
#drawerToggleBtn{display:none;}



/* ✅ USAGE PILL (header) */
.usage-pill{
  height:34px;
  padding:0 10px;
  border-radius:999px;
  border:1px solid rgba(148,163,184,.55);
  background:rgba(15,23,42,.92);
  color:#e5edff;
  display:inline-flex;
  align-items:center;
  gap:8px;
  font-size:var(--fs-xs);
  font-weight:900;
  letter-spacing:.02em;
  box-shadow:var(--shadow-sm);
  user-select:none;
  white-space:nowrap;
}
.usage-pill .dot{
  width:8px;
  height:8px;
  border-radius:999px;
  background:rgba(34,211,238,.95);
  box-shadow:0 0 0 3px rgba(34,211,238,.18);
}
.usage-pill.danger .dot{ background:rgba(239,68,68,.95); box-shadow:0 0 0 3px rgba(239,68,68,.18); }
.usage-pill.unlimited .dot{ background:rgba(185,253,80,.95); box-shadow:0 0 0 3px rgba(185,253,80,.18); }

html[data-theme="light"] .usage-pill{
  background:rgba(255,255,255,.92);
  color:#0f172a;
  border-color:rgba(15,23,42,.14);
  box-shadow:0 6px 18px rgba(148,163,184,.35);
}



    .brand{
      display:flex;
      align-items:center;
      gap:10px;
      flex:1 1 auto;
      min-width:200px;
      justify-content:space-between;
    }
    .brand-main{
      display:flex;
      align-items:center;
      gap:8px;
      flex:0 1 auto;
      min-width:0;
    }
    .brand-title-wrap{
      display:flex;
      flex-direction:column;
      gap:1px;
      min-width:0;
    }
    .brand-right{
      display:flex;
      align-items:center;
      gap:6px;
      flex:0 0 auto;
    }

    .brand-logo-image{
      width:32px;
      height:32px;
      border-radius:999px;
      padding:3px;
      object-fit:contain;
      background:white;
    
      flex:0 0 auto;
    }

    .chat-title{
      flex:0 0 auto;
      white-space:nowrap;
      font-size:1.4rem;
      font-weight:900;
      letter-spacing:.05em;
    }

    .selected-category-label{
      font-size:var(--fs-xs);
      color:var(--muted);
      min-height:1em;
      white-space:nowrap;
      text-overflow:ellipsis;
      overflow:hidden;
      max-width:220px;
    }

    #drawerToggleBtn, #changeCatBtn{
      border-radius:10px;
      padding:0 12px;
      height:34px;
      border:1px solid rgba(148,163,184,.8);
      background:rgba(15,23,42,.95);
      color:#e5edff;
      font-size:var(--fs-sm);
      font-weight:800;
      cursor:pointer;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:6px;
      box-shadow:var(--shadow-sm);
      transition:
        transform .12s ease,
        box-shadow .12s ease,
        filter .12s ease,
        border-color .12s ease;
      white-space:nowrap;
      flex:0 0 auto;
    }
    #drawerToggleBtn:hover, #changeCatBtn:hover{
      transform:translateY(-2px);
      box-shadow:var(--shadow-md);
      filter:brightness(1.05);
      border-color:rgba(129,140,248,1);
    }
    html[data-theme="light"] #drawerToggleBtn,
    html[data-theme="light"] #changeCatBtn{
      background:#ffffff;
      color:#111827;
      border-color:#d1d5db;
      box-shadow:0 6px 18px rgba(148,163,184,.35);
    }
    html[data-theme="light"] #drawerToggleBtn:hover,
    html[data-theme="light"] #changeCatBtn:hover{
      background:#eff6ff;
      border-color:#93c5fd;
    }
    #drawerToggleBtn{display:none;}

    /* PROFILE AVATAR BUTTON */
    #profileToggleBtn{
      display:flex;
      align-items:center;
      gap:6px;
      padding:2px 4px;
      border-radius:999px;
      border:none;
      background:transparent;
      box-shadow:none;
      cursor:pointer;
      position:relative;
      overflow:hidden;
      flex:0 0 auto;
    }
    #profileToggleBtn .profile-mini-avatar{
      width:34px;
      height:34px;
      border-radius:999px;
      display:grid;
      place-items:center;
      font-size:0.95rem;
      font-weight:900;
      background:radial-gradient(circle at 30% 0%,#ffffff,#cbd5f5,#1e40af);
      color:#020617;
      box-shadow: 0 6px 18px rgba(0,0,0,.7), inset 0 0 5px rgba(255,255,255,.7);
      z-index:1;
      border:1px solid rgba(148,163,184,.7);
    }
    #profileToggleBtn .profile-mini-name{
      max-width:150px;
      overflow:hidden;
      text-overflow:ellipsis;
      white-space:nowrap;
      font-size:var(--fs-xs);
      font-weight:800;
      color:#f9fafb;
      z-index:1;
    }
    html[data-theme="light"] #profileToggleBtn .profile-mini-name{
      color:#111827;
    }
    /* pill visible in light mode */
    html[data-theme="light"] #profileToggleBtn{
      background:rgba(255,255,255,.9);
      border:1px solid #d1d5db;
      padding:2px 8px;
      box-shadow:0 6px 18px rgba(148,163,184,.35);
    }

    /* hide name on mobile, show on laptop/desktop */
    @media (max-width:768px){
      #profileToggleBtn .profile-mini-name{
        display:none;
      }
    }
    @media (min-width:769px){
      #profileToggleBtn .profile-mini-name{
        display:inline;
      }
    }

    /* CLEAR CHAT ICON BUTTON */
    #clearChatIconBtn{
      border-radius:999px;
      width:34px;
      height:34px;
      border:1px solid rgba(148,163,184,.8);
      background:rgba(15,23,42,.98);
      color:#e5edff;
      font-size:16px;
      cursor:pointer;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      box-shadow:var(--shadow-sm);
      transition:
        transform .12s ease,
        box-shadow .12s ease,
        filter .12s ease,
        border-color .12s ease;
    }
    #clearChatIconBtn svg{
      width:18px;
      height:18px;
      stroke:currentColor;
      stroke-width:1.8;
      fill:none;
      stroke-linecap:round;
      stroke-linejoin:round;
    }
    #clearChatIconBtn:hover{
      transform:translateY(-2px);
      box-shadow:var(--shadow-md);
      border-color:rgba(248,113,113,1);
      filter:brightness(1.05);
    }
    html[data-theme="light"] #clearChatIconBtn{
      background:#ffffff;
      color:#111827;
      border-color:#d1d5db;
      box-shadow:0 6px 18px rgba(148,163,184,.35);
    }

    .industry-hint{ display:none !important; }

    /* ====== MOBILE 3D CTA BUTTON (See All Prompts) ====== */
    .mobile-actions-wrapper{
      display:none;
      position:relative;
      flex:0 0 auto;
      margin:6px 12px 0;
      justify-content:center;
      align-items:center;
    }

    /* 🔥 Even more glassy / 3D / 4K button – DARK NAVY BLUE */
    #mobileActionsBtn{
      position:relative;
      padding:9px 18px;
      height:auto;
      border-radius:999px;
      border:1px solid rgba(129,140,248,0.95);
      background:
        radial-gradient(circle at 15% -20%, rgba(148,163,253,.85), transparent 55%),
        radial-gradient(circle at 90% 120%, rgba(37,99,235,.55), transparent 60%),
        linear-gradient(135deg, #020617 0%, #020b3f 30%, #1e3a8a 65%, #2563eb 100%);
     color: darkblue;
      font-size:var(--fs-sm);
      font-weight:900;
      cursor:pointer;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:8px;
      box-shadow:
        0 0 0 1px rgba(15,23,42,1),
        0 16px 40px rgba(15,23,42,1),
        0 0 70px rgba(37,99,235,1);
      white-space:nowrap;
      transition:
        transform .14s ease,
        box-shadow .14s ease,
        filter .14s ease;
      text-align:center;
      text-shadow:0 0 14px rgba(15,23,42,.9);
      line-height:1.3;
      transform:translateY(0) translateZ(0);
      animation:ctaPulse 1.4s infinite alternate;
      overflow:hidden;
      backdrop-filter:blur(18px);
      -webkit-backdrop-filter:blur(18px);
    }

    #mobileActionsBtn .btn-main-text{
      display:inline-block;
    }

    #mobileActionsBtn .btn-icon{
      width:24px;
      height:24px;
      border-radius:999px;
      border:1px solid rgba(191,219,254,.9);
      display:inline-flex;
      align-items:center;
      justify-content:center;
      font-size:13px;
      font-weight:800;
      background:radial-gradient(circle at 30% 0%,#ffffff,#cbd5f5,#1e3a8a);
      color:#020617;
      box-shadow:0 4px 12px rgba(15,23,42,.9);
      transition:transform .18s ease, background .18s ease, box-shadow .18s ease;
    }

    #mobileActionsBtn.open{
      transform:translateY(-3px) scale(1.02);
      box-shadow:
        0 0 0 1px rgba(59,130,246,1),
        0 22px 50px rgba(15,23,42,1),
        0 0 100px rgba(59,130,246,1);
    }
    #mobileActionsBtn.open .btn-icon{
      transform:rotate(90deg);
      background:radial-gradient(circle at 30% 0%,#fee2e2,#fecaca,#b91c1c);
      color:#111827;
      box-shadow:0 6px 16px rgba(248,113,113,1);
    }

    #mobileActionsBtn::before{
      content:"";
      position:absolute;
      inset:2px;
      border-radius:999px;
      background:linear-gradient(145deg,
        rgba(255,255,255,.85),
        rgba(255,255,255,.15) 40%,
        transparent 65%);
      opacity:.35;
      mix-blend-mode:screen;
      pointer-events:none;
    }
    #mobileActionsBtn::after{
      content:"";
      position:absolute;
      top:-40%;
      left:-30%;
      width:60%;
      height:200%;
      background:linear-gradient(120deg,rgba(255,255,255,.0),rgba(255,255,255,.35),rgba(255,255,255,.0));
      transform:translateX(-140%);
      animation:btnSweep 2.2s infinite;
      pointer-events:none;
    }

    .mobile-actions-wrapper::after {
      content: "";
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
      bottom: -20px;
      width: 38px;
      height: 38px;
      background-image: url('https://static.vecteezy.com/system/resources/previews/021/996/874/large_2x/hand-cursor-icon-clip-art-free-png.png');
      background-size: contain;
      background-repeat: no-repeat;
      animation: arrowBounce 1s infinite alternate;
      filter: drop-shadow(0 4px 10px rgba(15,23,42,.9));
      pointer-events:none;
    }

    #mobileActionsBtn:hover{
      transform:translateY(-3px) scale(1.04);
      box-shadow:
        0 0 0 1px rgba(59,130,246,1),
        0 24px 52px rgba(15,23,42,1),
        0 0 110px rgba(59,130,246,1);
      filter:brightness(1.1);
    }

    html[data-theme="light"] #mobileActionsBtn{
      background:
        radial-gradient(circle at 15% -10%, rgba(255,255,255,.9), transparent 55%),
        linear-gradient(135deg,#1e3a8a,#2563eb,#60a5fa);
      color:#ffffff;
      border-color:#93c5fd;
      box-shadow:0 12px 30px rgba(59,130,246,.55);
    }

    @keyframes ctaPulse{
      0%{
        transform:translateY(0) scale(1);
        box-shadow:
          0 0 0 1px rgba(15,23,42,1),
          0 10px 26px rgba(15,23,42,1),
          0 0 40px rgba(56,189,248,.45);
        filter:brightness(1);
      }
      100%{
        transform:translateY(-4px) scale(1.05);
        box-shadow:
          0 0 0 1px rgba(59,130,246,1),
          0 22px 46px rgba(15,23,42,1),
          0 0 95px rgba(56,189,248,1);
        filter:brightness(1.14);
      }
    }

    @keyframes arrowBounce{
      0%{ transform:translate(-50%,0); opacity:0.75; }
      100%{ transform:translate(-50%,4px); opacity:1; }
    }

    @keyframes btnSweep{
      0%{ transform:translateX(-150%); opacity:0; }
      30%{ opacity:.7; }
      60%{ opacity:.4; }
      100%{ transform:translateX(170%); opacity:0; }
    }

    .mobile-actions-menu{
      position:absolute;
      top:110%;
      right:0;
      min-width:180px;
      padding:6px;
      border-radius:14px;
      background:rgba(15,23,42,.98);
      border:1px solid rgba(148,163,184,.85);
      box-shadow:0 18px 40px rgba(0,0,0,.95);
      display:none;
      flex-direction:column;
      gap:6px;
      z-index:10;
    }
    html[data-theme="light"] .mobile-actions-menu{
      background:#ffffff;
      border-color:#d1d5db;
      box-shadow:0 16px 38px rgba(15,23,42,.18);
    }

    .mobile-actions-item{
      border-radius:10px;
      padding:7px 10px;
      border:1px solid rgba(148,163,184,.8);
      background:rgba(15,23,42,.98);
      color:#e5edff;
      font-size:var(--fs-sm);
      font-weight:700;
      cursor:pointer;
      display:flex;
      align-items:center;
      gap:6px;
      white-space:nowrap;
      transition:background .12s ease, border-color .12s ease, transform .12s ease;
    }
    .mobile-actions-item:hover{
      background:rgba(30,64,175,1);
      border-color:rgba(129,140,248,1);
      transform:translateY(-1px);
    }
    html[data-theme="light"] .mobile-actions-item{
      background:#f9fafb;
      color:#111827;
      border-color:#e5e7eb;
    }
    html[data-theme="light"] .mobile-actions-item:hover{
      background:#eff6ff;
      border-color:#93c5fd;
    }

    .selected-category-tag{
      display:none;
      margin-top:6px;
      width:100%;
      text-align:center;
      font-size:var(--fs-xs);
      color:var(--muted);
      padding:6px 10px;
      border-radius:999px;
      border:1px solid rgba(148,163,184,.6);
      background:rgba(15,23,42,.96);
      box-shadow:var(--shadow-sm);
    }
    html[data-theme="light"] .selected-category-tag{
      background:#f9fafb;
      color:#4b5563;
      border-color:#d1d5db;
    }

    @media (max-width:1024px){
      #drawerToggleBtn{display:inline-flex;margin-left:auto;}
    }

    @media (max-width:560px){
      .shell{grid-template-columns:1fr;}
      .right #chat-container{
        max-width:none;
        width:100%;
      }
      #drawerToggleBtn, #changeCatBtn{
        display:none;
      }
      .mobile-actions-wrapper{
        display:flex;
        width:100%;
        margin-top:4px;
        flex-direction:column;
        justify-content:center;
        align-items:center;
      }
      #mobileActionsBtn{
        width:100%;
        max-width:340px;
        font-size:var(--fs-md);
        justify-content:center;
      }
      .mobile-actions-menu{ right:8px; }
      #profileToggleBtn .profile-mini-avatar{
        width:32px;height:32px;
      }
    }

    /* CHAT MESSAGES – only this scrolls */
    .chat-messages{
      flex:1;
      min-height:0;
      padding:10px 12px;
      overflow-y:auto;
      display:flex;
      flex-direction:column;
      gap:8px;
      background:transparent;
    }
.message{
  position: relative;            /* ✅ add this */
  max-width:min(92%,680px);
  border-radius:16px;
  padding:10px 52px 10px 12px;   /* ✅ right padding for copy button space */
  font-size:var(--fs-md);
  line-height:1.45;
  word-break:break-word;
}

    .message.bot{
      align-self:flex-start;
      background:
        radial-gradient(800px 800px at 0% 0%, rgba(37,99,235,.35), transparent 60%),
        rgba(15,23,42,.98);
      color:#e5edff;
      border:1px solid rgba(148,163,184,.6);
      box-shadow:var(--shadow-sm);
      border-bottom-left-radius:6px;
    }
    .message.user{
      align-self:flex-end;
      background:linear-gradient(135deg,#2563eb,#7c3aed);
      color:#ffffff;
      box-shadow:0 14px 40px rgba(37,99,235,.85);
      border-bottom-right-radius:6px;
    }
    html[data-theme="light"] .message.bot{
      background:#ffffff;
      color:#111827;
      border-color:#e5e7eb;
      box-shadow:0 6px 18px rgba(148,163,184,.3);
    }

    /* INPUT BAR – stuck at bottom, always visible */
    .chat-input{
      margin-top:auto;
      padding:8px 10px;
      display:flex;
      gap:8px;
      align-items:center;
      border-top:1px solid rgba(15,23,42,.9);
      background:rgba(15,23,42,.98);
      flex-shrink:0;
      margin-bottom:0;
    }
    html[data-theme="light"] .chat-input{
      background:#f9fafb;
      border-top:1px solid #e5e7eb;
    }
    .chat-input textarea{
      flex:1;
      min-height:42px;
      max-height:40vh;
      padding:8px 10px;
      border-radius:12px;
      border:1px solid rgba(30,64,175,.7);
      background:rgba(15,23,42,.98);
      color:#e5edff;
      font-size:var(--fs-md);
      line-height:1.4;
      resize:none;
      overflow-y:auto;
      outline:none;
      box-shadow:0 10px 30px rgba(0,0,0,.85);
    }
    .chat-input textarea::placeholder{color:rgba(148,163,184,.9);}
    html[data-theme="light"] .chat-input textarea{
      background:#ffffff;
      color:#111827;
      border-color:#d1d5db;
      box-shadow:0 8px 20px rgba(148,163,184,.35);
    }
    html[data-theme="light"] .chat-input textarea::placeholder{
      color:#9ca3af;
    }
    .chat-input button{
      flex:0 0 auto;
      padding:9px 13px;
      border-radius:12px;
      border:1px solid rgba(37,99,235,.9);
      background:linear-gradient(135deg,#2563eb,#7c3aed);
      color:#ffffff;
      font-size:var(--fs-sm);
      font-weight:900;
      cursor:pointer;
      box-shadow:0 10px 30px rgba(37,99,235,.8);
      transition:
        transform .12s ease,
        box-shadow .12s ease,
        filter .12s ease;
    }
    .chat-input button:hover{
      transform:translateY(-2px) scale(1.02);
      box-shadow:0 16px 40px rgba(37,99,235,1);
      filter:brightness(1.05);
    }

    @media (max-width:560px){
      .chat-input{
        margin-bottom:18px;
        padding-bottom:calc(8px + env(safe-area-inset-bottom, 0px));
      }
    }

    /* ========= MODALS ========= */
    .modal{
      position:fixed;
      inset:0;
      display:none;
      align-items:center;
      justify-content:center;
      background:rgba(3,7,18,.72);
      z-index:9999;
      padding:10px;
    }
    .modal-card{
      width:min(760px,100% - 20px);
      background:var(--panel);
      border-radius:16px;
      padding:18px;
      border:1px solid rgba(148,163,184,.8);
      box-shadow:var(--shadow-lg);
      color:var(--ink);
      position:relative;
    }
    .modal-title{font-weight:900;font-size:var(--fs-lg);}
    .modal-sub{font-size:var(--fs-sm);color:var(--muted);margin-top:4px;}
    .modal-opts{display:flex;flex-direction:column;gap:10px;margin-top:12px;}

    .opt{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:8px;
      padding:9px 10px;
      border-radius:12px;
      border:1px solid rgba(148,163,184,.7);
      background:var(--panel-2);
    }
    .opt input{
      border-radius:8px;
      border:1px solid rgba(148,163,184,.7);
      padding:7px 8px;
      min-width:120px;
      max-width:380px;
      flex:1;
      background:var(--panel);
      color:var(--ink);
      font-size:var(--fs-sm);
      outline:none;
    }
    .opt .apply{
      border-radius:10px;
      border:0;
      padding:7px 10px;
      font-size:var(--fs-sm);
      font-weight:800;
      cursor:pointer;
      background:linear-gradient(135deg,#2563eb,#7c3aed);
      color:#ffffff;
      box-shadow:var(--shadow-sm);
    }

    .cat-modal .modal-card{width:min(860px,100% - 20px);}

    .cat-list{
      margin-top:12px;
      max-height:min(60vh,420px);
      overflow:auto;
      border-radius:12px;
      border:1px solid rgba(148,163,184,.7);
      background:var(--panel-2);
    }
    .cat-item{
      display:flex;
      align-items:center;
      gap:10px;
      padding:9px 11px;
      border-bottom:1px solid rgba(30,64,175,.8);
      cursor:pointer;
      font-size:var(--fs-sm);
    }
    .cat-item:last-child{border-bottom:0;}
    .cat-item input{accent-color:var(--brand);}
    .cat-item:hover{
      background:rgba(15,23,42,.85);
      color:#e5edff;
    }
    html[data-theme="light"] .cat-item:hover{
      background:#eff6ff;
      color:#111827;
    }

    .cat-actions{
      display:flex;
      justify-content:flex-end;
      gap:8px;
      margin-top:12px;
      flex-wrap:wrap;
    }
    .icon-close{
      position:absolute;
      top:10px;
      right:10px;
      width:32px;height:32px;
      border-radius:999px;
      border:1px solid rgba(148,163,184,.85);
      background:var(--panel-2);
      display:inline-flex;
      align-items:center;
      justify-content:center;
      cursor:pointer;
      box-shadow:var(--shadow-sm);
      color:var(--ink);
    }
    .icon-close svg{
      width:18px;height:18px;
      stroke:currentColor;
      stroke-width:2;
      fill:none;
      stroke-linecap:round;
      stroke-linejoin:round;
    }
    .icon-close:hover{
      transform:translateY(-1px);
      box-shadow:var(--shadow-md);
    }

    .pill{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:6px;
      padding:8px 13px;
      border-radius:999px;
      border:1px solid rgba(148,163,184,.85);
      cursor:pointer;
      font-size:var(--fs-sm);
      font-weight:800;
      background:var(--panel-2);
      color:var(--ink);
      box-shadow:var(--shadow-sm);
    }
    .pill:hover{
      background:linear-gradient(135deg,#2563eb,#7c3aed);
      color:#ffffff;
      border-color:rgba(59,130,246,1);
    }

    .alert-card{
      width:min(420px,92vw);
      background:var(--panel);
      border-radius:16px;
      padding:18px;
      border:1px solid rgba(148,163,184,.8);
      color:var(--ink);
      text-align:center;
      box-shadow:var(--shadow-lg);
      position:relative;
    }
    .alert-emoji{
      width:52px;height:52px;
      border-radius:14px;
      margin:0 auto 8px;
      display:grid;
      place-items:center;
      background:radial-gradient(circle at 50% 0, rgba(250,204,21,1), transparent 60%);
      font-size:28px;
    }
    .alert-title{font-size:var(--fs-lg);font-weight:900;}
    .alert-sub{font-size:var(--fs-sm);color:var(--muted);margin-top:4px;}
    .alert-actions{margin-top:12px;display:flex;justify-content:center;gap:8px;}
    .btn{
      border-radius:12px;
      padding:9px 13px;
      border:1px solid rgba(37,99,235,.9);
      background:linear-gradient(135deg,#2563eb,#7c3aed);
      color:#ffffff;
      font-size:var(--fs-sm);
      font-weight:800;
      cursor:pointer;
      box-shadow:var(--shadow-sm);
    }

    .drawer-close-btn, .nav-close-btn{
      position:absolute;
      top:10px;
      right:10px;
      width:30px;
      height:30px;
      border-radius:999px;
      border:1px solid rgba(148,163,184,.85);
      background:rgba(15,23,42,.98);
      color:#e5edff;
      display:none;
      align-items:center;
      justify-content:center;
      font-size:18px;
      cursor:pointer;
      z-index:70;
      box-shadow:0 6px 18px rgba(0,0,0,.9);
    }
    html[data-theme="light"] .drawer-close-btn,
    html[data-theme="light"] .nav-close-btn{
      background:#ffffff;
      color:#111827;
      border-color:#d1d5db;
    }
    @media (max-width:1024px){
      .drawer-close-btn, .nav-close-btn{ display:inline-flex; }
    }



/* =========================================================
   ✅ MINI DASHBOARD POPUP — LIGHT SKY BLUE / WHITE (SMALL + FRIENDLY)
   + Requested tweaks:
   - Account Overview area dark blue
   - Profile info text dark blue
   - Numbers extra bold
   - Unlocked pill/button green
   - Cross/close button red
   - Arrow red
   - Footer close button dark blue
========================================================= */













/* =========================================================
   ✅ DASHBOARD POPUP CSS — Premium / Responsive / Extra Bold Key Text
   Requested:
   ✅ Username (dash-name) = EXTRA BOLD + DARK BLUE
   ✅ "Your Purchased Paid Prompts" title = EXTRA BOLD + DARK BLUE
   ✅ Prompt Category Name line (dash-cat-name) = EXTRA BOLD + DARK BLUE
   ✅ Red close X + red arrow = BOLD
   ✅ Unlocked pill = BOLD
   ✅ Fix small CSS issues in your pasted code (missing ;, extra 0)
========================================================= */

body.dash-lock{ overflow:hidden; }

.dash-modal{
  position:fixed;
  inset:0;
  display:grid;
  place-items:center;
  padding:14px;
  z-index:99999;
  background:
    radial-gradient(1200px 520px at 18% 10%, rgba(56,189,248,.26), transparent 60%),
    radial-gradient(900px 520px at 86% 22%, rgba(99,102,241,.14), transparent 60%),
    radial-gradient(1100px 700px at 50% 110%, rgba(125,211,252,.18), transparent 60%),
    rgba(2,6,23,.35);
  backdrop-filter: blur(12px);
}

/* ✅ Outer card keeps perfect rounded corners */
.dash-card{
  width:min(560px, 92vw);
  max-height:min(74vh, 640px);
  position:relative;
  border-radius:18px;
  border:1px solid rgba(148,163,184,.32);
  background:
    radial-gradient(900px 360px at 12% 0%, rgba(56,189,248,.22), transparent 58%),
    radial-gradient(760px 360px at 88% 18%, rgba(129,140,248,.14), transparent 60%),
    linear-gradient(180deg, rgba(255,255,255,.92), rgba(240,249,255,.90));
  box-shadow:
    0 30px 90px rgba(2,6,23,.35),
    0 1px 0 rgba(255,255,255,.85) inset;
  overflow:hidden;
  transform: translateY(8px) scale(.985);
  animation: dashPop .22s ease forwards;
}

/* ✅ If you are not using .dash-scroll, keep this to allow scroll */
.dash-card{
  overflow:auto;
}
.dash-card::-webkit-scrollbar{ width:10px; }
.dash-card::-webkit-scrollbar-thumb{
  background: linear-gradient(180deg, rgba(56,189,248,.55), rgba(99,102,241,.35));
  border-radius:999px;
}
.dash-card::-webkit-scrollbar-track{ background: rgba(2,6,23,.06); }

@keyframes dashPop{ to{ transform: translateY(0) scale(1); } }

/* ✅ Close X button: RED + BOLD */
.dash-close{
  position:absolute;
  top:10px; right:10px;
  width:40px; height:40px;
  border-radius:14px;
  border:2px solid rgba(248,113,113,.60);
  background: rgba(255,255,255,.82);
  color:#dc2626;
  font-weight:1000;
  font-size:18px;
  line-height:1;
  cursor:pointer;
  box-shadow: 0 18px 45px rgba(2,6,23,.18);
  transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
}
.dash-close:hover{
  transform: translateY(-1px) scale(1.03);
  box-shadow: 0 24px 60px rgba(2,6,23,.22);
  background: rgba(255,255,255,.96);
}

/* ✅ Account Overview section: DARK BLUE */
.dash-head{
  padding:14px 16px 12px;
  border-bottom:1px solid rgba(148,163,184,.20);
  background:
    radial-gradient(700px 220px at 18% 0%, rgba(56,189,248,.20), transparent 62%),
    linear-gradient(180deg, rgba(30,58,138,.98), rgba(15,23,42,.95));
}
.dash-kicker{
  font-weight:1000;
  letter-spacing:.14em;
  font-size:.70rem;
  color: rgba(226,232,240,.92);
}
.dash-title{
  font-size:1.12rem;
  font-weight:1100;
  margin-top:6px;
  color:#ffffff;
  text-shadow: 0 12px 40px rgba(0,0,0,.35);
}
.dash-sub{
  margin-top:4px;
  font-size:.92rem;
  color: rgba(226,232,240,.86);
}

/* Profile row */
.dash-profile{
  display:flex;
  gap:12px;
  align-items:center;
  padding:12px 16px;
  border-bottom:1px solid rgba(148,163,184,.18);
}

.dash-avatar{
  width:56px;height:56px;
  border-radius:999px;
  overflow:hidden;
  display:grid;
  place-items:center;
  border:1px solid rgba(148,163,184,.32);
  background:
    radial-gradient(circle at 30% 30%, rgba(56,189,248,.22), transparent 60%),
    linear-gradient(180deg, rgba(255,255,255,.95), rgba(224,242,254,.9));
  box-shadow:
    0 18px 55px rgba(2,6,23,.16),
    0 1px 0 rgba(255,255,255,.85) inset;
  transition: transform .18s ease;
}
.dash-avatar:hover{ transform: translateY(-1px) scale(1.02); }
.dash-avatar img{ width:100%; height:100%; object-fit:cover; }
.dash-avatar span{ font-weight:1100; font-size:1.25rem; color:#0b1220; }

.dash-user{ min-width:0; }

/* ✅ 1) USER NAME = EXTRA BOLD + DARKER BLUE */
.dash-name{
  font-weight:1200;            /* extra bold */
  font-size:1.06rem;
  letter-spacing:.01em;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
  color:#061a4a;               /* darker dark blue */
  text-shadow: 0 10px 30px rgba(2,6,23,.10);
}
.dash-email{
  margin-top:2px;
  font-size:.90rem;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
  color: rgba(6,26,74,.78);
}

.dash-badges{
  display:flex;
  gap:8px;
  flex-wrap:wrap;
  margin-top:8px;
}
.dash-badge{
  font-weight:1000;
  font-size:.75rem;
  padding:7px 10px;
  border-radius:999px;
  border:1px solid rgba(148,163,184,.28);
  background: rgba(255,255,255,.70);
  color: rgba(6,26,74,.90);
  box-shadow: 0 12px 30px rgba(2,6,23,.10);
}
.dash-badge-glow{
  border-color: rgba(56,189,248,.55);
  background: linear-gradient(135deg, rgba(224,242,254,.95), rgba(255,255,255,.88));
}

/* Stats */
.dash-stats{
  display:grid;
  grid-template-columns: repeat(2, minmax(0,1fr));
  gap:10px;
  padding:10px 16px;
}
.dash-stat{
  border-radius:16px;
  border:1px solid rgba(148,163,184,.22);
  background:
    radial-gradient(700px 220px at 18% 0%, rgba(56,189,248,.18), transparent 60%),
    rgba(255,255,255,.72);
  box-shadow: 0 18px 55px rgba(2,6,23,.12);
  padding:10px 12px;
  transition: transform .15s ease, box-shadow .15s ease;
}
.dash-stat:hover{
  transform: translateY(-2px);
  box-shadow: 0 22px 70px rgba(2,6,23,.16);
}
.dash-stat-num{
  font-weight:900;
  font-size:1.24rem;
  color:#061a4a;
  letter-spacing:.02em;
}
.dash-stat-label{
  margin-top:4px;
  font-size:.88rem;
  color: rgba(6,26,74,.72);
}

/* List text */
.dash-list-wrap{ padding:8px 16px 14px; }

/* ✅ 2) "Your Purchased Paid Prompts" = EXTRA BOLD + DARKER BLUE */
.dash-list-title{
  font-weight:1300;
  font-size:1.04rem;
  color:#061a4a;
  letter-spacing:.01em;
  text-shadow: 0 10px 30px rgba(2,6,23,.08);
}
.dash-list-title strong{
  font-weight:1300; /* keep strong even more bold */
  color:#061a4a;
}
.dash-list-sub{
  margin-top:4px;
  font-size:.90rem;
  color: rgba(6,26,74,.70);
}

/* Empty state */
.dash-empty{
  margin-top:12px;
  padding:14px;
  border-radius:16px;
  border:1px dashed rgba(148,163,184,.34);
  background: rgba(255,255,255,.68);
  text-align:center;
  box-shadow: 0 18px 55px rgba(2,6,23,.10);
}
.dash-empty-emoji{ font-size:1.5rem; }
.dash-empty-title{ margin-top:6px; font-weight:1200; color:#061a4a; }
.dash-empty-sub{ margin-top:4px; color: rgba(6,26,74,.70); }

/* Accordion */
.dash-accordion{ margin-top:12px; display:flex; flex-direction:column; gap:10px; }

.dash-cat{
  border-radius:16px;
  border:1px solid rgba(148,163,184,.22);
  background: rgba(255,255,255,.70);
  overflow:hidden;
  box-shadow: 0 18px 55px rgba(2,6,23,.12);
}

.dash-cat-head{
  width:100%;
  display:flex;
  align-items:center;
  gap:10px;
  padding:11px 12px;
  cursor:pointer;
  border:0;
  color:#061a4a;
  background:
    radial-gradient(700px 220px at 18% 0%, rgba(56,189,248,.18), transparent 62%),
    linear-gradient(180deg, rgba(255,255,255,.92), rgba(240,249,255,.88));
  font-weight:1200;
  transition: transform .15s ease, box-shadow .15s ease;
}
.dash-cat-head:hover{
  transform: translateY(-1px);
  box-shadow: 0 22px 70px rgba(2,6,23,.14);
}

.dash-cat-ico{ font-size:1.05rem; }

/* ✅ 3) CATEGORY NAME = EXTRA BOLD + DARKER BLUE */
.dash-cat-name{
  flex:1;
  text-align:left;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
  font-weight:900;
  color:#061a4a;
  letter-spacing:.01em;
}

/* ✅ Unlocked pill GREEN = EXTRA BOLD */
.dash-cat-pill{
  font-size:.74rem;
  padding:6px 10px;
  border-radius:999px;
  border:1px solid rgba(34,197,94,.55);
  background: rgba(34,197,94,.14);
  color:#166534;
  font-weight:1300;
  box-shadow: 0 10px 28px rgba(34,197,94,.16);
}

/* ✅ Arrow RED = EXTRA BOLD */
.dash-cat-arrow{
  color:#dc2626;
  opacity:.98;
  transition: transform .18s ease;
  font-weight:1300;
  font-size:1.05rem;
}
.dash-cat-head[aria-expanded="true"] .dash-cat-arrow{ transform: rotate(180deg); }

.dash-cat-body{
  display:none;
  padding:10px 12px 12px;
  background: rgba(255,255,255,.56);
}
.dash-cat-body.open{ display:block; }

/* Topic */
.dash-topic{ margin-top:10px; }
.dash-topic-title{
  font-weight:1200;
  margin-bottom:8px;
  color: rgba(6,26,74,.90);
}

/* Prompts grid */
.dash-prompt-grid{
  display:grid;
  grid-template-columns: repeat(2, minmax(0,1fr));
  gap:10px;
}
.dash-prompt{
  border-radius:14px;
  border:1px solid rgba(148,163,184,.22);
  background:
    radial-gradient(700px 220px at 18% 0%, rgba(56,189,248,.14), transparent 62%),
    rgba(255,255,255,.82);
  padding:10px;
  box-shadow: 0 16px 50px rgba(2,6,23,.10);
  transition: transform .15s ease, box-shadow .15s ease;
}
.dash-prompt:hover{
  transform: translateY(-2px);
  box-shadow: 0 22px 70px rgba(2,6,23,.14);
}
.dash-prompt-label{
  font-weight:1200;
  font-size:.92rem;
  color:#061a4a;
  white-space: pre-line;  /* ✅ ADD THIS */
}


/* Buttons */
.dash-prompt-actions{
  margin-top:10px;
  display:flex;
  gap:8px;
}
.dash-btn{
  flex:1;
  border-radius:12px;
  padding:9px 10px;
  border:1px solid rgba(148,163,184,.24);
  background: rgba(255,255,255,.88);
  color: rgba(6,26,74,.92);
  font-weight:1200;
  cursor:pointer;
  box-shadow: 0 14px 40px rgba(2,6,23,.10);
  transition: transform .15s ease, box-shadow .15s ease;
}
.dash-btn:hover{
  transform: translateY(-1px);
  box-shadow: 0 18px 55px rgba(2,6,23,.14);
}
.dash-btn-primary{
  border-color: rgba(56,189,248,.55);
  background: linear-gradient(135deg, rgba(37,99,235,.95), rgba(99,102,241,.82));
  color:#ffffff;
  font-weight:1300;
}

/* Footer */
.dash-footer{
  padding:10px 16px 14px;
  border-top:1px solid rgba(148,163,184,.20);
  display:flex;
  justify-content:flex-end;
  background: rgba(255,255,255,.55);
}
.dash-footer-btn{
  border-radius:14px;
  padding:10px 14px;
  border:1px solid rgba(37,99,235,.45);
  background: linear-gradient(135deg, rgba(30,58,138,.98), rgba(37,99,235,.92));
  color:#ffffff;
  font-weight:1300;
  cursor:pointer;
  box-shadow: 0 18px 55px rgba(37,99,235,.18);
  transition: transform .15s ease, box-shadow .15s ease;
}
.dash-footer-btn:hover{
  transform: translateY(-1px);
  box-shadow: 0 24px 70px rgba(37,99,235,.22);
}

/* Responsive */
@media (max-width: 720px){
  .dash-card{
    width:min(520px, 94vw);
    max-height:min(76vh, 640px);
  }
  .dash-prompt-grid{ grid-template-columns: 1fr; }
  .dash-stats{ grid-template-columns: 1fr; }
}




























/* Chat container must be positioning context */
#chat-container{ position: relative; }

/* Small down arrow button */
.scroll-down-btn{
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  bottom: 86px;               /* sits above input */
  width: 42px;
  height: 42px;
  border-radius: 999px;
  border: 1px solid rgba(148,163,184,.35);
  background: rgba(255,255,255,.85);
  backdrop-filter: blur(10px);
  display: none;              /* hidden by default */
  place-items: center;
  cursor: pointer;
  box-shadow: 0 18px 40px rgba(0,0,0,.18);
  z-index: 50;
}

html[data-theme="dark"] .scroll-down-btn{
  background: rgba(2,6,23,.72);
  border-color: rgba(148,163,184,.28);
  box-shadow: 0 22px 50px rgba(0,0,0,.55);
}

.scroll-down-btn span{
  font-size: 18px;
  font-weight: 900;
  line-height: 1;
  animation: downPulse 1.2s ease-in-out infinite;
}

@keyframes downPulse{
  0%,100%{ transform: translateY(0); opacity:.75; }
  50%    { transform: translateY(4px); opacity:1; }
}


/* 🌤 LIGHT MODE → SKY BLUE GRADIENT */
html[data-theme="light"] .scroll-down-btn{
  background: linear-gradient(
    135deg,
    #7dd3fc,   /* light sky blue */
    #2563eb    /* dark sky blue */
  );
  border: 1px solid rgba(37,99,235,.45);
  color: #ffffff;

  box-shadow:
    0 18px 42px rgba(37,99,235,.35),
    inset 0 1px 0 rgba(255,255,255,.45),
    inset 0 -6px 12px rgba(2,6,23,.18);
}


/* ✅ INVERSE: DARK THEME => LIGHT BUTTON */
html[data-theme="dark"] .scroll-down-btn{
  background: rgba(255,255,255,.92);           /* soft white */
  border: 1px solid rgba(2,6,23,.25);
  color: #020617;
  box-shadow:
    0 22px 50px rgba(0,0,0,.55),
    0 0 0 1px rgba(0,0,0,.06) inset,
    inset 0 10px 18px rgba(0,0,0,.04);
}
.login-modal{
  position:fixed;
  inset:0;
  z-index:9999;
  display:flex;
  align-items:center;
  justify-content:center;
}

.login-modal-overlay{
  position:absolute;
  inset:0;
  background:rgba(0,0,0,.6);
}

.login-modal-content{
  position:relative;
  z-index:2;
  background:#fff;
  border-radius:16px;
  padding:24px;
  width:min(420px,92vw);
}

.loginModal{ position:fixed; inset:0; display:none; z-index:99999; }
.loginModal.is-open{ display:block; }
.loginModal__backdrop{
  position:absolute; inset:0;
  background:rgba(0,0,0,.55);
  backdrop-filter: blur(6px);
}
.loginModal__panel{
  position:absolute; left:50%; top:50%; transform:translate(-50%,-50%);
  width:min(980px,96vw); height:min(720px,92vh);
  border-radius:18px; overflow:hidden;
  box-shadow:0 30px 120px rgba(0,0,0,.5);
  background:rgba(2,6,23,.35);
  border:1px solid rgba(148,163,184,.22);
}
.loginModal__close{
  position:absolute; top:10px; right:10px; z-index:2;
  width:40px; height:40px; border-radius:12px;
  border:0; cursor:pointer; font-size:20px;
  background:rgba(255,255,255,.12); color:#fff;
  backdrop-filter: blur(10px);
}
.loginModal__frame{ width:100%; height:100%; border:0; background:transparent; }

/* ===========================
   Reactions (Like/Dislike)
=========================== */
.ai-msg{ display:flex; flex-direction:column; gap:10px; }


.msg-reactions{
  display:flex;
  gap:10px;
  align-items:center;
  justify-content:flex-start;
  margin-top:8px;
}

.rx-btn{
  border:1px solid rgba(30, 58, 138, 0.55);     /* dark blue border */
  background: rgba(15, 23, 42, 0.06);           /* very light dark bg */
  color:#0f172a;                                 /* text dark (for white chat bg) */
  padding:8px 12px;
  border-radius:12px;
  cursor:pointer;
  font-weight:700;
  display:inline-flex;
  align-items:center;
  gap:8px;
  transition: transform .15s ease, background .2s ease, border-color .2s ease, box-shadow .2s ease, color .2s ease;
}

.rx-btn:hover{
  transform: none; /* ✅ no movement while scrolling */
  border-color: rgba(59,130,246,.85);
  background: rgba(37,99,235,.10);
  box-shadow: 0 8px 18px rgba(37,99,235,.18);
}

.rx-btn:active{ transform: scale(.97); }


/* ✅ STRONG selected state (Like/Dislike) */
.rx-btn.active{
  background: rgba(15, 23, 42, 0.92);      /* slate-900 */
  border-color: rgba(59, 130, 246, 0.85);  /* blue-500 */
  color: #ffffff;
  box-shadow:
    0 12px 28px rgba(15, 23, 42, 0.65),
    inset 0 0 0 1px rgba(255,255,255,0.08);
}

/* ✅ Copy success state */
.rx-btn.copied{
  background: rgba(15, 23, 42, 0.96);      /* slightly darker */
  border-color: rgba(96, 165, 250, 0.95);  /* blue-400 */
  color: #ffffff;
  box-shadow:
    0 14px 32px rgba(15, 23, 42, 0.75),
    inset 0 0 0 1px rgba(255,255,255,0.10);
}

.rx-btn.active .rx-count,
.rx-btn.copied .rx-count{
  opacity: 1;
}

/* keyboard accessibility (optional but good) */
.rx-btn:focus-visible{
outline: 3px solid rgba(59,130,246,.55);
outline-offset: 2px;
}

/* ✅ COPY button: no hover movement / no flicker while scrolling */
.rx-btn.rx-copy,
.rx-btn.rx-copy:hover,
.rx-btn.rx-copy:active{
  transform: none !important;     /* stop any translate/scale on copy */
}





/* --- message actions (rewrite button) --- */
.msg-actions{
  display:flex;
  gap:8px;
  margin-top:8px;
  justify-content:flex-end;
  opacity:.85;
}
.msg-actions button{
  border:1px solid rgba(148,163,184,.25);
  background: rgba(255,255,255,.06);
  color:#fff;
  font-size:12px;
  padding:6px 10px;
  border-radius:10px;
  cursor:pointer;
  transition:.2s ease;
}
.msg-actions button:hover{
  transform: translateY(-1px);
  background: rgba(255,255,255,.10);
}
.msg-actions button:active{
  transform: translateY(0);
}

.message.user.editing{
  outline: 2px solid rgba(185,253,80,.55);
  box-shadow: 0 0 0 6px rgba(185,253,80,.12);
  border-radius: 14px;
}
.btn-rewrite{
  border: 1px solid rgba(148,163,184,.2);
  background: rgba(255,255,255,.06);
  color: #fff;
  padding: 6px 10px;
  border-radius: 10px;
  cursor: pointer;
}
.btn-rewrite:hover{ transform: translateY(-1px); }

.rx-btn.rx-copy{
  position: static !important;  /* ✅ absolute हटाओ */
  top: auto !important;
  right: auto !important;
  margin-left: 8px;
  opacity: .95;
}



.btn-rewrite.disabled,
.btn-rewrite:disabled{
  opacity:.45;
  cursor:not-allowed;
  transform:none !important;
  filter:grayscale(1);
}


    /*.rx-btn.rx-copy:hover{*/
    /*  transform: translateY(-1px);*/
    /*}*/

.msg.rewrite-active{
  outline: 2px solid rgba(185,253,80,.55);
  box-shadow: 0 0 0 4px rgba(185,253,80,.15);
  border-radius: 14px;
}

.btn-cancel-rewrite{
  margin-left: 8px;
  padding: 8px 12px;
  border-radius: 999px;
  border: 1px solid rgba(255,255,255,.18);
  background: rgba(255,255,255,.06);
  color: #fff;
  cursor: pointer;
}

.btn-cancel-rewrite:disabled{
  opacity:.45;
  cursor:not-allowed;
}



  
.type-cursor{display:inline-block; margin-left:2px; opacity:.9; animation: blink 1s steps(1) infinite;}
@keyframes blink{50%{opacity:0;}}

</style>
  <script>
  window.IS_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
  window.USER_ID = <?php echo (int)$userIdPhp; ?>;
</script>

  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
</head>
<body>
<div class="app-shell">
  <!-- LEFT NAV (PROFILE BAR) -->
  <aside class="side-nav" aria-label="Main navigation" id="sideNav">
    <button type="button" class="nav-close-btn" id="navCloseBtn" aria-label="Close profile menu">✕</button>
    <div class="side-nav-inner">
      <div style="display:flex;flex-direction:column;align-items:center;gap:4px;">
        <div class="side-avatar-wrap">
          <div class="side-avatar" title="<?php echo htmlspecialchars($userName); ?>">
            <?php echo htmlspecialchars($userInitial); ?>
          </div>
        </div>
        <div class="side-user-name" title="<?php echo htmlspecialchars($userName); ?>">
          <?php echo htmlspecialchars($userName); ?>
        </div>
       
      </div>
      <nav class="side-menu">
          

<a href="dashboard.php" class="side-item active" id="openDashPopupBtn">
  <span class="icon">🏠</span>
  <span>Dashboard</span>
</a>



        <a href="history.php" class="side-item">
          <span class="icon">🕒</span>
          <span>History</span>
        </a>
        <!--<a href="profile.php" class="side-item">-->
        <!--  <span class="icon">👤</span>-->
        <!--  <span>Profile</span>-->
        <!--</a>-->
      </nav>
      <div class="side-bottom">
        <!--<a href="settings.php" class="side-item">-->
        <!--  <span class="icon">⚙️</span>-->
        <!--  <span>Settings</span>-->
        <!--</a>-->
        <?php if($isLoggedIn): ?>
  <a href="logout.php" class="side-item danger" onclick="clearChatCache();">
  <span class="icon">⏻</span>
  <span>Logout</span>
</a>
<?php endif; ?>

      </div>
    </div>
  </aside>





  
<!-- ✅ DASHBOARD POPUP MODAL (LEFT NAV Dashboard Button Opens This) -->
<div id="userDashModal" class="dash-modal" aria-hidden="true" style="display:none;">
  <div class="dash-card" role="dialog" aria-modal="true" aria-labelledby="userDashTitle">
    <button type="button" class="dash-close" id="dashCloseBtn" aria-label="Close dashboard popup">✕</button>

    <div class="dash-head">
 
      <div id="userDashTitle" class="dash-title">👤 Account Overview</div>
      <div class="dash-sub">Your profile + purchased premium prompts</div>
    </div>

    <div class="dash-profile">
      <div class="dash-avatar">
        <?php
          // ✅ Try to use profile image if your DB has it, else fallback to initial
          // Change field name if your DB uses another key (example: profile_photo, avatar, photo_url, image)
          $profileImg = '';
          if (isset($user['profile_photo']) && trim((string)$user['profile_photo']) !== '') {
            $profileImg = (string)$user['profile_photo'];
          }
        ?>
        <?php if($profileImg): ?>
          <img src="<?php echo htmlspecialchars($profileImg); ?>" alt="Profile photo">
        <?php else: ?>
          <span><?php echo htmlspecialchars($userInitial); ?></span>
        <?php endif; ?>
      </div>

      <div class="dash-user">
        <div class="dash-name"><?php echo htmlspecialchars($userName); ?></div>

        <div class="dash-email">
          <?php
            // ✅ Use your real email field (common keys: email, user_email)
            $userEmail = '';
            if (isset($user['email'])) $userEmail = $user['email'];
            else if (isset($user['user_email'])) $userEmail = $user['user_email'];
          ?>
          <?php echo htmlspecialchars($userEmail ?: ''); ?>
        </div>

        <div class="dash-badges">
          <span class="dash-badge">ID: <?php echo (int)$userIdPhp; ?></span>
     
        </div>
      </div>
    </div>

    <?php
      // ✅ Build purchased paid prompts list using YOUR existing logic:
      // - categories already on page
      // - userHasPaidForCategory()
      // - fetchPrompts()
      
      $purchasedCats = [];

foreach($categories as $cat){
  $hasPaidCat = $isLoggedIn ? userHasPaidForCategory($pdo, $userIdPhp, (int)$cat['id']) : false;
  if(!$hasPaidCat) continue;

  $purchasedCats[] = [
    'catName' => $cat['name'],
    'catIcon' => $cat['icon'] ?? '💎'
  ];
}

$totalCategories = count($categories);
$unlockedCategories = count($purchasedCats);
  
    ?>

   <div class="dash-stats">
  <div class="dash-stat">
    <div class="dash-stat-num"><?php echo (int)$totalCategories; ?></div>
    <div class="dash-stat-label">Total Prompt Categories</div>
  </div>

  <div class="dash-stat">
    <div class="dash-stat-num"><?php echo (int)$unlockedCategories; ?></div>
    <div class="dash-stat-label">Unlocked Categories</div>
  </div>
</div>


    <div class="dash-list-wrap">
      <div class="dash-list-title">
  ✅ Your Purchased Paid Categories
  <span style="font-weight:1200; color:#061a4a;">(<?php echo (int)$unlockedCategories; ?>)</span>
</div>

      <div class="dash-list-sub">Only premium prompts you unlocked are shown here.</div>
<?php if(empty($purchasedCats)): ?>
  <div class="dash-empty">
    <div class="dash-empty-emoji">🧊</div>
    <div class="dash-empty-title">No premium categories unlocked yet</div>
    <div class="dash-empty-sub">Unlock a category to see it here.</div>
  </div>
<?php else: ?>

  <div class="dash-accordion">
    <?php foreach($purchasedCats as $block): ?>
      <div class="dash-cat">
        <button class="dash-cat-head" type="button" aria-expanded="false">
          <span class="dash-cat-ico"><?php echo htmlspecialchars($block['catIcon']); ?></span>
          <span class="dash-cat-name"><?php echo htmlspecialchars($block['catName']); ?></span>
          <span class="dash-cat-pill">Unlocked</span>
          <span class="dash-cat-arrow">▾</span>
        </button>

        <!-- ✅ NO PROMPTS INSIDE -->
        <div class="dash-cat-body" aria-hidden="true">
          <div class="dash-topic-title" style="margin:0;">
            You have unlocked this category.
          </div>
        </div>

      </div>
    <?php endforeach; ?>
  </div>

<?php endif; ?>

    </div>

    <div class="dash-footer">
      <button type="button" class="dash-footer-btn" onclick="closeUserDashModal()">Close</button>
    </div>
  </div>
</div>






  <!-- MAIN: LEFT INDUSTRY + RIGHT CHAT -->
  <div class="shell" id="scene">
    <!-- LEFT INDUSTRY SIDEBAR -->
    <aside class="left" id="sidebar" aria-label="Business Prompt Sidebar" aria-hidden="true">
      <button type="button" class="drawer-close-btn" id="drawerCloseBtn" aria-label="Close industries sidebar">✕</button>

      <div class="sidebar-head">
        <div>
          <div class="sidebar-title">Business Prompt Categories</div>
          <div class="sidebar-sub">Tap a prompt to autofill</div>
        </div>
      </div>

      <div class="search" role="search">
        <input id="searchInput" type="search" placeholder="Search categories, topics & prompts…" aria-label="Search categories and prompts">
        <span class="icon">🔎</span>
      </div>

      <div class="cats" id="catsScroll" role="tree">
        <?php foreach($categories as $cidx => $cat): ?>
          <?php
          $getTopics->execute([$cat['id']]);
          $topics = $getTopics->fetchAll(PDO::FETCH_ASSOC);

          $hasPaidCat = $isLoggedIn
  ? userHasPaidForCategory($pdo, $userIdPhp, (int)$cat['id'])
  : false;

          $catPrice = $globalPrice;
          $catCurr = $globalCurr;
          ?>
          <div class="cat" data-index="<?php echo $cidx; ?>" data-cat="<?php echo htmlspecialchars($cat['name']); ?>" role="treeitem" aria-expanded="false">
            <div class="cat-head">
              <div class="cat-left">
                <div class="cat-ico <?php echo htmlspecialchars($cat['css_class'] ?? ''); ?>">
                  <?php echo htmlspecialchars($cat['icon'] ?? '📁'); ?>
                </div>
                <div>
                  <div class="cat-name"><?php echo htmlspecialchars($cat['name']); ?></div>
                  <div class="cat-meta">Click to view 14 topics & prompts</div>
                </div>
              </div>
              <button class="toggle-btn" type="button" aria-expanded="false">Show</button>
            </div>

            <div class="cat-body" aria-hidden="true">
              <?php foreach($topics as $tidx => $topic): ?>
                <div class="topic" data-topic="<?php echo htmlspecialchars($topic['name']); ?>">
                  <div class="topic-head" tabindex="0" role="button" aria-expanded="false">
                    <div class="topic-title">📚 <?php echo htmlspecialchars($topic['name']); ?></div>
                    <button class="toggle-btn mini-toggle" type="button">Open</button>
                  </div>
                  <div class="topic-body" aria-hidden="true">
                    <?php $freePrompts = fetchPrompts($pdo, (int)$cat['id'], (int)$topic['id'], 'free', 5); ?>
                    <?php foreach($freePrompts as $fp): ?>
                      <div class="sub" data-prompt="<?php echo htmlspecialchars($fp['label']); ?>">
                        <div class="sub-label">💬 <span><?php echo htmlspecialchars($fp['label']); ?></span></div>
                        <div class="sub-actions">
                         <button class="sub-action-btn" type="button"
  onclick="fillPrompt(
    event,
    <?php echo htmlspecialchars(json_encode($fp['label']), ENT_QUOTES, 'UTF-8'); ?>,
    <?php echo (int)$fp['id']; ?>,
    <?php echo (int)$cat['id']; ?>,
    <?php echo (int)$topic['id']; ?>
  )">
  ✎ Edit
</button>

<button class="sub-action-btn" type="button"
  onclick="sendPromptFromSidebar(
    event,
    <?php echo htmlspecialchars(json_encode($fp['label']), ENT_QUOTES, 'UTF-8'); ?>,
    <?php echo (int)$fp['id']; ?>,
    <?php echo (int)$cat['id']; ?>,
    <?php echo (int)$topic['id']; ?>
  )">
  ➤ Send
</button>

                        </div>
                      </div>
                    <?php endforeach; ?>

                    <div style="margin-top:8px;">
                      <div class="paid">
                        <div>💎 <strong>Paid Prompts</strong> — <?php echo htmlspecialchars($topic['name']); ?></div>
                        <div style="font-weight:800;"><?php echo $hasPaidCat ? 'Premium' : 'Locked'; ?></div>
                      </div>
<?php if($hasPaidCat): ?>
    <?php $paidPrompts = fetchPrompts($pdo, (int)$cat['id'], (int)$topic['id'], 'paid', 5); ?>

    <?php foreach($paidPrompts as $pp): ?>
      <?php
        $visibleLabel    = $pp['label'];                      // what user sees
        $editText        = $topic['name'].': '.$visibleLabel; // goes in textarea
        $backendTemplate = $pp['prompt_text'] ?? '';          // full prompt text
      ?>
      <div class="sub sub-premium" data-prompt="<?php echo htmlspecialchars($visibleLabel); ?>">
        <div class="sub-left">
          <div class="sub-label">✨ <span><?php echo htmlspecialchars($visibleLabel); ?></span></div>
        </div>
        <div class="sub-actions">
          <button class="sub-action-btn" type="button"
                  onclick='fillPrompt(event, <?php echo json_encode($editText); ?>, <?php echo json_encode($backendTemplate); ?>)'>
            ✎ Edit
          </button>
          <button class="sub-action-btn" type="button"
                  onclick='sendPromptFromSidebar(event, <?php echo json_encode($editText); ?>, <?php echo json_encode($backendTemplate); ?>)'>
            ➤ Send
          </button>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
                        <div style="margin-top:8px;">
                          <div class="paid">
                            <div>✨ Unlock premium prompts for this category (<?php echo htmlspecialchars($cat['name']); ?>)</div>
                            <button class="sub-action-btn" type="button"
                                    onclick="startUnlock(
                                      <?php echo (int)$cat['id']; ?>,
                                      '<?php echo htmlspecialchars(addslashes($cat['name'])); ?>',
                                      '<?php echo number_format((float)$catPrice, 2, '.', ''); ?>',
                                      '<?php echo htmlspecialchars($catCurr); ?>'
                                    )">
                              Unlock <?php echo htmlspecialchars($catCurr); ?> <?php echo number_format((float)$catPrice, 2); ?>
                            </button>
                          </div>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="left-footer">
        <div class="theme-toggle" id="themeToggle" role="button" tabindex="0" aria-pressed="false">
          <div class="toggle" id="toggleSwitch"><div class="knob"></div></div>
          <span id="themeLabel">Dark</span>
        </div>
        <div>Tip: Use ✎ to edit, ➤ to send</div>
      </div>
    </aside>

    <!-- RIGHT CHAT -->
    <div class="right">
      <div id="chat-container" class="tilt" role="main">
        <div class="chat-header">
          <div class="brand">
            <div class="brand-main">
              <img src="https://cdn-icons-png.flaticon.com/512/2040/2040653.png"
                   alt="Busineger logo"
                   class="brand-logo-image">
              <div class="brand-title-wrap">
                <span class="chat-title">Busineger</span>
                <div id="selectedCategoryDesktop" class="selected-category-label"></div>
              </div>
            </div>

            <!-- RIGHT TOP BAR ORDER: Change Industry → Dustbin → Profile -->
            <div class="brand-right">
              <button id="changeCatBtn" type="button"
                      title="Filter sidebar to show prompts for one industry category">
                Select Industry Category
              </button>

              <!-- ✅ Usage pill (Free: X tokens left | Paid: Unlimited) -->
<div id="usagePill" class="usage-pill" title="">
  <span class="dot"></span>
  <span>—</span>
</div>


              <button id="clearChatIconBtn" type="button" aria-label="Clear chat" title="Clear chat">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <polyline points="3 7 21 7"></polyline>
                  <path d="M9 7V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"></path>
                  <path d="M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13"></path>
                  <line x1="10" y1="11" x2="10" y2="17"></line>
                  <line x1="14" y1="11" x2="14" y2="17"></line>
                </svg>
              </button>

              <button id="profileToggleBtn" type="button" aria-expanded="false" aria-controls="sideNav"
                      title="Open profile & main menu">
                <span class="profile-mini-avatar"><?php echo htmlspecialchars($userInitial); ?></span>
                <span class="profile-mini-name"><?php echo htmlspecialchars($userName); ?></span>
              </button>
            </div>
          </div>

          <div class="industry-hint" id="industryHint"></div>

          <button id="drawerToggleBtn" type="button" aria-expanded="false" aria-controls="sidebar"
                  title="See all industries and their ready-made prompts">
            ☰ Industrie prompts
          </button>

          <!-- 🔥 Animated 3D CTA on mobile -->
          <div class="mobile-actions-wrapper" id="mobileActionsWrapper">
            <button id="mobileActionsBtn" type="button" aria-haspopup="true" aria-expanded="false">
              <span class="btn-main-text" style="color:darkblue">
                Click Here<br>See Ready Made Promptes
              </span>
              <span class="btn-icon" id="mobileBtnIcon">➤</span>
            </button>
            <div id="mobileActionsMenu" class="mobile-actions-menu" role="menu" aria-label="Mobile quick actions">
              <button type="button" id="mobileIndustryBtn" class="mobile-actions-item" role="menuitem">
                ☰ Industrie Prompts
              </button>
              <button type="button" id="mobileChangeCatBtn" class="mobile-actions-item" role="menuitem">
                🗂 Select Industry Category
              </button>
            </div>
          </div>
        </div>

        <div class="chat-messages" id="chatMessages" role="log" aria-live="polite">
          <div class="message bot">
            👋 Welcome to
            <span style="color:darkblue; font-weight:700;">Busineger!</span>
            How can I help you today?
          </div>
        </div>

        <div class="chat-input">
          <textarea id="userPrompt" placeholder="Type your message…" rows="2" aria-label="Message input"></textarea>
          <button type="button" onclick="sendPrompt()" aria-label="Send message">➤</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- PAID MODAL -->
<div id="paidModal" class="modal" aria-hidden="true">
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="paidModalTitle">
    <button onclick="closePaidModal()" class="icon-close" aria-label="Close paid prompt modal">
      <svg viewBox="0 0 24 24">
        <line x1="18" y1="6" x2="6" y2="18" />
        <line x1="6" y1="6" x2="18" y2="18" />
      </svg>
    </button>
    <div style="padding-right:34px;">
      <div id="paidModalTitle" class="modal-title">Unlock Paid Prompt</div>
      <div class="modal-sub">Customize this premium prompt before using it.</div>
    </div>
    <div id="modalPromptTitle" style="margin-top:12px;font-weight:900;"></div>
    <div class="modal-opts" id="modalOpts"></div>
    <div class="cat-actions">
      <button class="pill" onclick="closePaidModal()">Cancel</button>
      <button class="pill"
              style="background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;border-color:rgba(37,99,235,.9)"
              onclick="usePaidPrompt()">
        Unlock & Use
      </button>
    </div>
  </div>
</div>

<!-- CATEGORY MODAL -->
<div id="categoryModal" class="modal cat-modal" aria-hidden="true">
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="catModalTitle">
    <button class="icon-close" onclick="closeCategoryModal()" aria-label="Close category modal">
      <svg viewBox="0 0 24 24">
        <line x1="18" y1="6" x2="6" y2="18" />
        <line x1="6" y1="6" x2="18" y2="18" />
      </svg>
    </button>
    <div style="padding-right:34px;">
      <div id="catModalTitle" class="modal-title">Select a Category</div>
      <div class="modal-sub">
        This pop-up shows all categories.
        <strong>Select one category</strong> — only its topics & prompts will appear.
      </div>
    </div>
    <div class="search" style="margin-top:10px;">
      <input id="catSearchInput" type="search" placeholder="Search categories…" aria-label="Search categories in modal">
      <span class="icon">🔎</span>
    </div>
    <div id="catList" class="cat-list" role="listbox" aria-label="All Categories"></div>
    <div class="cat-actions">
      <button class="pill" onclick="applySelectedCategory()">Apply</button>
    </div>
  </div>
</div>

<!-- ALERT MODAL -->
<div id="alertModal" class="modal" aria-hidden="true">
  <div class="alert-card" role="alertdialog" aria-modal="true"
       aria-labelledby="alertTitle" aria-describedby="alertDesc">
    <div class="alert-emoji">⚠️</div>
    <div id="alertTitle" class="alert-title">Please select one category</div>
    <div id="alertDesc" class="alert-sub">Pick a category to load its 14 topics and prompts.</div>
    <div class="alert-actions">
      <button class="btn" onclick="closeAlert()">OK</button>
    </div>
  </div>
</div>
<!-- Payment Confirm Modal (Unlock Category) -->
<div id="paymentModal" class="modal" aria-hidden="true" style="display:none;">
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="payModalTitle">
    <button class="icon-close" onclick="closePaymentModal()" aria-label="Close payment modal" title="Close">
      <svg viewBox="0 0 24 24" aria-hidden="true" stroke="currentColor" stroke-width="2" fill="none"
           stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="6" x2="6" y2="18" />
        <line x1="6" y1="6" x2="18" y2="18" />
      </svg>
    </button>

    <div>
      <div id="payModalTitle" class="modal-title">Unlock Premium Prompts</div>
      <div class="modal-sub">You’re about to unlock premium prompts for:</div>

      <div id="payCategoryName" style="margin-top:8px;font-weight:900;font-size:1.05rem;"></div>
      <div id="payAmountLine" style="margin-top:4px;font-size:.95rem;opacity:.85;"></div>
    </div>

    <form id="paymentForm" method="POST" action="cashfree_start.php" style="margin-top:16px;">
      <input type="hidden" name="category_id" id="payCategoryId">
      <input type="hidden" name="category_name" id="payCategoryNameInput">
      <input type="hidden" name="amount" id="payAmount">
      <input type="hidden" name="currency" id="payCurrency">

      <div class="cat-actions">
        <button type="button" class="pill btn-ghost" onclick="closePaymentModal()">Cancel</button>
        <button type="submit" class="pill"
                style="background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;border:0">
          Pay & Unlock with Cashfree
        </button>
      </div>
    </form>
  </div>
</div>



<!-- ✅ LOGIN MODAL (iframe) — PUT HERE -->
  <div id="loginModal" class="loginModal" aria-hidden="true">
    <div class="loginModal__backdrop" data-close="1"></div>

    <div class="loginModal__panel" role="dialog" aria-modal="true" aria-label="Login">
      <button id="loginModalClose" type="button" class="loginModal__close">✕</button>
      <iframe id="loginFrame" src="" class="loginModal__frame"></iframe>
    </div>
  </div>

  <!-- ✅ SCRIPT that controls the modal (AFTER HTML exists) -->
  <script>
    const loginModal = document.getElementById('loginModal');
    const loginFrame = document.getElementById('loginFrame');
    const loginModalClose = document.getElementById('loginModalClose');

    function isLoggedInNow(){
  return (window.IS_LOGGED_IN === true || window.IS_LOGGED_IN === "true");
}

function requireLoginOrContinue(action, payload){
  if(isLoggedInNow()) return true;

  try{
    if(payload?.promptText) localStorage.setItem("pending_prompt", payload.promptText);
    if(payload?.promptId != null) localStorage.setItem("pending_prompt_id", String(payload.promptId));
    if(payload?.categoryId != null) localStorage.setItem("pending_category_id", String(payload.categoryId));
    if(payload?.topicId != null) localStorage.setItem("pending_topic_id", String(payload.topicId));
    if(action) localStorage.setItem("pending_action", action);
  }catch(e){}

  // open login modal iframe
  openLoginModal("newchat.php");
  return false;
}


   function openLoginModal(redirectUrl){
  loginFrame.src = 'mandilogin.php?embed=1&redirect=' + encodeURIComponent(redirectUrl || 'newchat.php');
  loginModal.classList.add('is-open');
  loginModal.setAttribute('aria-hidden','false');

  // ✅ focus fix (prevents aria-hidden warning)
  setTimeout(() => loginModalClose?.focus(), 0);
}


    function closeLoginModal(){
      loginModal.classList.remove('is-open');
      loginModal.setAttribute('aria-hidden','true');
      loginFrame.src = '';
    }

    loginModalClose.addEventListener('click', closeLoginModal);

    loginModal.addEventListener('click', (e) => {
      if (e.target?.dataset?.close === "1") closeLoginModal();
    });

    window.addEventListener('message', (event) => {
  if(event.origin !== window.location.origin) return;

  if (event.data && event.data.type === 'LOGIN_SUCCESS') {
    closeLoginModal();
    location.reload();
  }
});

  </script>

<script>
  function requireLoginThen(actionFn){
    if (window.IS_LOGGED_IN) {
      actionFn();
      return;
    }
    // store what to do after login
    window.__afterLoginAction = actionFn;
    openLoginModal('newchat.php'); // redirect param for consistency
  }

  // Example: Edit button
  document.addEventListener('click', function(e){
    const editBtn = e.target.closest('.btn-edit');     // change selector to yours
    const sendBtn = e.target.closest('.btn-send');     // change selector to yours
    const unlockBtn = e.target.closest('.btn-unlock'); // change selector to yours

    if(editBtn){
      e.preventDefault();
      requireLoginThen(() => {
        // ✅ your edit logic here
        console.log('EDIT now...');
      });
    }

    if(sendBtn){
      e.preventDefault();
      requireLoginThen(() => {
        // ✅ your send logic here
        console.log('SEND now...');
      });
    }

    if(unlockBtn){
      e.preventDefault();
      requireLoginThen(() => {
        // ✅ your unlock logic here
        console.log('UNLOCK now...');
      });
    }
  });
</script>


<script>
function requireLogin(){
  // Open modal without changing URL
  openLoginModal('newchat.php');
}


  const userId = <?php echo $isLoggedIn ? json_encode($userIdPhp) : 'null'; ?>;
  const IS_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
  

</script>
<script>
  // ===============================
  // 🔐 TOKEN USAGE STATE (KEEP ONLY THIS)
  // ===============================
  let USAGE = {
    used: 0,
    remaining: null,
    unlimited: false,
    limit: 8000,
    limitReached: false
  };

  async function refreshUsage(){
    if(!IS_LOGGED_IN || !userId) return;

    try{
      const res = await fetch("/api/usage", {
        method:"POST",
        headers:{ "Content-Type":"application/json" },
        body: JSON.stringify({ user_id: userId })
      });

      const data = await res.json();

      USAGE.used = Number(data.used ?? 0);
      USAGE.remaining = Number(data.remaining ?? 0);
      USAGE.unlimited = !!data.unlimited;
      USAGE.limit = Number(data.limit ?? 8000);
      USAGE.limitReached = (!USAGE.unlimited && USAGE.remaining <= 0);

      updateUsageUI(data); // if you already have this
    }catch(e){
      console.error("usage error", e);
    }
  }

  document.addEventListener("DOMContentLoaded", refreshUsage);
</script>
<script>
/* =======================
   Chat Restore (Ctrl+R)
======================= */
const CHAT_CACHE_KEY = "aimandi_chat_cache_v1";

function saveChatToCache(){
  try{
    if(!IS_LOGGED_IN || !userId) return; // ✅ guest: don't save
    const chatBox = document.getElementById("chatMessages");
    if(!chatBox) return;
    sessionStorage.setItem(CHAT_CACHE_KEY, chatBox.innerHTML); // ✅ sessionStorage
  }catch(e){}
}

function restoreChatFromCache(){
  try{
    if(!IS_LOGGED_IN || !userId) return false; // ✅ guest: don't restore
    const cached = sessionStorage.getItem(CHAT_CACHE_KEY);     // ✅ sessionStorage
    if(!cached) return false;

    const chatBox = document.getElementById("chatMessages");
    if(!chatBox) return false;

    chatBox.innerHTML = cached;
    return true;
  }catch(e){
    return false;
  }
}

function clearChatCache(){
  try{
    sessionStorage.removeItem(CHAT_CACHE_KEY); // ✅ sessionStorage
  }catch(e){}
}


</script>


<script>

  /* 🔥 FIX: use real viewport height for all devices so input is ALWAYS visible */
  function updateVH(){
    const vh = window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', vh + 'px');
  }
  window.addEventListener('load', updateVH);
  window.addEventListener('resize', updateVH);



function setUsagePill(text, cls = "", title = ""){
  if(!usagePill) return;
  usagePill.className = "usage-pill " + cls;
  usagePill.title = title || "";
  usagePill.innerHTML = `<span class="dot"></span><span>${text}</span>`;
}

function setChatEnabled(enabled, reasonText = ""){
  const ta = document.getElementById("userPrompt");
  const btn = document.querySelector(".chat-input button");
  if(ta) ta.disabled = !enabled;
  if(btn) btn.disabled = !enabled;
  if(ta){
    ta.placeholder = enabled ? "Type your message…" : (reasonText || "Daily limit reached. Please try again tomorrow.");
  }
}


  const htmlEl = document.documentElement;
  const themeToggle = document.getElementById('themeToggle');
  const toggleSwitch = document.getElementById('toggleSwitch');
  const themeLabel = document.getElementById('themeLabel');

  function setTheme(mode){
    htmlEl.setAttribute('data-theme', mode);
    themeLabel.textContent = mode === 'light' ? 'Light' : 'Dark';
    toggleSwitch.classList.toggle('active', mode === 'light');
    themeToggle.setAttribute('aria-pressed', mode === 'light');
    localStorage.setItem('aimandi-theme', mode);
  }
  (function initTheme(){
    const saved = localStorage.getItem('aimandi-theme');
    if (saved === 'light' || saved === 'dark'){
      setTheme(saved);
    } else {
      setTheme('dark');
    }
  })();
  themeToggle.addEventListener('click', ()=> {
    const current = htmlEl.getAttribute('data-theme') || 'dark';
    setTheme(current === 'dark' ? 'light' : 'dark');
  });
  themeToggle.addEventListener('keydown', (e)=>{
    if(e.key === 'Enter' || e.key === ' '){
      e.preventDefault();
      themeToggle.click();
    }
  });

  const bodyEl = document.body;
  const sidebarEl = document.getElementById('sidebar');
  const drawerBtn = document.getElementById('drawerToggleBtn');
  const drawerCloseBtn = document.getElementById('drawerCloseBtn');
  const navCloseBtn = document.getElementById('navCloseBtn');

  function isDrawerMode(){ return window.matchMedia('(max-width:1024px)').matches; }
  function openDrawer(){
    if(!isDrawerMode()) return;
    bodyEl.classList.add('drawer-open');
    drawerBtn?.setAttribute('aria-expanded','true');
    sidebarEl?.setAttribute('aria-hidden','false');
  }
  function closeDrawer(){
    bodyEl.classList.remove('drawer-open');
    drawerBtn?.setAttribute('aria-expanded','false');
    sidebarEl?.setAttribute('aria-hidden','true');
  }
  function toggleDrawer(){
    if(bodyEl.classList.contains('drawer-open')) closeDrawer(); else openDrawer();
  }
  drawerBtn?.addEventListener('click', toggleDrawer);

  /* X button on industry drawer closes it */
  drawerCloseBtn?.addEventListener('click', (e)=>{
    e.stopPropagation();
    closeDrawer();
  });

  const sideNavEl = document.getElementById('sideNav');
  const profileBtn = document.getElementById('profileToggleBtn');

  function isNavDrawerMode(){ return window.matchMedia('(max-width:1024px)').matches; }
  function openNavDrawer(){
    if(!isNavDrawerMode()) return;
    bodyEl.classList.add('nav-open');
    profileBtn?.setAttribute('aria-expanded','true');
  }
  function closeNavDrawer(){
    bodyEl.classList.remove('nav-open');
    profileBtn?.setAttribute('aria-expanded','false');
  }
  function toggleNavDrawer(){
    if(bodyEl.classList.contains('nav-open')) closeNavDrawer(); else openNavDrawer();
  }
  profileBtn?.addEventListener('click', toggleNavDrawer);

  navCloseBtn?.addEventListener('click', (e)=>{
    e.stopPropagation();
    closeNavDrawer();
  });

  const mobileActionsWrapper = document.getElementById('mobileActionsWrapper');
  const mobileActionsBtn = document.getElementById('mobileActionsBtn');
  const mobileActionsMenu = document.getElementById('mobileActionsMenu');
  const mobileIndustryBtn = document.getElementById('mobileIndustryBtn');
  const mobileChangeCatBtn = document.getElementById('mobileChangeCatBtn');
  const mobileClearChatBtn = document.getElementById('mobileClearChatBtn'); // may not exist
  const changeCatBtn = document.getElementById('changeCatBtn');
  const clearChatBtn = document.getElementById('clearChatBtn'); // (may not exist)
  const clearChatIconBtn = document.getElementById('clearChatIconBtn');
  const selectedCategoryTag = document.getElementById('selectedCategoryTag'); // label under Quick Actions

  function isMobileHeaderMode(){
    return window.matchMedia('(max-width:560px)').matches;
  }

  function openMobileMenu(){
    if(!mobileActionsMenu) return;
    mobileActionsMenu.style.display = 'flex';
    mobileActionsBtn?.setAttribute('aria-expanded','true');
    mobileActionsBtn?.classList.add('open');
  }
  function closeMobileMenu(){
    if(!mobileActionsMenu) return;
    mobileActionsMenu.style.display = 'none';
    mobileActionsBtn?.setAttribute('aria-expanded','false');
    mobileActionsBtn?.classList.remove('open');
  }
  function toggleMobileMenu(){
    if(!mobileActionsMenu) return;
    const isOpen = mobileActionsMenu.style.display === 'flex';
    if(isOpen) closeMobileMenu(); else openMobileMenu();
  }

  mobileActionsBtn?.addEventListener('click', (e)=>{
    e.stopPropagation();
    toggleMobileMenu();
  });

  mobileIndustryBtn?.addEventListener('click', (e)=>{
    e.stopPropagation();
    closeMobileMenu();
    toggleDrawer();
  });
  mobileChangeCatBtn?.addEventListener('click', (e)=>{
    e.stopPropagation();
    closeMobileMenu();
    changeCatBtn?.click();
  });

  document.addEventListener('click', (e)=>{
    if(isDrawerMode() && bodyEl.classList.contains('drawer-open')){
      const inSidebar = e.target.closest('#sidebar');
      const inBtn = e.target.closest('#drawerToggleBtn');
      if(!inSidebar && !inBtn) closeDrawer();
    }
    if(isNavDrawerMode() && bodyEl.classList.contains('nav-open')){
      const inNav = e.target.closest('#sideNav');
      const inProf = e.target.closest('#profileToggleBtn');
      if(!inNav && !inProf) closeNavDrawer();
    }

    if(isMobileHeaderMode() && mobileActionsMenu && mobileActionsMenu.style.display === 'flex'){
      const inWrapper = e.target.closest('#mobileActionsWrapper');
      if(!inWrapper) closeMobileMenu();
    }
  });

  window.addEventListener('resize', ()=>{
    if(!isDrawerMode()){
      closeDrawer();
      sidebarEl?.setAttribute('aria-hidden','false');
    } else {
      sidebarEl?.setAttribute('aria-hidden', bodyEl.classList.contains('drawer-open') ? 'false' : 'true');
    }
    if(!isNavDrawerMode()){
      closeNavDrawer();
    }
    if(!isMobileHeaderMode()){
      closeMobileMenu();
    }
  });

  function openSection(el){
    el.classList.add('open');
    el.setAttribute('aria-hidden','false');
  }
  function closeSection(el){
    el.classList.remove('open');
    el.setAttribute('aria-hidden','true');
  }
  function smoothScrollInto(el){
    const scroller = document.getElementById('catsScroll');
    if(!el || !scroller) return;
    const top = el.offsetTop - 6;
    scroller.scrollTo({ top, behavior:'smooth' });
  }

  function closeAllCategories(){
    document.querySelectorAll('.cat-body.open').forEach(b => closeSection(b));
    document.querySelectorAll('.topic-body.open').forEach(b => closeSection(b));
    document.querySelectorAll('.cat .toggle-btn').forEach(btn => { if(!btn.classList.contains('mini-toggle')) btn.textContent='Show'; });
    document.querySelectorAll('.topic-head').forEach(th => th.setAttribute('aria-expanded','false'));
    document.querySelectorAll('.mini-toggle').forEach(mt => mt.textContent='Open');
  }

  document.querySelectorAll('.cat').forEach(cat => {
    const btn = cat.querySelector('.toggle-btn');
    const body = cat.querySelector('.cat-body');

    function toggleCategory(){
      const isOpen = body.classList.contains('open');
      closeAllCategories();
      if(!isOpen){
        openSection(body);
        btn.textContent = 'Hide';
        cat.setAttribute('aria-expanded','true');
        smoothScrollInto(cat);
      } else {
        closeSection(body);
        btn.textContent = 'Show';
        cat.setAttribute('aria-expanded','false');
      }
    }

     

    btn.addEventListener('click', (e)=> { e.stopPropagation(); toggleCategory(); });
    cat.addEventListener('click', (e)=>{
      if(e.target.closest('.toggle-btn') || e.target.closest('.sub-action-btn') || e.target.closest('.mini-toggle')) return;
      if(!e.target.closest('.cat-body')) toggleCategory();
    });
  });

  document.querySelectorAll('.topic').forEach(topic => {
    const head = topic.querySelector('.topic-head');
    const miniBtn = topic.querySelector('.mini-toggle');
    const body = topic.querySelector('.topic-body');

    function toggleTopic(){
      const open = body.classList.contains('open');
      if(open){
        closeSection(body);
        head.setAttribute('aria-expanded','false');
        miniBtn.textContent = 'Open';
      } else {
        const parent = topic.parentElement;
        parent.querySelectorAll('.topic-body.open').forEach(tb => { if(tb!==body) closeSection(tb); });
        parent.querySelectorAll('.mini-toggle').forEach(mb => { if(mb!==miniBtn) mb.textContent='Open'; });
        parent.querySelectorAll('.topic-head').forEach(th => { if(th!==head) th.setAttribute('aria-expanded','false'); });
        openSection(body);
        head.setAttribute('aria-expanded','true');
        miniBtn.textContent = 'Close';
        smoothScrollInto(topic);
      }
    }

    head.addEventListener('click', toggleTopic);
    head.addEventListener('keydown', (e)=>{ if(e.key==='Enter' || e.key===' '){ e.preventDefault(); toggleTopic(); }});
    miniBtn.addEventListener('click', (e)=>{ e.stopPropagation(); toggleTopic(); });
  });

  const searchInput = document.getElementById('searchInput');
  searchInput.addEventListener('input', () => {
    const q = searchInput.value.toLowerCase().trim();
    const cats = document.querySelectorAll('.cat');
    cats.forEach(cat=>{
      if (cat.dataset.hiddenByFilter === '1') { cat.style.display = 'none'; return; }

      const catName = (cat.dataset.cat || '').toLowerCase();
      const catBody = cat.querySelector('.cat-body');
      const catBtn = cat.querySelector('.toggle-btn');
      const catMatch = catName.includes(q);
      let anyTopicShown = false;

      cat.querySelectorAll('.topic').forEach(topic=>{
        const topicName = (topic.dataset.topic || '').toLowerCase();
        const body = topic.querySelector('.topic-body');
        const subs = topic.querySelectorAll('.sub');
        const topicMatch = topicName.includes(q);
        let anySubMatch = false;

        subs.forEach(s=>{
          const text = (s.dataset.prompt || '').toLowerCase();
          const showSub = !q || text.includes(q) || topicMatch || catMatch;
          s.style.display = showSub ? '' : 'none';
          if(showSub) anySubMatch = true;
        });

        const showTopic = (!q || topicMatch || anySubMatch || catMatch);
        topic.style.display = showTopic ? '' : 'none';
        if(showTopic) anyTopicShown = true;

        if(q && showTopic){
          if(!body.classList.contains('open')) openSection(body);
          topic.querySelector('.topic-head')?.setAttribute('aria-expanded','true');
          const mini = topic.querySelector('.mini-toggle');
          if(mini) mini.textContent = 'Close';
        } else if(!q){
          if(body.classList.contains('open')) closeSection(body);
          topic.querySelector('.topic-head')?.setAttribute('aria-expanded','false');
          const mini = topic.querySelector('.mini-toggle');
          if(mini) mini.textContent = 'Open';
        }
      });

      const showCat = (!q || catMatch || anyTopicShown);
      cat.style.display = showCat ? '' : 'none';

      if(q && showCat){
        if(!catBody.classList.contains('open')) openSection(catBody);
        catBtn.textContent = 'Hide';
      } else if(!q){
        if(catBody.classList.contains('open')) closeSection(catBody);
        catBtn.textContent = 'Show';
      }
    });
  });

  (function(){
    const tiltEl = document.querySelector('.tilt');
    const isTouch = matchMedia('(hover: none) and (pointer: coarse)').matches;
    function onTilt(e){
      if(!tiltEl) return;
      const rect = tiltEl.getBoundingClientRect();
      const cx = rect.left + rect.width / 2;
      const cy = rect.top + rect.height / 2;
      const dx = (e.clientX - cx) / rect.width;
      const dy = (e.clientY - cy) / rect.height;
      const rotX = dy * -4;
      const rotY = dx * 6;
      tiltEl.style.transform = `rotateX(${rotX}deg) rotateY(${rotY}deg)`;
    }
    function resetTilt(){ if(tiltEl) tiltEl.style.transform = 'none'; }
    if(!isTouch && window.innerWidth >= 480 && tiltEl){
      window.addEventListener('mousemove', onTilt);
      window.addEventListener('mouseleave', resetTilt);
    }
    window.addEventListener('resize', ()=>{
      if(window.innerWidth < 480 || matchMedia('(hover: none) and (pointer: coarse)').matches) resetTilt();
    });
  })();

  function escapeHtml(s){
    return (s||'').replace(/[&<>"']/g, m => (
      {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m] || m
    ));
  }
let EDITING = { el: null, original: "" };
let ACTIVE_REWRITE = { messageEl: null, prevInput: "" };

function setRewriteButtonsState(messageEl, isActive){
  if(!messageEl) return;
  const rw = messageEl.querySelector(".btn-rewrite");
  const cc = messageEl.querySelector(".btn-cancel-rewrite");
  if(!rw || !cc) return;

  if(isActive){
    rw.disabled = true;
    rw.classList.add("disabled");
    cc.style.display = "inline-flex";
  }else{
    rw.disabled = false;
    rw.classList.remove("disabled");
    cc.style.display = "none";
  }
}

let IS_SENDING = false;

function setRewriteEnabled(enabled){
  document.querySelectorAll(".btn-rewrite").forEach(btn=>{
    btn.disabled = !enabled;
    btn.classList.toggle("disabled", !enabled);
    btn.title = enabled ? "Rewrite" : "Please wait… generating response";
  });
}


function buildMessageEl(role, text, meta = null){
  const msg = document.createElement('div');
  msg.className = 'message ' + (role === 'user' ? 'user' : 'bot');

  if(role === 'user'){
    const plain = (text ?? "").toString();
    msg.dataset.raw = plain;

    msg.innerHTML = `
      <div class="msg-text">${escapeHtml(plain)}</div>
      <div class="msg-actions">
        <button type="button" class="btn-rewrite" title="Rewrite">✏️ Rewrite</button>
        <button class="btn-cancel-rewrite" type="button" title="Cancel rewrite" style="display:none;">✖ Cancel</button>
      </div>
    `;
  } else {
    const html = (text ?? "").toString();

    if(meta && meta.history_id){
      const hid = Number(meta.history_id);
      const likeCount = Number(meta.like_count || 0);
      const dislikeCount = Number(meta.dislike_count || 0);
      const userReaction = Number(meta.user_reaction || 0);

      msg.innerHTML = `
        <div class="ai-msg">
          <div class="ai-text">${html}</div>

          <div class="msg-reactions" data-history-id="${hid}">
  <button class="rx-btn ${userReaction===1?'active':''}" data-reaction="1" type="button" title="Like">
    👍 <span class="rx-count rx-like">${likeCount}</span>
  </button>

  <button class="rx-btn ${userReaction===-1?'active':''}" data-reaction="-1" type="button" title="Dislike">
    👎 <span class="rx-count rx-dislike">${dislikeCount}</span>
  </button>

  <!-- ✅ COPY BUTTON -->
  <button class="rx-btn rx-copy" type="button" title="Copy">
    📋 <span class="rx-count">Copy</span>
  </button>
</div>

        </div>
      `;
    } else {
      msg.innerHTML = html;
    }
  }

  return msg;
}

function appendMessage(role, text, meta = null){
  const chatBox = document.getElementById('chatMessages');
  const el = buildMessageEl(role, text, meta);
  chatBox.appendChild(el);
  chatBox.scrollTop = chatBox.scrollHeight;

  // ✅ SAVE FULL CHAT AFTER EVERY MESSAGE
  saveChatToCache();

  return el;
}


function insertMessageAfter(afterEl, role, text, meta = null){
  const chatBox = document.getElementById('chatMessages');
  const el = buildMessageEl(role, text, meta);

  if(afterEl && afterEl.parentNode){
    afterEl.insertAdjacentElement('afterend', el);
  } else {
    chatBox.appendChild(el);
  }

  chatBox.scrollTop = chatBox.scrollHeight;
  saveChatToCache();
  return el;
}

function buildTypingEl(){
  const t = document.createElement("div");
  t.className = "message bot";
  t.innerHTML = "• • •";
  return t;
}

function removeFollowingBotReplies(userEl){
  if(!userEl) return;
  let n = userEl.nextElementSibling;

  // remove bot replies immediately after this user message (old answer + old typing, etc.)
  while(n && n.classList.contains('bot') && !n.classList.contains('user')){
    const kill = n;
    n = n.nextElementSibling;
    kill.remove();
  }
}

document.getElementById("chatMessages")?.addEventListener("click", (e) => {

  const rewriteBtn = e.target.closest(".btn-rewrite");
  const cancelBtn  = e.target.closest(".btn-cancel-rewrite");

  // ✅ Block while generating
  if(IS_SENDING) return;

  // ✅ Cancel rewrite
  if(cancelBtn){
    const msg = cancelBtn.closest(".message.user");
    if(!msg) return;

    if(ACTIVE_REWRITE.messageEl && msg === ACTIVE_REWRITE.messageEl){
      const input = document.getElementById("userPrompt");
      if(input) input.value = ACTIVE_REWRITE.prevInput || "";
      autoResizePrompt?.();

      msg.classList.remove("editing");
      msg.classList.remove("rewrite-active");
      setRewriteButtonsState(msg, false);

      ACTIVE_REWRITE = { messageEl: null, prevInput: "" };

      // reset old edit state too
      EDITING.el = null;
      EDITING.original = "";
    }
    return;
  }

  // ✅ Rewrite
  if(!rewriteBtn) return;

  if(!IS_LOGGED_IN){
    requireLogin();
    return;
  }

  const msg = rewriteBtn.closest(".message.user");
  if(!msg) return;

  const input = document.getElementById("userPrompt");
  if(!input) return;

  // if another rewrite was active, cancel it first
  if(ACTIVE_REWRITE.messageEl && ACTIVE_REWRITE.messageEl !== msg){
    ACTIVE_REWRITE.messageEl.classList.remove("editing");
    ACTIVE_REWRITE.messageEl.classList.remove("rewrite-active");
    setRewriteButtonsState(ACTIVE_REWRITE.messageEl, false);
    ACTIVE_REWRITE = { messageEl: null, prevInput: "" };
  }

  // save current input so Cancel can restore
  ACTIVE_REWRITE.prevInput = input.value || "";
  ACTIVE_REWRITE.messageEl = msg;

  // remove editing class from others
  document.querySelectorAll(".message.user.editing").forEach(m => m.classList.remove("editing"));

  msg.classList.add("editing");
  msg.classList.add("rewrite-active");
  setRewriteButtonsState(msg, true);

  // load into textarea
  const raw = msg.dataset.raw || msg.querySelector(".msg-text")?.innerText || "";
  input.value = raw;
  autoResizePrompt?.();
  input.focus();

  // keep your old edit state too
  EDITING.el = msg;
  EDITING.original = raw;
});





  // will store backend template (prompt_text) for currently selected prompt
  let currentPromptId = null;
let currentCategoryId = null;
let currentTopicId = null;
  let currentTemplateExtra = '';

  function savePendingIds(promptId, categoryId, topicId){
  try{
    localStorage.setItem("pending_prompt_id", String(promptId || ""));
    localStorage.setItem("pending_category_id", String(categoryId || ""));
    localStorage.setItem("pending_topic_id", String(topicId || ""));
  }catch(e){}
}

function restorePendingIds(){
  try{
    const pid = localStorage.getItem("pending_prompt_id") || "";
    const cid = localStorage.getItem("pending_category_id") || "";
    const tid = localStorage.getItem("pending_topic_id") || "";

    // clear after reading
    localStorage.removeItem("pending_prompt_id");
    localStorage.removeItem("pending_category_id");
    localStorage.removeItem("pending_topic_id");

    currentPromptId = pid ? Number(pid) : null;
    currentCategoryId = cid ? Number(cid) : null;
    currentTopicId = tid ? Number(tid) : null;
  }catch(e){}
}

  
function showDailyLimitMessage(used, limit){
  const u = Number(used ?? 0);
  const l = Number(limit ?? 8000);

  const html = `
    ✅ Daily limit reached<br>
    Used today: <b>${u}</b> / <b>${l}</b><br>
    It resets automatically tomorrow 😊
  `;

  appendMessage("bot", html);
  setUsagePill("0 tokens left", "danger", "Daily free limit");
  setChatEnabled(false, "Daily limit reached. Please try again tomorrow.");
}



function fillPrompt(evt, promptText, promptId, categoryId, topicId){
  evt.stopPropagation();

  if(!IS_LOGGED_IN){
  savePendingIds(promptId, categoryId, topicId);
  localStorage.setItem('pending_prompt', promptText);
  localStorage.setItem('pending_action', 'edit');
  requireLogin();
  return;
}


  if(!USAGE.unlimited && USAGE.limitReached){
    showDailyLimitMessage(USAGE.used, USAGE.limit);
    return;
  }

  // ✅ store ids
  currentPromptId = promptId || null;
  currentCategoryId = categoryId || null;
  currentTopicId = topicId || null;

  // ✅ optional: clear old template to avoid confusion
  currentTemplateExtra = "";

  const input = document.getElementById('userPrompt');
  input.value = promptText;
  autoResizePrompt();
  input.focus();
}

function sendPromptFromSidebar(evt, promptText, promptId, categoryId, topicId){
  evt.stopPropagation();

if(!IS_LOGGED_IN){
  savePendingIds(promptId, categoryId, topicId);
  localStorage.setItem('pending_prompt', promptText);
  localStorage.setItem('pending_action', 'send');
  requireLogin();
  return;
}



  if(!USAGE.unlimited && USAGE.limitReached){
    showDailyLimitMessage(USAGE.used, USAGE.limit);
    return;
  }

  // ✅ store ids
  currentPromptId = promptId || null;
  currentCategoryId = categoryId || null;
  currentTopicId = topicId || null;

  // ✅ optional: clear old template to avoid confusion
  currentTemplateExtra = "";

  const input = document.getElementById('userPrompt');
  input.value = promptText;
  autoResizePrompt();
  sendPrompt();
}


function sendPrompt(){

  // ✅ FALLBACK: restore pending ids if missing
  if(currentPromptId === null){
    restorePendingIds();
  }

  const input = document.getElementById('userPrompt');
  const prompt = (input.value || '').trim();
  if(!prompt) return;

  // ✅ NEW: If not logged in → store prompt + ids and open login popup (NO URL change)
  if(!IS_LOGGED_IN || !userId){
    savePendingIds(currentPromptId, currentCategoryId, currentTopicId);
    localStorage.setItem('pending_prompt', prompt);
    localStorage.setItem('pending_action', 'send');

    // open login modal/popup (same as sidebar buttons)
    requireLogin();
    return; // ❗ don't clear input
  }

  // ✅ clear input UI ONLY when logged in
  input.value = '';
  input.style.height = '';

  if(isDrawerMode() && bodyEl.classList.contains('drawer-open')) closeDrawer();

  // ✅ if editing → update existing bubble + re-run backend (keep prompt_id)
  if(EDITING.el){
    sendPromptDirect(prompt, { editEl: EDITING.el });
    return;
  }

  // normal send
  sendPromptDirect(prompt);

  // ✅ clear edit state (this block is mostly unnecessary now, but ok)
  if(EDITING.el){
    EDITING.el.classList.remove("editing");
    EDITING.el = null;
    EDITING.original = "";
  }
}



  

/* ===========================
   Live Markdown Typing (ChatGPT-like)
   - renders markdown while typing
=========================== */
function mdToHtml(mdText){
  try{
    if(typeof marked !== 'undefined'){
      // breaks:true => single newlines become <br>
      return marked.parse(mdText || '', { breaks: true, gfm: true });
    }
  }catch(e){}
  // fallback: escape + <br>
  return `<div class=\"msg-text\">${escapeHtml(mdText||'').replace(/\n/g,'<br>')}</div>`;
}

function sleep(ms){ return new Promise(r=>setTimeout(r, ms)); }

async function typeMarkdownInto(containerEl, fullMd, opts={}){
  const speed = typeof opts.speed === 'number' ? opts.speed : 12; // ms
  const chunk = typeof opts.chunk === 'number' ? opts.chunk : 3;  // chars per render
  const chatBox = document.getElementById('chatMessages');

  const cursor = document.createElement('span');
  cursor.className = 'type-cursor';
  cursor.textContent = '▍';

  let buf = '';
  for(let i=0;i<fullMd.length;i++){
    buf += fullMd[i];

    // throttle rendering
    if(i % chunk === 0 || i === fullMd.length-1){
      containerEl.innerHTML = mdToHtml(buf);
      containerEl.appendChild(cursor);
      // keep scroll pinned
      if(chatBox) chatBox.scrollTop = chatBox.scrollHeight;
      await sleep(speed);
    }
  }

  // final render (no cursor)
  containerEl.innerHTML = mdToHtml(fullMd);
  if(chatBox) chatBox.scrollTop = chatBox.scrollHeight;
}

function botHtml(reply){
  const s = (reply ?? "").toString();
  // If backend already sends HTML, keep it
  if(s.trim().startsWith("<")) return s;
  // Otherwise show safely with line breaks
  return `<div class="msg-text">${escapeHtml(s).replace(/\n/g, "<br>")}</div>`;
}

function updateUsageUI(data){
  if(!data) return;

  if(data.unlimited){
    setUsagePill("Unlimited", "unlimited", "Premium user");
    setChatEnabled(true);
    return;
  }

  if(typeof data.remaining === "number"){
    const remaining = Number(data.remaining);
    setUsagePill(`${remaining} tokens left`, remaining <= 0 ? "danger" : "ok", "Daily free limit");
    setChatEnabled(remaining > 0, "Daily limit reached. Please try again tomorrow.");
  }
}
if(ACTIVE_REWRITE.messageEl){
  ACTIVE_REWRITE.messageEl.classList.remove("rewrite-active");
  setRewriteButtonsState(ACTIVE_REWRITE.messageEl, false);
  ACTIVE_REWRITE = { messageEl: null, prevInput: "" };
}

function sendPromptDirect(promptText, opts = {}){
  const chatBox = document.getElementById("chatMessages");

  // ✅ Decide where to place output
  let userEl = null;

  // EDIT-IN-PLACE
  if(opts.editEl){
    userEl = opts.editEl;

    // update user bubble content
    userEl.dataset.raw = promptText;
    const txt = userEl.querySelector(".msg-text");
    if(txt) txt.innerHTML = escapeHtml(promptText);

    // remove old bot reply under this user bubble
    removeFollowingBotReplies(userEl);

    // clear editing state
    userEl.classList.remove("editing");
    EDITING.el = null;
    EDITING.original = "";
  }
  // NORMAL
  else{
    userEl = appendMessage('user', promptText);
    saveChatToCache();

  }

  // remove any global typing leftovers (safe)
  document.getElementById("typingIndicator")?.remove();

  // typing indicator inserted right after THIS user bubble
  const typingIndicator = buildTypingEl();
  typingIndicator.id = "typingIndicator";
  userEl.insertAdjacentElement("afterend", typingIndicator);
  chatBox.scrollTop = chatBox.scrollHeight;

    // ✅ LOCK UI while generating
  IS_SENDING = true;
  setChatEnabled(false, "Generating… please wait");
  setRewriteEnabled(false);

  // 🔍 DEBUG: check ids before sending to backend
console.log("FINAL IDS BEFORE SEND:", {
  currentPromptId,
  currentCategoryId,
  currentTopicId
});
  fetch("/api/chat", {
  method:"POST",
  headers:{ "Content-Type":"application/json" },
  body: JSON.stringify({
    prompt: promptText,
    user_id: userId,

    // ✅ REQUIRED for backend hidden prompt
    prompt_id: currentPromptId,
    category_id: currentCategoryId,
    topic_id: currentTopicId
  })
})

.then(async (res) => {
  // ✅ Read body ONCE safely (JSON if possible, else text)
  const ct = (res.headers.get("content-type") || "").toLowerCase();
  const rawText = ct.includes("application/json") ? null : await res.text();

  let data = null;
  if (ct.includes("application/json")) {
    try {
      data = await res.json();
    } catch (e) {
      // JSON parsing failed unexpectedly
      console.error("JSON parse failed:", e);
      data = { error: "Invalid JSON from server" };
    }
  } else {
    console.error("Non-JSON response:", res.status, (rawText || "").slice(0, 200));
    data = { error: `Non-JSON response (HTTP ${res.status})`, raw: rawText };
  }


    if(res.status === 429){
      typingIndicator?.remove();

      const used  = (data?.used ?? USAGE.used ?? 0);
      const limit = (data?.limit ?? USAGE.limit ?? 8000);

      showDailyLimitMessage(used, limit);
      updateUsageUI(data);
      refreshUsage?.();
      autoFocusChat();
      return null;
    }

    if(!res.ok){
      const fallback = data?.error ? data.error : `Request failed (HTTP ${res.status})`;
      throw new Error(fallback);
    }

    return data;
  })
  .then((data) => {
    if(!data) return;

    typingIndicator?.remove();

    // ✅ Insert a bot bubble first (empty), then type + render markdown live
    const botEl = insertMessageAfter(userEl, "bot", `<div class="msg-text live-md"></div>`, {
      history_id: data.history_id,
      like_count: data.like_count,
      dislike_count: data.dislike_count,
      user_reaction: data.user_reaction
    });

    // Prefer markdown from backend for best formatting while typing
    const fullMd = (data.reply_md ?? data.reply ?? "").toString();
    const target = botEl?.querySelector('.live-md') || botEl?.querySelector('.msg-text') || botEl;

    // Live type with formatting
    typeMarkdownInto(target, fullMd, { speed: 10, chunk: 2 });

    updateUsageUI(data);
    refreshUsage?.();
    autoFocusChat();
  })
  .catch((err) => {
    typingIndicator?.remove();
    insertMessageAfter(userEl, "bot", botHtml("⚠️ Something went wrong. Please try again."));
    console.error(err);
    autoFocusChat();
  })
  .finally(() => {
    // ✅ UNLOCK UI
    IS_SENDING = false;
    setChatEnabled(true);
    setRewriteEnabled(true);
      // ✅ RESET IDS after request finished
  currentPromptId = null;
  currentCategoryId = null;
  currentTopicId = null;
  });
  }


  /* ✅ Shared clear chat function used by icon + mobile menu */
function clearChat(){
  const box = document.getElementById('chatMessages');
  box.innerHTML = '';

  // ✅ clear restore cache
  clearChatCache();

  // ✅ reset ids
  currentPromptId = null;
  currentCategoryId = null;
  currentTopicId = null;

  appendMessage('bot','🧹 Chat cleared. How can I help you next?');

  // ✅ save empty/new state
  saveChatToCache();
}


  if(clearChatBtn){
    clearChatBtn.addEventListener('click', clearChat);
  }
  if(clearChatIconBtn){
    clearChatIconBtn.addEventListener('click', clearChat);
  }

  mobileClearChatBtn?.addEventListener('click', (e)=>{
    e.stopPropagation();
    closeMobileMenu();
    clearChat();
  });

  let currentPaid = '';
  const paidChips = [
    "Ads purpose (sales / awareness)",
    "Tone (friendly / professional)",
    "Budget (low / medium / high)",
    "Audience (location / age / interests)",
    "CTA (Book Now / Buy / Sign-up)"
  ];

  function openPaidModal(paidTitle){
    currentPaid = paidTitle;
    document.getElementById('modalPromptTitle').innerText = paidTitle;
    const opts = document.getElementById('modalOpts');
    opts.innerHTML = '';
    paidChips.forEach((c,i)=>{
      const div = document.createElement('div');
      div.className = 'opt';
      div.innerHTML = `
        <div style="font-weight:800;">${c}</div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
          <input placeholder="enter value (optional)" id="optInput${i}">
          <button class="apply" onclick="applyPaidOpt(event, ${i})">Apply</button>
        </div>
      `;
      opts.appendChild(div);
    });
    const m = document.getElementById('paidModal');
    m.style.display = 'flex';
    m.setAttribute('aria-hidden','false');
  }

  function closePaidModal(){
    const m = document.getElementById('paidModal');
    m.style.display = 'none';
    m.setAttribute('aria-hidden','true');
    currentPaid = '';
  }

  function applyPaidOpt(evt, idx){
    evt.stopPropagation();
    const val = (document.getElementById('optInput'+idx)?.value || '').trim();
    const chip = paidChips[idx] + (val ? (": " + val) : "");
    const input = document.getElementById('userPrompt');
    input.value = currentPaid + " — " + chip;
    autoResizePrompt();
    closePaidModal();
    input.focus();
  }




















  function usePaidPrompt(){
    let combined = currentPaid + " — Premium: ";
    const bits = [];
    for(let i=0;i<5;i++){
      const v = (document.getElementById('optInput'+i)?.value || '').trim();
      bits.push(v || '[not specified]');
    }
    combined += bits.join(' | ');
    const input = document.getElementById('userPrompt');
    input.value = combined;
    autoResizePrompt();
    closePaidModal();
    input.focus();
  }

  document.getElementById('paidModal').addEventListener('click', (e)=>{
    if(e.target === document.getElementById('paidModal')) closePaidModal();
  });

  const categoryModal = document.getElementById('categoryModal');
  const catList = document.getElementById('catList');
  const catSearchInput = document.getElementById('catSearchInput');

 function openCategoryModal(){
  // 1️⃣ Open modal immediately (no delay)
  categoryModal.style.display = 'flex';
  categoryModal.setAttribute('aria-hidden','false');

  // 2️⃣ Show instant loading text
  catList.innerHTML = '<div style="padding:12px;color:#6b7280">Loading categories...</div>';

  // 3️⃣ Build category list AFTER browser paints UI
  requestAnimationFrame(()=>{
    requestAnimationFrame(()=>{
      buildCategoryList();
      catSearchInput.value = '';
      catSearchInput.focus();
    });
  });
}

  function closeCategoryModal(){
    categoryModal.style.display = 'none';
    categoryModal.setAttribute('aria-hidden','true');
  }
  categoryModal.addEventListener('click', (e)=>{
    if(e.target === categoryModal) closeCategoryModal();
  });

  function openAlert(){
    const m = document.getElementById('alertModal');
    m.style.display = 'flex';
    m.setAttribute('aria-hidden','false');
  }
  function closeAlert(){
    const m = document.getElementById('alertModal');
    m.style.display = 'none';
    m.setAttribute('aria-hidden','true');
  }

  function buildCategoryList(filter=''){
    const cats = Array.from(document.querySelectorAll('.cat'))
      .map(c => ({
        name: c.dataset.cat || '',
        icon: c.querySelector('.cat-ico')?.textContent?.trim() || '📁'
      }));
    const selected = localStorage.getItem('aimandi-selected-category') || '';
    catList.innerHTML = '';
    const q = (filter || '').toLowerCase().trim();
    cats
      .filter(c => !q || c.name.toLowerCase().includes(q))
      .forEach((c, idx)=>{
        const id = 'catOpt_' + idx;
        const row = document.createElement('label');
        row.className = 'cat-item';
        row.innerHTML = `
          <input type="radio" name="catChoice" id="${id}" value="${c.name.replace(/"/g,'&quot;')}" ${selected===c.name ? 'checked' : ''} />
          <span style="font-size:1.1rem">${c.icon}</span>
          <strong>${c.name}</strong>
        `;
        catList.appendChild(row);
      });
    if(!catList.children.length){
      const empty = document.createElement('div');
      empty.style.padding = '10px';
      empty.textContent = 'No categories found.';
      catList.appendChild(empty);
    }
  }
  catSearchInput.addEventListener('input', ()=> buildCategoryList(catSearchInput.value));

  /* 🔥 Update selected category UI (desktop + mobile tag) */
  function updateSelectedCategoryUI(name){
    const desktopLabel = document.getElementById('selectedCategoryDesktop');
    const mobileTag = document.getElementById('selectedCategoryTag');

    if(desktopLabel){
      if(name){
        desktopLabel.textContent = 'Selected: ' + name;
        desktopLabel.style.display = 'block';
      } else {
        desktopLabel.textContent = '';
        desktopLabel.style.display = 'none';
      }
    }

    if(mobileTag){
      if(name){
        mobileTag.textContent = 'Selected: ' + name;
        mobileTag.style.display = 'block';
      } else {
        mobileTag.textContent = '';
        mobileTag.style.display = 'none';
      }
    }
  }

function applySelectedCategory(){
  const picked = document.querySelector('input[name="catChoice"]:checked');
  if(!picked){ openAlert(); return; }

  const name = picked.value;

  // ✅ save + filter UI
  localStorage.setItem('aimandi-selected-category', name);
  applyCategoryFilter(name);
  updateSelectedCategoryUI(name);

  // ✅ NEW: update URL without reload
  const slug = catToSlug(name);
  setUrlCategorySlug(slug);

  closeCategoryModal();
  if(isDrawerMode()) openDrawer();
}

  
  function catToSlug(name){
  return (name || "")
    .toString()
    .trim()
    .toLowerCase()
    .replace(/&/g, "and")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/-+/g, "-")
    .replace(/^-|-$/g, "");
}

function slugToCatName(slug){
  // find exact category name from DOM by slug match
  const cats = Array.from(document.querySelectorAll(".cat"));
  const found = cats.find(el => catToSlug(el.dataset.cat || "") === slug);
  return found ? (found.dataset.cat || "") : "";
}

function setUrlCategorySlug(slug){
  const url = new URL(window.location.href);
  if(slug) url.searchParams.set("cat", slug);
  else url.searchParams.delete("cat");
  history.pushState({cat: slug || ""}, "", url.pathname + "?" + url.searchParams.toString());
}

function getUrlCategorySlug(){
  return (new URL(window.location.href)).searchParams.get("cat") || "";
}

function applyCategoryFromSlug(slug){
  if(!slug) return false;

  const name = slugToCatName(slug);
  if(!name) return false;

  localStorage.setItem("aimandi-selected-category", name);
  applyCategoryFilter(name);
  updateSelectedCategoryUI(name);
  return true;
}


  function applyCategoryFilter(catName){
    document.querySelectorAll('.cat').forEach(cat=>{
      const isMatch = (cat.dataset.cat === catName);
      cat.dataset.hiddenByFilter = isMatch ? '0' : '1';
      cat.style.display = isMatch ? '' : 'none';
      const body = cat.querySelector('.cat-body');
      const btn = cat.querySelector('.toggle-btn');
      if(!isMatch){
        if(body && body.classList.contains('open')) closeSection(body);
        if(btn && !btn.classList.contains('mini-toggle')) btn.textContent='Show';
        cat.setAttribute('aria-expanded','false');
      }
    });
    const s = document.getElementById('searchInput'); if(s) s.value='';
  }

  document.getElementById('changeCatBtn').addEventListener('click', openCategoryModal);
  document.getElementById('alertModal').addEventListener('click', (e)=>{
    if(e.target === document.getElementById('alertModal')) closeAlert();
  });

  document.getElementById('userPrompt').addEventListener('keydown', (e)=>{
    if(e.key === 'Enter' && !e.shiftKey){
      e.preventDefault();
      sendPrompt();
    }
  });

  function autoFocusChat(){
    const chatMessages = document.getElementById('chatMessages');
    const input = document.getElementById('userPrompt');
    chatMessages.scrollTop = chatMessages.scrollHeight;
    input.focus();
  }

  function autoResizePrompt(){
    const el = document.getElementById('userPrompt');
    if(!el) return;
    el.style.height = 'auto';
    const maxHeight = window.innerHeight * 0.4;
    const newH = Math.min(el.scrollHeight, maxHeight);
    el.style.height = Math.max(newH, 42) + 'px';
  }

  const promptEl = document.getElementById('userPrompt');
  if(promptEl){
    promptEl.addEventListener('input', autoResizePrompt);
  }
  window.addEventListener('resize', autoResizePrompt);

 window.addEventListener('DOMContentLoaded', ()=>{

  const url = new URL(window.location.href);
  const isResume = url.searchParams.get('resume') === '1' || localStorage.getItem('resume_after_login') === '1';

  if(isResume){
    // ✅ don't show category popup on resume
    localStorage.removeItem('resume_after_login');

    // ✅ remove ?resume=1 from URL (so refresh behaves normally)
    url.searchParams.delete('resume');
    window.history.replaceState({}, document.title, url.pathname + (url.searchParams.toString() ? '?' + url.searchParams.toString() : ''));
  } else {
    // ✅ normal behavior: show category popup only if nothing selected
    const savedCategory = localStorage.getItem('aimandi-selected-category');
    if(!savedCategory){
      openCategoryModal();
    }
  }

  sidebarEl?.setAttribute('aria-hidden', isDrawerMode() ? 'true' : 'false');
  autoResizePrompt();
  updateVH();

  const savedCategory = localStorage.getItem('aimandi-selected-category');
  if(savedCategory){
    applyCategoryFilter(savedCategory);
    updateSelectedCategoryUI(savedCategory);
  }
  
    // ✅ ADD HERE: If URL has ?cat=slug, override localStorage
  const slug = getUrlCategorySlug();
  if(slug){
    applyCategoryFromSlug(slug);
  }
});



 /* PAYMENT MODAL (Unlock Category) */
let payData = { catId: null, name: '', price: '0.00', currency: 'INR' };

function startUnlock(categoryId, categoryName, price, currency) {

  // ✅ Guest click on Unlock → force login first
  if(!IS_LOGGED_IN){
    localStorage.setItem('pending_unlock', JSON.stringify({
      categoryId,
      categoryName,
      price,
      currency: currency || 'INR'
    }));
    requireLogin();
    return;
  }

  // ✅ set payData (important)
  payData = {
    catId: categoryId,
    name: categoryName,
    price: price,
    currency: currency || 'INR'
  };

  document.getElementById('payCategoryId').value         = categoryId;
  document.getElementById('payCategoryNameInput').value  = categoryName;
  document.getElementById('payAmount').value             = price;
  document.getElementById('payCurrency').value           = payData.currency;

  document.getElementById('payCategoryName').innerText   = categoryName;
  document.getElementById('payAmountLine').innerText     =
    `Amount: ${payData.currency} ${parseFloat(price).toFixed(2)}`;

  const m = document.getElementById('paymentModal');
  m.style.display = 'flex';
  m.setAttribute('aria-hidden', 'false');
}


function closePaymentModal() {
  const m = document.getElementById('paymentModal');
  m.style.display = 'none';
  m.setAttribute('aria-hidden', 'true');
}

document.getElementById('paymentModal')?.addEventListener('click', (e) => {
  if (e.target === document.getElementById('paymentModal')) closePaymentModal();
});

/* Cashfree: intercept form submit and open JS checkout */
document.addEventListener('DOMContentLoaded', () => { 
  
  /* ===============================
     1️⃣ Restore chat on refresh
  =============================== */
let restored = false;

if(IS_LOGGED_IN && userId){
  restored = restoreChatFromCache(); // ✅ logged-in: restore on refresh
} else {
  clearChatCache();                  // ✅ guest: always blank
  const chatBox = document.getElementById("chatMessages");
  if(chatBox) chatBox.innerHTML = "";
}
    // ✅ restore pending prompt AFTER chat cache restore
  if(IS_LOGGED_IN){
    try{
      const p = localStorage.getItem('pending_prompt');
      const a = localStorage.getItem('pending_action');

      if(p && a){
        restorePendingIds();
        localStorage.removeItem('pending_prompt');
        localStorage.removeItem('pending_action');

        const input = document.getElementById('userPrompt');
        if(input){
          input.value = p;
          autoResizePrompt();
          input.focus();

          if(a === 'send'){
            sendPrompt(); // ✅ now typing dots will show correctly
          }
        }
      }
    }catch(e){}
  }

  if(restored){
    const chatBox = document.getElementById("chatMessages");
    if(chatBox){
      chatBox.scrollTop = chatBox.scrollHeight;
    }
  }

  const form = document.getElementById("paymentForm");
  if(!form) return;

  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    try {
      const formData = new FormData(form);

      const res = await fetch("cashfree_start.php", {
        method: "POST",
        body: formData
      });

      // cashfree_start.php MUST return JSON:
      // { success: true, payment_session_id: "..." }
      const data = await res.json();

      if (!data.success || !data.payment_session_id) {
        alert(data.message || "Error starting payment.");
        console.error("cashfree_start error:", data);
        return;
      }

      closePaymentModal();

      cf.checkout({
        paymentSessionId: data.payment_session_id,
        redirectTarget: "_self"
      });

    } catch (err) {
      alert("Payment Error: " + err.message);
      console.error(err);
    }
  });
});

// (function(){
//   if(!IS_LOGGED_IN) return;

//   // ✅ restore free prompt action (edit/send)
//   try{
//     const p = localStorage.getItem('pending_prompt');
//     const a = localStorage.getItem('pending_action');

//     if(p && a){
//       restorePendingIds();
//       localStorage.removeItem('pending_prompt');
//       localStorage.removeItem('pending_action');

//       const input = document.getElementById('userPrompt');
//       if(input){
//         input.value = p;
//         autoResizePrompt();
//         input.focus();
//         if(a === 'send'){
//           sendPrompt();
//         }
//       }
//     }
//   }catch(e){}

//   // ✅ restore unlock action
//   try{
//     const u = localStorage.getItem('pending_unlock');
//     if(u){
//       localStorage.removeItem('pending_unlock');
//       const d = JSON.parse(u);
//       if(d && d.categoryId){
//         startUnlock(d.categoryId, d.categoryName, d.price, d.currency);
//       }
//     }
//   }catch(e){}
// })();


</script>



<script>
  /* ✅ DASHBOARD POPUP (LEFT NAV Dashboard Button) */
const openDashPopupBtn = document.getElementById('openDashPopupBtn');
const userDashModal = document.getElementById('userDashModal');
const dashCloseBtn = document.getElementById('dashCloseBtn');

function openUserDashModal(){
  if(!userDashModal) return;
  userDashModal.style.display = 'grid';
  userDashModal.setAttribute('aria-hidden','false');
  document.body.classList.add('dash-lock');
}

function closeUserDashModal(){
  if(!userDashModal) return;
  userDashModal.style.display = 'none';
  userDashModal.setAttribute('aria-hidden','true');
  document.body.classList.remove('dash-lock');
}

openDashPopupBtn?.addEventListener('click', (e)=>{
  // prevent navigation to dashboard.php (popup opens instead)
  e.preventDefault();
  e.stopPropagation();
  openUserDashModal();
});

dashCloseBtn?.addEventListener('click', (e)=>{
  e.stopPropagation();
  closeUserDashModal();
});

userDashModal?.addEventListener('click', (e)=>{
  // click outside card closes
  if(e.target === userDashModal) closeUserDashModal();
});

document.addEventListener('keydown', (e)=>{
  if(e.key === 'Escape' && userDashModal && userDashModal.getAttribute('aria-hidden') === 'false'){
    closeUserDashModal();
  }
});

/* ✅ Smooth accordion open/close inside dash modal */
document.addEventListener('click', (e)=>{
  const head = e.target.closest('.dash-cat-head');
  if(!head) return;

  const cat = head.closest('.dash-cat');
  const body = cat?.querySelector('.dash-cat-body');
  if(!body) return;

  const isOpen = body.classList.contains('open');

  // close others (nice clean UX)
  document.querySelectorAll('.dash-cat-body.open').forEach(b=>{
    if(b !== body){
      b.classList.remove('open');
      b.setAttribute('aria-hidden','true');
      const h = b.closest('.dash-cat')?.querySelector('.dash-cat-head');
      h?.setAttribute('aria-expanded','false');
    }
  });

  if(isOpen){
    body.classList.remove('open');
    body.setAttribute('aria-hidden','true');
    head.setAttribute('aria-expanded','false');
  } else {
    body.classList.add('open');
    body.setAttribute('aria-hidden','false');
    head.setAttribute('aria-expanded','true');
  }
});

</script>

<script>// ===========================
// Like / Dislike click handler
// ===========================
document.getElementById("chatMessages")?.addEventListener("click", async (e) => {
  const btn = e.target.closest(".rx-btn");
  if(!btn) return;

  // ✅ COPY FIRST (before historyId checks)
  if(btn.classList.contains("rx-copy")){
    const botMessage = btn.closest(".message.bot");
    const aiTextEl = botMessage?.querySelector(".ai-text"); // your bot html area
    const textToCopy = (aiTextEl ? aiTextEl.innerText : botMessage?.innerText || "").trim();

    if(!textToCopy){
      alert("Nothing to copy.");
      return;
    }

    try{
      await navigator.clipboard.writeText(textToCopy);
    }catch(err){
      // fallback for some browsers
      const ta = document.createElement("textarea");
      ta.value = textToCopy;
      ta.style.position = "fixed";
      ta.style.left = "-9999px";
      document.body.appendChild(ta);
      ta.select();
      document.execCommand("copy");
      ta.remove();
    }

    const lbl = btn.querySelector(".rx-count");
    if(lbl){
  const old = lbl.textContent;
  lbl.textContent = "Copied!";

  btn.classList.add("copied");                 // ✅ ADD
  setTimeout(()=> btn.classList.remove("copied"), 1200);  // ✅ ADD

  setTimeout(()=> lbl.textContent = old || "Copy", 1200);
}
    return;
  }

  // ✅ Like/Dislike continues below
  if(!IS_LOGGED_IN){
    requireLogin();
    return;
  }

  const wrap = btn.closest(".msg-reactions");
  const historyId = Number(wrap?.dataset?.historyId || 0);
  if(!historyId) return;

  const reaction = Number(btn.dataset.reaction || 0); // 1 or -1

  try{
    const res = await fetch("/api/react", {
      method:"POST",
      headers:{"Content-Type":"application/json"},
      body: JSON.stringify({
        user_id: userId,
        history_id: historyId,
        reaction: reaction
      })
    });

    const data = await res.json();

    if(!res.ok){
      console.error("React error:", data);
      return;
    }

    wrap.querySelector(".rx-like").textContent = Number(data.like_count || 0);
    wrap.querySelector(".rx-dislike").textContent = Number(data.dislike_count || 0);

    wrap.querySelectorAll(".rx-btn").forEach(b => b.classList.remove("active"));
    if(Number(data.user_reaction) === 1){
      wrap.querySelector('.rx-btn[data-reaction="1"]')?.classList.add("active");
    } else if(Number(data.user_reaction) === -1){
      wrap.querySelector('.rx-btn[data-reaction="-1"]')?.classList.add("active");
    }

  }catch(err){
    console.error(err);
  }
});

</script>
<script>
    window.addEventListener("popstate", () => {
  const slug = getUrlCategorySlug();
  if(slug){
    applyCategoryFromSlug(slug);
  } else {
    // if no cat in URL, you can either show all or keep last selection
    // Here: show all categories again
    document.querySelectorAll('.cat').forEach(cat=>{
      cat.dataset.hiddenByFilter = '0';
      cat.style.display = '';
    });
    updateSelectedCategoryUI("");
    localStorage.removeItem("aimandi-selected-category");
  }
});

</script>

<script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
<script>
  // sandbox for testing, switch to "production" when live
  const cf = Cashfree({ mode: "sandbox" });
</script>

</body>
</html>


