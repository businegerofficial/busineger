<?php
session_start();
require('./backend/db.php');

if (isset($_GET['from_cart']) && $_GET['from_cart'] == '1') {
  $_SESSION['from_cart'] = true;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

  $email = htmlspecialchars($_POST['email']);
  $password = htmlspecialchars($_POST['password']);

  $sql = "SELECT * FROM users WHERE email = :email";
  $stmt = $pdo->prepare($sql);
  $stmt->bindParam(':email', $email, PDO::PARAM_STR);
  $stmt->execute();

  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($user && password_verify($password, $user['password'])) {

    $_SESSION['user'] = [
      'id'       => (int)$user['id'],
      'user_id'  => (int)$user['id'],
      'email'    => $user['email'],
      'username' => $user['username'],
      'role'     => $user['role'] ?? 'user'
    ];

    /* ✅ EMBED LOGIN (for newchat modal login) */
  /* ✅ EMBED LOGIN (for newchat modal login) */
$redirect = $_GET['redirect'] ?? 'newchat.php';
$redirect = preg_replace('/[^a-zA-Z0-9_\-\/\.\?\=\&]/', '', $redirect);
if ($redirect === '') $redirect = 'newchat.php';

if (isset($_GET['embed']) && $_GET['embed'] == '1') {

  // If user came from cart, go to payment page (top window)
  if (isset($_SESSION['from_cart']) && $_SESSION['from_cart']) {
    echo "<script>
      try { window.parent.postMessage({ type:'LOGIN_SUCCESS' }, window.location.origin); } catch(e){}
      window.top.location.href = '/mandipayment.php';
    </script>";
    exit;
  }

  // Normal: return to redirect page (top window)
  echo "<script>
    try { window.parent.postMessage({ type:'LOGIN_SUCCESS' }, window.location.origin); } catch(e){}
    window.top.location.href='/" . $redirect . "';
  </script>";
  exit;
}

    if (isset($_SESSION['from_cart']) && $_SESSION['from_cart']) {
      if (isset($_SESSION['pending_cart'])) {
        $_SESSION['cart'] = $_SESSION['pending_cart'];
        unset($_SESSION['pending_cart']);
      }
      unset($_SESSION['from_cart']);
      header("Location: mandipayment.php");
    } else {
      $redir = $_GET['redirect'] ?? '';
      $redir = is_string($redir) ? trim($redir) : '';

      $safe = 'index.php';
      if ($redir !== ''
        && strpos($redir, '://') === false
        && strpos($redir, "\n") === false
        && strpos($redir, "\r") === false
        && !preg_match('#^(//|https?:)#i', $redir)
        && $redir[0] !== '/'
      ) {
        $safe = $redir;
      }

      header("Location: {$safe}");
    }
    exit();
  } else {
    $error_message = "Invalid email or password!";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>Busineger - Login</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <?php $isEmbed = (isset($_GET['embed']) && $_GET['embed']=='1'); ?>
<?php if(!$isEmbed): ?>
  <script src="https://accounts.google.com/gsi/client" async defer></script>
<?php endif; ?>


<style>
  :root{
    --bgTop:#071a3a;
    --bgMid:#061531;
    --bgBot:#050e24;

    --text:#f9fafb;
    --muted:rgba(226,232,240,.70);

    --blue:#2563eb;
    --cyan:#22d3ee;
    --sky:#60a5fa;
    --violet:#4f46e5;
    --lime:#b9fd50;

    --border:rgba(148,163,184,.16);
    --r:26px;

    --chatA:rgba(10,28,78,.78);
    --chatB:rgba(6,14,40,.94);

    --shadowDeep: 0 34px 110px rgba(0,0,0,0.46);
    --shadowSoft: 0 22px 70px rgba(0,0,0,0.42);
    --shadowBtn: 0 18px 44px rgba(0,0,0,.38);
    --shadowBtn2: 0 28px 80px rgba(37,99,235,.18);
  }

  *{box-sizing:border-box}
  html,body{height:100%}
  body{
    margin:0;
    font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
    color:var(--text);
    overflow-x:hidden;
    overflow-y:auto;

    background:
      radial-gradient(1100px 680px at 22% 24%, rgba(37,99,235,.16), transparent 60%),
      radial-gradient(1100px 700px at 80% 26%, rgba(34,211,238,.10), transparent 62%),
      radial-gradient(900px 700px at 60% 88%, rgba(79,70,229,.09), transparent 60%),
      linear-gradient(180deg, var(--bgTop), var(--bgMid) 52%, var(--bgBot));
  }

  /* Subtle vignette */
  .vignette{
    position:fixed; inset:-30%;
    background:
      radial-gradient(circle at 50% -10%, rgba(0,0,0,.55), transparent 48%),
      radial-gradient(circle at 50% 110%, rgba(0,0,0,.55), transparent 48%);
    pointer-events:none;
    z-index:0;
  }

  /* Soft grid (static) */
  .grid{
    position:fixed; inset:0;
    pointer-events:none;
    z-index:0;
    opacity:.35;
    background:
      linear-gradient(to right, rgba(148,163,184,.055) 1px, transparent 1px),
      linear-gradient(to bottom, rgba(148,163,184,.055) 1px, transparent 1px);
    background-size: 60px 60px;
    mask-image: radial-gradient(circle at 42% 38%, rgba(0,0,0,1), rgba(0,0,0,.22) 58%, rgba(0,0,0,0) 78%);
  }

  /* ====== STAGE ====== */
  .stage{
    position:relative;
    z-index:1;
    min-height:100vh;
    display:grid;
    place-items:center;
    padding: clamp(18px, 3vw, 56px);
  }

  /* ===== Busineger Growth Chat chip (DESKTOP top-left) ===== */
  .floating-chip{
    position:absolute;
    left: clamp(16px, 4vw, 56px);
    top: clamp(16px, 4vh, 64px);
    display:inline-flex;
    align-items:center;
    gap:10px;
    padding: 10px 14px;
    border-radius: 999px;
    border: 1px solid rgba(148,163,184,.18);
    background: rgba(6,14,40,.35);
    box-shadow: var(--shadowSoft);
    backdrop-filter: blur(14px);
    letter-spacing: .10em;
    text-transform: uppercase;
    font-weight: 800;
    font-size: 12px;
    color: rgba(249,250,251,.92);
    white-space:nowrap;
  }
  .floating-chip .dot{
    width:26px;height:26px;border-radius:999px;
    display:grid;place-items:center;
    background:
      radial-gradient(circle at 30% 25%, rgba(255,255,255,.16), transparent 55%),
      linear-gradient(135deg, rgba(34,211,238,.92), rgba(37,99,235,.82));
    box-shadow: 0 18px 44px rgba(0,0,0,.32);
  }
  .floating-chip .dot i{ font-size: 12px; color:#04142a; }

  /* ===== Login Card ===== */
  .loginCard{
    width: min(520px, 94vw);
    border-radius: var(--r);
    border: 1px solid rgba(148,163,184,.18);
    background:
      radial-gradient(circle at 0% 0%, rgba(255,255,255,0.08), transparent 55%),
      radial-gradient(circle at 100% 0%, rgba(34,211,238,0.10), transparent 58%),
      linear-gradient(180deg, rgba(9,20,56,.68), rgba(6,14,40,.90));
    box-shadow: var(--shadowDeep);
    position:relative;
    overflow:hidden;
    transform:none !important;
    transition:none !important;
  }

  .loginCard::before{
    content:"";
    position:absolute; inset:-1px;
    border-radius: var(--r);
    background:
      radial-gradient(720px 240px at 20% 18%, rgba(34,211,238,.14), transparent 62%),
      radial-gradient(760px 260px at 70% 22%, rgba(37,99,235,.12), transparent 64%),
      radial-gradient(780px 440px at 50% 120%, rgba(79,70,229,.14), transparent 62%);
    mix-blend-mode: screen;
    opacity:.9;
    pointer-events:none;
  }

  .loginCard::after{
    content:"";
    position:absolute;
    inset: 0;
    background: radial-gradient(800px 240px at 50% 0%, rgba(255,255,255,.10), transparent 58%);
    opacity:.5;
    pointer-events:none;
  }

  .inner{
    position:relative;
    z-index:1;
    padding: clamp(18px, 2.2vw, 26px);
  }

  .topRow{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    margin-bottom: 12px;
    flex-wrap:wrap;
  }

  .brand{
    display:flex;
    align-items:center;
    gap:12px;
    min-width: 0;
  }

  .bIcon{
    width:44px;height:44px;border-radius:14px;
    display:grid;place-items:center;
    border:1px solid rgba(148,163,184,.16);
    background:
      radial-gradient(circle at 30% 25%, rgba(255,255,255,.14), transparent 55%),
      linear-gradient(135deg, rgba(34,211,238,.90), rgba(37,99,235,.78));
    box-shadow: 0 22px 70px rgba(0,0,0,.36);
    color:#04142a;
    font-weight: 900;
    letter-spacing: .08em;
    flex:0 0 auto;
  }

  .brandText{ min-width:0; }
  .brandText strong{
    display:block;
    font-size: 16px;
    letter-spacing:.2px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
  }
  .brandText span{
    display:block;
    font-size: 12px;
    color: var(--muted);
    margin-top: 2px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
  }

  .miniPills{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    justify-content:flex-end;
  }
  .pill{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding: 10px 12px;
    border-radius: 999px;
    border:1px solid rgba(148,163,184,.16);
    background: rgba(255,255,255,.06);
    color: rgba(249,250,251,.92);
    box-shadow: 0 18px 52px rgba(0,0,0,.28);
    backdrop-filter: blur(12px);
    font-weight: 800;
    font-size: 12px;
    white-space:nowrap;
  }
  .pill i{ color: rgba(34,211,238,.95); }

  .heroTitle{
    margin: 10px 0 6px;
    font-size: clamp(22px, 3.2vw, 28px);
    line-height: 1.12;
    letter-spacing: -0.6px;
  }
  .heroTitle .grad1{
    background: linear-gradient(90deg, rgba(249,250,251,1), rgba(96,165,250,1));
    -webkit-background-clip:text;
    background-clip:text;
    color: transparent;
  }
  .heroTitle .grad2{
    background: linear-gradient(90deg, rgba(34,211,238,1), rgba(185,253,80,1));
    -webkit-background-clip:text;
    background-clip:text;
    color: transparent;
  }

  .desc{
    margin:0 0 14px;
    color: rgba(226,232,240,.72);
    font-size: 13.5px;
    line-height: 1.55;
    max-width: 52ch;
  }

  .googleWrap{
    display:flex;
    justify-content:center;
    padding: 8px 0 14px;
  }

  form#registration-form{
    display:grid;
    gap: 12px;
  }

  .field{ position:relative; }
  .field input{
    width:100%;
    padding: 14px 14px 14px 46px;
    border-radius: 18px;
    border: 1px solid rgba(148,163,184,.18);
    background: rgba(255,255,255,.06);
    color: var(--text);
    outline:none;
    font-size: 15px;
    box-shadow: 0 18px 64px rgba(0,0,0,.22);
    transition: border-color .18s ease, box-shadow .18s ease;
  }
  .field input::placeholder{ color: rgba(226,232,240,.55); }
  .field input:focus{
    border-color: rgba(34,211,238,.45);
    box-shadow: 0 26px 86px rgba(37,99,235,.18);
  }

  .leftIcon{
    position:absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(226,232,240,.72);
    font-size: 16px;
    pointer-events:none;
  }

  #toggle-password{
    position:absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor:pointer;
    color: rgba(226,232,240,.78);
    padding: 10px;
    border-radius: 14px;
    border: 1px solid rgba(148,163,184,.14);
    background: rgba(255,255,255,.05);
    transition: border-color .18s ease, background .18s ease, color .18s ease;
    user-select:none;
  }
  #toggle-password:hover{
    border-color: rgba(185,253,80,.30);
    background: rgba(185,253,80,.07);
    color: var(--lime);
  }

  .actions{
    display:grid;
    gap: 10px;
    margin-top: 4px;
  }

  .btnPrimary{
    width:100%;
    border:none;
    border-radius: 18px;
    padding: 14px 14px;
    font-weight: 900;
    letter-spacing:.2px;
    cursor:pointer;
    color: #04142a;
    background:
      radial-gradient(circle at 30% 20%, rgba(255,255,255,.50), transparent 58%),
      linear-gradient(135deg, rgba(34,211,238,1), rgba(37,99,235,.88));
    box-shadow: var(--shadowBtn), var(--shadowBtn2);
    transition: filter .18s ease;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:10px;
  }
  .btnPrimary:hover{ filter: brightness(1.03); }
  .btnPrimary:active{ filter: brightness(.98); }

  .btnGhost{
    width:100%;
    text-decoration:none;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    border-radius: 18px;
    padding: 12px 14px;
    border: 1px solid rgba(148,163,184,.16);
    color: rgba(249,250,251,.92);
    background: rgba(255,255,255,.05);
    box-shadow: 0 18px 60px rgba(0,0,0,.20);
    transition: border-color .18s ease, background .18s ease;
  }
  .btnGhost:hover{
    border-color: rgba(34,211,238,.32);
    background: rgba(34,211,238,.07);
  }

  .smallText{
    margin: 14px 0 0;
    text-align:center;
    color: rgba(226,232,240,.70);
    font-size: 13px;
  }
  .smallText a{
    color: rgba(185,253,80,1);
    font-weight: 900;
    text-decoration:none;
  }
  .smallText a:hover{ text-decoration:underline; }

  .errorMsg{
    margin-top: 8px;
    padding: 10px 12px;
    border-radius: 16px;
    border: 1px solid rgba(148,163,184,.18);
    background: rgba(37,99,235,.10);
    color: rgba(249,250,251,.95);
    font-size: 13.5px;
    text-align:center;
  }

  /* Profile (kept functional) */
  #profile{ display:none; position:relative; margin-top:10px; }
  .profile-icon{
    width:46px;height:46px;border-radius:50%;
    cursor:pointer;
    background: rgba(255,255,255,.08);
    border:1px solid rgba(148,163,184,.22);
    display:flex; align-items:center; justify-content:center;
    color: rgba(249,250,251,.9);
    box-shadow: 0 16px 46px rgba(0,0,0,.28);
    transition: border-color .18s ease, color .18s ease;
    margin: 0 auto;
  }
  .profile-icon:hover{
    border-color: rgba(185,253,80,.35);
    color: var(--lime);
  }
  .profile-box{
    display:none;
    position:absolute;
    left:50%; transform: translateX(-50%);
    top:58px;
    width:min(320px, 92vw);
    padding:16px;
    border-radius:20px;
    border:1px solid rgba(148,163,184,.22);
    background:
      radial-gradient(circle at 10% 0%, rgba(34,211,238,.10), transparent 55%),
      radial-gradient(circle at 90% 0%, rgba(185,253,80,.08), transparent 55%),
      linear-gradient(180deg, rgba(10,28,78,.56), rgba(6,14,40,.90));
    box-shadow: 0 26px 90px rgba(0,0,0,.55);
    backdrop-filter: blur(14px);
    z-index:50;
  }
  .profile-large{
    width:64px;height:64px;border-radius:50%;
    margin:0 auto 10px auto;
    background: rgba(255,255,255,.08);
    border:1px solid rgba(148,163,184,.22);
    display:flex; align-items:center; justify-content:center;
    font-size:26px;
    color: rgba(249,250,251,.9);
  }
  .profile-box h3{ margin:0; font-size:16px; text-align:center; }
  .profile-box p{ margin:4px 0 0; font-size:13px; color: rgba(226,232,240,.70); text-align:center; word-break:break-word; }
  .logout-btn{
    width:100%;
    margin-top:12px;
    padding: 10px 12px;
    border-radius: 16px;
    border:1px solid rgba(148,163,184,.22);
    background: rgba(255,255,255,.06);
    color: rgba(249,250,251,.92);
    cursor:pointer;
    font-weight: 900;
    transition: border-color .18s ease, background .18s ease;
  }
  .logout-btn:hover{
    background: rgba(34,211,238,.10);
    border-color: rgba(34,211,238,.35);
  }
  .profile-container.show .profile-box{ display:block; }

  


  /* ======================================================
   TOP-STACK MODE — Mobile, Tablets, Small Laptops
   0px → 1095px (iPhone • Android • iPad • Small Laptops)
   ====================================================== */
