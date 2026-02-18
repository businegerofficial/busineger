<?php
session_start();

/*
  Allow access ONLY if user is logged in
  OR is admin (aap)
*/

$isLoggedIn = isset($_SESSION['user']);
$isAdmin    = $isLoggedIn && (($_SESSION['user']['role'] ?? '') === 'admin');

if (!$isLoggedIn && !$isAdmin) {
    header("Location: /coming-soon.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Busineger — Premium Blue Hero + Chat</title>

  <!-- Fonts & Icons -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>

  <style>










/* =========================================================
   ✅ ONE GLOBAL :root + ONE GLOBAL body (NO extra body classes)
   ✅ All sections keep their own scoped variables (#featured, #spiComparison)
   ✅ Same look, but more “fluid” (clamp/relative sizing) for all devices
   ✅ FIXES: Mobile center, no right empty space, burger works with .apm-nav-right,
            no horizontal overflow, no partition backgrounds
========================================================= */

/* =============== GLOBAL TOKENS =============== */
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

  /* ✅ responsive sizing tokens */
  --navH: clamp(64px, 6.2vw, 78px);
  --r: clamp(18px, 2.2vw, 26px);

  --chatA:rgba(10,28,78,.78);
  --chatB:rgba(6,14,40,.94);

  --shadowDeep: 0 34px 110px rgba(0,0,0,0.46);
  --shadowBtn: 0 18px 44px rgba(0,0,0,.38);
  --shadowBtn2: 0 28px 80px rgba(37,99,235,.18);

  /* ✅ global layout helpers */
  --pagePad: clamp(12px, 3.6vw, 64px);
  --maxW: 1320px;

  /* ✅ fluid spacing helpers */
  --s1: clamp(8px, 1.0vw, 12px);
  --s2: clamp(10px, 1.2vw, 14px);
  --s3: clamp(12px, 1.4vw, 16px);
  --s4: clamp(14px, 1.8vw, 18px);
  --s5: clamp(16px, 2.2vw, 22px);
  --s6: clamp(18px, 2.6vw, 26px);
  --s7: clamp(22px, 3.2vw, 34px);
  --s8: clamp(26px, 4.2vw, 48px);

  --ease: cubic-bezier(.2,.8,.2,1);

  --fontSans: "Poppins","Inter",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;
}

/* =============== GLOBAL RESET =============== */
*{ box-sizing:border-box; }
*::before,*::after{ box-sizing:border-box; }
html,body{ height:100%; }
html{
  -webkit-text-size-adjust: 100%;
  overflow-x: clip; /* modern */
}
body{ overflow-x: clip; } /* modern */
@supports not (overflow: clip){
  html,body{ overflow-x:hidden; }
}
a{ color:inherit; text-decoration:none; }
button{ font-family:inherit; }
img, svg, video, canvas{ max-width:100%; height:auto; }

/* =============== GLOBAL BODY (ONLY ONCE) =============== */
body{
  margin:0;
  font-family: var(--fontSans);
  color:var(--text);

background: #071a3a;

  background-attachment: fixed;
}

/* subtle global grid + vignette */
body::before{
  content:"";
  position:fixed;
  inset:0;
  pointer-events:none;
  background:
    linear-gradient(to right, rgba(96,165,250,0.05) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(96,165,250,0.05) 1px, transparent 1px);
  background-size: clamp(72px, 7vw, 96px) clamp(72px, 7vw, 96px);
  opacity:.22;
  mask-image: radial-gradient(circle at 30% 18%, rgba(0,0,0,1) 0, rgba(0,0,0,.55) 45%, rgba(0,0,0,0) 78%);
  z-index:-2;
}
body::after{
  content:"";
  position:fixed;
  inset:0;
  pointer-events:none;
  background: radial-gradient(circle at center, transparent 60%, rgba(0,0,0,.28) 100%);
  z-index:-1;
}

/* =========================================================
   NAVBAR
========================================================= */
.apm-nav{
  position:fixed;
  top:0; left:0; right:0;
  height:var(--navH);
  z-index:999;
  display:flex;
  align-items:center;
  justify-content:center;

  /* ✅ safe area padding for iPhone */
  padding-left: calc(var(--pagePad) + env(safe-area-inset-left));
  padding-right: calc(var(--pagePad) + env(safe-area-inset-right));

  background: linear-gradient(180deg, rgba(6,18,43,.84), rgba(6,18,43,.30));
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  border-bottom: 1px solid rgba(148,163,184,.12);
}
.apm-nav-inner{
  width:100%;
  max-width: var(--maxW);
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap: clamp(10px, 1.2vw, 14px);

  /* ✅ fixes flex overflow */
  min-width:0;
  position:relative; /* ✅ dropdown anchor */
}












/* =========================
   LOGO (mark + text)
========================= */
.apm-logo{
  display:inline-flex;
  align-items:center;

  /* ✅ distance between logo & text = ZERO */
  gap: 0px;

  flex: 1 1 auto;     /* ✅ allow logo block to take space */
  min-width: 180px;   /* ✅ keeps tagline visible on mobile */
  user-select:none;
  text-decoration:none;
}

/* LOGO MARK BOX */
.apm-logo-mark{
  width: clamp(40px, 4.8vw, 48px);
  height: clamp(40px, 4.8vw, 48px);
  border-radius: clamp(14px, 1.6vw, 16px);
  display:flex;
  align-items:center;
  justify-content:center;
  position:relative;

  /* keep rounded mask */
  overflow:hidden;
}

/* LOGO IMAGE */
.apm-logo-mark img{
  width: clamp(24px, 3.2vw, 30px);
  height: clamp(24px, 3.2vw, 30px);
  object-fit:contain;
  display:block;
  filter: drop-shadow(0 10px 18px rgba(0,0,0,.25));
  user-select:none;
  -webkit-user-drag:none;
}

/* TEXT BLOCK */
.apm-logo-text{
  display:flex;
  flex-direction:column;
  justify-content:center;
  min-width:0;

  /* ✅ attach even tighter than gap:0 */
  margin-left: -2px;

  /* ✅ avoid clipping */
  overflow: visible;
}

/* BRAND NAME */
.apm-logo-text strong{
  display:block;
  font-size: clamp(.95rem, .6vw + .8rem, 1.05rem);
  letter-spacing:-.02em;

  /* ✅ prevents 'g' cut */
  line-height: 1.15;
  padding-bottom: 2px;

  margin:0;
  white-space:nowrap;
  overflow:visible;
}

/* TAGLINE */
.apm-logo-text small{
  display:block;
  font-size: clamp(.62rem, .45vw + .52rem, .74rem);
  color: rgba(226,232,240,.70);
  letter-spacing:.10em;
  text-transform: uppercase;

  /* ✅ ALWAYS show on mobile: allow wrap instead of disappearing */
  line-height: 1.15;
  margin-top: 1px;
  max-width: 100%;
  white-space: normal;   /* ✅ wrap */
  overflow: visible;
  text-overflow: clip;
}

/* =========================
   NAV RIGHT (your real items)
========================= */
.apm-nav-right{
  display:flex;
  align-items:center;
  gap: clamp(8px, 1vw, 10px);

  flex: 0 0 auto;     /* ✅ don’t steal logo space */
  min-width: auto;
}

/* =========================
   IMPORTANT NAV PARENT FIX
   (apply this to the parent
   that contains .apm-logo
   + .apm-nav-right)
========================= */
.apm-nav-inner{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap: 10px;

  /* ✅ stop cutting text */
  overflow: visible;
}

/* =========================
   MOBILE TUNING
========================= */
@media (max-width: 520px){
  .apm-logo{
    min-width: 160px; /* a bit smaller for very small phones */
  }

  .apm-logo-text small{
    font-size: .58rem;
    letter-spacing: .06em;
  }
}

@media (max-width: 380px){
  .apm-logo{
    min-width: 140px;
  }

  .apm-logo-text small{
    font-size: .55rem;
    letter-spacing: .05em;
  }
}
/* ===== FORCE TAGLINE TO SHOW (final override) ===== */
.apm-logo-text small{
  display:block !important;
  visibility: visible !important;
  opacity: 1 !important;

  /* allow it to fit */
  white-space: normal !important; /* wrap allowed */
  overflow: visible !important;
  text-overflow: clip !important;

  /* readable on dark bg */
  color: rgba(226,232,240,.82) !important;
  line-height: 1.2 !important;
}

/* ===== PREVENT NAV FROM CUTTING 2ND LINE ===== */
/* Apply to your actual navbar wrapper if different */
.apm-nav,
.apm-nav-inner,
header,
nav{
  overflow: visible !important;
  height: auto !important;     /* important if you set fixed height earlier */
  min-height: 64px;            /* keep nice header height */
  align-items: center;
}

/* ===== GIVE LOGO SPACE ON MOBILE ===== */
@media (max-width: 700px){
  .apm-logo{
    flex: 1 1 auto !important;
    min-width: 220px !important; /* increase if tagline still squeezed */
  }

  .apm-nav-right{
    flex: 0 0 auto !important;
    max-width: 45vw; /* prevents right side from stealing all space */
  }
}

























/* ✅ your real nav items container */
.apm-nav-right{
  display:flex;
  align-items:center;
  gap: clamp(8px, 1vw, 10px);
  flex:0 0 auto;
  min-width:0;
}












/* =========================================================
   Premium 3D Buttons + Stable Login Typing (NO SHIFT)
   Drop-in CSS (keeps your design, fixes "See Prompts" moving)
========================================================= */

/* Optional: ensure menu row is stable */
.apm-menu{
  display:flex;
  align-items:center;
  gap:12px;
  flex-wrap:nowrap;
}

/* =========================================================
   Premium 3D Buttons
========================================================= */
.apm-press{
  transform: translateZ(0);
  transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease, filter .18s ease;
  will-change: transform;
}
.apm-press:hover{
  transform: translateY(-2px) translateZ(0);
  filter: saturate(1.05);
}
.apm-press:active{ transform: translateY(0px) scale(.99); }

/* ✅ Prevent flex sizing from making siblings shift */
.apm-btn3d,
.apm-login{
  flex: 0 0 auto;
}

.apm-btn3d{
  position:relative;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:.60rem;
  padding: clamp(.62rem, .7vw, .72rem) clamp(.92rem, 1.2vw, 1.08rem);
  border-radius:999px;
  border:1px solid rgba(148,163,184,.18);
  background:
    radial-gradient(circle at 30% 15%, rgba(255,255,255,.12), transparent 55%),
    linear-gradient(180deg, rgba(148,163,184,.08), rgba(6,18,43,.70));
  box-shadow:
    var(--shadowBtn),
    inset 0 1px 0 rgba(255,255,255,.10),
    inset 0 -10px 22px rgba(0,0,0,.20);
  overflow:hidden;
  white-space:nowrap;
  user-select:none;
  max-width:100%;
}
.apm-btn3d::before{
  content:"";
  position:absolute;
  inset:-2px;
  background: linear-gradient(120deg, transparent 0%, rgba(255,255,255,.16) 25%, transparent 50%);
  transform: translateX(-60%);
  opacity:0;
  transition: opacity .18s ease;
  pointer-events:none;
}
.apm-btn3d:hover::before{
  opacity:.9;
  animation: apm-sweep 850ms ease-out 1;
}
@keyframes apm-sweep{
  0%{ transform: translateX(-65%); }
  100%{ transform: translateX(65%); }
}

.apm-chip{
  width: clamp(26px, 3vw, 30px);
  height: clamp(26px, 3vw, 30px);
  border-radius:999px;
  display:grid;
  place-items:center;
  background:
    radial-gradient(circle at 30% 20%, rgba(255,255,255,.18), transparent 60%),
    linear-gradient(135deg, rgba(37,99,235,1), rgba(34,211,238,1));
  color:#061024;
  border:1px solid rgba(56,189,248,.18);
  box-shadow: 0 12px 34px rgba(37,99,235,.14);
  flex:0 0 auto;
}
.apm-btn3d .txt{
  font-size: clamp(.86rem, .35vw + .78rem, .90rem);
  font-weight:850;
  letter-spacing:.01em;
  color: rgba(219,234,254,.96);
  text-shadow: 0 10px 26px rgba(0,0,0,.22);
}

.apm-btn3d.accent{
  border-color: rgba(56,189,248,.22);
  background:
    radial-gradient(circle at 30% 15%, rgba(255,255,255,.18), transparent 55%),
    linear-gradient(135deg, rgba(37,99,235,1), rgba(34,211,238,1));
  box-shadow:
    var(--shadowBtn2),
    inset 0 1px 0 rgba(255,255,255,.12),
    inset 0 -12px 24px rgba(0,0,0,.18);
}
.apm-btn3d.accent .txt{ color:#061024; text-shadow:none; }
.apm-btn3d.accent .apm-chip{
  background:
    radial-gradient(circle at 30% 20%, rgba(255,255,255,.22), transparent 60%),
    linear-gradient(135deg, rgba(6,16,36,1), rgba(3,10,25,1));
  color: rgba(219,234,254,.96);
  border: 1px solid rgba(255,255,255,.12);
  box-shadow: 0 12px 34px rgba(0,0,0,.28);
}

/* =========================================================
   LOGIN (Stable width so typing does NOT shift "See Prompts")
========================================================= */
.apm-login{
  position:relative;
  display:inline-flex;
  align-items:center;
  gap:.62rem;
  padding: clamp(.56rem, .7vw, .66rem) clamp(.70rem, .9vw, .92rem);
  border-radius:999px;
  border:1px solid rgba(148,163,184,.18);
  background:
    radial-gradient(circle at 30% 15%, rgba(255,255,255,.12), transparent 55%),
    linear-gradient(180deg, rgba(148,163,184,.08), rgba(6,18,43,.70));
  box-shadow:
    var(--shadowBtn),
    inset 0 1px 0 rgba(255,255,255,.10),
    inset 0 -10px 22px rgba(0,0,0,.20);
  cursor:pointer;
  user-select:none;

  /* ✅ KEY FIX: lock width so typing text won't expand/shrink the button */
  width: clamp(180px, 22vw, 220px);
  max-width: none;

  min-height: clamp(46px, 5vw, 52px);
  justify-content:flex-start;
  overflow:hidden;
}
.apm-login:hover{ border-color: rgba(96,165,250,.28); }

.apm-login-ico{
  width: clamp(30px, 3.6vw, 34px);
  height: clamp(30px, 3.6vw, 34px);
  border-radius: clamp(12px, 1.6vw, 14px);
  display:grid;
  place-items:center;
  background:
    radial-gradient(circle at 30% 20%, rgba(255,255,255,.18), transparent 60%),
    linear-gradient(135deg, rgba(37,99,235,1), rgba(34,211,238,1));
  border:1px solid rgba(56,189,248,.18);
  color:#061024;
  box-shadow: 0 12px 34px rgba(37,99,235,.14);
  flex:0 0 auto;
}

.apm-login-text{
  display:flex;
  font-weight:bold;
  flex-direction:column;
  justify-content:center;
  line-height:1.15;

  /* ✅ critical for ellipsis to work inside fixed-width flex item */
  min-width:0;
  width: 100%;
  padding-top: 2px;
}

.apm-login-text strong{
  font-size: clamp(.82rem, .35vw + .78rem, .88rem);
  font-weight:850;
  color: rgba(219,234,254,.96);
  letter-spacing:-.01em;

  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}
.apm-login-text small{
  font-size: clamp(.66rem, .3vw + .62rem, .72rem);
  color: rgba(219,234,254,.74);

  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
  margin-top:2px;
}

/* caret */
.apm-typing::after{
  content:"";
  display:inline-block;
  width:8px;
  height:1.05em;
  margin-left:6px;
  border-right:2px solid rgba(219,234,254,.60);
  animation: apm-caret 900ms steps(1) infinite;
  transform: translateY(2px);
}
@keyframes apm-caret{
  0%,49%{ opacity:1; }
  50%,100%{ opacity:0; }
}

/* =========================================================
   Optional: mobile tightening (if needed)
========================================================= */
@media (max-width: 520px){
  .apm-menu{ gap:10px; }
  .apm-login{ width: min(210px, 56vw); }
}









































/* =========================================================
   HERO  ✅ FIX: Mobile-first 1 column (prevents right empty gap)
========================================================= */
.apm-hero{
  width:100%;
  min-height:100vh;
  min-height:100svh;

  /* ✅ safe-area */
  padding:
    calc(var(--navH) + clamp(26px, 3vw, 48px))
    calc(clamp(14px, 5vw, 74px) + env(safe-area-inset-right))
    clamp(26px, 3vw, 60px)
    calc(clamp(14px, 5vw, 74px) + env(safe-area-inset-left));

  display:flex;
  align-items:center;
  justify-content:center;
}

.apm-hero-inner{
  width:100%;
  max-width: var(--maxW);
  display:grid;

  /* ✅ MOBILE DEFAULT: 1 column always */
  grid-template-columns: 1fr;
  align-items:start;
  gap: clamp(14px, 2.4vw, 34px);

  min-width:0;
}
.apm-hero-left{ max-width: 760px; min-width:0; }

.apm-eyebrow{
  display:inline-flex;
  align-items:center;
  gap:.55rem;
  padding:.40rem 1.0rem;
  border-radius:999px;
  background: linear-gradient(120deg, rgba(37,99,235,0.18), rgba(34,211,238,0.07));
  border:1px solid rgba(148,163,184,0.18);
  box-shadow: 0 0 0 1px rgba(3,10,25,0.86), 0 14px 40px rgba(0,0,0,.34);
  font-size: clamp(.66rem, .35vw + .55rem, .72rem);
  letter-spacing:.14em;
  text-transform:uppercase;
  color:rgba(248,250,252,.96);
  margin-bottom: clamp(.95rem, 1.2vw, 1.3rem);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
}
.apm-eyebrow .icon{
  width: clamp(18px, 2.2vw, 22px);
  height: clamp(18px, 2.2vw, 22px);
  border-radius:999px;
  display:grid;
  place-items:center;
  background: radial-gradient(circle at 30% 20%, var(--cyan), var(--blue));
  color:#061024;
  box-shadow: 0 0 18px rgba(34,211,238,.34);
}

.apm-title{
  font-size: clamp(2.0rem, 2.65vw + 1rem, 3.5rem);
  line-height: 1.02;
  font-weight: 800;
  letter-spacing: -0.045em;
  margin-bottom: .9rem;
  text-shadow: 0 22px 62px rgba(0,0,0,.34);
}
.apm-title .gradient{
  background: linear-gradient(120deg,
    #B4D24F 0%,
    #A9CE57 12%,
    #95C460 26%,
    #74B074 40%,
    #5CA986 52%,
    #3E9A9A 64%,
    #2E93A5 74%,
    #1B8AB2 88%,
    #0F79AA 100%
  );
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;

  /* ✅ makes gradient look richer (optional) */
  filter: saturate(1.08) contrast(1.02);
}

.apm-subhead{
  font-size: clamp(1rem, .62vw + .90rem, 1.20rem);
  color: rgba(229,231,235,0.95);
  margin-bottom: .95rem;
  max-width: 46rem;
}
.apm-kicker{
  font-size: clamp(.92rem, .35vw + .82rem, 1.02rem);
  color: rgba(219,234,254,.70);
  margin-bottom: 1.45rem;
}
.apm-body{
  font-size: clamp(.92rem, .35vw + .82rem, 1.02rem);
  line-height: 1.65;
  color: rgba(219,234,254,.72);
  max-width: 46rem;
  margin-bottom: 1.85rem;
}
.apm-cta-row{ display:flex; flex-wrap:wrap; align-items:center; gap:.9rem; }
.apm-cta{ padding: clamp(.82rem, .9vw, .92rem) clamp(1.12rem, 1.4vw, 1.45rem); }

/* =========================================================
   CHAT ✅ FIX: Mobile = normal flow + centered (no absolute)
========================================================= */
.apm-hero-right{
  display:flex;
  justify-content:center;  /* ✅ center on mobile */
  position: relative;
  min-height: auto;        /* ✅ stops blank gap on right */
  align-self: start;
  width:100%;
  min-width:0;
}
.apm-hero-right .apm-chat-card{
  position: relative;      /* ✅ not absolute on mobile */
  top:auto; right:auto;
  width:min(100%, 560px);
  will-change: auto;
  transform: none;         /* ✅ prevents off-screen rotation */
  transform-origin: center;
  animation: none;
}

/* Desktop only: 2 columns + sticky left + 3D card */
@media (min-width: 1025px){
  .apm-hero-inner{
    grid-template-columns: 1fr minmax(280px, 560px);
  }
  .apm-hero-left{
    position: sticky;
    top: calc(var(--navH) + 22px);
    align-self: start;
  }
  .apm-hero-inner{
    min-height: calc(100svh - var(--navH) - 90px);
  }
  .apm-hero-right{
    justify-content:flex-end;
    min-height: clamp(440px, 54vw, 560px);
  }
  .apm-hero-right .apm-chat-card{
    position: absolute;
    top: 0;
    right: 0;
    width:min(100%, 560px);
    will-change: transform;
    transform: perspective(1200px) rotateY(-6deg) rotateX(3deg) rotateZ(1.4deg) translate3d(0,0,0);
    transform-origin: left center;
    animation: apm-float 3.6s ease-in-out infinite;
  }
}

@keyframes apm-float{
  0%,100%{
    transform: perspective(1200px) rotateY(-6deg) rotateX(3deg) rotateZ(1.4deg) translate3d(0,0,0);
  }
  50%{
    transform: perspective(1200px) rotateY(-6deg) rotateX(3deg) rotateZ(1.4deg) translate3d(0,clamp(10px, 1.4vw, 14px),0);
  }
}

/* chat card */
@property --spin { syntax: "<angle>"; inherits: false; initial-value: 0deg; }

.apm-chat-card{
  border-radius: var(--r);
  padding: clamp(18px, 2vw, 24px) clamp(14px, 1.6vw, 22px);
  position:relative;
  overflow:visible;
  background:
    radial-gradient(circle at 0% 0%, rgba(255,255,255,0.06), transparent 55%),
    radial-gradient(circle at 100% 0%, rgba(34,211,238,0.16), transparent 58%),
    linear-gradient(180deg, #020617, #031a2f);
  border:1px solid rgba(96,165,250,.35);
  box-shadow:
    var(--shadowDeep),
    0 0 0 1px rgba(34,211,238,.28),
    0 0 42px rgba(34,211,238,.28),
    0 0 90px rgba(96,165,250,.22),
    18px 22px 70px rgba(0,0,0,.45),
    inset 0 1px 0 rgba(255,255,255,.14);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
}
.apm-chat-card::after{
  content:"";
  position:absolute;
  inset:-3px;
  border-radius:inherit;
  padding:2px;
  background: conic-gradient(
    from var(--spin),
    rgba(34,211,238,.85),
    rgba(96,165,250,.7),
    rgba(79,70,229,.6),
    rgba(185,253,80,.55),
    rgba(34,211,238,.85)
  );
  -webkit-mask:
    linear-gradient(#000 0 0) content-box,
    linear-gradient(#000 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  pointer-events:none;
  filter: blur(0.4px);
  opacity:.95;
  animation: borderSpin 6s linear infinite;
}
.apm-chat-card::before{
  content:"";
  position:absolute;
  inset:-25%;
  background:
    radial-gradient(circle at 20% 30%, rgba(255,255,255,.14), transparent 40%),
    radial-gradient(circle at 80% 70%, rgba(34,211,238,.18), transparent 42%);
  filter: blur(42px);
  opacity:.55;
  pointer-events:none;
  animation: glassPulse 4.5s ease-in-out infinite alternate;
}
@keyframes borderSpin{ to{ --spin: 360deg; } }
@keyframes glassPulse{
  0%{ transform: translate(-12%, -6%) scale(1); opacity:.35; }
  100%{ transform: translate(12%, 6%) scale(1.08); opacity:.70; }
}

/* floating pills */
.apm-floating-pill{
  position:absolute;
  display:inline-flex;
  align-items:center;
  gap:.55rem;
  padding:.42rem .95rem;
  border-radius:999px;
  border:1px solid rgba(96,165,250,.28);
  background:
    radial-gradient(circle at 30% 20%, rgba(255,255,255,.12), transparent 58%),
    linear-gradient(180deg, rgba(6,18,43,.78), rgba(3,10,25,.92));
  color: rgba(219,234,254,.92);
  font-weight: 750;
  font-size: clamp(.64rem, .35vw + .55rem, .72rem);
  letter-spacing: .12em;
  text-transform: uppercase;
  box-shadow: 0 18px 44px rgba(0,0,0,.34);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  user-select:none;
  white-space:nowrap;
  z-index:5;
}
.apm-floating-pill .dot{
  width:8px; height:8px;
  border-radius:999px;
  background: radial-gradient(circle, var(--cyan), var(--blue));
  box-shadow: 0 0 14px rgba(34,211,238,.45);
  flex:0 0 auto;
}
.apm-pill-top{ top:clamp(-18px,-1.4vw,-14px); right:clamp(10px, 2vw, 18px); }
.apm-pill-bottom{ bottom:clamp(-18px,-1.4vw,-14px); left:clamp(10px, 2vw, 18px); letter-spacing:.10em; }

.apm-chat-header{
  display:flex;
  align-items:center;
  justify-content:space-between;
  margin-bottom: .95rem;
  gap: 1rem;
}
.apm-chat-badge{
  display:inline-flex;
  align-items:center;
  gap:.55rem;
  padding:.46rem .98rem;
  border-radius:999px;
  background: linear-gradient(120deg, rgba(37,99,235,0.92), rgba(34,211,238,0.84));
  font-size: clamp(.66rem, .35vw + .58rem, .72rem);
  font-weight: 850;
  color:#061024;
  box-shadow: 0 0 22px rgba(37,99,235,.16);
  letter-spacing:.02em;
  white-space: nowrap;
}
.apm-chat-status{
  display:flex;
  align-items:center;
  gap:.45rem;
  font-size: clamp(.62rem, .3vw + .56rem, .70rem);
  color: rgba(219,234,254,.62);
  white-space: nowrap;
}
.apm-chat-status-dot{
  width:7px; height:7px;
  border-radius:50%;
  background: radial-gradient(circle, var(--cyan), var(--blue));
  box-shadow: 0 0 12px rgba(34,211,238,0.44);
  animation: apm-live 1.6s ease-in-out infinite;
}
@keyframes apm-live{
  0%,100%{ opacity:.65; transform:scale(1); }
  50%{ opacity:1; transform:scale(1.18); }
}

.apm-chat-body{
  margin-top: .35rem;
  padding-top: .9rem;
  border-top: 1px solid rgba(148,163,184,0.14);
  max-height: clamp(380px, 52vh, 470px);
  overflow:hidden;
}
.apm-chat-stream{
  display:flex;
  flex-direction:column;
  gap: .95rem;
  padding-top: .25rem;
}
.apm-chat-row{ width:100%; display:flex; align-items:flex-start; gap:.6rem; }
.apm-chat-row.user{ justify-content:flex-end; }
.apm-chat-row.ai{ justify-content:flex-start; }

.apm-avatar{
  width: clamp(34px, 4vw, 40px);
  height: clamp(34px, 4vw, 40px);
  border-radius:999px;
  border:1px solid rgba(148,163,184,0.18);
  overflow:hidden;
  background: radial-gradient(circle at top, rgba(255,255,255,0.06), rgba(6,14,35,1));
  box-shadow: 0 12px 26px rgba(0,0,0,0.38);
  flex-shrink:0;
}
.apm-avatar img{ width:100%; height:100%; object-fit:cover; display:block; border-radius:inherit; }
.apm-avatar.ai{ border-color: rgba(56,189,248,0.22); }

.apm-chat-row.user .apm-bubble{ order:1; }
.apm-chat-row.user .apm-avatar{ order:2; }

.apm-bubble{
  max-width: min(84%, 520px);
  border-radius: 22px;
  padding: .82rem 1.05rem .86rem;
  font-size: clamp(.84rem, .35vw + .78rem, .88rem);
  line-height: 1.48;
  color: rgba(248,250,252,0.94);
  border: 3px solid rgba(56,189,248,1);
  background:
    radial-gradient(circle at 18% 15%, rgba(255,255,255,0.08), transparent 55%),
    linear-gradient(180deg, rgba(8,18,52,0.52), rgba(5,10,28,0.92));
  box-shadow: 0 18px 34px rgba(0,0,0,0.38);
  opacity:0;
  transform: translateY(12px);
  animation: apm-bubble-in .34s ease-out forwards;
}
.apm-chat-row.user .apm-bubble{
  color:#051023;
  border-color: rgba(56,189,248,0.26);
  background:
    radial-gradient(circle at 18% 15%, rgba(255,255,255,0.22), transparent 55%),
    linear-gradient(135deg, rgba(56,189,248,1), rgba(34,211,238,1));
  box-shadow: 0 18px 34px rgba(8,47,73,0.22);
}
.apm-bubble-title{
  font-weight: 850;
  font-size: clamp(.70rem, .3vw + .64rem, .76rem);
  margin-bottom: .22rem;
  opacity:.92;
  color: rgba(147,197,253,0.95);
}
.apm-chat-row.user .apm-bubble-title{ color: rgba(7,35,84,0.88); }
.apm-bubble-tagline{ font-size: clamp(.66rem, .28vw + .6rem, .72rem); margin-top:.32rem; opacity:.78; }

@keyframes apm-bubble-in{
  0%{ opacity:0; transform: translateY(14px) scale(.985); }
  100%{ opacity:1; transform: translateY(0) scale(1); }
}

.apm-metadata-chip{
  display:flex;
  align-items:center;
  gap:.55rem;
  font-size: clamp(.68rem, .3vw + .62rem, .74rem);
  color: rgba(219,234,254,0.78);
  margin-top: 1rem;
  padding: .62rem 1rem;
  border-radius: 999px;
  border: 1px solid rgba(148,163,184,0.12);
  background:
    radial-gradient(circle at 30% 20%, rgba(255,255,255,.07), transparent 60%),
    linear-gradient(180deg, rgba(148,163,184,0.04), rgba(6,18,43,0.74));
  box-shadow: 0 12px 22px rgba(0,0,0,0.30);
}
.apm-metadata-chip i{ color: var(--cyan); }

.apm-chat-input-preview{
  margin-top: 1rem;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:.75rem;
  padding:.74rem 1rem;
  border-radius: 999px;
  border:1px solid rgba(148,163,184,0.10);
  background:
    radial-gradient(circle at 30% 20%, rgba(255,255,255,.06), transparent 60%),
    linear-gradient(180deg, rgba(148,163,184,0.04), rgba(6,18,43,0.72));
  box-shadow: 0 12px 22px rgba(0,0,0,.28);
}
.apm-chat-input-preview .hint{
  font-size: clamp(.72rem, .32vw + .66rem, .78rem);
  color: rgba(219,234,254,.60);
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
  padding-right:8px;
}
.apm-chat-input-preview .send{
  width: clamp(40px, 5vw, 44px);
  height: clamp(40px, 5vw, 44px);
  border-radius: 999px;
  border:1px solid rgba(56,189,248,0.18);
  background: linear-gradient(135deg, rgba(37,99,235,1), rgba(34,211,238,1));
  color:#061024;
  cursor:pointer;
  transition: transform .16s ease, box-shadow .16s ease, filter .16s ease;
  box-shadow: 0 14px 38px rgba(37,99,235,.14);
  flex: 0 0 auto;
}
.apm-chat-input-preview .send:hover{
  transform: translateY(-1px);
  box-shadow: 0 20px 50px rgba(37,99,235,.18);
  filter: saturate(1.03);
}

/* =========================================================
   ✅ ADDED FIX (INSIDE YOUR CODE): Prevent chat from pushing
   the sections below on mobile/tablet
========================================================= */
@media (max-width: 1024px){

  /* stop any sideways jump */
  .apm-hero{ overflow-x: clip; }

  /* ✅ lock card height so new chat bubbles never resize HERO */
  .apm-hero-right .apm-chat-card{
    display:flex;
    flex-direction:column;

    /* stable height = no layout push */
    height: clamp(420px, 66svh, 640px);

    /* extra safety on short screens */
    max-height: calc(100svh - var(--navH) - 110px);
  }

  /* ✅ allow scrolling INSIDE chat body (not page push) */
  .apm-chat-body{
    flex: 1 1 auto;
    min-height: 0;            /* ⭐ CRITICAL */
    max-height: none;         /* override your clamp */
    overflow: auto;           /* override overflow:hidden */
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
    padding-right: 4px;
  }

  /* keep bottom parts stable */
  .apm-metadata-chip,
  .apm-chat-input-preview{
    flex: 0 0 auto;
  }
}




































/* =========================================================
   GRAPH SECTION (fluid)
========================================================= */
.bgGraph{
  margin-top: clamp(90px, 10vw, 200px);
  color: var(--text);
  padding: clamp(22px, 4.2vw, 40px) clamp(12px, 2.8vw, 14px);
}
.bgGraph, .bgGraph *{ box-sizing: border-box; }
.bgGraph .wrap{ max-width: min(1080px, 100%); margin: 0 auto; }

.bgGraph .card{
  position:relative;
  border: 1px solid var(--border);
  border-radius: var(--r);
  background:
    radial-gradient(800px 260px at 18% 0%, rgba(255,255,255,.06), transparent 55%),
    radial-gradient(900px 320px at 88% 12%, rgba(37,99,235,.10), transparent 55%),
    rgba(255,255,255,.03);
  box-shadow: var(--shadowDeep);
  overflow:hidden;
  padding: clamp(12px, 2vw, 16px);
}
.bgGraph .card::before{
  content:"";
  position:absolute; inset:0;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='140' height='140' filter='url(%23n)' opacity='.12'/%3E%3C/svg%3E");
  mix-blend-mode:overlay;
  opacity:.22;
  pointer-events:none;
}

.bgGraph .topRow{
  position:relative;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap: var(--s2);
  flex-wrap:wrap;
  margin-bottom: var(--s2);
  z-index:1;
}

.bgGraph .pill{
  display:inline-flex;
  align-items:center;
  gap: var(--s2);
  padding: clamp(9px, 1.3vw, 10px) clamp(12px, 1.6vw, 14px);
  border-radius: 999px;
  border:1px solid rgba(148,163,184,.18);
  background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.03));
  color: var(--lime);
  font-weight: 950;
  letter-spacing: .14em;
  font-size: clamp(10px, .7vw + 9px, 11.5px);
  text-transform: uppercase;
  box-shadow: 0 12px 30px rgba(0,0,0,.22);
  position:relative;
  overflow:hidden;
}

.bgGraph .title{
  margin: 8px 0 0;
  font-size: clamp(18px, 2.1vw, 28px);
  line-height: 1.1;
  letter-spacing:-.02em;
  position:relative;
  z-index:1;
}

.bgGraph .graphBox{
  position:relative;
  border: 1px solid rgba(148,163,184,.14);
  border-radius: clamp(16px, 2vw, 18px);
  background:
    radial-gradient(800px 320px at 18% 18%, rgba(34,211,238,.14), transparent 58%),
    radial-gradient(700px 300px at 82% 22%, rgba(185,253,80,.10), transparent 58%),
    linear-gradient(180deg, var(--chatA), var(--chatB));
  overflow:hidden;
  padding: clamp(10px, 1.6vw, 12px);
  padding-bottom: clamp(82px, 11vw, 92px);
  min-height: clamp(320px, 52vw, 420px);
  box-shadow: 0 24px 80px rgba(0,0,0,.38);
}

.bgGraph svg{
  width:100%;
  height: clamp(280px, 48vw, 390px);
  display:block;
  position:relative;
  z-index:1;
}

.bgGraph .stepsRow{
  position:absolute;
  left: clamp(10px, 1.2vw, 12px);
  right: clamp(10px, 1.2vw, 12px);
  bottom: clamp(10px, 1.2vw, 12px);
  display:grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: clamp(10px, 1.4vw, 14px);
  z-index:2;
  pointer-events:none;
}

.bgGraph .stepCard{
  display:flex;
  align-items:center;
  gap: clamp(10px, 1.2vw, 12px);
  padding: clamp(10px, 1.6vw, 12px) clamp(12px, 1.8vw, 14px);
  border-radius: clamp(16px, 2.2vw, 18px);
  border: 1px solid rgba(148,163,184,.18);
  box-shadow: 0 18px 55px rgba(0,0,0,.38), inset 0 1px 0 rgba(255,255,255,.10);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  position:relative;
  overflow:hidden;
  opacity:0;
  transform: translateY(6px) scale(.985);
}

.bgGraph .stepBadge{
  width: clamp(36px, 4.6vw, 40px);
  height: clamp(36px, 4.6vw, 40px);
  border-radius: clamp(12px, 1.6vw, 14px);
  display:grid;
  place-items:center;
  font-weight: 1000;
  color:#061011;
  box-shadow: 0 16px 40px rgba(0,0,0,.28), inset 0 1px 0 rgba(255,255,255,.20);
  flex: 0 0 auto;
}

.bgGraph .stepTitle{
  font-size: clamp(14px, 1.25vw + 10px, 18.6px);
  font-weight: 950;
  letter-spacing: -.01em;
  color: #071a3a;
  margin:0;
}
.bgGraph .stepSub{
  margin: 6px 0 0;
  font-size: clamp(12px, .55vw + 10px, 12.6px);
  font-weight: 850;
  color: rgba(226,232,240,.92);
  opacity:.95;
}

.bgGraph .stepCard.s1,
.bgGraph .stepCard.s2,
.bgGraph .stepCard.s3{
  background:
    radial-gradient(circle at 30% 15%, rgba(255,255,255,.18), transparent 55%),
    linear-gradient(135deg, rgba(37, 99, 235, 1), rgba(34, 211, 238, 1));
}
.bgGraph .s1 .stepBadge{ background: linear-gradient(135deg, rgba(185,253,80,1), rgba(34,211,238,1)); }
.bgGraph .s2 .stepBadge{ background: linear-gradient(135deg, rgba(34,211,238,1), rgba(96,165,250,1)); }
.bgGraph .s3 .stepBadge{ background: linear-gradient(135deg, rgba(96,165,250,1), rgba(79,70,229,1)); }

/* =========================================================
   FEATURED SECTION (scoped only)
   ✅ FIX: background transparent so body stays ONE
========================================================= */
#featured{
  --bg: transparent; /* ✅ was #050e24 */

  --cardTop: rgba(9, 34, 82, .82);
  --cardBot: rgba(6, 16, 46, .92);

  --stroke: rgba(148,163,184,.16);

  --pad: clamp(16px, 4vw, 84px);
  --maxW: 1120px;

  --shadowSoft: 0 14px 40px rgba(0,0,0,.28);
  --shadowLift: 0 34px 110px rgba(0,0,0,.56);

  position:relative;
  isolation:isolate;
  background: var(--bg); /* ✅ now transparent */
  color: var(--text);
  padding: var(--pad);
}
#featured,
#featured *{ box-sizing:border-box; }

/* ambience: fixed (no extra scroll height) */
#featured::before{
  content:"";
  position:fixed;
  inset:-35%;
  pointer-events:none;
  z-index:-1;
  background:
    radial-gradient(circle at 18% 16%, rgba(34,211,238,.16), transparent 46%),
    radial-gradient(circle at 86% 22%, rgba(96,165,250,.12), transparent 48%),
    radial-gradient(circle at 70% 88%, rgba(185,253,80,.08), transparent 48%);
  filter: blur(26px);
  opacity:0;
  transition: opacity .35s ease;
}
#featured.inview::before{ opacity:.72; }

#featured .wrap{ max-width: var(--maxW); margin: 0 auto; }

/* heading */
#featured .heading{
  text-align:center;
  margin: 0 0 clamp(28px, 5.5vw, 70px);
  opacity:0;
  transform: translateX(90px);
  transition: transform .9s cubic-bezier(.22,.61,.36,1), opacity .9s ease;
}
#featured.inview .heading{ opacity:1; transform: translateX(0); }

