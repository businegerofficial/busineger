<?php
require_once 'vendor/autoload.php';
require_once './backend/db.php'; // Include your database connection file
require_once "./backend/mail.php";

session_start(); // ✅ needed because you use $_SESSION below
$embed = (isset($_GET['embed']) && $_GET['embed'] == '1') ? '1' : '';
$redirect = $_GET['redirect'] ?? 'newchat.php';
$redirect = preg_replace('/[^a-zA-Z0-9_\-\/\.\?\=\&]/', '', $redirect);
if ($redirect === '') $redirect = 'newchat.php';


function generateOTP() {
  return rand(100000, 999999);
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $email = $_POST['email'] ?? '';

  // Check if the email exists in the database
  $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
  $stmt->bindParam(':email', $email);
  $stmt->execute();
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($user) {
    $otp = generateOTP();

    // Save OTP to the session (same functionality)
    $_SESSION['otp'] = $otp;
    $_SESSION['email'] = $email;

    // Send email
    $subject = "Your OTP Code";
    $message = "Your OTP code is: " . $otp;
    $name = $user['username'];

    if (sendEmail($email, $name, $subject, $message)) {
      $qs = [];
if ($embed === '1') $qs[] = "embed=1";
$qs[] = "redirect=" . urlencode($redirect);

header("Location: otpconfirm.php?" . implode("&", $qs));
exit();

    } else {
      $error_message = "OTP email failed. Please try again.";
    }

  } else {
    $error_message = "Email not found. Please enter a registered email.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>AIPromptMandi - Forgot Password</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

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

    .vignette{
      position:fixed; inset:-30%;
      background:
        radial-gradient(circle at 50% -10%, rgba(0,0,0,.55), transparent 48%),
        radial-gradient(circle at 50% 110%, rgba(0,0,0,.55), transparent 48%);
      pointer-events:none;
      z-index:0;
    }

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

    .stage{
      position:relative;
      z-index:1;
      min-height:100vh;
      display:grid;
      place-items:center;
      padding: clamp(18px, 3vw, 56px);
    }

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
      transform: none !important;
      transition: none !important;
    }


    .logoWrap{
  padding:6px;
}

.logoWrap img{
  width:100%;
  height:100%;
  object-fit:contain;
  border-radius:10px;
  filter: drop-shadow(0 6px 18px rgba(0,0,0,.35));
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

    form#registration-form{ display:grid; gap: 12px; }

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

    .actions{ display:grid; gap: 10px; margin-top: 4px; }

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
      margin-top: 6px;
      padding: 10px 12px;
      border-radius: 16px;
      border: 1px solid rgba(148,163,184,.18);
      background: rgba(37,99,235,.10);
      color: rgba(249,250,251,.95);
      font-size: 13.5px;
      text-align:center;
    }

    @media (max-width: 560px){
      .floating-chip{ display:none; }
      .topRow{ flex-direction:column; align-items:flex-start; }
      .miniPills{ justify-content:flex-start; }
      .pill{ padding: 9px 10px; font-size: 11.5px; }
    }
    @media (max-width: 380px){
      .inner{ padding: 16px; }
      .bIcon{ width:40px;height:40px;border-radius:12px; }
      .heroTitle{ font-size: 22px; }
      .field input{ padding: 13px 12px 13px 44px; border-radius: 16px; }
      .btnPrimary,.btnGhost{ border-radius: 16px; }
    }



















    /* ======================================================
   TOP-STACK MODE — Mobile, Tablets, Small Laptops
   0px → 1095px (iPhone • Android • iPad • Small Laptops)
   ====================================================== */
@media (max-width: 1095px){

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

@media (max-width: 560px){
  /* ❌ remove this line:
  .floating-chip{ display:none; }
  */

  .topRow{ flex-direction:column; align-items:flex-start; }
  .miniPills{ justify-content:flex-start; }
  .pill{ padding: 9px 10px; font-size: 11.5px; }

  /* ✅ make chip visible + proper on small screens */
  .floating-chip{
    display:flex !important;
    position:static !important;
    width:100%;
    max-width:94vw;
    margin: 0 0 14px;
    justify-content:center;
    text-align:center;
    border-radius:999px;
    padding: 10px 12px;
    font-size: 11.5px;
    letter-spacing: .08em;
  }
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
        <span class="dot"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
      <span>BUSINEGER GROWTH CHAT</span>
    </div>

    <section class="loginCard">
      <div class="inner">
        <div class="topRow">
          <div class="brand">
      <div class="bIcon logoWrap">
  <img src="assets/img/logo-icon.png" alt="">
</div>

            <div class="brandText">
              <strong>Busineger</strong>
              <span>Reset password via OTP</span>
            </div>
          </div>

          <div class="miniPills">
            <span class="pill"><i class="fa-solid fa-envelope"></i> Email OTP</span>
            <span class="pill"><i class="fa-solid fa-lock"></i> Protected</span>
          </div>
        </div>

        <h2 class="heroTitle">
          <span class="grad1">Forgot your</span><br>
          <span class="grad2">Password?</span>
        </h2>

        <p class="desc">
          Enter your registered email. We’ll send a secure OTP to verify your account and reset your password.
        </p>

        <!-- ✅ OTP Request form (same functionality) -->
        <form id="registration-form" method="POST" autocomplete="on">
          <div class="field">
            <i class="fa-solid fa-envelope leftIcon"></i>
            <input type="email" id="email" name="email" placeholder="Enter your registered email" required />
          </div>

          <div class="actions">
            <button class="btnPrimary" type="submit">
              <i class="fa-solid fa-paper-plane"></i> Send OTP
            </button>

            <a class="btnGhost"
   href="mandilogin.php?embed=1&redirect=<?php echo urlencode($redirect); ?>">
  <i class="fa-solid fa-arrow-left"></i> Back to Login
</a>

          </div>

          <?php if (!empty($error_message)): ?>
            <div class="errorMsg"><?php echo htmlspecialchars($error_message); ?></div>
          <?php endif; ?>
        </form>

        <p class="smallText">
          Need help? Contact support from the main site page.
        </p>
      </div>
    </section>
  </div>
</body>
</html>