@media (max-width:  1207px){

  .stage{
    display:flex !important;
    flex-direction:column;
    align-items:center;
    justify-content:flex-start;
    padding-top:28px;
    min-height:auto;
  }

  /* chip becomes full-width pill bar */
  .floating-chip{
    position:static !important;
    width:100%;
    max-width:94vw;
    margin: 0 0 16px;
    justify-content:center;
    text-align:center;
    border-radius:999px;
  }

  /* card follows under it */
  .loginCard{
    margin:0;
  }
}

/* 560px and down: pills stack nicely */
@media (max-width: 560px){
  .topRow{ flex-direction:column; align-items:flex-start; }
  .miniPills{ justify-content:flex-start; }
  .pill{ padding: 9px 10px; font-size: 11.5px; }
}

/* Extra tiny phones */
@media (max-width: 380px){
  .inner{ padding: 16px; }
  .bIcon{ width:40px;height:40px;border-radius:12px; }
  .heroTitle{ font-size: 22px; }
  .field input{ padding: 13px 12px 13px 44px; border-radius: 16px; }
  .btnPrimary,.btnGhost{ border-radius: 16px; }

  .floating-chip{
    padding: 9px 12px;
    font-size: 11px;
    letter-spacing: .08em;
  }
  .floating-chip .dot{
    width:24px;height:24px;
  }
}