#featured .kicker{
  display:inline-flex;
  align-items:center;
  gap: var(--s2);
  padding: clamp(9px, 1.2vw, 10px) clamp(12px, 1.6vw, 14px);
  border-radius: 999px;
  border: 1px solid rgba(148,163,184,.18);
  background: linear-gradient(180deg, rgba(7,26,58,.72), rgba(5,14,36,.72));
  color: rgba(226,232,240,.88);
  font-size: clamp(.82rem, .9vw, .95rem);
  letter-spacing:.3px;
}
#featured .kicker i{
  width:10px; height:10px;
  border-radius:50%;
  background: linear-gradient(135deg, var(--cyan), var(--lime));
  box-shadow: 0 0 18px rgba(34,211,238,.22);
  display:inline-block;
}

#featured .heading h2{
  margin: 16px 0 10px;
  font-size: clamp(2.15rem, 3.25vw, 3.2rem);
  font-weight: 900;
  letter-spacing:.2px;
  color: var(--text);
  text-shadow:
    0 1px 0 rgba(0,0,0,.25),
    0 14px 30px rgba(0,0,0,.55);
}
#featured .heading p{
  margin: 0 auto;
  max-width: 760px;
  color: rgba(226,232,240,.72);
  font-size: clamp(.98rem, 1.05vw, 1.05rem);
  line-height: 1.7;
}
#featured .underline{
  width: min(520px, 80vw);
  height: 3px;
  margin: 18px auto 0;
  border-radius: 999px;
  background: linear-gradient(90deg, transparent, rgba(34,211,238,.9), rgba(185,253,80,.65), transparent);
  opacity:.78;
  filter: drop-shadow(0 0 14px rgba(34,211,238,.18));
}

#featured .cards{
  display:grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: clamp(18px, 3.2vw, 40px);
}

/* entry */
#featured .card{
  perspective: 950px;
  opacity:0;
  transform: translateY(60px) scale(.985);
  transition: transform .9s cubic-bezier(.22,.61,.36,1), opacity .9s ease;
}
#featured .card.left{  transform: translateX(-110px) translateY(24px) scale(.985); }
#featured .card.right{ transform: translateX(110px) translateY(24px) scale(.985); }
#featured .card.show{ opacity:1; transform: translateX(0) translateY(0) scale(1); }

/* shell */
#featured .shell{
  position:relative;
  border-radius: var(--r);
  padding: clamp(26px, 3.2vw, 40px) clamp(20px, 2.8vw, 32px);
  background: linear-gradient(180deg, var(--cardTop), var(--cardBot));
  border: 1px solid rgba(148,163,184,.20);
  box-shadow: var(--shadowSoft);
  backdrop-filter: blur(14px);
  overflow:hidden;
  isolation:isolate;

  transform: translateY(0) rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg));
  transition: transform .18s ease, box-shadow .25s ease, border-color .25s ease;
}

#featured .shell::before{
  content:"";
  position:absolute;
  inset:0;
  border-radius: inherit;
  pointer-events:none;
  z-index:0;
  background:
    radial-gradient(520px circle at var(--mx, 50%) var(--my, 50%),
      rgba(34,211,238,.20),
      rgba(96,165,250,.10) 30%,
      transparent 60%
    );
  opacity:0;
  transition: opacity .18s ease;
}
#featured .shell::after{
  content:"";
  position:absolute;
  inset:0;
  border-radius: inherit;
  padding: 1.6px;
  background: linear-gradient(135deg,
    rgba(34,211,238,.85),
    rgba(96,165,250,.40),
    rgba(79,70,229,.45),
    rgba(185,253,80,.60)
  );
  -webkit-mask:
    linear-gradient(#000 0 0) content-box,
    linear-gradient(#000 0 0);
  -webkit-mask-composite: xor;
  mask:
    linear-gradient(#000 0 0) content-box,
    linear-gradient(#000 0 0);
  mask-composite: exclude;

  opacity:.18;
  filter:
    drop-shadow(0 0 14px rgba(34,211,238,.14))
    drop-shadow(0 0 22px rgba(185,253,80,.08));
  transition: opacity .25s ease, filter .25s ease;
  pointer-events:none;
  z-index:1;
}

@media (hover:hover) and (pointer:fine){
  #featured .card:hover .shell{
    transform: translateY(-12px) rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg));
    box-shadow: var(--shadowLift);
    border-color: rgba(34,211,238,.22);
  }
  #featured .card:hover .shell::before{ opacity:1; }
  #featured .card:hover .shell::after{
    opacity:.62;
    filter:
      drop-shadow(0 0 18px rgba(34,211,238,.24))
      drop-shadow(0 0 26px rgba(185,253,80,.12));
  }
}

/* items one-by-one */
#featured .shell > *{
  position:relative;
  z-index:2;
  opacity:0;
  transform: translateY(12px);
}
#featured .card.show .shell > *{ animation: itemIn .72s cubic-bezier(.22,.61,.36,1) forwards; }
#featured .card.show .shell > *:nth-child(1){ animation-delay: .10s; }
#featured .card.show .shell > *:nth-child(2){ animation-delay: .18s; }
#featured .card.show .shell > *:nth-child(3){ animation-delay: .26s; }
#featured .card.show .shell > *:nth-child(4){ animation-delay: .34s; }
#featured .card.show .shell > *:nth-child(5){ animation-delay: .42s; }
@keyframes itemIn{
  from{ opacity:0; transform: translateY(12px); }
  to  { opacity:1; transform: translateY(0); }
}

#featured .tag{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding: 8px 12px;
  border-radius: 999px;
  width: fit-content;
  border: 1px solid rgba(148,163,184,.18);
  background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
  color: rgba(226,232,240,.88);
  font-size: clamp(.82rem, .35vw + .72rem, .86rem);
  letter-spacing:.28px;
  margin-bottom: 16px;
}
#featured .tag b{ color: var(--lime); font-weight: 800; }

#featured .icon{
  width: clamp(52px, 5.2vw, 60px);
  height: clamp(52px, 5.2vw, 60px);
  display:grid;
  place-items:center;
  border-radius: clamp(16px, 1.6vw, 18px);
  background: linear-gradient(135deg, rgba(34,211,238,1), rgba(96,165,250,1));
  box-shadow: 0 12px 30px rgba(79,70,229,.30);
  font-size: clamp(22px, 2.2vw, 28px);
  margin-bottom: 18px;
}

#featured .shell h3{
  margin: 0 0 10px;
  font-size: clamp(1.18rem, 1.35vw, 1.45rem);
  color: white;
}
#featured .shell p{
  margin: 0;
  font-size: clamp(.95rem, 1vw, .98rem);
  line-height: 1.75;
  color: rgba(226,232,240,.78);
}
#featured .badges{
  display:flex;
  gap: clamp(10px, 1.4vw, 12px);
  margin-top: 18px;
  font-size: clamp(16px, 1.6vw, 18px);
  opacity: .88;
}

@media (max-width: 520px){
  #featured .card.left, #featured .card.right{ transform: translateY(44px) scale(.985); }
  #featured .heading{ transform: translateX(60px); }
}

/* =========================================================
   COMPARISON SECTION (scoped only)
   ✅ FIX: background transparent so body stays ONE
========================================================= */
#spiComparison{
  --primary:#6366f1;
  --secondary:#22d3ee;
  --accent:#b9fd50;

  --dark:#020617;
  --glass:rgba(255,255,255,0.06);
  --border2:rgba(255,255,255,0.12);

  color:#f8fafc;
  font-family: var(--fontSans);
  -webkit-font-smoothing:antialiased;
  -moz-osx-font-smoothing:grayscale;

  padding:clamp(64px, 7vw, 96px) 6vw;

  background: transparent; /* ✅ was linear-gradient(180deg,#020617,#000) */
  position:relative;
  overflow:hidden;
  isolation:isolate;
}
#spiComparison *{ box-sizing:border-box; }

#spiComparison::before{
  content:"";
  position:absolute; inset:-2px;
  background:
    radial-gradient(520px 280px at 22% 8%, rgba(99,102,241,.18), transparent 62%),
    radial-gradient(520px 280px at 82% 14%, rgba(34,211,238,.14), transparent 60%),
    radial-gradient(740px 460px at 50% 115%, rgba(185,253,80,.10), transparent 62%);
  pointer-events:none;
  z-index:-1;
}
#spiComparison .spi-container{max-width:1180px;margin:auto;}

/* heading */
#spiComparison .spi-head{
  text-align:center;
  max-width:980px;
  margin:0 auto;
  position:relative;
  padding-top:6px;
}
#spiComparison .spi-pill{
  display:inline-flex;
  align-items:center;
  gap: clamp(8px, 1.2vw, 10px);
  padding: clamp(9px, 1.2vw, 10px) clamp(14px, 1.8vw, 18px);
  border-radius:999px;
  border:1px solid rgba(255,255,255,.14);
  background:rgba(255,255,255,.05);
  backdrop-filter: blur(14px);
  box-shadow: 0 18px 60px rgba(0,0,0,.55);
  font-weight:700;
  letter-spacing:.02em;
  color:rgba(248,250,252,.92);
  transform: translateY(10px);
  opacity:0;
  transition: transform .8s var(--ease), opacity .8s var(--ease);
}
#spiComparison .spi-dot{
  width:10px;height:10px;border-radius:50%;
  background: radial-gradient(circle at 30% 30%, #fff, var(--accent) 35%, #22d3ee 80%);
  box-shadow: 0 0 0 4px rgba(185,253,80,.14), 0 0 24px rgba(34,211,238,.30);
}

#spiComparison .spi-head h2{
  margin:18px 0 0;
  font-size:clamp(34px, 4.6vw, 64px);
  line-height:1.03;
  letter-spacing:-.03em;
  font-weight:900;
  transform: translateY(12px);
  opacity:0;
  transition: transform .9s var(--ease), opacity .9s var(--ease);
}
#spiComparison .spi-head p{
  margin:14px auto 0;
  max-width:70ch;
  color:rgba(226,232,240,.78);
  font-size:clamp(14px, 1.4vw, 18px);
  line-height:1.6;
  transform: translateY(12px);
  opacity:0;
  transition: transform .95s var(--ease), opacity .95s var(--ease);
}
#spiComparison .spi-line{
  width:min(720px, 88%);
  height:4px;
  margin:26px auto 0;
  border-radius:999px;
  background: linear-gradient(90deg,
    transparent 0%,
    rgba(34,211,238,.0) 10%,
    rgba(34,211,238,.85) 35%,
    rgba(185,253,80,.85) 65%,
    rgba(34,211,238,.0) 90%,
    transparent 100%);
  box-shadow:
    0 0 30px rgba(34,211,238,.20),
    0 0 36px rgba(185,253,80,.12);
  transform: scaleX(.55);
  opacity:0;
  transition: transform 1.0s var(--ease), opacity 1.0s var(--ease);
}
#spiComparison .spi-head.is-in .spi-pill,
#spiComparison .spi-head.is-in h2,
#spiComparison .spi-head.is-in p{
  transform: translateY(0);
  opacity:1;
}
#spiComparison .spi-head.is-in .spi-line{
  transform: scaleX(1);
  opacity:1;
}

/* grid */
#spiComparison .spi-grid{
  display:grid;
  grid-template-columns:repeat(12,1fr);
  gap: clamp(18px, 2.2vw, 26px);
  margin-top:clamp(28px, 4.8vw, 60px);
  position:relative;
  perspective: 1100px;
}
#spiComparison .spi-card{grid-column:span 6;}
@media(max-width:900px){
  #spiComparison .spi-card{grid-column:span 12;}
}

/* VS chip */
#spiComparison .spi-vs{
  position:absolute;
  left:50%;top:50%;
  transform:translate(-50%,-50%);
  padding: clamp(9px, 1.2vw, 10px) clamp(12px, 1.6vw, 14px);
  border-radius:999px;
  border:1px solid var(--border2);
  background:rgba(255,255,255,.06);
  backdrop-filter:blur(14px);
  font-weight:900;
  letter-spacing:.12em;
  text-transform:uppercase;
  box-shadow: 0 18px 60px rgba(0,0,0,.55);
}
#spiComparison .spi-vs::before{
  content:"";
  position:absolute; inset:-1px;
  border-radius:inherit;
  background: linear-gradient(135deg, rgba(99,102,241,.35), rgba(34,211,238,.28), rgba(255,255,255,.06));
  filter: blur(10px);
  opacity:.65;
  z-index:-1;
}
@media(max-width:900px){ #spiComparison .spi-vs{display:none;} }

/* cards */
#spiComparison .spi-card{
  position:relative;
  background:
    radial-gradient(900px 240px at 30% 0%, rgba(99,102,241,.18), transparent 55%),
    linear-gradient(145deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
  border-radius: clamp(22px, 2.2vw, 26px);
  padding:clamp(22px, 2.6vw, 36px);
  border:1px solid var(--border2);
  box-shadow:0 26px 85px rgba(0,0,0,.55);
  transition: transform .55s var(--ease), box-shadow .55s var(--ease), border-color .55s var(--ease);
  transform-style:preserve-3d;

  opacity:0;
  transform: translateY(18px) scale(.985);
}
#spiComparison .spi-card::before{
  content:"";
  position:absolute; inset:0;
  border-radius:inherit;
  padding:1px;
  background: linear-gradient(135deg,
      rgba(99,102,241,.40),
      rgba(34,211,238,.28),
      rgba(255,255,255,.08));
  -webkit-mask:
    linear-gradient(#000 0 0) content-box,
    linear-gradient(#000 0 0);
  -webkit-mask-composite:xor;
          mask-composite:exclude;
  opacity:.35;
  pointer-events:none;
}
#spiComparison .spi-card.is-in{
  opacity:1;
  transform: translateY(0) scale(1);
}
@media (hover:hover) and (pointer:fine){
  #spiComparison .spi-card:hover{
    transform:translateY(-10px);
    box-shadow:0 45px 140px rgba(0,0,0,.72);
    border-color: rgba(255,255,255,.18);
  }
}

/* metrics */
#spiComparison .spi-metric{display:grid;gap: clamp(10px, 1.4vw, 14px);margin-top: clamp(14px, 1.8vw, 18px);}
#spiComparison .spi-label{display:flex;justify-content:space-between;font-size: clamp(12px, .55vw + 11px, 13.5px);opacity:.95;gap:10px;}
#spiComparison .spi-label span:last-child{font-weight:800;}

#spiComparison .spi-bar{
  height: clamp(14px, 1.4vw, 16px);
  background:rgba(255,255,255,.10);
  border-radius:999px;
  overflow:hidden;
  border:1px solid rgba(255,255,255,.08);
  box-shadow: inset 0 1px 0 rgba(255,255,255,.10);
}
#spiComparison .spi-bar span{
  display:block;
  height:100%;
  width:0;
  border-radius:999px;
  transition:width 1.25s var(--ease);
  position:relative;
}
#spiComparison .spi-bar span::after{
  content:"";
  position:absolute;
  top:0; left:-35%;
  width:35%;
  height:100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.35), transparent);
  transform: skewX(-18deg);
  opacity:.55;
  animation: spiShimmer 1.8s var(--ease) infinite;
}
@keyframes spiShimmer{
  0%{ left:-35%; }
  100%{ left:110%; }
}
#spiComparison .spi-bad span{background:linear-gradient(135deg,#ef4444,#991b1b);}
#spiComparison .spi-good span{background:linear-gradient(135deg,#22c55e,#16a34a);}

/* CTA */
#spiComparison .spi-cta-row{
  margin-top:clamp(28px, 4.4vw, 50px);
  display:flex;
  gap: clamp(10px, 1.4vw, 14px);
  flex-wrap:wrap;
}
#spiComparison .spi-cta{
  padding:clamp(14px, 1.6vw, 16px) clamp(20px, 2.2vw, 26px);
  background:linear-gradient(135deg,var(--primary),var(--secondary));
  border:none;
  border-radius:999px;
  color:#fff;
  font-weight:900;
  cursor:pointer;
  box-shadow:0 20px 60px rgba(99,102,241,.45);
  position:relative;
  overflow:hidden;
  transition: transform .25s var(--ease), box-shadow .35s var(--ease);
}
#spiComparison .spi-cta::before{
  content:"";
  position:absolute; inset:-2px;
  background: linear-gradient(115deg, transparent 0%, rgba(255,255,255,.22) 35%, transparent 70%);
  transform: translateX(-120%) skewX(-12deg);
  transition: transform .7s var(--ease);
  opacity:.9;
}
#spiComparison .spi-cta:hover::before{ transform: translateX(120%) skewX(-12deg); }
#spiComparison .spi-cta:hover{ transform: translateY(-2px); box-shadow:0 28px 80px rgba(99,102,241,.55); }

#spiComparison .spi-cta-ghost{
  padding:clamp(12px, 1.5vw, 14px) clamp(16px, 2vw, 20px);
  border-radius:999px;
  border:1px solid var(--border2);
  background:rgba(255,255,255,.06);
  color:#fff;
  font-weight:800;
  cursor:pointer;
  backdrop-filter: blur(10px);
  transition: transform .25s var(--ease), box-shadow .35s var(--ease), border-color .35s var(--ease);
}
#spiComparison .spi-cta-ghost:hover{
  transform: translateY(-2px);
  border-color: rgba(255,255,255,.18);
  box-shadow: 0 22px 70px rgba(0,0,0,.55);
}

/* =========================================================
   RESPONSIVE BREAKPOINTS (GLOBAL)
========================================================= */
@media (max-width: 1024px){
  /* ✅ keep 1-column (already mobile-first, but extra safety) */
  .apm-hero-inner{ grid-template-columns: 1fr !important; align-items:flex-start; }
  .apm-hero-left{ position: static; top:auto; }
  .apm-hero-right{ justify-content:center; min-height:auto; }
  .apm-hero-right .apm-chat-card{
    position: static !important;
    animation: none !important;
    transform: none !important;
    width: min(100%, 640px);
  }
}

@media (max-width: 768px){
  .apm-logo-text small{ display:none; }
  .apm-login-text{ display:none; }
  .apm-login{ width:auto; min-height:auto; padding:.56rem .56rem; max-width:none; }
  .apm-cta-row{ flex-direction: column; align-items: stretch; }
  .apm-cta, .apm-btn3d{ width:100%; justify-content:center; }
}

/* Graph: stack steps (no overlap) */
@media (max-width: 720px){
  .bgGraph .graphBox{ padding-bottom: 12px; min-height: auto; }
  .bgGraph .stepsRow{
    position: static;
    margin-top: 12px;
    grid-template-columns: 1fr;
    pointer-events:auto;
  }
  .bgGraph .stepCard{ pointer-events:auto; }
}

/* ultra small phones */
@media (max-width: 420px){
  .apm-bubble{ max-width: 92%; }
  .apm-chat-body{ max-height: 440px; }
}

/* reduced motion */
@media (prefers-reduced-motion: reduce){
  .apm-chat-status-dot{ animation:none !important; }
  .apm-logo-mark::before{ animation:none !important; }
  .apm-btn3d::before{ display:none !important; }
  .apm-hero-right .apm-chat-card{ animation:none !important; }

  #featured .heading{ opacity:1; transform:none; transition:none; }
  #featured .card{ opacity:1; transform:none; transition:none; }
  #featured .shell{ transform:none !important; transition:none !important; }
  #featured .shell::before, #featured .shell::after{ transition:none !important; }
  #featured .shell > *{ opacity:1; transform:none; animation:none !important; }

  #spiComparison .spi-bar span,
  #spiComparison .spi-card,
  #spiComparison .spi-cta,
  #spiComparison .spi-cta-ghost,
  #spiComparison .spi-pill,
  #spiComparison .spi-head h2,
  #spiComparison .spi-head p,
  #spiComparison .spi-line{transition:none;}
  #spiComparison .spi-bar span::after{animation:none;}
}

/* =========================================================
   ✅ FINAL RESPONSIVE + NO-GLITCH PATCH (ADDED)
========================================================= */

/* Better modern viewport behavior */
.apm-hero{
  min-height: 100dvh;
}

/* Prevent any long text/urls from breaking layouts on small screens */
.apm-title,
.apm-subhead,
.apm-kicker,
.apm-body,
.apm-bubble,
.apm-login-text strong,
.apm-login-text small,
#featured .heading p,
#featured .shell p,
#spiComparison .spi-head p{
  overflow-wrap: anywhere;
  word-break: break-word;
}

/* Mobile/Tablet GPU stability fix (keeps desktop effects, stops blinking) */
@media (hover: none), (pointer: coarse), (max-width: 1024px){

  /* Biggest mobile glitch source: fixed background attachment */
  body{
    background-attachment: scroll;
    position: relative;
  }

  /* Reduce repaint-heavy fixed overlays on mobile */
  body::before,
  body::after{
    position: absolute;
    transform: translateZ(0);
  }

  /* Grid overlay mask can flicker on some mobile browsers */
  body::before{
    mask-image: none;
    -webkit-mask-image: none;
    opacity: .18;
  }

  /* Stop animated glow layers that look like blinking on mobile */
  .apm-chat-card::after{ animation: none !important; }  /* borderSpin */
  .apm-chat-card::before{ animation: none !important; } /* glassPulse */

  /* Featured ambience: fixed+blur heavy → make stable on mobile */
  #featured::before{
    position: absolute;
    filter: blur(22px);
    transform: translateZ(0);
  }

  /* Optional safety: logo sheen shimmer on some phones */
  .apm-logo-mark::before{
    animation: none !important;
    opacity: .35;
  }
}

/* Fallback if dvh not supported */
@supports not (height: 100dvh){
  .apm-hero{ min-height: 100vh; }
}

/* ===========================
   MOBILE NAV TOGGLE
   ✅ FIX: apply to your real .apm-nav-right (not only .apm-menu)
=========================== */

/* desktop default */
.apm-burger{ display:none; }

/* burger button */
.apm-burger{
  width: 44px;
  height: 44px;
  border-radius: 999px;
  border: 1px solid rgba(148,163,184,.18);
  background:
    radial-gradient(circle at 30% 15%, rgba(255,255,255,.10), transparent 55%),
    linear-gradient(180deg, rgba(148,163,184,.06), rgba(6,18,43,.74));
  box-shadow:
    0 18px 44px rgba(0,0,0,.34),
    inset 0 1px 0 rgba(255,255,255,.10),
    inset 0 -10px 22px rgba(0,0,0,.20);
  cursor:pointer;
  display:grid;
  place-items:center;
}

/* hamburger icon */
.apm-burger-ic{
  width: 18px;
  height: 12px;
  position:relative;
  display:block;
}
.apm-burger-ic::before,
.apm-burger-ic::after{
  content:"";
  position:absolute;
  left:0;
  right:0;
  height:2px;
  border-radius:999px;
  background: rgba(219,234,254,.92);
  box-shadow: 0 10px 18px rgba(0,0,0,.30);
  transition: transform .22s var(--ease), top .22s var(--ease), opacity .18s ease;
}
.apm-burger-ic::before{ top:0; }
.apm-burger-ic::after{ top:10px; }
.apm-burger-ic{
  background: rgba(219,234,254,.92);
  height:2px;
  top:5px;
  border-radius:999px;
  transition: opacity .18s ease;
  position:relative;
}

/* X state */
.apm-nav.is-open .apm-burger-ic{ opacity:0; }
.apm-nav.is-open .apm-burger-ic::before{ top:5px; transform: rotate(45deg); }
.apm-nav.is-open .apm-burger-ic::after{ top:5px; transform: rotate(-45deg); }

/* ===========================
   MOBILE BEHAVIOR
=========================== */
@media (max-width: 768px){
  .apm-burger{ display:grid; }

  /* ✅ dropdown uses .apm-nav-right (your real container) */
  .apm-nav-right{
    position:absolute;
    top: calc(100% + 10px);
    left: 0;
    right: 0;

    display:none;
    flex-direction:column;
    align-items:stretch;
    gap: 12px;

    padding: 14px;
    border-radius: 18px;
    border: 1px solid rgba(148,163,184,.18);

    background:
      radial-gradient(circle at 30% 15%, rgba(255,255,255,.10), transparent 55%),
      linear-gradient(180deg, rgba(6,18,43,.86), rgba(3,10,25,.92));
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);

    box-shadow: 0 28px 80px rgba(0,0,0,.48);
    z-index: 1000;

    max-height: min(70vh, 520px);
    overflow:auto;
  }

  .apm-nav.is-open .apm-nav-right{
    display:flex;
    animation: apmDrop .22s var(--ease) both;
    transform-origin: top right;
  }

  @keyframes apmDrop{
    from{ opacity:0; transform: translateY(-8px) scale(.98); }
    to{ opacity:1; transform: translateY(0) scale(1); }
  }

  .apm-nav-right .apm-btn3d,
  .apm-nav-right .apm-login{
    width:100%;
    justify-content:flex-start;
  }
}







/* =========================================================
   ✅ MOBILE NAV FIX OVERRIDE (PASTE AT VERY END)
   - Burger stays visible
   - Only .apm-menu becomes dropdown on mobile
========================================================= */

/* Desktop: burger hidden, menu inline */
.apm-burger{ display:none; }

.apm-menu{
  display:flex;
  align-items:center;
  gap: clamp(8px, 1vw, 10px);
}

/* Make sure dropdown can overflow below nav */
.apm-nav-inner{ overflow: visible; }

/* ---------- Burger icon (clean + correct X) ---------- */
.apm-burger{
  width: 44px;
  height: 44px;
  border-radius: 999px;
  border: 1px solid rgba(148,163,184,.18);
  background:
    radial-gradient(circle at 30% 15%, rgba(255,255,255,.10), transparent 55%),
    linear-gradient(180deg, rgba(148,163,184,.06), rgba(6,18,43,.74));
  box-shadow:
    0 18px 44px rgba(0,0,0,.34),
    inset 0 1px 0 rgba(255,255,255,.10),
    inset 0 -10px 22px rgba(0,0,0,.20);
  cursor:pointer;
  place-items:center;
}

.apm-burger:focus-visible{
  outline: 2px solid rgba(34,211,238,.55);
  outline-offset: 3px;
}

/* 3 lines */
.apm-burger-ic{
  width: 18px;
  height: 2px;                 /* middle line */
  background: rgba(219,234,254,.92);
  border-radius: 999px;
  position:relative;
  display:block;
  box-shadow: 0 10px 18px rgba(0,0,0,.22);
}
.apm-burger-ic::before,
.apm-burger-ic::after{
  content:"";
  position:absolute;
  left:0; right:0;
  height:2px;
  border-radius:999px;
  background: rgba(219,234,254,.92);
  transition: transform .22s var(--ease), top .22s var(--ease), opacity .18s ease;
  box-shadow: 0 10px 18px rgba(0,0,0,.22);
}
.apm-burger-ic::before{ top:-6px; }
.apm-burger-ic::after { top: 6px; }

/* Open state -> X */
.apm-nav.is-open .apm-burger-ic{
  background: transparent;      /* hide middle line only */
}
.apm-nav.is-open .apm-burger-ic::before{
  top:0;
  transform: rotate(45deg);
}
.apm-nav.is-open .apm-burger-ic::after{
  top:0;
  transform: rotate(-45deg);
}

/* =========================================================
   Mobile: burger visible, .apm-menu becomes dropdown
========================================================= */
@media (max-width: 768px){

  /* Keep right container in the navbar row (DON'T hide it) */
  .apm-nav-right{
    position:relative;          /* anchor dropdown */
    display:flex;
    align-items:center;
    gap: 10px;

    /* cancel your old dropdown styles */
    top:auto; left:auto; right:auto;
    flex-direction:row;
    padding:0;
    border:0;
    background:none;
    box-shadow:none;
    max-height:none;
    overflow:visible;
  }

  .apm-burger{ display:grid; }

  /* Hide menu by default (closed) */
  .apm-menu{
    position:absolute;
    top: calc(100% + 10px);     /* below nav */
    right: 0;

    width: min(92vw, 420px);
    display:none;

    flex-direction:column;
    align-items:stretch;
    gap: 12px;

    padding: 14px;
    border-radius: 18px;
    border: 1px solid rgba(148,163,184,.18);

    background:
      radial-gradient(circle at 30% 15%, rgba(255,255,255,.10), transparent 55%),
      linear-gradient(180deg, rgba(6,18,43,.86), rgba(3,10,25,.92));
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);

    box-shadow: 0 28px 80px rgba(0,0,0,.48);
    z-index: 1001;
/* ✅ FORCE X (Close icon) on open — override old opacity:0 rule */
.apm-nav.is-open .apm-burger-ic{
  opacity: 1 !important;       /* critical override */
  background: transparent !important; /* hide middle line only */
}

.apm-nav.is-open .apm-burger-ic::before,
.apm-nav.is-open .apm-burger-ic::after{
  opacity: 1 !important;
  top: 0 !important;
}

.apm-nav.is-open .apm-burger-ic::before{
  transform: rotate(45deg) !important;
}
.apm-nav.is-open .apm-burger-ic::after{
  transform: rotate(-45deg) !important;
}

    max-height: min(70vh, 520px);
    overflow:auto;
    -webkit-overflow-scrolling: touch;
  }

  /* Open -> show dropdown */
  .apm-nav.is-open .apm-menu{
    display:flex;
    animation: apmDrop .22s var(--ease) both;
    transform-origin: top right;
  }

  @keyframes apmDrop{
    from{ opacity:0; transform: translateY(-8px) scale(.98); }
    to  { opacity:1; transform: translateY(0) scale(1); }
  }

  /* Make items full width inside dropdown */
  .apm-menu .apm-btn3d,
  .apm-menu .apm-login{
    width:100%;
    justify-content:flex-start;
    max-width:none;
  }

  /* ✅ Re-show login text inside dropdown (your earlier media query hides it) */
  .apm-menu .apm-login-text{ display:flex; }
}
/* ✅ FORCE X (Close icon) on open — override old opacity:0 rule */
.apm-nav.is-open .apm-burger-ic{
  opacity: 1 !important;       /* critical override */
  background: transparent !important; /* hide middle line only */
}

.apm-nav.is-open .apm-burger-ic::before,
.apm-nav.is-open .apm-burger-ic::after{
  opacity: 1 !important;
  top: 0 !important;
}

.apm-nav.is-open .apm-burger-ic::before{
  transform: rotate(45deg) !important;
}
.apm-nav.is-open .apm-burger-ic::after{
  transform: rotate(-45deg) !important;
}








/* ============================
   4K 3D Animated Button (Blink + Shine)
   Use with: <button class="btn" type="button">Try Busineger →</button>
============================ */
.btn{
  --btn-h: 56px;
  --r: 999px;

  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: .6rem;
  margin-top: 30px;
  margin-bottom: 30px; /* ✅ added */
  height: var(--btn-h);
  padding: 0 1.25rem;
  border-radius: var(--r);

  font-family: inherit;
  font-weight: 900;
  font-size: clamp(.95rem, .6vw + .8rem, 1.05rem);
  letter-spacing: .02em;

  color: #061024;
  cursor: pointer;
  user-select: none;
  -webkit-tap-highlight-color: transparent;

  border: 1px solid rgba(185,253,80,.35);
  background:
    radial-gradient(circle at 20% 20%, rgba(255,255,255,.55), transparent 42%),
    linear-gradient(135deg, #b9fd50, #22d3ee 55%, #2563eb);

  box-shadow:
    0 18px 44px rgba(0,0,0,.45),                 /* deep */
    0 10px 0 rgba(3,10,25,.55),                  /* hard 3D base */
    0 0 0 1px rgba(255,255,255,.10) inset,       /* glass edge */
    0 22px 70px rgba(34,211,238,.22),            /* glow */
    0 0 34px rgba(185,253,80,.22);               /* lime glow */

  transform: translateZ(0);
  transition: transform .16s ease, box-shadow .16s ease, filter .16s ease;
  overflow: hidden;
  isolation: isolate;
}

/* glossy top highlight */
.btn::before{
  content:"";
  position:absolute;
  inset: 2px 2px auto 2px;
  height: 55%;
  border-radius: inherit;
  background: linear-gradient(180deg, rgba(255,255,255,.55), rgba(255,255,255,0));
  opacity: .35;
  pointer-events:none;
  mix-blend-mode: overlay;
}

/* moving shine sweep */
.btn::after{
  content:"";
  position:absolute;
  inset:-40% -30%;
  background: linear-gradient(120deg,
    transparent 40%,
    rgba(255,255,255,.65) 50%,
    transparent 60%
  );
  transform: translateX(-60%) rotate(8deg);
  opacity: .0;
  pointer-events:none;
}

/* blinking neon pulse */
@keyframes btnPulse{
  0%,100%{
    filter: saturate(1) brightness(1);
    box-shadow:
      0 18px 44px rgba(0,0,0,.45),
      0 10px 0 rgba(3,10,25,.55),
      0 0 0 1px rgba(255,255,255,.10) inset,
      0 22px 70px rgba(34,211,238,.18),
      0 0 34px rgba(185,253,80,.18);
  }
  50%{
    filter: saturate(1.15) brightness(1.06);
    box-shadow:
      0 22px 58px rgba(0,0,0,.48),
      0 10px 0 rgba(3,10,25,.55),
      0 0 0 1px rgba(255,255,255,.14) inset,
      0 0 80px rgba(34,211,238,.35),
      0 0 62px rgba(185,253,80,.35);
  }
}

/* shine sweep animation */
@keyframes btnShine{
  0%{ opacity:0; transform: translateX(-70%) rotate(8deg); }
  18%{ opacity:.55; }
  35%{ opacity:0; transform: translateX(70%) rotate(8deg); }
  100%{ opacity:0; transform: translateX(70%) rotate(8deg); }
}

.btn{
  animation: btnPulse 1.6s ease-in-out infinite;
}

/* hover = more 3D lift + run shine */
.btn:hover{
  transform: translateY(-2px) scale(1.01);
  box-shadow:
    0 26px 74px rgba(0,0,0,.55),
    0 12px 0 rgba(3,10,25,.55),
    0 0 0 1px rgba(255,255,255,.12) inset,
    0 0 110px rgba(34,211,238,.30),
    0 0 80px rgba(185,253,80,.30);
}
.btn:hover::after{
  animation: btnShine 1.45s ease-in-out infinite;
}

/* press = deep push */
.btn:active{
  transform: translateY(2px) scale(.995);
  box-shadow:
    0 14px 30px rgba(0,0,0,.50),
    0 6px 0 rgba(3,10,25,.55),
    0 0 0 1px rgba(255,255,255,.10) inset;
}

/* keyboard focus */
.btn:focus-visible{
  outline: none;
  box-shadow:
    0 18px 44px rgba(0,0,0,.45),
    0 10px 0 rgba(3,10,25,.55),
    0 0 0 3px rgba(185,253,80,.35),
    0 0 0 1px rgba(255,255,255,.12) inset,
    0 0 90px rgba(34,211,238,.25);
}

/* reduced motion */
@media (prefers-reduced-motion: reduce){
  .btn{ animation:none; }
  .btn::after{ animation:none !important; }
}
















/* ===================== SECTION (scoped) ===================== */
.what-you-get{
  --bgTop:#071a3a; --bgMid:#061531; --bgBot:#050e24;
  --text:#f9fafb; --muted:rgba(226,232,240,.72);

  --cyan:#22d3ee; --blue:#2563eb; --violet:#4f46e5; --sky:#60a5fa; --lime:#b9fd50;
  --border:rgba(148,163,184,.18);

  --maxW:1320px;
  --ease:cubic-bezier(.2,.8,.2,1);
  --font:"Inter","Poppins",system-ui;

  /* rail positioning */
  --railX:22px;

  position:relative;
  padding:150px 6vw;
  background:
    radial-gradient(circle at 15% 25%, rgba(34,211,238,.18), transparent 40%),
    radial-gradient(circle at 85% 75%, rgba(79,70,229,.18), transparent 45%),
    linear-gradient(180deg,var(--bgTop),var(--bgMid),var(--bgBot));
  overflow:hidden;
  isolation:isolate;

  font-family:var(--font);
  color:var(--text);
}

/* box sizing (scoped, no universal selector) */
.what-you-get,
.what-you-get :is(div,section,span,p,h2,h4,button,img){
  box-sizing:border-box;
}

/* animated grid */
.what-you-get::after{
  content:"";
  position:absolute;
  inset:0;
  background:
    linear-gradient(rgba(255,255,255,.035) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,.035) 1px, transparent 1px);
  background-size:64px 64px;
  opacity:.14;
  animation:gridMove 24s linear infinite;
  z-index:0;
}
@keyframes gridMove{ to{background-position:64px 64px} }

/* orbs */
.what-you-get .orb{
  position:absolute;
  width:520px; height:520px;
  filter:blur(140px);
  opacity:.35;
  background:radial-gradient(circle,var(--cyan),transparent 65%);
  animation:floatOrb 14s ease-in-out infinite alternate;
  z-index:0;
}
.what-you-get .orb.right{
  right:-200px; bottom:-200px;
  background:radial-gradient(circle,var(--violet),transparent 65%);
  animation-delay:4s;
}
@keyframes floatOrb{ to{transform:translateY(-120px)} }

/* ===================== LAYOUT ===================== */
.what-you-get .container{
  max-width:var(--maxW);
  margin:auto;
  position:relative;
  z-index:2;
  display:grid;
  grid-template-columns:1.05fr .95fr;
  gap:90px;
  align-items:center; /* ✅ right image centered vs left content */
}
.what-you-get .container > *{ min-width:0; }