</style>


</head>

<body>
  <div class="grid"></div>
  <div class="vignette"></div>

  <div class="stage">
    <div class="floating-chip">
      <span class="dot"><i class="fa-solid fa-bolt"></i></span>
      <span>BUSINEGER GROWTH CHAT</span>
    </div>

    <section class="loginCard" id="loginCard">
      <div class="inner">
        <div class="topRow">
          <div class="brand">
            <div class="bIcon">B</div>
            <div class="brandText">
              <strong>Busineger test</strong>
              <span>Unlock prompts. Start growing.</span>
            </div>
          </div>

          <div class="miniPills">
            <span class="pill"><i class="fa-solid fa-layer-group"></i> See Prompts</span>
            <span class="pill"><i class="fa-solid fa-shield-halved"></i> Secure Login</span>
          </div>
        </div>

        <h2 class="heroTitle">
          <span class="grad1">Business Growth Enabled</span><br>
          <span class="grad2">Login Access</span>
        </h2>

        <p class="desc">
          Login to unlock prompt packs, saved chats, and premium growth workflows — clean, fast, and secure.
        </p>

        <!-- Google Sign-In (functional, unchanged) -->
        <div id="g_id_onload"
          data-client_id="577134171328-ha3bnmmc4kcoriuilil1dgulmh374msk.apps.googleusercontent.com"
          data-callback="handleCredentialResponse">
        </div>
        <div class="googleWrap">
          <div class="g_id_signin" data-type="standard"></div>
        </div>

        <!-- Profile UI (functional) -->
        <div id="profile" class="profile-container">
          <div id="profileIcon" class="profile-icon" title="Profile">
            <i class="fa-solid fa-user"></i>
          </div>
          <div class="profile-box" id="profileBox">
            <div class="profile-large">
              <i class="fa-solid fa-user"></i>
            </div>
            <h3 id="userName"></h3>
            <p id="userEmail"></p>
            <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
          </div>
        </div>

        <!-- Email/Password Login (functional, unchanged) -->
        <form id="registration-form" method="POST" autocomplete="on">
          <div class="field">
            <i class="fa-solid fa-envelope leftIcon"></i>
            <input type="email" id="email" name="email" placeholder="Enter your email" required />
          </div>

          <div class="field">
            <i class="fa-solid fa-lock leftIcon"></i>
            <input type="password" id="password" name="password" placeholder="Enter your password" required />
            <span id="toggle-password" aria-label="Show/Hide password" title="Show/Hide password">
              <i class="fa-solid fa-eye-slash"></i>
            </span>
          </div>

          <div class="actions">
            <a class="btnGhost"
   href="forgotpassword.php?embed=1&redirect=<?php echo urlencode($_GET['redirect'] ?? 'newchat.php'); ?>">
  <i class="fa-solid fa-key"></i> Forgot Password?