/* ===================== HEADING ===================== */
.what-you-get .badge{
  display:inline-flex;
  align-items:center;
  gap:10px;
  padding:10px 22px;
  border-radius:999px;
  background:rgba(15,23,42,.65);
  border:1px solid var(--border);
  backdrop-filter:blur(10px);
  margin-bottom:28px;
  box-shadow:0 20px 60px rgba(0,0,0,.45);
}
.what-you-get .badge::before{
  content:"";
  width:10px; height:10px;
  border-radius:50%;
  background:var(--lime);
  box-shadow:0 0 14px var(--lime);
}
.what-you-get h2{
  font-size:clamp(42px,5vw,68px);
  font-weight:900;
  letter-spacing:-.03em;
  margin:0 0 18px;
}
.what-you-get .subtitle{
  font-size:17px;
  color:var(--muted);
  max-width:560px;
  line-height:1.6;
  margin:0;
}
.what-you-get .divider{
  margin-top:26px;
  height:2px; width:180px;
  background:linear-gradient(90deg,var(--cyan),var(--violet),transparent);
  border-radius:999px;
  box-shadow:0 0 18px rgba(34,211,238,.18);
}

/* ===================== LEFT: RAIL LIST ===================== */
.what-you-get .holoRail{
  margin-top:54px;
  position:relative;
  padding-top:6px;
}
.what-you-get .holoRail::before{
  content:"";
  position:absolute;
  left:var(--railX);
  top:0; bottom:0;
  width:2px;
  background:linear-gradient(180deg,
    transparent,
    rgba(34,211,238,.55),
    rgba(79,70,229,.55),
    transparent
  );
  opacity:.72;
  filter:drop-shadow(0 0 12px rgba(34,211,238,.16));
}

/* each row: 2 columns => prevents overlap */
.what-you-get .featureRow{
  position:relative;
  display:grid;
  grid-template-columns:96px 1fr;
  gap:14px;
  padding:18px 10px;
  margin:10px 0;
  border-bottom:1px solid rgba(148,163,184,.14);
  transition:.55s var(--ease);
  transform-style:preserve-3d;
  overflow:hidden;
  border-radius:18px;
}

/* zig-zag */
.what-you-get .featureRow:nth-child(even){ margin-left:clamp(10px, 3.8vw, 70px); }
.what-you-get .featureRow:nth-child(odd){ margin-right:clamp(8px, 2.8vw, 40px); }

/* hover glow */
.what-you-get .featureRow::after{
  content:"";
  position:absolute;
  inset:-1px 0 -1px 0;
  background:
    radial-gradient(420px 220px at var(--mx, 20%) var(--my, 50%),
      rgba(34,211,238,.16),
      transparent 60%
    );
  opacity:0;
  transition:.55s var(--ease);
  pointer-events:none;
}
.what-you-get .featureRow:hover::after{ opacity:1; }

/* gutter */
.what-you-get .gutter{ position:relative; min-height:62px; }
.what-you-get .featureDot{
  position:absolute;
  left:calc(var(--railX) - 6px);
  top:22px;
  width:14px; height:14px;
  border-radius:50%;
  background:linear-gradient(135deg,var(--cyan),var(--sky));
  box-shadow:0 0 0 7px rgba(34,211,238,.10), 0 0 30px rgba(34,211,238,.22);
}
.what-you-get .connector{
  position:absolute;
  left:calc(var(--railX) + 10px);
  top:28px;
  width:34px;
  height:2px;
  background:linear-gradient(90deg, rgba(34,211,238,.0), rgba(34,211,238,.55), rgba(79,70,229,.55));
  opacity:.72;
}
.what-you-get .featureIcon{
  position:absolute;
  left:calc(var(--railX) + 30px);
  top:8px;
  width:46px; height:46px;
  border-radius:16px;
  display:grid;
  place-items:center;
  font-size:20px;
  background:linear-gradient(180deg, rgba(34,211,238,.18), rgba(79,70,229,.10));
  border:1px solid rgba(34,211,238,.18);
  box-shadow:
    0 22px 70px rgba(34,211,238,.10),
    inset 0 0 0 1px rgba(255,255,255,.04);
  transform:translateZ(18px);
  transition:.55s var(--ease);
}

/* content */
.what-you-get .fcopy{ padding-right:10px; min-width:0; }
.what-you-get .featureTitle{
  margin:0 0 6px;
  font-size:18px;
  letter-spacing:-.01em;
  line-height:1.22;
  word-break:break-word;
}
.what-you-get .featureDesc{
  margin:0;
  font-size:14.6px;
  color:var(--muted);
  line-height:1.55;
  max-width:560px;
  word-break:break-word;
}

/* hover lift */
.what-you-get .featureRow:hover{
  transform:translateY(-6px);
  border-bottom-color:rgba(34,211,238,.22);
}
.what-you-get .featureRow:hover .featureIcon{
  transform:translateZ(18px) translateY(-2px) scale(1.06);
  box-shadow:0 30px 90px rgba(34,211,238,.16);
}
.what-you-get .featureRow:hover .featureTitle{
  background:linear-gradient(135deg,var(--cyan),var(--sky));
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
}

/* ✅ AUTO-HOVER (continuous) — same as hover */
.what-you-get .featureRow.is-active::after{ opacity:1; }
.what-you-get .featureRow.is-active{
  transform:translateY(-6px);
  border-bottom-color:rgba(34,211,238,.22);
}
.what-you-get .featureRow.is-active .featureIcon{
  transform:translateZ(18px) translateY(-2px) scale(1.06);
  box-shadow:0 30px 90px rgba(34,211,238,.16);
}
.what-you-get .featureRow.is-active .featureTitle{
  background:linear-gradient(135deg,var(--cyan),var(--sky));
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
}

/* ===================== REVEAL (from LEFT) ===================== */
.what-you-get .reveal{
  opacity:0;
  transform:translateX(-34px);
  filter:blur(6px);
  transition:
    opacity .95s var(--ease),
    transform .95s var(--ease),
    filter .95s var(--ease);
  transition-delay:var(--d, 0s);
}
.what-you-get .reveal.in{
  opacity:1;
  transform:translateX(0);
  filter:blur(0);
}

/* ===================== RIGHT: ONLY IMAGE (no movement) ===================== */
.what-you-get .visual{
  display:flex;
  justify-content:center;
  align-items:center;
}

.what-you-get .preview{
  width:100%;
  border-radius:28px;
  overflow:hidden;
  border:1px solid rgba(148,163,184,.18);
  background:linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.01));
  box-shadow:0 70px 200px rgba(0,0,0,.65);
  position:relative;
}

.what-you-get .preview::before{
  content:"";
  position:absolute;
  inset:-60%;
  background:
    radial-gradient(circle at 30% 30%, rgba(185,253,80,.18), transparent 45%),
    radial-gradient(circle at 70% 70%, rgba(34,211,238,.16), transparent 46%);
  opacity:.60;
  pointer-events:none;
}

.what-you-get .preview img{
  width:100%;
  height:clamp(460px, 66vh, 820px); /* ✅ big image */
  object-fit:cover;
  display:block;
  transform:none; /* ✅ no moving */
}

.what-you-get .previewLabel{
  position:absolute;
  top:14px; left:14px;
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:8px 10px;
  border-radius:999px;
  border:1px solid rgba(148,163,184,.18);
  background:rgba(2,6,23,.58);
  backdrop-filter:blur(12px);
  font-size:12px;
  font-weight:900;
  z-index:2;
}
.what-you-get .previewLabel::before{
  content:"";
  width:10px; height:10px;
  border-radius:50%;
  background:var(--lime);
  box-shadow:0 0 14px rgba(185,253,80,.35);
}

/* ===================== RESPONSIVE ===================== */
@media(max-width:1050px){
  .what-you-get .container{ gap:46px; }
}
@media(max-width:900px){
  .what-you-get .container{ grid-template-columns:1fr; align-items:start; }
  /* ✅ mobile order: LEFT first, IMAGE below (default DOM order) */
}
@media(max-width:640px){
  .what-you-get{ padding:110px 5.2vw; }
  .what-you-get .featureRow{
    margin-left:0 !important;
    margin-right:0 !important;
    grid-template-columns:84px 1fr;
  }
}

/* reduced motion */
@media (prefers-reduced-motion: reduce){
  .what-you-get::after,
  .what-you-get .orb{ animation:none !important; }
  .what-you-get .reveal{ transition:none !important; opacity:1; transform:none; filter:none; }
}





























/* reset only inside section */
.b3r-premium *{margin:0;padding:0;box-sizing:border-box}

/* container */
.b3r-premium .container{max-width:1200px;margin:auto}

/* ================= HERO ================= */
.b3r-premium .hero{
  text-align:center;
  margin-bottom:clamp(22px, 3vw, 40px);
}
.b3r-premium .pill{
  display:inline-flex;align-items:center;gap:10px;
  padding:8px 18px;border-radius:999px;
  background:rgba(2,6,23,.6);
  border:1px solid var(--border);
  backdrop-filter:blur(10px);
  box-shadow:0 10px 34px rgba(0,0,0,.45), 0 0 0 1px rgba(255,255,255,.03) inset;
  font-size:13px;
  letter-spacing:.3px;
}
.b3r-premium .dot{
  width:10px;height:10px;border-radius:50%;
  background:linear-gradient(180deg,#4ade80,#22c55e);
  box-shadow:0 0 12px rgba(34,197,94,1);
}
.b3r-premium .hero h1{
  font-size:clamp(30px,5vw,72px);
  font-weight:900;
  letter-spacing:-1px;
  margin-top:14px;
  text-shadow:0 14px 34px rgba(0,0,0,.55);
  line-height:1.05;
}
.b3r-premium .hero h1 span{
  background:linear-gradient(90deg,var(--cyan),#60a5fa,var(--lime));
  -webkit-background-clip:text;
  color:transparent;
}
.b3r-premium .hero-underline{
  margin:clamp(10px, 1.8vw, 16px) auto 0;
  width:min(520px, 88%);
  height:3px;
  border-radius:999px;
  background:linear-gradient(90deg,
    rgba(34,211,238,0),
    rgba(34,211,238,.75),
    rgba(37,99,235,.70),
    rgba(185,253,80,.55),
    rgba(34,211,238,0)
  );
  box-shadow:0 0 18px rgba(34,211,238,.14);
}
.b3r-premium .hero p{
  margin-top:10px;
  color:var(--muted);
  max-width:680px;
  margin-inline:auto;
  font-size:15px;
  line-height:1.65;
}

/* ================= GRID ================= */
.b3r-premium .grid{
  display:grid;
  grid-template-columns:minmax(0,1.25fr) minmax(0,.85fr);
  gap:clamp(16px, 4vw, 46px);
  align-items:start;
}

/* ================= LEFT: FLOW ================= */
.b3r-premium .flow{position:relative;padding-left:18px}
.b3r-premium .flow-rail{
  position:absolute;left:0;top:62px;bottom:0;width:6px;border-radius:999px;
  background:linear-gradient(180deg, rgba(34,211,238,.85), rgba(99,102,241,.28), transparent 92%);
  box-shadow:0 0 24px rgba(34,211,238,.16);
  opacity:.78;
}
.b3r-premium .flow-steps{display:flex;flex-direction:column;gap:14px;margin-top:6px}
.b3r-premium .flow-step{
  display:grid;
  grid-template-columns:64px 1fr;
  gap:14px;
  align-items:stretch;
  opacity:1;
  transform:none;
  transition:.55s var(--ease);
}
.b3r-premium .flow-step:not(.active){opacity:.88}
.b3r-premium .node{
  width:64px;display:flex;justify-content:center;position:relative;
}
.b3r-premium .node:before{
  content:"";position:absolute;top:10px;bottom:10px;width:2px;
  background:linear-gradient(180deg, rgba(34,211,238,.6), rgba(99,102,241,.18));
  border-radius:999px;opacity:.55;
}
.b3r-premium .badge{
  width:52px;height:52px;border-radius:18px;
  display:flex;align-items:center;justify-content:center;
  background:
    radial-gradient(circle at 20% 15%, rgba(34,211,238,.35), rgba(37,99,235,.12) 60%),
    linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
  border:1px solid rgba(34,211,238,.22);
  box-shadow:0 18px 60px rgba(0,0,0,.35), 0 0 26px rgba(34,211,238,.16);
  backdrop-filter:blur(10px);
}
.b3r-premium .flow-step.active .badge{
  border-color:rgba(185,253,80,.22);
  box-shadow:0 24px 80px rgba(0,0,0,.38), 0 0 40px rgba(34,211,238,.34);
}
.b3r-premium .badge svg{width:22px;height:22px;stroke:white;stroke-width:2.4;fill:none;opacity:.95}

.b3r-premium .flow-card{
  position:relative;
  background:
    radial-gradient(420px 220px at 18% 18%, rgba(34,211,238,.12), transparent 62%),
    radial-gradient(420px 240px at 95% 10%, rgba(185,253,80,.08), transparent 55%),
    linear-gradient(180deg,var(--glassA),var(--glassB));
  border:1px solid var(--border);
  border-radius:22px;
  padding:16px 16px;
  backdrop-filter:blur(14px);
  box-shadow:0 22px 70px rgba(0,0,0,.34), 0 0 0 1px rgba(255,255,255,.03) inset;
  overflow:hidden;
}
.b3r-premium .flow-card:before{
  content:"";position:absolute;inset:0;
  background:linear-gradient(90deg, rgba(34,211,238,.18), rgba(99,102,241,.12), rgba(185,253,80,.14));
  opacity:0;transition:.5s var(--ease);
}
.b3r-premium .flow-step.active .flow-card:before{opacity:.12}

.b3r-premium .toprow{display:flex;align-items:center;justify-content:space-between;gap:12px}
.b3r-premium .flow-card h3{font-size:15px;font-weight:900;letter-spacing:.2px}
.b3r-premium .step-tag{
  font-size:11px;font-weight:900;letter-spacing:.35px;
  padding:7px 10px;border-radius:999px;
  color:rgba(248,250,252,.92);
  background:rgba(2,6,23,.45);
  border:1px solid rgba(255,255,255,.10);
  box-shadow:0 12px 30px rgba(0,0,0,.28);
}
.b3r-premium .flow-card p{margin-top:6px;color:var(--muted);font-size:13.5px;line-height:1.6}

.b3r-premium .progress{
  margin-top:12px;height:8px;border-radius:999px;
  background:rgba(255,255,255,.05);
  border:1px solid rgba(255,255,255,.08);
  overflow:hidden;
}
.b3r-premium .progress > span{
  display:block;height:100%;width:0%;
  border-radius:999px;
  background:linear-gradient(90deg, var(--cyan), #60a5fa, var(--lime));
  box-shadow:0 0 18px rgba(34,211,238,.18);
}
.b3r-premium .flow-step.active .progress > span{animation:fillbar 1.35s var(--ease) forwards}
@keyframes fillbar{to{width:100%}}

.b3r-premium .growth-note{
  margin-top:16px;padding:18px 20px;border-radius:24px;
  background:
    radial-gradient(460px 240px at 20% 20%, rgba(34,211,238,.14), transparent 62%),
    radial-gradient(380px 260px at 110% 120%, rgba(37,99,235,.18), transparent 60%),
    linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02));
  border:1px solid rgba(34,211,238,.14);
  box-shadow:0 20px 70px rgba(0,0,0,.42), 0 0 0 1px rgba(255,255,255,.04) inset;
}
.b3r-premium .growth-note h2{
  font-size:clamp(18px,2.1vw,24px);
  font-weight:900;letter-spacing:-.2px;line-height:1.25;
  background:linear-gradient(90deg,#e0f2fe,var(--cyan),#93c5fd,var(--blue),#e0f2fe);
  background-size:260% 100%;
  -webkit-background-clip:text;color:transparent;
  text-shadow:0 14px 30px rgba(0,0,0,.52);
  animation:growthTextShift 2.9s ease-in-out infinite;
}
@keyframes growthTextShift{0%,100%{background-position:0% 50%}50%{background-position:100% 50%}}
.b3r-premium .growth-underline{
  margin-top:10px;width:min(340px, 92%);height:2px;border-radius:999px;
  background:linear-gradient(90deg, rgba(34,211,238,0), rgba(34,211,238,.75), rgba(37,99,235,.65), rgba(37,99,235,0));
  box-shadow:0 0 18px rgba(34,211,238,.10);
}

/* ================= RIGHT SIDE ================= */
.b3r-premium .side{display:flex;flex-direction:column;gap:18px;align-items:flex-end}
.b3r-premium .token,.b3r-premium .freecard{opacity:1 !important;transform:none !important}

/* PREMIUM CARD */
.b3r-premium .token{
  position:relative;width:min(460px,100%);
  padding:28px;border-radius:28px;
  background:
    radial-gradient(520px 280px at 20% 10%, rgba(251,191,36,.14), transparent 60%),
    radial-gradient(520px 300px at 90% 90%, rgba(179,8,210,.10), transparent 60%),
    linear-gradient(180deg,var(--glassA),var(--glassB));
  border:1px solid rgba(251,191,36,.26);
  backdrop-filter:blur(22px);
  box-shadow:0 60px 150px rgba(0,0,0,.60), 0 0 44px rgba(251,191,36,.18), 0 0 0 1px rgba(255,255,255,.04) inset;
  transform-style:preserve-3d;overflow:hidden;
}
.b3r-premium .token:before{
  content:"";position:absolute;inset:-2px;
  background:conic-gradient(from 220deg, rgba(251,191,36,.22), rgba(34,211,238,.14), rgba(99,102,241,.14), rgba(251,191,36,.22));
  filter:blur(18px);opacity:.25;pointer-events:none;
}
.b3r-premium .free-badge{
  position:absolute;top:14px;right:14px;
  padding:6px 14px;border-radius:999px;
  font-size:12px;font-weight:900;color:#1f1300;
  background:linear-gradient(180deg,#fde68a,#fbbf24);
  box-shadow:0 10px 25px rgba(251,191,36,.22);
}
.b3r-premium .token-top{display:flex;align-items:center;gap:12px;margin-bottom:16px}
.b3r-premium .icon-bubble{
  width:40px;height:40px;border-radius:14px;
  display:flex;align-items:center;justify-content:center;
  background:linear-gradient(180deg, rgba(251,191,36,.22), rgba(251,191,36,.08));
  border:1px solid rgba(251,191,36,.22);
  box-shadow:0 14px 40px rgba(0,0,0,.28);
}
.b3r-premium .icon-bubble svg{width:18px;height:18px;fill:#fbbf24}
.b3r-premium .token-top h3{font-size:16px;font-weight:900;letter-spacing:.2px}
.b3r-premium .premium-title{
  font-size:36px;font-weight:900;line-height:1.08;
  background:linear-gradient(90deg,#fff7d6,#fde68a,#fbbf24,#f59e0b);
  -webkit-background-clip:text;color:transparent;
  margin-bottom:8px;text-shadow:0 16px 30px rgba(0,0,0,.35);
}
.b3r-premium .premium-sub{font-size:14.8px;color:rgba(226,232,240,.92);margin-bottom:18px;line-height:1.55}
.b3r-premium .token ul{list-style:none;margin-bottom:18px}
.b3r-premium .token li{display:flex;align-items:flex-start;gap:12px;margin-bottom:12px;font-size:14px;color:rgba(248,250,252,.92)}
.b3r-premium .tick{
  width:20px;height:20px;border-radius:50%;
  background:linear-gradient(180deg,#fde68a,#fbbf24);
  display:flex;align-items:center;justify-content:center;flex:0 0 auto;
  box-shadow:0 10px 24px rgba(251,191,36,.20);
}
.b3r-premium .tick svg{width:12px;height:12px;stroke:#1f1300;stroke-width:3;fill:none}
.b3r-premium .token button{
  width:100%;padding:14px;border-radius:18px;border:none;cursor:pointer;
  font-weight:900;font-size:15px;color:#1f1300;
  background:linear-gradient(90deg,#fff1a6,#fde68a,#fbbf24,#f59e0b);
  box-shadow:0 22px 60px rgba(251,191,36,.45);
  display:flex;align-items:center;justify-content:center;gap:10px;
  position:relative;overflow:hidden;
}
.b3r-premium .token button:before{
  content:"";position:absolute;inset:0;
  background:linear-gradient(90deg, transparent, rgba(255,255,255,.28), transparent);
  transform:translateX(-120%);transition:.7s var(--ease);
}
.b3r-premium .token button:hover:before{transform:translateX(120%)}
.b3r-premium .token button:hover{filter:brightness(1.06)}
.b3r-premium .token button svg{width:18px;height:18px;stroke:#1f1300;stroke-width:2.5;fill:none}

/* FREE CARD */
.b3r-premium .freecard{
  position:relative;width:min(460px,100%);
  padding:26px;border-radius:28px;
  background:
    radial-gradient(520px 280px at 18% 12%, rgba(34,211,238,.16), transparent 60%),
    radial-gradient(520px 300px at 90% 10%, rgba(185,253,80,.11), transparent 55%),
    linear-gradient(180deg,var(--glassA),var(--glassB));
  border:1px solid rgba(34,211,238,.22);
  backdrop-filter:blur(22px);
  box-shadow:0 58px 140px rgba(0,0,0,.55), 0 0 44px rgba(34,211,238,.14), 0 0 0 1px rgba(255,255,255,.04) inset;
  overflow:hidden;
}
.b3r-premium .free-badge2{
  position:absolute;top:14px;right:14px;
  padding:6px 14px;border-radius:999px;font-size:12px;font-weight:900;color:#001018;
  background:linear-gradient(90deg, rgba(34,211,238,1), rgba(185,253,80,1));
  box-shadow:0 10px 25px rgba(34,211,238,.18);
}
.b3r-premium .free-top{display:flex;align-items:center;gap:12px;margin-bottom:14px}
.b3r-premium .icon-bubble2{
  width:40px;height:40px;border-radius:14px;
  display:flex;align-items:center;justify-content:center;
  background:linear-gradient(180deg, rgba(34,211,238,.18), rgba(34,211,238,.07));
  border:1px solid rgba(34,211,238,.22);
  box-shadow:0 14px 40px rgba(0,0,0,.28);
}
.b3r-premium .icon-bubble2 svg{width:18px;height:18px;fill:none;stroke:rgba(248,250,252,.95);stroke-width:2.2}
.b3r-premium .free-top h3{font-size:16px;font-weight:900;letter-spacing:.2px}
.b3r-premium .free-big{display:flex;align-items:baseline;gap:10px;margin:10px 0 10px}
.b3r-premium .free-big .num{
  font-size:46px;font-weight:900;line-height:1;
  background:linear-gradient(90deg, var(--cyan), #60a5fa, var(--lime));
  -webkit-background-clip:text;color:transparent;
  text-shadow:0 14px 28px rgba(0,0,0,.35);
}
.b3r-premium .free-big .unit{font-size:14px;font-weight:800;color:rgba(226,232,240,.88)}
.b3r-premium .free-sub{color:rgba(226,232,240,.88);font-size:14px;line-height:1.55;margin-bottom:16px}
.b3r-premium .freecard ul{list-style:none;margin-bottom:16px}
.b3r-premium .freecard li{display:flex;align-items:flex-start;gap:12px;margin-bottom:12px;font-size:14px;color:rgba(248,250,252,.92)}
.b3r-premium .tick2{
  width:20px;height:20px;border-radius:50%;
  background:linear-gradient(180deg, rgba(34,211,238,1), rgba(96,165,250,1));
  display:flex;align-items:center;justify-content:center;flex:0 0 auto;
  box-shadow:0 12px 30px rgba(34,211,238,.18);
}
.b3r-premium .tick2 svg{width:12px;height:12px;stroke:#001018;stroke-width:3;fill:none}
.b3r-premium .freecard button{
  width:100%;padding:13px 14px;border-radius:18px;
  border:1px solid rgba(34,211,238,.22);cursor:pointer;
  font-weight:900;font-size:15px;color:rgba(248,250,252,.92);
  background:linear-gradient(180deg, rgba(34,211,238,.18), rgba(37,99,235,.10));
  box-shadow:0 20px 60px rgba(34,211,238,.18);
  display:flex;align-items:center;justify-content:center;gap:10px;
  position:relative;overflow:hidden;
}
.b3r-premium .freecard button:before{
  content:"";position:absolute;inset:0;
  background:linear-gradient(90deg, transparent, rgba(255,255,255,.22), transparent);
  transform:translateX(-120%);transition:.7s var(--ease);
}
.b3r-premium .freecard button:hover:before{transform:translateX(120%)}
.b3r-premium .freecard button:hover{filter:brightness(1.06)}
.b3r-premium .freecard button svg{width:18px;height:18px;stroke:rgba(248,250,252,.92);stroke-width:2.4;fill:none}

/* ================= RESPONSIVE ================= */
@media(max-width:980px){
  .b3r-premium .grid{grid-template-columns:1fr}
  .b3r-premium .side{align-items:center}
  .b3r-premium .flow-rail{top:58px}
}
@media(max-width:520px){
  .b3r-premium{padding:clamp(16px,4vw,22px)}
  .b3r-premium .flow{padding-left:14px}
  .b3r-premium .flow-step{grid-template-columns:56px 1fr}
  .b3r-premium .badge{width:48px;height:48px;border-radius:16px}
  .b3r-premium .token,.b3r-premium .freecard{padding:22px}
  .b3r-premium .premium-title{font-size:32px}
  .b3r-premium .free-big .num{font-size:42px}
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce){
  .b3r-premium .growth-note h2{animation:none}
  .b3r-premium .flow-step.active .progress > span{animation:none}
}

/* ===============================
   YOUR ROOT TOKENS (UNCHANGED)
=============================== */
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

  --navH: clamp(64px, 6.2vw, 78px);
  --r: clamp(18px, 2.2vw, 26px);

  --chatA:rgba(10,28,78,.78);
  --chatB:rgba(6,14,40,.94);

  --shadowDeep: 0 34px 110px rgba(0,0,0,0.46);
  --shadowBtn: 0 18px 44px rgba(0,0,0,.38);
  --shadowBtn2: 0 28px 80px rgba(37,99,235,.18);

  --pagePad: clamp(12px, 3.6vw, 64px);
  --maxW: 1320px;

  --s1: clamp(8px, 1.0vw, 12px);
  --s2: clamp(10px, 1.2vw, 14px);
  --s3: clamp(12px, 1.4vw, 16px);
  --s4: clamp(14px, 1.8vw, 18px);
  --s5: clamp(16px, 2.2vw, 22px);
  --s6: clamp(18px, 2.6vw, 26px);
  --s7: clamp(22px, 3.2vw, 34px);
  --s8: clamp(26px, 4.2vw, 48px);

  --ease: cubic-bezier(.2,.8,.2,1);

  --fontSans: "Poppins","Inter",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;

  /* ✅ icon gradient */
  --iconGrad: linear-gradient(135deg, rgba(34, 211, 238, 1), rgba(96, 165, 250, 1));

  /* ✅ dark-blue for popbox text */
  --deepBlueText: #071a3a;
}

/* ===============================
   SAFE DROP-IN SECTION ONLY
=============================== */
.sb-whyfaq{
  font-family: var(--fontSans);
  color: var(--text);
  position: relative;
  isolation: isolate;
}
.sb-whyfaq::before{
  content:"";
  position:absolute; inset:0;
  z-index:-2;
  background:
    radial-gradient(1200px 700px at 14% 18%, rgba(34,211,238,.16), transparent 60%),
    radial-gradient(1000px 700px at 82% 24%, rgba(96,165,250,.10), transparent 58%),
    radial-gradient(900px 520px at 60% 90%, rgba(79,70,229,.10), transparent 62%),
    linear-gradient(180deg, var(--bgTop), var(--bgMid) 45%, var(--bgBot));
}
.sb-whyfaq::after{
  content:"";
  position:absolute; inset:0;
  z-index:-1;
  opacity:.26;
  background:
    radial-gradient(circle at 20% 20%, rgba(255,255,255,.08), transparent 55%),
    radial-gradient(circle at 80% 30%, rgba(255,255,255,.06), transparent 55%);
  pointer-events:none;
}

/* container */
.sb-whyfaq .wrap{
  max-width: var(--maxW);
  margin: 0 auto;
  padding: calc(var(--s8) + 6px) var(--pagePad);
}

/* ===============================
   MAIN CENTER HEADINGS
=============================== */
.sb-whyfaq .main-head{
  text-align:center;
  margin: 0 auto var(--s8);
  max-width: 980px;
}
.sb-whyfaq .top-pill{
  display:inline-flex;
  align-items:center;
  gap: 10px;
  padding: 10px 16px;
  border-radius: 999px;
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(148,163,184,.20);
  box-shadow: 0 16px 46px rgba(0,0,0,.32);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  margin-bottom: var(--s6);
}
.sb-whyfaq .top-pill .dot{
  width: 10px; height: 10px;
  border-radius: 50%;
  background: var(--lime);
  box-shadow: 0 0 18px rgba(185,253,80,.55);
}
.sb-whyfaq .top-pill span{
  font-weight: 600;
  color: rgba(226,232,240,.88);
  font-size: 14px;
  letter-spacing: .01em;
}

.sb-whyfaq .main-head h2{
  margin:0;
  font-weight:900;
  letter-spacing:-.03em;
  line-height:1.04;
  font-size: clamp(34px, 4.6vw, 72px);
  color: #ffffff;
  text-shadow:
    0 18px 50px rgba(0,0,0,.42),
    0 0 34px rgba(34,211,238,.10);
}
.sb-whyfaq .main-head p{
  margin: var(--s4) auto 0;
  color: var(--muted);
  max-width: 70ch;
  font-size: clamp(14px, 1.3vw, 16px);
}

.sb-whyfaq .headline-underline{
  width: min(720px, 92%);
  height: 16px;
  margin: var(--s5) auto 0;
  position: relative;
}
.sb-whyfaq .headline-underline::before{
  content:"";
  position:absolute;
  left: 0; right: 0;
  top: 50%;
  height: 2px;
  transform: translateY(-50%);
  border-radius:999px;
  background: linear-gradient(90deg,
    rgba(34,211,238,0),
    rgba(34,211,238,.95),
    rgba(185,253,80,.65),
    rgba(34,211,238,0)
  );
  box-shadow:
    0 0 20px rgba(34,211,238,.28),
    0 0 28px rgba(185,253,80,.14);
}
.sb-whyfaq .headline-underline::after{
  content:"";
  position:absolute;
  left: 12%;
  right: 12%;
  top: calc(50% - 8px);
  height: 16px;
  border-radius:999px;
  background: linear-gradient(90deg,
    rgba(34,211,238,0),
    rgba(34,211,238,.18),
    rgba(185,253,80,.10),
    rgba(34,211,238,0)
  );
  filter: blur(6px);
  opacity: .9;
}

/* ===============================
   KICKER BADGE
=============================== */
.sb-whyfaq .section-kicker{
  display:flex;
  align-items:center;
  gap: 12px;
  margin-bottom: var(--s4);
}
.sb-whyfaq .k-badge{
  display:inline-flex;
  align-items:center;
  gap:10px;
  padding: 10px 12px;
  border-radius: 999px;
  border: 1px solid rgba(148,163,184,.18);
  background: rgba(255,255,255,.04);
  box-shadow: 0 14px 44px rgba(0,0,0,.26);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}
.sb-whyfaq .k-ico{
  width: 34px; height: 34px;
  border-radius: 12px;
  display:grid; place-items:center;
  background: var(--iconGrad);
  color:#061531;
  font-weight:900;
  box-shadow: 0 12px 34px rgba(0,0,0,.35);
}
.sb-whyfaq .k-txt{ display:flex; flex-direction:column; line-height:1.1; }
.sb-whyfaq .k-txt b{ font-size: 13px; letter-spacing:.02em; color: rgba(249,250,251,.92); }
.sb-whyfaq .k-txt span{ font-size: 12px; color: rgba(226,232,240,.70); }

/* ===============================
   WHY GRID
=============================== */
.sb-whyfaq .why-grid{
  display:grid;
  grid-template-columns: 1.10fr .90fr;
  gap: var(--s8);
  align-items:center;
}
.sb-whyfaq .why-left h3{
  margin:0;
  font-weight:900;
  letter-spacing:-.02em;
  line-height:1.06;
  font-size: clamp(22px, 2.2vw, 32px);
  color: rgba(249,250,251,.96);
}
.sb-whyfaq .why-sub{
  margin: var(--s3) 0 var(--s6);
  color: var(--muted);
  max-width: 62ch;
}

/* ===============================
   LEFT CARDS
   ✅ CHANGE: cards appear ONE-BY-ONE from LEFT SIDE
=============================== */
.sb-whyfaq .why-features{
  display:grid;
  grid-template-columns: repeat(2, minmax(0,1fr));
  gap: clamp(16px, 2.2vw, 26px);
  margin-top: var(--s4);
}
@media (max-width: 860px){
  .sb-whyfaq .why-features{ grid-template-columns: 1fr; }
}

/* base */
.sb-whyfaq .why-card{
  display:flex;
  gap: 14px;
  align-items:flex-start;
  padding: clamp(14px, 1.8vw, 18px) clamp(16px, 2vw, 22px);
  border-radius: var(--r);

  border: 1px solid rgba(148,163,184,.22);
  background:
    linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02)),
    linear-gradient(180deg, var(--chatA), var(--chatB));
  box-shadow:
    0 28px 90px rgba(0,0,0,.48),
    inset 0 1px 0 rgba(255,255,255,.07);

  position:relative;
  overflow:hidden;

  transform: translate3d(-28px, 0, 0) scale(.96);
  opacity: 0;
  transition:
    transform .85s var(--ease),
    opacity .85s var(--ease),
    box-shadow .45s var(--ease),
    border-color .45s var(--ease);
  transition-delay: var(--d, 0ms);
}
.sb-whyfaq .why-card.in{
  opacity: 1;
  transform: translate3d(0,0,0) scale(1);
}
.sb-whyfaq .why-card::before{
  content:"";
  position:absolute; inset:0;
  background:
    radial-gradient(520px 260px at 18% 12%, rgba(34,211,238,.10), transparent 60%),
    radial-gradient(520px 260px at 88% 70%, rgba(96,165,250,.08), transparent 62%);
  pointer-events:none;
}
.sb-whyfaq .why-card:hover{
  transform: translate3d(0,-8px,0) scale(1.02);
  border-color: rgba(34,211,238,.30);
  box-shadow:
    0 40px 120px rgba(0,0,0,.62),
    0 0 44px rgba(34,211,238,.12),
    inset 0 1px 0 rgba(255,255,255,.09);
}

/* icon box */
.sb-whyfaq .why-ico{
  width: 48px; height: 48px;
  border-radius: 14px;
  display:grid; place-items:center;
  background: var(--iconGrad);
  color:#061531;
  box-shadow: 0 18px 44px rgba(0,0,0,.45);
  flex: 0 0 auto;
  position:relative;
  z-index:1;
}
.sb-whyfaq .why-ico::after{
  content:"";
  position:absolute; inset:0;
  border-radius: 14px;
  background: radial-gradient(circle at 25% 20%, rgba(255,255,255,.45), transparent 55%);
  pointer-events:none;
}
.sb-whyfaq .why-ico svg{ width: 22px; height: 22px; display:block; }

.sb-whyfaq .why-card h4{
  margin: 0 0 6px;
  font-size: 18px;
  letter-spacing: -.01em;
  position:relative;
  z-index:1;
}
.sb-whyfaq .why-card p{
  margin:0;
  color: var(--muted);
  font-size: 14px;
  line-height: 1.55;
  position:relative;
  z-index:1;
}

/* mini pills */
.sb-whyfaq .why-mini{
  margin-top: var(--s6);
  display:flex;
  gap: var(--s3);
  flex-wrap:wrap;
}
.sb-whyfaq .pill{
  display:inline-flex; align-items:center; gap:10px;
  padding: 10px 12px;
  border-radius: 999px;
  border:1px solid rgba(148,163,184,.18);
  background: rgba(255,255,255,.04);
  color: rgba(226,232,240,.86);
  font-size: 13px;
  box-shadow: 0 14px 44px rgba(0,0,0,.26);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}
.sb-whyfaq .why-mini .dot{
  width:10px; height:10px; border-radius: 50%;
  background: var(--cyan);
  box-shadow: 0 0 16px rgba(34,211,238,.55);
}

/* ===============================
   RIGHT IMAGE + POP BOX
=============================== */
.sb-whyfaq .why-right{ position:relative; }
.sb-whyfaq .why-media{
  position:relative;
  border-radius: var(--r);
  overflow:hidden;
  box-shadow: var(--shadowDeep);
  border: 1px solid rgba(148,163,184,.18);
}
.sb-whyfaq .why-media::before{
  content:"";
  position:absolute; inset:0;
  background:
    radial-gradient(800px 520px at 20% 20%, rgba(34,211,238,.16), transparent 62%),
    radial-gradient(800px 520px at 80% 80%, rgba(96,165,250,.12), transparent 60%);
  pointer-events:none;
  z-index:1;
}
.sb-whyfaq .why-media::after{
  content:"";
  position:absolute; inset:0;
  background: linear-gradient(180deg, rgba(0,0,0,.02), rgba(0,0,0,.55));
  pointer-events:none;
  z-index:1;
}
.sb-whyfaq .why-img{
  width:100%;
  display:block;
  transform: scale(1.04);
  filter: contrast(1.08) saturate(1.10);
}

/* pop */
.sb-whyfaq .popbox{
  position:absolute;
  top: clamp(12px, 2.2vw, 18px);
  right: clamp(12px, 1.6vw, 18px);
  width: min(350px, 94%);
  padding: 14px 14px;
  border-radius: 22px;
  background: rgba(255,255,255,.16);
  border: 1px solid rgba(255,255,255,.24);
  box-shadow:
    0 30px 80px rgba(0,0,0,.55),
    inset 0 1px 0 rgba(255,255,255,.22);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  display:flex;
  gap: 12px;
  align-items:center;
  animation: sbFloat 4.8s ease-in-out infinite;
  z-index:2;
  overflow:hidden;
}
.sb-whyfaq .popbox::before{
  content:"";
  position:absolute; inset:-40px;
  background:
    radial-gradient(circle at 25% 20%, rgba(255,255,255,.40), transparent 45%),
    radial-gradient(circle at 70% 60%, rgba(34,211,238,.22), transparent 52%),
    radial-gradient(circle at 40% 85%, rgba(96,165,250,.18), transparent 55%);
  filter: blur(10px);
  pointer-events:none;
}
.sb-whyfaq .popbox::after{
  content:"";
  position:absolute;
  inset:0;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.16), transparent);
  transform: translateX(-120%);
  animation: popShine 3.6s var(--ease) infinite;
  pointer-events:none;
  opacity:.9;
}
@keyframes popShine{
  0%{transform:translateX(-120%)}
  55%{transform:translateX(120%)}
  100%{transform:translateX(120%)}
}
.sb-whyfaq .pop-ring{
  width: 58px; height: 58px;
  border-radius: 18px;
  background: rgba(255,255,255,.14);
  border: 1px solid rgba(255,255,255,.24);
  box-shadow: 0 18px 40px rgba(0,0,0,.42);
  display:grid; place-items:center;
  position:relative;
}
.sb-whyfaq .pop-ring::before{
  content:"";
  position:absolute; inset:6px;
  border-radius: 14px;
  background: var(--iconGrad);
  opacity:.95;
}
.sb-whyfaq .pop-ring b{
  position:relative;
  font-size: 16px;
  color:#061531;
  font-weight:900;
}
.sb-whyfaq .pop-txt{ display:flex; flex-direction:column; line-height:1.2; position:relative; z-index:1; }
.sb-whyfaq .pop-txt strong{
  font-size: 16px;
  letter-spacing: -.01em;
  color: var(--deepBlueText);
  font-weight:900;
}
.sb-whyfaq .pop-txt span{
  font-size: 13px;
  color: rgba(7,26,58,.78);
  font-weight:600;
}

/* ===============================
   FAQ SECTION
=============================== */
.sb-whyfaq .faq{
  margin-top: var(--s8);
  padding-top: var(--s7);
}
.sb-whyfaq .faq-grid2{
  display:grid;
  grid-template-columns: .95fr 1.05fr;
  gap: var(--s8);
  align-items:start;
}
.sb-whyfaq .faq-left{
  position:sticky;
  top: calc(var(--s7));
}

/* faq image */
.sb-whyfaq .faq-media{
  border-radius: var(--r);
  overflow:hidden;
  border: 1px solid rgba(148,163,184,.18);
  box-shadow: 0 26px 90px rgba(0,0,0,.50);
  position:relative;
}
.sb-whyfaq .faq-media::before{
  content:"";
  position:absolute; inset:0;
  background:
    radial-gradient(900px 520px at 18% 16%, rgba(34,211,238,.14), transparent 62%),
    radial-gradient(900px 520px at 88% 78%, rgba(96,165,250,.12), transparent 60%);
  pointer-events:none;
  z-index:1;
}
.sb-whyfaq .faq-media::after{
  content:"";
  position:absolute; inset:0;
  background: linear-gradient(180deg, rgba(0,0,0,.02), rgba(0,0,0,.55));
  pointer-events:none;
  z-index:1;
}
.sb-whyfaq .faq-img{
  width:100%;
  display:block;
  transform: scale(1.04);
  filter: contrast(1.08) saturate(1.10);
}

/* bubble */
.sb-whyfaq .faq-bubble{
  position:absolute;
  left: 14px;
  bottom: 14px;
  z-index:2;
  background: rgba(255,255,255,.10);
  border: 1px solid rgba(255,255,255,.18);
  border-radius: 18px;
  padding: 12px 14px;
  box-shadow: 0 22px 70px rgba(0,0,0,.55);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  max-width: 92%;
  display:flex;
  gap: 10px;
  align-items:flex-start;
}
.sb-whyfaq .faq-bubble .b-ico{
  width: 38px; height: 38px;
  border-radius: 14px;
  display:grid; place-items:center;
  background: var(--iconGrad);
  box-shadow: 0 18px 44px rgba(0,0,0,.45);
  color:#061531;
  flex:0 0 auto;
}
.sb-whyfaq .faq-bubble .b-ico svg{ width: 18px; height: 18px; }
.sb-whyfaq .faq-bubble b{
  display:block;
  font-size: 13px;
  color: rgba(249,250,251,.92);
}
.sb-whyfaq .faq-bubble span{
  display:block;
  margin-top: 2px;
  font-size: 12px;
  color: rgba(226,232,240,.76);
  line-height:1.35;
}

/* faq list */
.sb-whyfaq .faq-right{
  display:grid;
  gap: var(--s5);
}

/* accordion base */
.sb-whyfaq details.sb-acc{
  border-radius: var(--r);
  border: 1px solid rgba(148,163,184,.18);
  background:
    linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.02)),
    linear-gradient(180deg, var(--chatA), var(--chatB));
  box-shadow:
    0 20px 70px rgba(0,0,0,.38),
    inset 0 1px 0 rgba(255,255,255,.06);
  overflow:hidden;
  transition: transform .45s var(--ease), box-shadow .45s var(--ease), border-color .45s var(--ease);
}

/* ===============================
   ✅ FAQ AUTO SHOW ONE-BY-ONE + ZOOM IN/OUT
   - Each FAQ card enters with "zoom pop"
   - When section enters, JS will pulse each item one-by-one
=============================== */
.sb-whyfaq details.sb-acc{
  opacity: 0;
  transform: translateY(18px) scale(.92);
  transition:
    opacity .75s var(--ease),
    transform .75s var(--ease),
    box-shadow .45s var(--ease),
    border-color .45s var(--ease);
  transition-delay: var(--d, 0ms);
}
.sb-whyfaq details.sb-acc.in{
  opacity: 1;
  transform: translateY(0) scale(1);
}
.sb-whyfaq details.sb-acc[open]{
  transform: translateY(-6px) scale(1);
  border-color: rgba(34,211,238,.26);
  box-shadow:
    0 30px 96px rgba(0,0,0,.60),
    0 0 40px rgba(34,211,238,.10);
}

/* pulse effect class added by JS */
.sb-whyfaq details.sb-acc.pulse{
  animation: faqPulse .92s var(--ease) 1;
}
@keyframes faqPulse{
  0%{ transform: translateY(0) scale(1); }
  45%{ transform: translateY(-10px) scale(1.04); }
  100%{ transform: translateY(0) scale(1); }
}

/* summary */
.sb-whyfaq summary.sb-sum{
  list-style:none;
  cursor:pointer;
  padding: 18px 18px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap: 14px;
  position:relative;
  overflow:hidden;
}
.sb-whyfaq summary.sb-sum::-webkit-details-marker{display:none;}

.sb-whyfaq .qrow{
  display:flex;
  align-items:center;
  gap: 12px;
  font-weight:800;
  color: rgba(249,250,251,.92);
  letter-spacing: -.01em;
}
.sb-whyfaq .qic{
  width: 42px; height: 42px;
  border-radius: 14px;
  display:grid; place-items:center;
  background: var(--iconGrad);
  box-shadow: 0 18px 44px rgba(0,0,0,.45);
  color:#061531;
  flex:0 0 auto;
}
.sb-whyfaq .qic svg{ width: 18px; height: 18px; }

.sb-whyfaq .chev{
  width: 44px; height: 44px;
  border-radius: 14px;
  display:grid; place-items:center;
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.14);
  color: rgba(226,232,240,.92);
  transition: transform .35s var(--ease);
  flex:0 0 auto;
}
.sb-whyfaq details[open] .chev{ transform: rotate(180deg); }

.sb-whyfaq .ans-wrap{ padding: 0 18px 18px; }
.sb-whyfaq .ansBox{
  border-radius: 16px;
  border: 1px solid rgba(255,255,255,.18);
  background: rgba(255,255,255,.12);
  box-shadow:
    inset 0 1px 0 rgba(255,255,255,.18),
    0 16px 44px rgba(0,0,0,.28);
  padding: 14px 14px;
  color: rgba(226,232,240,.90);
  line-height: 1.7;
  font-size: 14px;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}
.sb-whyfaq .divider{
  height:1px;
  background: linear-gradient(90deg, transparent, rgba(148,163,184,.18), transparent);
  margin: 0 18px 12px;
}

/* ripple */
.sb-whyfaq summary.sb-sum .rip{
  position:absolute;
  border-radius:50%;
  transform: scale(0);
  animation: sbRip .75s var(--ease);
  background: rgba(34,211,238,.18);
  pointer-events:none;
}
@keyframes sbRip{ to{ transform: scale(12); opacity:0; } }

/* ===============================
   REVEAL
=============================== */
.sb-whyfaq .reveal{
  opacity:0;
  transform: translateY(18px) scale(.985);
  transition: opacity .85s var(--ease), transform .85s var(--ease);
}
.sb-whyfaq .reveal.in{
  opacity:1;
  transform: translateY(0) scale(1);
}

/* float */
@keyframes sbFloat{
  50%{ transform: translateY(-10px) scale(1.01); }
}

/* ===============================
   RESPONSIVE
=============================== */
@media (max-width: 1040px){
  .sb-whyfaq .why-grid{ grid-template-columns: 1fr; }
  .sb-whyfaq .faq-grid2{ grid-template-columns: 1fr; }
  .sb-whyfaq .faq-left{ position:relative; top:auto; }
}
@media (max-width: 560px){
  .sb-whyfaq .top-pill{ width: 100%; justify-content:center; }
  .sb-whyfaq .popbox{ width: min(360px, 96%); }
  .sb-whyfaq summary.sb-sum{ padding: 16px 14px; }
  .sb-whyfaq .divider{ margin: 0 14px 12px; }
  .sb-whyfaq .ans-wrap{ padding: 0 14px 14px; }
}































/* ================= ROOT (AS YOU PROVIDED) ================= */
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

  --r: clamp(18px, 2.2vw, 26px);

  --shadowDeep: 0 34px 110px rgba(0,0,0,0.46);
  --shadowBtn: 0 18px 44px rgba(0,0,0,.38);
  --shadowBtn2: 0 28px 80px rgba(37,99,235,.18);

  --pagePad: clamp(12px, 3.6vw, 64px);
  --maxW: 1320px;

  --ease: cubic-bezier(.2,.8,.2,1);
  --fontSans: "Poppins","Inter",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;

  --iconGrad: linear-gradient(135deg, rgba(34,211,238,1), rgba(96,165,250,1));
}

/* ✅ NO universal body styles. Everything is scoped inside .apm-scope */
.apm-scope{
  font-family: var(--fontSans);
  color: var(--text);
}

/* box sizing only inside this widget scope */
.apm-scope, .apm-scope *{ box-sizing:border-box; }

/* ================= FOOTER (UNCHANGED) ================= */
.site-footer{
  position:relative;
  margin-top: 20px;
  padding: 26px 0 14px; /* compact */
  background:
    radial-gradient(900px 520px at 15% 0%, rgba(34,211,238,.12), transparent 60%),
    radial-gradient(900px 520px at 85% 0%, rgba(185,253,80,.08), transparent 60%),
    linear-gradient(180deg,var(--bgMid),var(--bgBot));
  border-top:1px solid var(--border);
  overflow:hidden;
  isolation:isolate;
  color: var(--text);
  font-family: var(--fontSans);

  /* ✅ moved marquee vars here (NOT :root) so it won’t affect other pages */
  --adH: clamp(40px, 4vw, 52px);
  --adSpeed: 180s;
  --adPad: clamp(12px, 2.8vw, 22px);
}

.site-footer .aurora{
  position:absolute;
  inset:-35% -25%;
  background:
    radial-gradient(60% 45% at 18% 22%, rgba(34,211,238,.14), transparent 55%),
    radial-gradient(55% 42% at 78% 28%, rgba(185,253,80,.10), transparent 60%),
    radial-gradient(55% 42% at 55% 78%, rgba(79,70,229,.10), transparent 60%);
  filter: blur(14px);
  opacity:.75;
  animation: auroraMove 11s var(--ease) infinite alternate;
  pointer-events:none;
  z-index:0;
}
@keyframes auroraMove{
  0%{transform:translate3d(-1.2%, -1.2%, 0) rotate(-2deg) scale(1.02)}
  100%{transform:translate3d(1.2%, 1.0%, 0) rotate(2deg) scale(1.04)}
}

.site-footer::before{
  content:"";
  position:absolute;
  inset:0;
  background:
    radial-gradient(1200px 600px at 50% 10%, rgba(255,255,255,.05), transparent 55%),
    radial-gradient(900px 520px at 20% 80%, rgba(34,211,238,.06), transparent 60%),
    radial-gradient(900px 520px at 80% 85%, rgba(185,253,80,.05), transparent 60%);
  opacity:.75;
  pointer-events:none;
  z-index:1;
}

.site-footer::after{
  content:"";
  position:absolute;
  inset:0;
  background:
    repeating-linear-gradient(90deg,rgba(255,255,255,.03) 0 1px,transparent 1px 72px),
    repeating-linear-gradient(0deg,rgba(255,255,255,.025) 0 1px,transparent 1px 72px);
  opacity:.22;
  pointer-events:none;
  z-index:1;
}

.footer-wrap{
  width:min(var(--maxW),calc(100% - var(--pagePad)*2));
  margin:auto;
  position:relative;
  z-index:3;
}

/* ================= MAIN GRID ================= */
.footer-main{
  display:grid;
  grid-template-columns: 1.25fr .95fr .85fr;
  gap:18px;
  align-items:start;
}

/* Brand block */
.footer-brand{
  display:flex;
  flex-direction:column;
  gap:12px;
  padding-top:6px;
}

.brand-head{
  display:flex;
  align-items:center;
  gap:12px;
  font-weight:900;
  letter-spacing:.3px;
}

.brand-icon{
  width:44px;
  height:44px;
  border-radius:14px;
  display:grid;
  place-items:center;
  background:
    linear-gradient(180deg,rgba(255,255,255,.17),rgba(255,255,255,.02)),
    linear-gradient(135deg,rgba(34,211,238,.32),rgba(79,70,229,.22));
  box-shadow:
    inset 0 1px 1px rgba(255,255,255,.30),
    inset 0 -6px 12px rgba(0,0,0,.45),
    var(--shadowBtn2);
  position:relative;
  overflow:hidden;
  transform-style:preserve-3d;
}
.brand-icon::after{
  content:"";
  position:absolute;
  inset:-40%;
  background:linear-gradient(135deg, rgba(255,255,255,.20), transparent 60%);
  transform:rotate(25deg) translateX(-30%);
  opacity:.42;
  animation: iconGloss 5.6s var(--ease) infinite;
  pointer-events:none;
}
@keyframes iconGloss{
  0%{transform:rotate(25deg) translateX(-55%)}
  100%{transform:rotate(25deg) translateX(55%)}
}
.brand-icon i{
  background:var(--iconGrad);
  -webkit-background-clip:text;
  color:transparent;
  transform: translateZ(18px);
}

.brand-name{
  display:flex;
  align-items:center;
  gap:10px;
  line-height:1.1;
}
.brand-name .logo-img{
  width:22px;
  height:22px;
  border-radius:7px;
  object-fit:cover;
  border:1px solid rgba(148,163,184,.22);
  box-shadow: 0 10px 22px rgba(0,0,0,.25);
}
.brand-name .title{
  position:relative;
  display:inline-block;
  padding-bottom:6px;
  font-size: clamp(1.1rem, .9vw + 1rem, 1.28rem);
}
.brand-name .title::after{
  content:"";
  position:absolute;
  left:0;
  bottom:0;
  width:72px;
  height:3px;
  border-radius:999px;
  background:linear-gradient(90deg,var(--cyan),var(--lime));
  box-shadow:0 10px 26px rgba(34,211,238,.16);
  opacity:.95;
}
.brand-name .title::before{
  content:"";
  position:absolute;
  left:-18px;
  bottom:-1px;
  width:54px;
  height:6px;
  border-radius:999px;
  filter:blur(1px);
  animation:shineSweep 4.2s var(--ease) infinite;
  pointer-events:none;
  opacity:.42;
}
@keyframes shineSweep{
  0%{transform:translateX(-30px); opacity:0}
  35%{opacity:.5}
  100%{transform:translateX(140px); opacity:0}
}

.footer-desc{
  color:var(--muted);
  font-size:.95rem;
  line-height:1.62;
  max-width:58ch;
  margin-top:-2px;
}

.footer-meta{
  display:flex;
  flex-wrap:wrap;
  gap:10px;
}
.footer-meta span{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:8px 12px;
  border-radius:999px;
  background:linear-gradient(180deg,rgba(255,255,255,.07),rgba(255,255,255,.02));
  border:1px solid rgba(148,163,184,.18);
  backdrop-filter:blur(12px);
  box-shadow:0 10px 28px rgba(0,0,0,.20);
  font-size:.9rem;
  position:relative;
  overflow:hidden;
}
.footer-meta i{color:var(--cyan)}

/* Columns */
.footer-col{ padding-top:6px; }
.footer-title{
  font-weight:900;
  letter-spacing:.02em;
  margin:0 0 10px;
  font-size:1rem;
}
.footer-title .line{
  display:block;
  width:54px;
  height:3px;
  border-radius:999px;
  margin-top:8px;
  background:linear-gradient(90deg, rgba(34,211,238,.7), rgba(185,253,80,.55));
  box-shadow:0 10px 22px rgba(34,211,238,.10);
  opacity:.95;
}

.footer-links{
  list-style:none;
  padding:0;
  margin:0;
  display:grid;
  gap:8px;
}
.footer-links a{
  color:rgba(226,232,240,.86);
  text-decoration:none;
  display:inline-flex;
  align-items:center;
  gap:10px;
  padding:9px 10px;
  border-radius:14px;
  border:1px solid transparent;
  background:transparent;
  transition: transform .35s var(--ease), background .35s var(--ease), border-color .35s var(--ease);
  position:relative;
}
.footer-links a i{
  width:18px;
  color:rgba(34,211,238,.9);
  filter: drop-shadow(0 10px 16px rgba(0,0,0,.22));
}
.footer-links a:hover{
  transform:translateY(-2px);
  background:rgba(255,255,255,.035);
  border-color:rgba(148,163,184,.18);
}
.footer-links a:focus-visible{
  outline:2px solid rgba(185,253,80,.55);
  outline-offset:4px;
}

/* ================= BOTTOM (SOCIAL CENTER) ================= */
.footer-bottom{
  margin-top:16px;
  padding-top:14px;
  border-top:1px solid var(--border);
  position:relative;
}
.footer-bottom::before{
  content:"";
  position:absolute;
  top:-1px;
  left:0;
  right:0;
  height:1px;
  background:linear-gradient(90deg, transparent, rgba(34,211,238,.42), rgba(185,253,80,.30), transparent);
  opacity:.85;
  pointer-events:none;
}

.footer-social-row{
  display:flex;
  justify-content:center;
  gap:12px;
  margin-bottom:10px;
  perspective:900px;
}
.footer-social-row a:nth-child(1){ --brand:#1877F2; }
.footer-social-row a:nth-child(2){ --brand:#E1306C; }
.footer-social-row a:nth-child(3){ --brand:#FF0000; }
.footer-social-row a:nth-child(4){ --brand:#0A66C2; }

.footer-social-row a{
  width:46px;
  height:46px;
  position:relative;
  display:grid;
  place-items:center;
  border-radius:50%;
  background-color: white;
  border:1px solid rgba(148,163,184,.18);
  text-decoration:none;
  transform-style:preserve-3d;
  transition:transform .35s var(--ease), box-shadow .35s var(--ease), filter .35s var(--ease);
  box-shadow:
    inset 0 1px 2px rgba(255,255,255,.28),
    inset 0 -8px 18px rgba(0,0,0,.55),
    0 18px 44px rgba(0,0,0,.52);
  overflow:hidden;
}
.footer-social-row a::after{
  content:"";
  position:absolute;
  inset:-35%;
  border-radius:50%;
  background: conic-gradient(from 180deg, transparent, rgba(255,255,255,.10), transparent, rgba(255,255,255,.09), transparent);
  filter: blur(10px);
  opacity:0;
  transition: opacity .35s var(--ease);
  pointer-events:none;
}
.footer-social-row a:hover{
  transform:translateY(-5px) scale(1.07) rotateX(14deg);
  box-shadow:
    inset 0 1px 2px rgba(255,255,255,.36),
    inset 0 -10px 22px rgba(0,0,0,.65),
    0 34px 86px rgba(0,0,0,.72);
  filter:drop-shadow(0 18px 30px rgba(0,0,0,.22));
}
.footer-social-row a:hover::after{
  opacity:.7;
  animation: haloSpin 1.8s linear infinite;
}
@keyframes haloSpin{
  0%{transform: rotate(0deg)}
  100%{transform:rotate(360deg)}
}
.footer-social-row i{
  font-size:1.06rem;
  transform:translateZ(18px);
  color:var(--brand);
  text-shadow:0 10px 22px rgba(0,0,0,.42);
}
.footer-social-row a:focus-visible{
  outline:2px solid rgba(185,253,80,.55);
  outline-offset:4px;
}

.footer-bottom-line{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  flex-wrap:wrap;
  font-size:.9rem;
  color:var(--muted);
}
.footer-bottom-line b{color:#fff}

.to-top{
  display:inline-flex;
  align-items:center;
  gap:10px;
  padding:9px 12px;
  border-radius:999px;
  border:1px solid rgba(148,163,184,.18);
  background:rgba(255,255,255,.035);
  color:rgba(226,232,240,.9);
  cursor:pointer;
  font-weight:900;
  text-decoration:none;
  transition: transform .35s var(--ease), background .35s var(--ease), border-color .35s var(--ease);
  white-space:nowrap;
}
.to-top i{color:var(--lime)}
.to-top:hover{
  transform: translateY(-2px);
  background:rgba(255,255,255,.055);
  border-color:rgba(185,253,80,.22);
}
.to-top:focus-visible{
  outline:2px solid rgba(185,253,80,.55);
  outline-offset:4px;
}

@media (max-width: 980px){
  .footer-main{ grid-template-columns: 1fr 1fr; }
}
@media (max-width: 720px){
  .footer-main{ grid-template-columns: 1fr; }
  .footer-bottom-line{ justify-content:center; text-align:center; }
  .to-top{ width:100%; justify-content:center; }
}

/* ==========================================================
   ✅ FOOTER TOP MARQUEE (ATTACHED - FIXED, NO OVERLAP)
========================================================== */
.site-footer .apm-adbar--footer{
  position:absolute;
  top:0; left:0; right:0;
  height: var(--adH);
  display:flex;
  align-items:center;

  overflow-x:hidden;
  overflow-y:visible;

  background:
    radial-gradient(1200px 100px at 18% 50%, rgba(185,253,80,.18), transparent 60%),
    radial-gradient(900px 100px at 82% 50%, rgba(34,211,238,.16), transparent 62%),
    linear-gradient(90deg, rgba(37,99,235,.18), rgba(6,18,43,.94), rgba(34,211,238,.14));

  border-top: 1px solid rgba(148,163,184,.16);
  border-bottom: 1px solid rgba(148,163,184,.14);

  box-shadow: 0 18px 44px rgba(0,0,0,.28);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);

  z-index: 6;
}
.site-footer .apm-adbar--footer::after{
  content:"";
  position:absolute;
  left:0; right:0; bottom:-1px;
  height:1px;
  background: linear-gradient(90deg, transparent, rgba(34,211,238,.45), rgba(185,253,80,.25), transparent);
  opacity:.9;
  pointer-events:none;
}
.site-footer .apm-adwrap{
  width:100%;
  padding-inline: calc(var(--adPad) + env(safe-area-inset-left)) calc(var(--adPad) + env(safe-area-inset-right));
  overflow-x:hidden;
  overflow-y:visible;
  padding-block:2px;
}
.site-footer .footer-wrap{
  padding-top: calc(var(--adH) + 14px);
}

.site-footer .apm-strip{
  display:inline-flex;
  align-items:center;
  white-space:nowrap;
  will-change: transform;
  transform: translateX(0);
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-rendering: optimizeLegibility;
  animation: apm-scroll var(--adSpeed) linear infinite;
}
.site-footer .apm-track{
  display:inline-flex;
  align-items:center;
  gap: clamp(10px, 1.4vw, 18px);
  padding-right: clamp(10px, 1.4vw, 18px);
}

.site-footer .apm-adlink{
  display:inline-flex;
  align-items:center;
  gap:10px;
  padding:7px 12px;
  border-radius:999px;
  border:1px solid rgba(34,211,238,.45);
  background: linear-gradient(135deg, rgba(8,16,38,.78), rgba(8,16,38,.45));
  box-shadow:
    0 18px 48px rgba(0,0,0,.30),
    0 0 0 4px rgba(34,211,238,.08),
    inset 0 1px 0 rgba(255,255,255,.06);
  color: rgba(255,255,255,.98);
  text-decoration:none;
  font-weight:800;
  letter-spacing:.01em;
  font-size: clamp(.80rem, .55vw + .62rem, .98rem);
  line-height:1;
  transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
  user-select:none;
  backface-visibility:hidden;
}
.site-footer .apm-adlink i{ color: var(--cyan); font-size:14px; }
.site-footer .apm-adlink span{ text-shadow:0 2px 10px rgba(0,0,0,.45); }

@media (hover:hover){
  .site-footer .apm-adbar--footer:hover .apm-strip{ animation-play-state: paused; }
  .site-footer .apm-adlink:hover{
    transform: translateY(-1px);
    border-color: rgba(148,163,184,.18);
    box-shadow:
      0 14px 34px rgba(0,0,0,.24),
      inset 0 1px 0 rgba(255,255,255,.06);
  }
  .site-footer .apm-adlink:hover i{ color: var(--lime); }
}

.site-footer .apm-adsep{
  width:7px; height:7px;
  border-radius:99px;
  background: rgba(34,211,238,.85);
  box-shadow: 0 0 0 4px rgba(34,211,238,.10), 0 10px 22px rgba(0,0,0,.22);
  flex:0 0 auto;
}

@keyframes apm-scroll{
  0%{ transform: translateX(0); }
  100%{ transform: translateX(-50%); }
}

@media (prefers-reduced-motion: reduce){
  .site-footer .apm-strip{ animation:none; }
}

@media (max-width: 480px){
  .site-footer{ --adH: 44px; }
  .site-footer .apm-adlink{ padding:6px 10px; gap:8px; }
  .site-footer .apm-adlink i{ font-size:13px; }
}
  </style>
</head>

<body>

<!-- NAVBAR -->
<header class="apm-nav" id="apmNav">
  <div class="apm-nav-inner">

    <a class="apm-logo" href="#">
      <div class="apm-logo-mark">
        <img src="Assets/busineger%20logo%202%20png.png" alt="Busineger logo" />
      </div>
      <div class="apm-logo-text">
        <strong>Busineger</strong>
        <small>BUSINESS GROWTH Strategy</small>
      </div>
    </a>

    <div class="apm-nav-right">
      <!-- ✅ Mobile Toggle Button -->
      <button class="apm-burger apm-press" id="apmBurger"
              type="button"
              aria-label="Open menu"
              aria-controls="apmNavMenu"
              aria-expanded="false">
        <span class="apm-burger-ic" aria-hidden="true"></span>
      </button>

      <!-- ✅ Menu (desktop inline, mobile dropdown) -->
      <div class="apm-menu" id="apmNavMenu">
        <a class="apm-btn3d apm-press" href="newchat.php" aria-label="See Prompts">
          <span class="apm-chip"><i class="fa-solid fa-layer-group"></i></span>
          <span class="txt">See Prompts</span>
        </a>

        <div class="apm-login apm-press" id="apmLoginBtn" role="button" tabindex="0"
             data-login-url="mandilogin.php" data-profile-url="profile.php">
          <span class="apm-login-ico"><i class="fa-solid fa-user"></i></span>
          <span class="apm-login-text">
            <strong id="apmLoginTitle">Login</strong>
            <small class="apm-typing" id="apmLoginTyping"></small>
          </span>
        </div>
      </div>
    </div>

  </div>
</header>

  <script>
(() => {
  const nav = document.getElementById("apmNav");
  const burger = document.getElementById("apmBurger");
  const menu = document.getElementById("apmNavMenu");

  if(!nav || !burger || !menu) return;

  function setOpen(open){
    nav.classList.toggle("is-open", open);
    burger.setAttribute("aria-expanded", open ? "true" : "false");
    burger.setAttribute("aria-label", open ? "Close menu" : "Open menu");
  }

  burger.addEventListener("click", (e) => {
    e.stopPropagation();
    setOpen(!nav.classList.contains("is-open"));
  });

  // close on outside click
  document.addEventListener("click", (e) => {
    if(!nav.classList.contains("is-open")) return;
    if(nav.contains(e.target)) return;
    setOpen(false);
  });

  // close on ESC
  document.addEventListener("keydown", (e) => {
    if(e.key === "Escape") setOpen(false);
  });

  // close after clicking a link inside menu (mobile)
  menu.addEventListener("click", (e) => {
    const a = e.target.closest("a");
    if(a) setOpen(false);
  });

  // close when resizing to desktop
  window.addEventListener("resize", () => {
    if(window.innerWidth > 768) setOpen(false);
  });
})();
</script>







<!-- HERO -->
<section class="apm-hero">
  <div class="apm-hero-inner">

    <div class="apm-hero-left">
      <div class="apm-eyebrow">
        <span class="icon"><i class="fa-solid fa-bolt"></i></span>
        <span>Busineger Growth Chat</span>
      </div>

      <h1 class="apm-title">
      Busineger <br>   <span class="gradient">   Business Growth </span>
        <span class="gradient"> <br>Strategy Packs</span>
      </h1>

      <p class="apm-subhead">
        Turn every idea into <strong>ready-to-sell content</strong> — from reels to offers, without the blank-page stress.
      </p>

      <p class="apm-kicker">
        Prompts. Chat. Growth. <strong>Made for Indian businesses.</strong>
      </p>

      <p class="apm-body">
        Sell business growth prompt packs and plug them into one smart chat that your team uses daily for reels, ads, captions, offers
        and customer replies — all in one place, customised for your brand voice.
      </p>

      <div class="apm-cta-row">
        <a href="newchat.php" class="apm-btn3d accent apm-press apm-cta">
          <span class="apm-chip"><i class="fa-solid fa-robot"></i></span>
          <span class="txt">Open Growth Chatbot</span>
        </a>

        <a href="mandilogin.php" class="apm-btn3d apm-press apm-cta">
          <span class="apm-chip"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
          <span class="txt">Start Free</span>
        </a>
      </div>
    </div>

    <div class="apm-hero-right">
      <div class="apm-chat-card">

        <!-- ✅ TOP PILL (like your screenshot) -->
        <div class="apm-floating-pill apm-pill-top">
          <span class="dot"></span>
          REELS • ADS • OFFERS • REPLIES
        </div>

        <!-- ✅ BOTTOM PILL (like your screenshot) -->
        <div class="apm-floating-pill apm-pill-bottom">
          <span class="dot"></span>
          NO IDEAS? BUSINEGER GIVES READY PROMPTS
        </div>

        <div class="apm-chat-header">
          <div class="apm-chat-badge">
            <i class="fa-solid fa-robot"></i>
            <span>Busineger Growth Chat</span>
          </div>
          <div class="apm-chat-status">
            <span class="apm-chat-status-dot"></span>
            <span>Live growth ideas</span>
          </div>
        </div>

        <div class="apm-chat-body">
          <div class="apm-chat-stream" id="apmChatStream"></div>
        </div>

        <div class="apm-metadata-chip">
          <i class="fa-solid fa-bolt"></i>
          <span>Prompt packs auto-adapt to your business type, city & season in seconds.</span>
        </div>

        <div class="apm-chat-input-preview">
          <span class="hint">Type your business & city…</span>
          <button class="send" type="button" aria-label="Send">
            <i class="fa-solid fa-paper-plane"></i>
          </button>
        </div>

      </div>
    </div>

  </div>
</section>

<script>
  // LOGIN STATE
  const loginBtn   = document.getElementById("apmLoginBtn");
  const loginTitle = document.getElementById("apmLoginTitle");
  const typingEl   = document.getElementById("apmLoginTyping");

  function getLoginState(){
    if (typeof window.IS_LOGGED_IN === "boolean") return window.IS_LOGGED_IN;
    return localStorage.getItem("apm_logged_in") === "1";
  }
  function getUserName(){
    if (typeof window.USER_NAME === "string" && window.USER_NAME.trim()) return window.USER_NAME.trim();
    return localStorage.getItem("apm_user_name") || "User";
  }

  function applyLoginUI(isLoggedIn){
    if(isLoggedIn){
      loginTitle.textContent = getUserName();
      typingEl.textContent = "Logged in";
      typingEl.classList.remove("apm-typing");
      stopTypingLoop();
    }else{
      loginTitle.textContent = "Login";
      typingEl.classList.add("apm-typing");
      startTypingLoop();
    }
  }

  let loopRunning = false;
  let typingTimer = null;

  function stopTypingLoop(){
    loopRunning = false;
    if(typingTimer) clearTimeout(typingTimer);
    typingTimer = null;
  }

  function startTypingLoop(){
    if(loopRunning) return;
    loopRunning = true;

    const phrases = ["Start free", "Join now", "Login here", "Unlock prompts"];
    let p = 0;

    const type = (phrase, i=0)=>{
      if(getLoginState()){ loopRunning=false; return; }
      typingEl.textContent = phrase.slice(0,i);
      if(i <= phrase.length){
        typingTimer = setTimeout(()=>type(phrase, i+1), 44);
      }else{
        typingTimer = setTimeout(()=>erase(phrase, phrase.length), 900);
      }
    };

    const erase = (phrase, i)=>{
      if(getLoginState()){ loopRunning=false; return; }
      typingEl.textContent = phrase.slice(0,i);
      if(i >= 0){
        typingTimer = setTimeout(()=>erase(phrase, i-1), 26);
      }else{
        p++;
        typingTimer = setTimeout(()=>type(phrases[p % phrases.length], 0), 160);
      }
    };

    type(phrases[0], 0);
  }

  loginBtn.addEventListener("click", ()=>{
    const isLoggedIn = getLoginState();
    const loginUrl = loginBtn.getAttribute("data-login-url") || "mandilogin.php";
    const profileUrl = loginBtn.getAttribute("data-profile-url") || "profile.php";
    window.location.href = isLoggedIn ? profileUrl : loginUrl;
  });

  applyLoginUI(getLoginState());

  // CHAT ANIMATION
  const apmMessages = [
    { type:"user", title:"Business Owner", icon:"👨‍💼", text:"Meri business growth stuck hai. Har month sales same hi rehti hain." },
    { type:"ai", title:"Busineger Chatbot", icon:"🤖", text:"No tension 🙂 Main aapke business ke liye ready growth prompts dunga.", tagline:"Built for Indian businesses 🇮🇳" },
    { type:"user", title:"Business Owner", icon:"❓", text:"In prompts ka use kaise karna hoga?" },
    { type:"ai", title:"Busineger Chatbot", icon:"🚀", text:"Bas copy–paste karo. Reels, ads, captions & WhatsApp replies sab ready.", tagline:"From idea → execution in seconds ⚡" }
  ];

  const stream = document.getElementById("apmChatStream");

  /* ✅ ADDED: Auto-scroll so new messages stay visible (and no layout push) */
  const chatBody = document.querySelector(".apm-chat-body");
  function scrollChatToBottom(){
    if(!chatBody) return;
    chatBody.scrollTop = chatBody.scrollHeight;
  }

  function createBubble(msg){
    const row = document.createElement("div");
    row.className = "apm-chat-row " + (msg.type === "user" ? "user" : "ai");

    const avatar = document.createElement("div");
    avatar.className = "apm-avatar " + (msg.type === "user" ? "user" : "ai");

    avatar.innerHTML = (msg.type === "user")
      ? '<img alt="user" src="https://static.vecteezy.com/system/resources/previews/018/742/015/original/minimal-profile-account-symbol-user-interface-theme-3d-icon-rendering-illustration-isolated-in-transparent-background-png.png">'
      : '<img alt="bot" src="https://cdn-icons-png.flaticon.com/512/3649/3649460.png">';

    const bubble = document.createElement("div");
    bubble.className = "apm-bubble";

    const title = document.createElement("div");
    title.className = "apm-bubble-title";
    title.textContent = msg.title || "";

    const text = document.createElement("div");
    text.className = "apm-bubble-text";
    text.textContent = "";

    bubble.appendChild(title);
    bubble.appendChild(text);

    if(msg.tagline){
      const tag = document.createElement("div");
      tag.className = "apm-bubble-tagline";
      tag.textContent = msg.tagline;
      bubble.appendChild(tag);
    }

    row.appendChild(avatar);
    row.appendChild(bubble);
    stream.appendChild(row);

    /* ✅ ADDED: scroll after adding bubble */
    scrollChatToBottom();

    const full = msg.text || "";
    let i = 0;
    const speed = 16;
    const typer = setInterval(()=>{
      text.textContent = full.slice(0,i);
      i++;

      /* ✅ ADDED: keep scrolling while typing */
      scrollChatToBottom();

      if(i > full.length) clearInterval(typer);
    }, speed);
  }

  function playChatLoop(){
    stream.innerHTML = "";
    scrollChatToBottom();

    let idx = 0;

    function next(){
      if(idx >= apmMessages.length){
        setTimeout(playChatLoop, 2200);
        return;
      }
      const m = apmMessages[idx];
      createBubble(m);
      idx++;

      const baseDelay = m.type === "user" ? 1100 : 1500;
      const typingTime = (m.text || "").length * 12;
      setTimeout(next, baseDelay + typingTime);
    }
    next();
  }

  document.addEventListener("DOMContentLoaded", playChatLoop);
</script>







<section class="bgGraph" aria-label="Busineger Growth Graph">

<div class="wrap">
    <div class="card">
      <div class="topRow">
        <div>
          <span class="pill">BUSINEGER GROWTH GRAPH</span>
          <div class="title">Use Prompts → Follow Answers → Busineger for Business Growth</div>
        </div>
      </div>

      <div class="graphBox">
        <svg viewBox="0 0 920 440" role="img" aria-label="Growth bars with premium arrow">
          <defs>
            <pattern id="grid2" width="34" height="34" patternUnits="userSpaceOnUse">
              <path d="M 34 0 L 0 0 0 34" fill="none" stroke="rgba(148,163,184,.11)" stroke-width="1"/>
            </pattern>

            <linearGradient id="barFront2" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="rgba(96,165,250,.98)"/>
              <stop offset="60%" stop-color="rgba(34,211,238,.95)"/>
              <stop offset="100%" stop-color="rgba(37,99,235,.98)"/>
            </linearGradient>
            <linearGradient id="barTop2" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0%" stop-color="rgba(255,255,255,.20)"/>
              <stop offset="100%" stop-color="rgba(255,255,255,.06)"/>
            </linearGradient>
            <linearGradient id="barSide2" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0%" stop-color="rgba(0,0,0,.06)"/>
              <stop offset="100%" stop-color="rgba(0,0,0,.26)"/>
            </linearGradient>

            <filter id="barShadow" x="-30%" y="-30%" width="160%" height="160%">
              <feDropShadow dx="0" dy="10" stdDeviation="10" flood-color="rgba(0,0,0,.45)"/>
            </filter>

            <linearGradient id="arrowGrad2" x1="0" y1="1" x2="1" y2="0">
              <stop offset="0%" stop-color="#ff2a2a"/>
              <stop offset="100%" stop-color="#ff6b6b"/>
            </linearGradient>
            <linearGradient id="arrowHi2" x1="0" y1="0" x2="1" y2="0">
              <stop offset="0%" stop-color="rgba(255,255,255,.45)"/>
              <stop offset="55%" stop-color="rgba(255,255,255,.10)"/>
              <stop offset="100%" stop-color="rgba(255,255,255,0)"/>
            </linearGradient>

            <filter id="arrowGlowStrong" x="-70%" y="-70%" width="240%" height="240%">
              <feDropShadow dx="0" dy="0" stdDeviation="10" flood-color="rgba(255,60,60,.18)"/>
              <feDropShadow dx="0" dy="0" stdDeviation="18" flood-color="rgba(255,60,60,.10)"/>
            </filter>

            <filter id="headGlow" x="-90%" y="-90%" width="280%" height="280%">
              <feDropShadow dx="0" dy="0" stdDeviation="8" flood-color="rgba(255,60,60,.18)"/>
            </filter>
          </defs>

          <rect x="0" y="0" width="920" height="440" fill="url(#grid2)" opacity="1"/>
          <line x1="70" y1="370" x2="880" y2="370" stroke="rgba(148,163,184,.20)" stroke-width="2"/>

          <!-- bars -->
          <g id="bars" filter="url(#barShadow)">
            <g class="bar" data-x="120" data-w="38" data-top="300" data-base="370">
              <polygon class="top" points="120,300 148,288 186,288 158,300"/>
              <polygon class="side" points="158,300 186,288 186,370 158,382"/>
              <rect class="front" x="120" y="300" width="38" height="70"/>
            </g>

            <g class="bar" data-x="190" data-w="38" data-top="286" data-base="370">
              <polygon class="top" points="190,286 218,274 256,274 228,286"/>
              <polygon class="side" points="228,286 256,274 256,370 228,382"/>
              <rect class="front" x="190" y="286" width="38" height="84"/>
            </g>

            <g class="bar" data-x="260" data-w="38" data-top="272" data-base="370">
              <polygon class="top" points="260,272 288,260 326,260 298,272"/>
              <polygon class="side" points="298,272 326,260 326,370 298,382"/>
              <rect class="front" x="260" y="272" width="38" height="98"/>
            </g>

            <g class="bar" data-x="330" data-w="38" data-top="256" data-base="370">
              <polygon class="top" points="330,256 358,244 396,244 368,256"/>
              <polygon class="side" points="368,256 396,244 396,370 368,382"/>
              <rect class="front" x="330" y="256" width="38" height="114"/>
            </g>

            <g class="bar" data-x="400" data-w="38" data-top="240" data-base="370">
              <polygon class="top" points="400,240 428,228 466,228 438,240"/>
              <polygon class="side" points="438,240 466,228 466,370 438,382"/>
              <rect class="front" x="400" y="240" width="38" height="130"/>
            </g>

            <g class="bar" data-x="470" data-w="38" data-top="222" data-base="370">
              <polygon class="top" points="470,222 498,210 536,210 508,222"/>
              <polygon class="side" points="508,222 536,210 536,370 508,382"/>
              <rect class="front" x="470" y="222" width="38" height="148"/>
            </g>

            <g class="bar" data-x="540" data-w="38" data-top="202" data-base="370">
              <polygon class="top" points="540,202 568,190 606,190 578,202"/>
              <polygon class="side" points="578,202 606,190 606,370 578,382"/>
              <rect class="front" x="540" y="202" width="38" height="168"/>
            </g>

            <g class="bar" data-x="610" data-w="38" data-top="180" data-base="370">
              <polygon class="top" points="610,180 638,168 676,168 648,180"/>
              <polygon class="side" points="648,180 676,168 676,370 648,382"/>
              <rect class="front" x="610" y="180" width="38" height="190"/>
            </g>

            <g class="bar" data-x="680" data-w="38" data-top="154" data-base="370">
              <polygon class="top" points="680,154 708,142 746,142 718,154"/>
              <polygon class="side" points="718,154 746,142 746,370 718,382"/>
              <rect class="front" x="680" y="154" width="38" height="216"/>
            </g>

            <g class="bar" data-x="750" data-w="38" data-top="124" data-base="370">
              <polygon class="top" points="750,124 778,112 816,112 788,124"/>
              <polygon class="side" points="788,124 816,112 816,370 788,382"/>
              <rect class="front" x="750" y="124" width="38" height="246"/>
            </g>
          </g>

          <!-- zigzag arrow -->
          <path id="growthArrowLineGlow"
            d="M110 332
               L220 248
               L300 282
               L430 206
               L540 234
               L660 162
               L760 182
               L850 96"
            fill="none"
            stroke="url(#arrowGrad2)"
            stroke-width="18"
            stroke-linecap="round"
            stroke-linejoin="round"
            opacity="0"
            filter="url(#arrowGlowStrong)"/>

          <path id="growthArrowLine"
            d="M110 332
               L220 248
               L300 282
               L430 206
               L540 234
               L660 162
               L760 182
               L850 96"
            fill="none"
            stroke="url(#arrowGrad2)"
            stroke-width="12"
            stroke-linecap="round"
            stroke-linejoin="round"
            opacity="0"/>

          <path id="growthArrowLineHi"
            d="M110 332
               L220 248
               L300 282
               L430 206
               L540 234
               L660 162
               L760 182
               L850 96"
            fill="none"
            stroke="url(#arrowHi2)"
            stroke-width="3.2"
            stroke-linecap="round"
            stroke-linejoin="round"
            opacity="0"
            style="mix-blend-mode:screen"/>

          <polygon id="growthArrowHead"
            points="0,-24 50,0 0,24"
            fill="url(#arrowGrad2)"
            opacity="0"
            filter="url(#headGlow)"/>

          <style>
            .bar .front{ fill: url(#barFront2); }
            .bar .top{ fill: url(#barTop2); }
            .bar .side{ fill: url(#barSide2); opacity: .92; }
            .bar{ opacity: 0; }
          </style>
        </svg>

        <div class="stepsRow" aria-label="3 steps to growth">
          <div class="stepCard s1" id="step1">
            <div class="stepBadge">1</div>
            <div class="stepText">
              <p class="stepTitle">Use prompts</p>
              <p class="stepSub">Pick goal → paste</p>
            </div>
          </div>

          <div class="stepCard s2" id="step2">
            <div class="stepBadge">2</div>
            <div class="stepText">
              <p class="stepTitle">Follow answers</p>
              <p class="stepSub">Copy → implement</p>
            </div>
          </div>

          <div class="stepCard s3" id="step3">
            <div class="stepBadge">3</div>
            <div class="stepText">
              <p class="stepTitle">Get growth</p>
              <p class="stepSub">Consistency wins</p>
            </div>
          </div>
        </div>

      <div class="ctaRow">
  <div class="note" style="margin-top:30px;">✅ 3 steps = growth</div>

  <a class="btn btn-full" href="/newchat.php">Try Busineger →</a>
</div>

      </div>
    </div>
  </div>

  <script>
    (function(){
      // ✅ scope everything inside this section only
      const root = document.currentScript.closest('.bgGraph');
      if(!root) return;

      const svg = root.querySelector('.graphBox svg');
      const bars = Array.from(svg.querySelectorAll('.bar'));

      const arrowGlow = svg.querySelector('#growthArrowLineGlow');
      const arrowLine = svg.querySelector('#growthArrowLine');
      const arrowHi   = svg.querySelector('#growthArrowLineHi');
      const arrowHead = svg.querySelector('#growthArrowHead');

      const step1 = root.querySelector('#step1');
      const step2 = root.querySelector('#step2');
      const step3 = root.querySelector('#step3');

      function revealStep(el){
        el.animate(
          [
            { opacity: 0, transform: 'translateY(8px) scale(0.985)' },
            { opacity: 1, transform: 'translateY(0px) scale(1)' }
          ],
          { duration: 320, easing: 'cubic-bezier(.2,.9,.2,1)', fill: 'forwards' }
        );
      }

      function setBarProgress(bar, p){
        const x = +bar.dataset.x;
        const w = +bar.dataset.w;
        const topY = +bar.dataset.top;
        const baseY = +bar.dataset.base;
        const hFull = baseY - topY;

        const h = Math.max(0, Math.min(hFull, hFull * p));
        const y = baseY - h;

        const rect = bar.querySelector('.front');
        rect.setAttribute('y', y);
        rect.setAttribute('height', h);

        const top = bar.querySelector('.top');
        const side = bar.querySelector('.side');
        const dx = 28, dy = -12;

        top.setAttribute('points',
          `${x},${y} ${x+dx},${y+dy} ${x+w+dx},${y+dy} ${x+w},${y}`
        );

        side.setAttribute('points',
          `${x+w},${y} ${x+w+dx},${y+dy} ${x+w+dx},${baseY+dy} ${x+w},${baseY}`
        );

        bar.style.opacity = 1;
      }

      // init steps
      [step1, step2, step3].forEach(s => {
        s.style.opacity = 0;
        s.style.transformOrigin = 'center';
      });

      // init bars
      bars.forEach(b => setBarProgress(b, 0));

      // init arrow (hidden)
      [arrowGlow, arrowLine, arrowHi].forEach(p => p.style.opacity = 0);
      arrowHead.style.opacity = 0;

      const fillDuration = 260;
      const stagger = 70;

      function animateBar(bar, done){
        const start = performance.now();
        function tick(now){
          const t = (now - start) / fillDuration;
          const p = t < 0 ? 0 : t > 1 ? 1 : (1 - Math.pow(1 - t, 3));
          setBarProgress(bar, p);
          if(t < 1) requestAnimationFrame(tick);
          else done && done();
        }
        requestAnimationFrame(tick);
      }

      function easeInOut(t){
        return (t < 0.5) ? 2*t*t : 1 - Math.pow(-2*t+2,2)/2;
      }

      // line draws first, triangle appears ONLY at end and stays attached
      function drawArrow(){
        const path = arrowLine;
        const len = path.getTotalLength();

        [arrowGlow, arrowLine, arrowHi].forEach(p => {
          p.style.opacity = 1;
          p.style.strokeDasharray = len;
          p.style.strokeDashoffset = len;
        });

        arrowHead.style.opacity = 0;

        const start = performance.now();
        const dur = 980;

        function tick(now){
          const t = (now - start) / dur;
          const p = t < 0 ? 0 : t > 1 ? 1 : easeInOut(t);

          const draw = len * (1 - p);
          [arrowGlow, arrowLine, arrowHi].forEach(pl => pl.style.strokeDashoffset = draw);

          const L = Math.max(0.0001, p * len);
          const pt = path.getPointAtLength(L);

          const ahead = Math.min(len, L + 3);
          const pt2 = path.getPointAtLength(ahead);
          const ang = Math.atan2(pt2.y - pt.y, pt2.x - pt.x) * 180 / Math.PI;

          arrowHead.setAttribute('transform', `translate(${pt.x},${pt.y}) rotate(${ang})`);

          if (p > 0.987){
            const fade = Math.min(1, (p - 0.987) / 0.013);
            arrowHead.style.opacity = fade.toFixed(3);
          }

          if(t < 1) requestAnimationFrame(tick);
          else {
            [arrowGlow, arrowLine, arrowHi].forEach(pl => pl.style.strokeDashoffset = 0);

            const endPt = path.getPointAtLength(len);
            const prevPt = path.getPointAtLength(Math.max(0, len - 4));
            const endAng = Math.atan2(endPt.y - prevPt.y, endPt.x - prevPt.x) * 180 / Math.PI;

            arrowHead.setAttribute('transform', `translate(${endPt.x},${endPt.y}) rotate(${endAng})`);
            arrowHead.style.opacity = 1;
          }
        }
        requestAnimationFrame(tick);
      }

      const stepIndex1 = 0;
      const stepIndex2 = Math.max(1, Math.floor(bars.length * 0.34));
      const stepIndex3 = Math.max(stepIndex2 + 1, Math.floor(bars.length * 0.68));

      let i = 0;
      function runBars(){
        if(i === stepIndex1) revealStep(step1);
        if(i === stepIndex2) revealStep(step2);
        if(i === stepIndex3) revealStep(step3);

        if(i >= bars.length) return drawArrow();

        animateBar(bars[i], () => {
          i++;
          setTimeout(runBars, stagger);
        });
      }

      runBars();
    })();
  </script>







</section>



<section class="body-featured">
<section class="featured" id="featured">
  <div class="wrap">
    <div class="heading">
      <div class="kicker"><i></i> Premium Prompt Engine</div>
      <h2>Featured Highlights</h2>
      <p>Clean, business-ready features that keep outputs consistent, editable, and results-focused.</p>
      <div class="underline"></div>
    </div>

    <div class="cards">
      <div class="card left">
        <div class="shell">
          <div class="tag"><b>01</b> Feature</div>
          <div class="icon">⚡</div>
          <h3>One-Click Prompt Results</h3>
          <p>Instant AI output with zero friction and minimal effort.</p>
          <div class="badges">🚀 ⏱️ 📊</div>
        </div>
      </div>

      <div class="card right">
        <div class="shell">
          <div class="tag"><b>02</b> Feature</div>
          <div class="icon">🏢</div>
          <h3>Business Custom Prompts</h3>
          <p>Professionally designed prompts for real business growth.</p>
          <div class="badges">💼 📈 🧠</div>
        </div>
      </div>

      <div class="card left">
        <div class="shell">
          <div class="tag"><b>03</b> Feature</div>
          <div class="icon">✏️</div>
          <h3>Editable & Flexible</h3>
          <p>Adapt prompts effortlessly to suit your exact needs.</p>
          <div class="badges">📝 ⚙️ 🔁</div>
        </div>
      </div>

      <div class="card right">
        <div class="shell">
          <div class="tag"><b>04</b> Feature</div>
          <div class="icon">👨‍💼</div>
          <h3>Written by Industry Experts</h3>
          <p>Built by professionals with proven domain experience.</p>
          <div class="badges">🎓 🏆 🛡️</div>
        </div>
      </div>

      <div class="card left">
        <div class="shell">
          <div class="tag"><b>05</b> Feature</div>
          <div class="icon">🚀</div>
          <h3>Optimized for Real Business</h3>
          <p>Focused on efficiency, clarity, and real-world impact.</p>
          <div class="badges">⚙️ 📊 💡</div>
        </div>
      </div>

      <div class="card right">
        <div class="shell">
          <div class="tag"><b>06</b> Feature</div>
          <div class="icon">🤖</div>
          <h3>AI-Tested & Validated</h3>
          <p>Each prompt is tested for accuracy and consistency.</p>
          <div class="badges">🧪 ✅ 🔍</div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
/* when section enters viewport */
const section = document.querySelector("#featured");
const sectionObserver = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      section.classList.add("inview");
      sectionObserver.unobserve(section);
    }
  });
}, { threshold: 0.18 });
sectionObserver.observe(section);

/* reveal cards on scroll */
const cards = document.querySelectorAll('#featured .card');
const cardObserver = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      entry.target.classList.add('show');
      cardObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.22 });
cards.forEach((card) => cardObserver.observe(card));

/* hover spotlight + tilt */
const finePointer = window.matchMedia("(hover:hover) and (pointer:fine)").matches;

if (finePointer) {
  document.querySelectorAll("#featured .shell").forEach((shell) => {
    shell.addEventListener("mousemove", (e) => {
      const r = shell.getBoundingClientRect();
      const x = ((e.clientX - r.left) / r.width) * 100;
      const y = ((e.clientY - r.top) / r.height) * 100;

      shell.style.setProperty("--mx", x.toFixed(2) + "%");
      shell.style.setProperty("--my", y.toFixed(2) + "%");

      const rx = ((y - 50) / 50) * -6;
      const ry = ((x - 50) / 50) *  6;
      shell.style.setProperty("--rx", rx.toFixed(2) + "deg");
      shell.style.setProperty("--ry", ry.toFixed(2) + "deg");
    });

    shell.addEventListener("mouseleave", () => {
      shell.style.setProperty("--mx", "50%");
      shell.style.setProperty("--my", "50%");
      shell.style.setProperty("--rx", "0deg");
      shell.style.setProperty("--ry", "0deg");
    });
  });
}
</script>
</section>
<section>





<!-- ✅ Scoped Section START -->
<section id="spiComparison" class="spi-comparison">
  <div class="spi-container">

    <div class="spi-head" id="spiHead">
      <div class="spi-pill"><span class="spi-dot"></span><span>Premium Prompt Engine</span></div>
      <h2>Prompt Quality → Business Strategy → Business Growth</h2>
      <p>Bad prompts create random output. Smart prompts create reliable results.</p>
      <div class="spi-line"></div>
    </div>

    <div class="spi-grid">
      <div class="spi-vs">VS</div>

      <div class="spi-card">
        <h3>❌ Bad Prompts</h3>
        <p>Unclear inputs → messy answers → wasted time.</p>

        <div class="spi-metric">
          <div class="spi-label"><span>Output Quality</span><span>Low</span></div>
          <div class="spi-bar spi-bad"><span data-w="22%"></span></div>

          <div class="spi-label"><span>Business Growth</span><span>Zero</span></div>
          <div class="spi-bar spi-bad"><span data-w="18%"></span></div>

          <div class="spi-label"><span>AI Efficiency</span><span>Poor</span></div>
          <div class="spi-bar spi-bad"><span data-w="25%"></span></div>
        </div>
      </div>

      <div class="spi-card">
        <h3>✅ Smart Prompts</h3>
        <p>Structured prompts → business-ready outputs.</p>

        <div class="spi-metric">
          <div class="spi-label"><span>Output Quality</span><span>High</span></div>
          <div class="spi-bar spi-good"><span data-w="92%"></span></div>

          <div class="spi-label"><span>Business Growth</span><span>Strong</span></div>
          <div class="spi-bar spi-good"><span data-w="88%"></span></div>

          <div class="spi-label"><span>AI Efficiency</span><span>Maximum</span></div>
          <div class="spi-bar spi-good"><span data-w="95%"></span></div>
        </div>
      </div>
    </div>

    <div class="spi-cta-row">
      <button type="button" class="spi-cta" id="spiActivate">Use Smart Prompts to Grow Faster</button>
      <button type="button" class="spi-cta-ghost" id="spiLearn">From Chaos to Clarity</button>
    </div>

  </div>
</section>

<script>
/* ✅ Fully scoped JS (no global pollution) */
(() => {
  const root = document.getElementById("spiComparison");
  if(!root) return;

  const head = root.querySelector("#spiHead");
  const cards = root.querySelectorAll(".spi-card");
  const bars  = root.querySelectorAll(".spi-bar span");

  let didRun = false;

  const io = new IntersectionObserver((entries)=>{
    entries.forEach(entry=>{
      if(entry.isIntersecting){
        head && head.classList.add("is-in");

        cards.forEach((c,i)=> setTimeout(()=>c.classList.add("is-in"), 180 + i*140));

        if(!didRun){
          didRun = true;
          setTimeout(()=> bars.forEach(b => b.style.width = b.dataset.w || "0%"), 520);
        }
        io.disconnect();
      }
    });
  },{ threshold:0.25 });

  io.observe(root);

  /* subtle tilt (desktop only) */
  const finePointer = window.matchMedia && window.matchMedia("(pointer:fine)").matches;
  if(finePointer){
    cards.forEach(card=>{
      card.addEventListener("mousemove",(e)=>{
        const r = card.getBoundingClientRect();
        const x = (e.clientX - r.left)/r.width;
        const y = (e.clientY - r.top)/r.height;
        const ry = (x - .5) * 10;
        const rx = (.5 - y) * 8;
        card.style.transform = `translateY(-10px) rotateX(${rx}deg) rotateY(${ry}deg)`;
      });
      card.addEventListener("mouseleave",()=>{ card.style.transform=""; });
    });
  }

const activateBtn = root.querySelector("#spiActivate");
const learnBtn = root.querySelector("#spiLearn");

if (activateBtn) {
  activateBtn.addEventListener("click", () => {
    window.location.href = "/newchat.php";
  });
}

if (learnBtn) {
  learnBtn.disabled = true;                 // truly non-clickable
  learnBtn.style.cursor = "default";        // arrow cursor
  learnBtn.style.opacity = "0.75";          // optional: muted look
}

})();
</script>




























<section class="what-you-get">
  <div class="orb"></div>
  <div class="orb right"></div>

  <div class="container">

    <!-- LEFT -->
    <div>
      <div class="badge">Premium Prompt Engine</div>

      <h2>     What You’ll Get</h2>

      <p class="subtitle">
     AI-powered strategies that feel <b>consulting-grade</b> — built for clarity, growth, and execution.
          Clean output, next steps, and real decisions you can take immediately.
      </p>

      <div class="divider"></div>

      <div class="holoRail" id="holoRailAuto">
        <div class="featureRow reveal" style="--d:.06s">
          <div class="gutter">
            <span class="featureDot"></span>
            <span class="connector"></span>
            <span class="featureIcon">📊</span>
          </div>
          <div class="fcopy">
            <h4 class="featureTitle">Market Domination Blueprint</h4>
            <p class="featureDesc">Competitive positioning, pricing psychology & growth levers engineered to win.</p>
          </div>
        </div>

        <div class="featureRow reveal" style="--d:.18s">
          <div class="gutter">
            <span class="featureDot"></span>
            <span class="connector"></span>
            <span class="featureIcon">🧠</span>
          </div>
          <div class="fcopy">
            <h4 class="featureTitle">AI Strategy Prompt Engine</h4>
            <p class="featureDesc">Turns raw ideas into structured strategies with clarity, priorities & next steps.</p>
          </div>
        </div>

        <div class="featureRow reveal" style="--d:.30s">
          <div class="gutter">
            <span class="featureDot"></span>
            <span class="connector"></span>
            <span class="featureIcon">⚡</span>
          </div>
          <div class="fcopy">
            <h4 class="featureTitle">Conversion Optimization</h4>
            <p class="featureDesc">Offer refinement + funnel logic + revenue-first improvements that compound.</p>
          </div>
        </div>

        <div class="featureRow reveal" style="--d:.42s">
          <div class="gutter">
            <span class="featureDot"></span>
            <span class="connector"></span>
            <span class="featureIcon">🚀</span>
          </div>
          <div class="fcopy">
            <h4 class="featureTitle">Execution-Ready Playbooks</h4>
            <p class="featureDesc">Clear action plan & tasks your team can deploy instantly.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- RIGHT (ONLY IMAGE) -->
    <div class="visual">
      <div class="preview">
        <div class="previewLabel">Output Preview</div>
        <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?q=80&w=1400" alt="AI Dashboard" />
      </div>
    </div>

  </div>
</section>

<script>
/* ✅ Scoped JS for this section only */
(() => {
  const section = document.querySelector('.what-you-get');
  if(!section) return;

  const rows = Array.from(section.querySelectorAll('.featureRow'));
  const reveals = Array.from(section.querySelectorAll('.reveal'));

  /* Cursor glow */
  rows.forEach(row => {
    row.addEventListener('mousemove', (e) => {
      const r = row.getBoundingClientRect();
      const x = ((e.clientX - r.left) / r.width) * 100;
      const y = ((e.clientY - r.top) / r.height) * 100;
      row.style.setProperty('--mx', x + '%');
      row.style.setProperty('--my', y + '%');
    }, { passive:true });
  });

  /* Reveal from left (one-by-one) */
  const reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const revealAll = () => {
    reveals.forEach(el => el.classList.add('in'));
  };

  if(reduce){
    revealAll();
    return;
  }

  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if(e.isIntersecting){
        revealAll();
        io.disconnect();
      }
    });
  }, { threshold: 0.18 });

  // observe rail area (or section)
  const rail = section.querySelector('#holoRailAuto') || section;
  io.observe(rail);

  /* ✅ Auto-hover continuously one by one (after reveal finishes) */
  let timer = null;
  let idx = 0;

  const setActive = (i) => {
    rows.forEach(r => r.classList.remove('is-active'));
    if(rows[i]) rows[i].classList.add('is-active');
  };

  const getMaxDelayMs = () => {
    let max = 0;
    reveals.forEach(el => {
      const d = getComputedStyle(el).getPropertyValue('--d').trim();
      // expects like ".18s"
      const sec = parseFloat(d || "0") || 0;
      if(sec > max) max = sec;
    });
    return Math.round((max * 1000) + 950 + 120); // delay + transition duration + buffer
  };

  const start = () => {
    if(timer || !rows.length) return;
    setActive(idx);
    timer = setInterval(() => {
      idx = (idx + 1) % rows.length;
      setActive(idx);
    }, 1700);
  };

  const stop = () => {
    if(!timer) return;
    clearInterval(timer);
    timer = null;
  };

  // pause on real hover (feels premium)
  rows.forEach((row, i) => {
    row.addEventListener('mouseenter', () => {
      stop();
      idx = i;
      setActive(idx);
    });
    row.addEventListener('mouseleave', () => {
      setTimeout(start, 700);
    });
  });

  // start after reveal ends
  const kick = () => setTimeout(start, getMaxDelayMs());
  // if section already visible quickly, still start
  window.addEventListener('load', kick, { once:true });

  // pause when tab hidden
  document.addEventListener('visibilitychange', () => {
    if(document.hidden) stop();
    else start();
  });
})();
</script>





<section class="b3r-premium">
  <div class="container">

    <header class="hero">
      <div class="pill"><span class="dot"></span> Premium Prompt Engine</div>
      <h1>Featured <span>Highlights</span></h1>
      <div class="hero-underline"></div>
      <p>Enterprise-grade workflows designed for speed, clarity, and scale.</p>
    </header>

    <div class="grid">

      <!-- LEFT SIDE -->
      <div class="flow" id="b3rFlow">
        <div class="flow-rail" aria-hidden="true"></div>

        <div class="flow-steps">

          <div class="flow-step active">
            <div class="node">
              <div class="badge" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M4 5h16v14H4z"/><path d="M8 9h8"/><path d="M8 13h6"/></svg>
              </div>
            </div>
            <div class="flow-card">
              <div class="toprow">
                <h3>Select Industry</h3>
                <span class="step-tag">STEP 01</span>
              </div>
              <p>Busineger maps your niche instantly and pulls the best-ready strategy framework.</p>
              <div class="progress"><span></span></div>
            </div>
          </div>

          <div class="flow-step">
            <div class="node">
              <div class="badge" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/></svg>
              </div>
            </div>
            <div class="flow-card">
              <div class="toprow">
                <h3>Pick a Topic</h3>
                <span class="step-tag">STEP 02</span>
              </div>
              <p>Choose a goal like leads, ads, content, SEO, offers — and lock the direction.</p>
              <div class="progress"><span></span></div>
            </div>
          </div>

          <div class="flow-step">
            <div class="node">
              <div class="badge" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M4 20h16"/><path d="M7 17l10-10"/><path d="M15 7h2v2"/></svg>
              </div>
            </div>
            <div class="flow-card">
              <div class="toprow">
                <h3>Edit Prompt</h3>
                <span class="step-tag">STEP 03</span>
              </div>
              <p>Fine-tune inputs (location, budget, tone, audience) to make output custom-built.</p>
              <div class="progress"><span></span></div>
            </div>
          </div>

          <div class="flow-step">
            <div class="node">
              <div class="badge" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 3v10"/><path d="M7 8l5-5 5 5"/><path d="M5 21h14"/></svg>
              </div>
            </div>
            <div class="flow-card">
              <div class="toprow">
                <h3>Generate Strategy</h3>
                <span class="step-tag">STEP 04</span>
              </div>
              <p>One click produces a structured plan — no fluff, only actions and outputs.</p>
              <div class="progress"><span></span></div>
            </div>
          </div>

          <div class="flow-step">
            <div class="node">
              <div class="badge" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
              </div>
            </div>
            <div class="flow-card">
              <div class="toprow">
                <h3>Apply & Scale</h3>
                <span class="step-tag">STEP 05</span>
              </div>
              <p>Deploy across teams, repeat across industries, and scale output without losing quality.</p>
              <div class="progress"><span></span></div>
            </div>
          </div>

        </div>

        <div class="growth-note">
          <h2>Busineger helps you grow your business</h2>
          <div class="growth-underline"></div>
        </div>

      </div>

      <!-- RIGHT SIDE -->
      <div class="side">

        <div class="token">
          <div class="free-badge">PREMIUM</div>

          <div class="token-top">
            <div class="icon-bubble" aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="M12 2l2.95 6.29 6.93.6-5.24 4.55 1.6 6.8L12 16.9 5.76 20.24l1.6-6.8L2.12 8.89l6.93-.6L12 2z"/></svg>
            </div>
            <h3>Busineger Pro</h3>
          </div>

          <div class="premium-title">Unlimited Words</div>
          <div class="premium-sub">
            Buy <b>1 category</b> and unlock <b>unlimited usage</b> forever.
          </div>

          <ul>
            <li><div class="tick"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg></div>No usage limits</li>
            <li><div class="tick"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg></div>Lifetime access</li>
            <li><div class="tick"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg></div>Professional-grade output</li>
          </ul>

          <button type="button" onclick="window.location.href='/newchat.php';">
  Unlock Premium
  <svg viewBox="0 0 24 24" aria-hidden="true">
    <path d="M5 12h12"/>
    <path d="M13 6l6 6-6 6"/>
  </svg>
</button>

        </div>

        <div class="freecard">
          <div class="free-badge2">FREE DAILY</div>

          <div class="free-top">
            <div class="icon-bubble2" aria-hidden="true">
              <svg viewBox="0 0 24 24">
                <path d="M12 3c4.4 0 8 1.8 8 4s-3.6 4-8 4-8-1.8-8-4 3.6-4 8-4Z"/>
                <path d="M4 7v5c0 2.2 3.6 4 8 4s8-1.8 8-4V7"/>
                <path d="M4 12v5c0 2.2 3.6 4 8 4s8-1.8 8-4v-5"/>
              </svg>
            </div>
            <h3>Free Tokens Pack</h3>
          </div>

          <div class="free-big">
            <div class="num">8,000</div>
            <div class="unit">tokens / day</div>
          </div>

          <div class="free-sub">
            Use Busineger daily with a fresh token reset — perfect for testing, drafts, and quick wins.
          </div>

          <ul>
            <li><div class="tick2"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg></div>Daily reset (every 24 hours)</li>
            <li><div class="tick2"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg></div>Great for small tasks & trials</li>
            <li><div class="tick2"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg></div>Upgrade anytime to Unlimited</li>
          </ul>

          <button type="button" onclick="window.location.href='/newchat.php';">
  Start Free
  <svg viewBox="0 0 24 24" aria-hidden="true">
    <path d="M5 12h12"/>
    <path d="M13 6l6 6-6 6"/>
  </svg>
</button>

        </div>

      </div>

    </div>

  </div>
</section>

<script>
/* ✅ One-by-one highlight (scoped) */
(() => {
  const flow = document.getElementById("b3rFlow");
  if (!flow) return;

  const steps = Array.from(flow.querySelectorAll(".flow-step"));
  if (!steps.length) return;

  const reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (reduce) return;

  let i = 0;

  function setActive(idx){
    steps.forEach((el, k) => el.classList.toggle("active", k === idx));
    const bar = steps[idx].querySelector(".progress > span");
    if (bar){
      const clone = bar.cloneNode(true);
      bar.parentNode.replaceChild(clone, bar);
    }
  }

  setActive(0);

  setInterval(() => {
    i = (i + 1) % steps.length;
    setActive(i);
  }, 1700);
})();
</script>







<section class="sb-whyfaq" id="why-choose-us">
  <div class="wrap">

    <!-- MAIN HEADING -->
    <div class="main-head reveal">
      <div class="top-pill" aria-label="Section badge">
        <span class="dot"></span>
        <span>Premium Prompt Engine</span>
      </div>
      <h2>Featured Highlights</h2>
      <p>Clean, business-ready features that keep outputs consistent, editable, and results-focused.</p>
      <div class="headline-underline" aria-hidden="true"></div>
    </div>

    <div class="why-grid">
      <!-- LEFT -->
      <div class="why-left reveal">
        <div class="section-kicker">
          <div class="k-badge">
            <div class="k-ico" aria-hidden="true">★</div>
            <div class="k-txt">
              <b>Premium Growth Partner</b>
              <span>Strategy • Creatives • Performance</span>
            </div>
          </div>
        </div>

        <h3>Why Choose Us?</h3>
        <p class="why-sub">
          Premium strategy + creators + performance — all in one place. No chaos, no random posting,
          only consistent growth that looks premium and converts.
        </p>

        <div class="why-features" id="sbWhyCards">
          <div class="why-card">
            <div class="why-ico" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none">
                <path d="M6 6h12M6 12h8M6 18h12" stroke="#061531" stroke-width="2" stroke-linecap="round"/>
                <path d="M16 12l2-2 2 2-2 2-2-2Z" fill="#061531"/>
              </svg>
            </div>
            <div>
              <h4>Clear Growth Plan</h4>
              <p>We build a step-by-step roadmap so your marketing always has direction and milestones.</p>
            </div>
          </div>

          <div class="why-card">
            <div class="why-ico" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none">
                <path d="M13 2 4 14h7l-1 8 10-14h-7l0-6Z" fill="#061531"/>
              </svg>
            </div>
            <div>
              <h4>Fast Execution</h4>
              <p>Quick creatives, quick publishing, quick results — without compromising premium quality.</p>
            </div>
          </div>

          <div class="why-card">
            <div class="why-ico" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none">
                <path d="M4 19V5" stroke="#061531" stroke-width="2" stroke-linecap="round"/>
                <path d="M4 19h16" stroke="#061531" stroke-width="2" stroke-linecap="round"/>
                <path d="M7 15l4-4 3 3 6-7" stroke="#061531" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div>
              <h4>Performance Focus</h4>
              <p>We track content & campaigns weekly and optimize continuously for leads and conversions.</p>
            </div>
          </div>

          <div class="why-card">
            <div class="why-ico" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none">
                <path d="M12 2l7 4v6c0 5-3 9-7 10-4-1-7-5-7-10V6l7-4Z" stroke="#061531" stroke-width="2" stroke-linejoin="round"/>
                <path d="M9 12l2 2 4-5" stroke="#061531" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <div>
              <h4>Premium + Secure Process</h4>
              <p>Clear approvals, clean timelines and a reliable system — no confusion, no delays.</p>
            </div>
          </div>
        </div>

        <div class="why-mini">
          <div class="pill"><span class="dot"></span> Reels + Ads + SEO</div>
          <div class="pill"><span class="dot"></span> Weekly Reporting</div>
          <div class="pill"><span class="dot"></span> Premium Creatives</div>
        </div>
      </div>

      <!-- RIGHT -->
      <div class="why-right reveal">
        <div class="why-media">
          <img class="why-img"
               src="https://images.unsplash.com/photo-1556761175-b413da4baf72?q=80&w=1600&auto=format&fit=crop"
               alt="Business growth"/>

          <div class="popbox" aria-label="Success stats">
            <div class="pop-ring"><b>89%</b></div>
            <div class="pop-txt">
              <strong>Success Stats</strong>
              <span>Higher engagement & conversions</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FAQ -->
    <div class="faq" id="faqSection">
      <div class="main-head reveal" style="margin-top: var(--s8);">
        <div class="top-pill" aria-label="FAQ badge">
          <span class="dot"></span>
          <span>Support & Clarity</span>
        </div>
        <h2>FAQs</h2>
        <p>Clear, transparent answers so you can start with confidence.</p>
        <div class="headline-underline" aria-hidden="true"></div>
      </div>

      <div class="faq-grid2">
        <!-- LEFT -->
        <div class="faq-left reveal">
          <div class="section-kicker">
            <div class="k-badge">
              <div class="k-ico" aria-hidden="true">✦</div>
              <div class="k-txt">
                <b>Quick Answers</b>
                <span>Start with confidence</span>
              </div>
            </div>
          </div>

          <p>
            Here are the most common questions clients ask before starting.
            Everything is structured, premium, and easy to understand.
          </p>

          <div class="faq-media">
            <img class="faq-img"
                 src="https://images.unsplash.com/photo-1553877522-43269d4ea984?q=80&w=1600&auto=format&fit=crop"
                 alt="FAQ support"/>

            <div class="faq-bubble" aria-label="Support bubble">
              <div class="b-ico" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                  <path d="M12 22a8 8 0 1 0-8-8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  <path d="M4 14v6h6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M8 10a4 4 0 1 1 8 0v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  <path d="M10 16h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
              </div>
              <div>
                <b>Quick Support</b>
                <span>We guide you from strategy to execution — without confusion.</span>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT -->
        <div class="faq-right" id="sbFaq">
          <details class="sb-acc">
            <summary class="sb-sum">
              <span class="qrow">
                <span class="qic" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none">
                    <path d="M9.5 9a3 3 0 1 1 4.8 2.4c-.8.6-1.3 1.1-1.3 2.1v.6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M12 18h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                  </svg>
                </span>
                What exactly do you provide?
              </span>
              <span class="chev">⌄</span>
            </summary>
            <div class="divider"></div>
            <div class="ans-wrap">
              <div class="ansBox">We handle strategy, content planning, premium creatives (posts/reels), posting support, ads guidance, and weekly performance tracking — end to end.</div>
            </div>
          </details>

          <details class="sb-acc">
            <summary class="sb-sum">
              <span class="qrow">
                <span class="qic" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 2v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M7 6l2.2 2.2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M17 6l-2.2 2.2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M4 12h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M16 12h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M12 12l3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M12 22a10 10 0 1 1 10-10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  </svg>
                </span>
                Will this help me get leads?
              </span>
              <span class="chev">⌄</span>
            </summary>
            <div class="divider"></div>
            <div class="ans-wrap">
              <div class="ansBox">Yes. We design content and campaigns around your goal: enquiries, calls, WhatsApp messages, DMs, footfall, and conversions — not “random posting”.</div>
            </div>
          </details>

          <details class="sb-acc">
            <summary class="sb-sum">
              <span class="qrow">
                <span class="qic" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 2l7 4v6c0 5-3 9-7 10-4-1-7-5-7-10V6l7-4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M9 12l2 2 4-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </span>
                Do you work for all industries?
              </span>
              <span class="chev">⌄</span>
            </summary>
            <div class="divider"></div>
            <div class="ans-wrap">
              <div class="ansBox">Yes. We customize by niche: restaurants, clinics, consultancies, salons, gyms, real estate, education, and more — with industry-specific creatives.</div>
            </div>
          </details>

          <details class="sb-acc">
            <summary class="sb-sum">
              <span class="qrow">
                <span class="qic" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 8v5l3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M21 12a9 9 0 1 1-9-9 9 9 0 0 1 9 9Z" stroke="currentColor" stroke-width="2"/>
                  </svg>
                </span>
                How soon will I see results?
              </span>
              <span class="chev">⌄</span>
            </summary>
            <div class="divider"></div>
            <div class="ans-wrap">
              <div class="ansBox">You’ll usually see improvement in 2–4 weeks (reach/engagement). Lead momentum builds with consistency + correct offers + ads budget.</div>
            </div>
          </details>

          <details class="sb-acc">
            <summary class="sb-sum">
              <span class="qrow">
                <span class="qic" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 2l3 7 7 3-7 3-3 7-3-7-7-3 7-3 3-7Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                  </svg>
                </span>
                What makes you different?
              </span>
              <span class="chev">⌄</span>
            </summary>
            <div class="divider"></div>
            <div class="ans-wrap">
              <div class="ansBox">Premium creatives + measurable performance. We plan content like a funnel, not just posting. You’ll see weekly insights, not vague promises.</div>
            </div>
          </details>

          <details class="sb-acc">
            <summary class="sb-sum">
              <span class="qrow">
                <span class="qic" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none">
                    <path d="M4 4h16v12H7l-3 3V4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M7 8h10M7 12h7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                  </svg>
                </span>
                How do I start?
              </span>
              <span class="chev">⌄</span>
            </summary>
            <div class="divider"></div>
            <div class="ans-wrap">
              <div class="ansBox">Send your business category + goal + target location. We’ll share a plan, timeline, and pricing options based on your needs.</div>
            </div>
          </details>
        </div>
      </div>
    </div>

  </div>
</section>

<script>
(function(){
  const root = document.querySelector('.sb-whyfaq');
  if(!root) return;

  // ---------- Reveal headings/blocks ----------
  const revealEls = root.querySelectorAll('.reveal');
  const io = new IntersectionObserver((entries)=>{
    entries.forEach((e)=>{
      if(e.isIntersecting){
        e.target.classList.add('in');
        io.unobserve(e.target);
      }
    });
  },{threshold:.15});
  revealEls.forEach(el => io.observe(el));

  // ---------- WHY cards: one-by-one from LEFT ----------
  const whyCards = [...root.querySelectorAll('.why-card')];
  whyCards.forEach((c, idx)=> c.style.setProperty('--d', (idx * 140) + 'ms'));

  const whyIO = new IntersectionObserver((entries)=>{
    entries.forEach((e)=>{
      if(!e.isIntersecting) return;
      // when wrapper enters, animate each card sequentially
      const wrapper = e.target;
      const cards = [...wrapper.querySelectorAll('.why-card')];
      cards.forEach((card, i)=>{
        setTimeout(()=> card.classList.add('in'), i * 160);
      });
      whyIO.unobserve(wrapper);
    });
  },{threshold:.18});
  const whyWrap = root.querySelector('#sbWhyCards');
  if(whyWrap) whyIO.observe(whyWrap);

  // ---------- FAQ cards: show one-by-one + AUTO pulse (zoom in/out) ----------
  const faqWrap = root.querySelector('#sbFaq');
  const allDetails = faqWrap ? [...faqWrap.querySelectorAll('details.sb-acc')] : [];
  allDetails.forEach((d, idx)=> d.style.setProperty('--d', (idx * 120) + 'ms'));

  // close all initially
  allDetails.forEach(d => d.removeAttribute('open'));

  // allow only one open on click
  allDetails.forEach((d)=>{
    d.addEventListener('toggle', ()=>{
      if(d.open){
        allDetails.forEach((other)=>{
          if(other !== d) other.removeAttribute('open');
        });
      }
    });
  });

  // On entering FAQ section:
  // 1) each FAQ item appears one by one
  // 2) automatically pulses (zoom in/out) one-by-one (no auto open)
  const faqSection = root.querySelector('#faqSection');
  const faqIO = new IntersectionObserver((entries)=>{
    entries.forEach((e)=>{
      if(!e.isIntersecting) return;

      // reveal each details
      allDetails.forEach((d, i)=>{
        setTimeout(()=> d.classList.add('in'), i * 140);
      });

      // after reveal, do auto pulse one-by-one
      const startDelay = allDetails.length * 140 + 150;
      allDetails.forEach((d, i)=>{
        setTimeout(()=>{
          d.classList.add('pulse');
          setTimeout(()=> d.classList.remove('pulse'), 980);
        }, startDelay + i * 620);
      });

      faqIO.unobserve(e.target);
    });
  },{threshold:.22});
  if(faqSection) faqIO.observe(faqSection);

  // ---------- Ripple on summary ----------
  root.querySelectorAll('summary.sb-sum').forEach((s)=>{
    s.addEventListener('click',(ev)=>{
      const rect = s.getBoundingClientRect();
      const x = (ev.clientX || (rect.left + rect.width/2)) - rect.left;
      const y = (ev.clientY || (rect.top + rect.height/2)) - rect.top;

      const rip = document.createElement('span');
      rip.className = 'rip';
      rip.style.left = x + 'px';
      rip.style.top = y + 'px';
      const size = Math.max(rect.width, rect.height);
      rip.style.width = rip.style.height = size + 'px';
      s.appendChild(rip);
      setTimeout(()=>rip.remove(), 780);
    });
  });
})();
</script>








<!-- ✅ Wrap ONLY the footer (recommended for your website) -->
<div class="apm-scope">

  <footer class="site-footer" id="top">

    <!-- ✅ FOOTER TOP MARQUEE -->
    <div class="apm-adbar apm-adbar--footer" aria-label="Scrolling category bar">
      <div class="apm-adwrap">
        <div class="apm-strip" id="apmStripFooter"></div>
      </div>
    </div>

    <div class="aurora" aria-hidden="true"></div>

    <div class="footer-wrap">

      <div class="footer-main">

        <!-- Brand -->
        <div class="footer-brand">
          <div class="brand-head">
            <div class="brand-icon"><i class="fa-solid fa-bolt"></i></div>

            <div class="brand-name">
              <img class="logo-img" src="assets/logo-32.png" alt="Busineger Logo" onerror="this.style.display='none'">
              <span class="title">Busineger</span>
            </div>
          </div>

          <div class="footer-desc">
            Smart business tools and AI solutions to help you scale faster with confidence.
            Premium UI, faster workflows, better results.
          </div>

          <div class="footer-meta">
            <span><i class="fa-solid fa-location-dot"></i> India, Maharashtra</span>
            <span><i class="fa-solid fa-envelope"></i> support@yourdomain.com</span>
          </div>
        </div>

        <!-- Links -->
        <div class="footer-col">
          <h3 class="footer-title">Quick Links <span class="line"></span></h3>
          <ul class="footer-links">
            <li><a href="index.php"><i class="fa-solid fa-house"></i> Home</a></li>
            <li><a href="newchat.php"><i class="fa-solid fa-layer-group"></i> Prompts</a></li>
            <li><a href="about.php"><i class="fa-solid fa-circle-info"></i> About</a></li>
          </ul>
        </div>

        <!-- Legal -->
        <div class="footer-col">
          <h3 class="footer-title">Legal <span class="line"></span></h3>
          <ul class="footer-links">
            <li><a href="terms.php"><i class="fa-solid fa-file-signature"></i> Terms & Conditions</a></li>
            <li><a href="privacy.php"><i class="fa-solid fa-user-shield"></i> Privacy Policy</a></li>
            <li><a href="policy.php"><i class="fa-solid fa-shield-halved"></i> Policy</a></li>
          </ul>
        </div>

      </div>

      <!-- Bottom -->
      <div class="footer-bottom">

        <div class="footer-social-row" aria-label="Social links">
          <a href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
          <a href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
        </div>

        <div class="footer-bottom-line">
          <div>© <b>Busineger</b> <span id="year"></span>. All rights reserved.</div>

          <a class="to-top" href="#top" id="toTopBtn" role="button" aria-label="Back to top">
            <i class="fa-solid fa-arrow-up"></i> Top
          </a>
        </div>

      </div>

    </div>
  </footer>

</div>

<script>
/* year */
document.getElementById("year").textContent = new Date().getFullYear();

/* ✅ Footer marquee items */
(() => {
  const strip = document.getElementById("apmStripFooter");
  if(!strip) return;

  const BASE_URL = "category.php?cat=";

  const items = [
    { t:"Ac service", i:"fa-snowflake", slug:"ac-service" },
    { t:"Beauty spa", i:"fa-spa", slug:"beauty-spa" },
    { t:"Car hire", i:"fa-car", slug:"car-hire" },
    { t:"Caterers", i:"fa-utensils", slug:"caterers" },
    { t:"Chartered accountant", i:"fa-calculator", slug:"chartered-accountant" },
    { t:"Computer & laptop repair & services", i:"fa-laptop", slug:"computer-laptop-repair" },
    { t:"Car repair & services", i:"fa-screwdriver-wrench", slug:"car-repair" },
    { t:"Dentists", i:"fa-tooth", slug:"dentists" },
    { t:"Event organizer", i:"fa-calendar-check", slug:"event-organizer" },
    { t:"Real estate", i:"fa-building", slug:"real-estate" },
    { t:"Hospitals", i:"fa-hospital", slug:"hospitals" },
    { t:"House keeping services", i:"fa-broom", slug:"house-keeping" },
    { t:"Interior designers", i:"fa-couch", slug:"interior-designers" },
    { t:"Internet website designers", i:"fa-globe", slug:"website-designers" },
    { t:"Jewellery showrooms", i:"fa-gem", slug:"jewellery-showrooms" },
    { t:"Transporters", i:"fa-truck", slug:"transporters" },
    { t:"Photographers", i:"fa-camera", slug:"photographers" },
    { t:"Nursing services", i:"fa-user-nurse", slug:"nursing-services" },
    { t:"Printing & publishing services", i:"fa-print", slug:"printing-publishing" },
    { t:"Pest control services", i:"fa-bug", slug:"pest-control" },
    { t:"Coaching", i:"fa-chalkboard-user", slug:"coaching" },
    { t:"Car dealer", i:"fa-car-side", slug:"car-dealer" },
    { t:"Clothing", i:"fa-shirt", slug:"clothing" },
    { t:"Interior design", i:"fa-ruler-combined", slug:"interior-design" },
    { t:"Studio", i:"fa-video", slug:"studio" },
    { t:"Resort", i:"fa-umbrella-beach", slug:"resort" },
    { t:"Tours and travels", i:"fa-plane-departure", slug:"tours-travels" },
    { t:"Gym", i:"fa-dumbbell", slug:"gym" },
    { t:"Cake shop and bakery", i:"fa-cake-candles", slug:"cake-bakery" },
    { t:"Cafe", i:"fa-mug-saucer", slug:"cafe" },
    { t:"Foot wear", i:"fa-shoe-prints", slug:"footwear" },
    { t:"Ice cream", i:"fa-ice-cream", slug:"ice-cream" },
    { t:"Pet shop", i:"fa-paw", slug:"pet-shop" },
    { t:"Eyeglasses", i:"fa-glasses", slug:"eyeglasses" },
    { t:"Florist", i:"fa-seedling", slug:"florist" },
    { t:"Perfume store", i:"fa-spray-can-sparkles", slug:"perfume-store" },
    { t:"Accessories", i:"fa-bag-shopping", slug:"accessories" },
    { t:"Yoga classes", i:"fa-person-walking", slug:"yoga-classes" },
    { t:"Toy shop", i:"fa-rocket", slug:"toy-shop" },
    { t:"Indian sweet shop", i:"fa-candy-cane", slug:"sweet-shop" },
    { t:"Helmet", i:"fa-helmet-safety", slug:"helmet" },
    { t:"Electronic shop", i:"fa-plug", slug:"electronic-shop" },
    { t:"Watch store", i:"fa-clock", slug:"watch-store" },
    { t:"Mobile shop", i:"fa-mobile-screen", slug:"mobile-shop" },
    { t:"Restaurant", i:"fa-bowl-food", slug:"restaurant" },
    { t:"Hotel", i:"fa-hotel", slug:"hotel" },
    { t:"Camping", i:"fa-campground", slug:"camping" }
  ];

  const mkTrack = () => `
    <div class="apm-track">
      ${items.map((x) => `
        <a class="apm-adlink" href="${BASE_URL + encodeURIComponent(x.slug)}" title="${x.t}">
          <i class="fa-solid ${x.i}" aria-hidden="true"></i>
          <span>${x.t}</span>
        </a>
        <span class="apm-adsep" aria-hidden="true"></span>
      `).join("")}
    </div>
  `;

  strip.innerHTML = mkTrack() + mkTrack();
})();
</script>





















</body>
</html>