</a>


            <button class="btnPrimary" type="submit">
              <i class="fa-solid fa-right-to-bracket"></i> Login
            </button>
          </div>

          <?php
            if (isset($error_message)) {
              echo "<div class='errorMsg'>{$error_message}</div>";
            }
          ?>
        </form>

        <p class="smallText">
          Don’t have an account?
<a href="mandiresgistration.php?embed=1&redirect=<?php echo urlencode($_GET['redirect'] ?? 'newchat.php'); ?>">
  Register
</a>

        </p>
      </div>
    </section>
  </div>

  <script>
    // password toggle (same functionality)
    const passwordInput = document.getElementById("password");
    const togglePassword = document.getElementById("toggle-password");
    const icon = togglePassword.querySelector("i");

    togglePassword.addEventListener("click", () => {
      if (icon.classList.contains("fa-eye-slash")) {
        passwordInput.type = "text";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
      } else {
        passwordInput.type = "password";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
      }
    });
  </script>

  <script>
  // ✅ Google login callback (fixed for embed + redirect)
  function handleCredentialResponse(response) {
    const data = parseJwt(response.credential);

    const qs = new URLSearchParams(window.location.search);
    const isEmbed  = qs.get("embed") === "1";
    const redirect = (qs.get("redirect") || "newchat.php").trim();

    // ✅ basic safe redirect (block external urls)
    const safeRedirect = (
      redirect &&
      redirect.indexOf("://") === -1 &&
      redirect.indexOf("\n") === -1 &&
      redirect.indexOf("\r") === -1 &&
      redirect[0] !== "/"
    ) ? redirect : "newchat.php";

    fetch("save_google_login.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        email: data.email,
        name: data.name,
        from_cart: qs.get("from_cart")
      })
    })
    .then(res => res.json())
    .then(res => {
      if (!res.success) {
        alert("Login failed. Please try again.");
        return;
      }

      // ✅ If user came from cart
      if (res.from_cart === true) {
        if (isEmbed) {
          try { window.parent.postMessage({ type: "LOGIN_SUCCESS" }, window.location.origin); } catch(e){}
          window.top.location.href = "/mandipayment.php";
        } else {
          window.location.href = "mandipayment.php";
        }
        return;
      }

      // ✅ Normal flow
      if (isEmbed) {
        // notify newchat.php to close modal & reload (your newchat already listens)
        try { window.parent.postMessage({ type: "LOGIN_SUCCESS" }, window.location.origin); } catch(e){}
        // also force top redirect to the correct page
        window.top.location.href = "/" + safeRedirect;
      } else {
        window.location.href = safeRedirect;
      }
    })
    .catch(err => {
      console.error("Error:", err);
      alert("Something went wrong. Please try again.");
    });
  }

  function parseJwt(token) {
    let base64Url = token.split('.')[1];
    let base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
    let jsonPayload = decodeURIComponent(window.atob(base64).split('').map(function(c) {
      return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
    }).join(''));
    return JSON.parse(jsonPayload);
  }
</script>


  <script>
    // profile dropdown UI (no backend change)
    const profileIcon = document.getElementById("profileIcon");
    const profile = document.getElementById("profile");
    if (profileIcon && profile) {
      profileIcon.addEventListener("click", () => profile.classList.toggle("show"));
      document.addEventListener("click", (e) => {
        if (!profile.contains(e.target)) profile.classList.remove("show");
      });
    }
  </script>
</body>
</html>
