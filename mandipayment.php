<?php
session_start();
require('./backend/db.php');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: mandilogin.php");
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['user_id'];

if (empty($_SESSION['cart']) && isset($_SESSION['pending_cart'])) {
    $_SESSION['cart'] = $_SESSION['pending_cart'];
    unset($_SESSION['pending_cart']);
}

$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    echo '
    <div style="
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100vh;
        font-family: Arial, sans-serif;
        text-align: center;
    ">
        <h2>Your cart is empty.</h2>
        <a href="new1mandi.php" style="
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        " onmouseover="this.style.backgroundColor=\'#0056b3\'" onmouseout="this.style.backgroundColor=\'#007bff\'">
            Back to Shop
        </a>
    </div>';
    exit();
}


// 🔥 Check if product already purchased
$filtered_cart = [];
$already_purchased = [];

foreach ($cart as $item) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND product_id = ? AND payment_status = 'Paid'");
    $stmt->execute([$user_id, $item['id']]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        $already_purchased[] = $item;
    } else {
        $filtered_cart[] = $item;
    }
}

// Update cart session to only include items not yet purchased
$_SESSION['cart'] = $filtered_cart;

$total = 0;
foreach ($filtered_cart as $item) {
    $total += (float)$item['price'];
}
$_SESSION['cart_total'] = $total;

?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment | AIPromptMandi</title>
  <style>
 * {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
  font-family: 'Poppins', sans-serif;
}

body {
  background: linear-gradient(135deg, #dbeffe, #f0f8ff);
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 60px 15px;
  min-height: 100vh;
  transition: background 0.5s ease-in-out;
}

.container {
  background: #ffffff;
  padding: 40px 30px;
  max-width: 760px;
  width: 100%;
  border-radius: 20px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  animation: slideUp 0.7s ease-out;
}

.container:hover {
  transform: scale(1.01);
  box-shadow: 0 25px 70px rgba(0, 0, 0, 0.12);
}

@keyframes slideUp {
  0% {
    opacity: 0;
    transform: translateY(40px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

h2 {
  font-size: 26px;
  font-weight: 700;
  margin-bottom: 20px;
  color: #1e3a8a;
  display: flex;
  align-items: center;
  gap: 10px;
  animation: fadeIn 1s ease-in;
}

.email-box {
  display: flex;
  align-items: center;
  background: #f1f5ff;
  padding: 14px 20px;
  border-radius: 12px;
  margin-bottom: 20px;
  font-size: 15px;
  color: #1e3a8a;
  font-weight: 500;
  box-shadow: 3px 3px 8px #dce9ff, -3px -3px 8px #ffffff;
  transition: transform 0.3s ease;
}

.email-box:hover {
  transform: translateY(-3px);
}

.email-box i {
  color: #2563eb;
  font-size: 18px;
  margin-right: 10px;
  animation: bounceIcon 2s infinite;
}

@keyframes bounceIcon {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-3px); }
}

.back-link {
  font-size: 14px;
  text-decoration: none;
  color: #2563eb;
  margin-bottom: 25px;
  display: inline-block;
  transition: all 0.3s ease;
}

.back-link:hover {
  text-decoration: underline;
  color: #1d4ed8;
}

h3 {
  font-size: 18px;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.cart-item {
  background: #e9f2ff;
  padding: 16px 20px;
  border-left: 5px solid #3b82f6;
  border-radius: 12px;
  margin-bottom: 16px;
  box-shadow: 0 5px 15px rgba(59, 130, 246, 0.15);
  font-size: 15px;
  font-weight: 500;
  position: relative;
  overflow: hidden;
  transition: all 0.3s ease;
}

.cart-item::after {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(120deg, rgba(255,255,255,0.3), transparent);
  transition: all 0.4s ease;
}

.cart-item:hover::after {
  left: 100%;
}

.cart-item strong {
  font-size: 16px;
  font-weight: 700;
  color: #111827;
  display: block;
  margin-bottom: 6px;
}

.total {
  font-size: 16px;
  font-weight: 700;
  color: #1e3a8a;
  text-align: right;
  margin-top: 12px;
  transition: color 0.3s ease;
}

.total:hover {
  color: #3b82f6;
}

.pay-now-btn {
  margin-top: 30px;
  padding: 14px 30px;
  background: linear-gradient(to right, #2563eb, #3b82f6);
  color: white;
  border: none;
  border-radius: 60px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  box-shadow: 0 12px 25px rgba(37, 99, 235, 0.3);
  display: block;
  margin-left: auto;
  margin-right: auto;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.pay-now-btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -75%;
  width: 50%;
  height: 100%;
  background: rgba(255,255,255,0.15);
  transform: skewX(-20deg);
  transition: all 0.75s ease;
}

.pay-now-btn:hover::before {
  left: 125%;
}

.pay-now-btn:hover {
  background: linear-gradient(to right, #1d4ed8, #2563eb);
  transform: translateY(-2px);
}

.footer {
  text-align: center;
  margin-top: 40px;
  font-size: 14px;
  color: #64748b;
}

.footer a {
  color: #2563eb;
  font-weight: 600;
  margin-left: 10px;
  font-size: 16px;
  text-decoration: none;
  transition: color 0.3s ease;
}

.footer a:hover {
  text-decoration: underline;
  color: #1e40af;
}

/* Responsive */
@media (max-width: 480px) {
  .container {
    padding: 25px 18px;
  }

  .pay-now-btn {
    width: 100%;
  }

  .total {
    text-align: left;
  }
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

  </style>
</head>
<body>
  <div class="container">
    <h2>🔥 Hello, <?= htmlspecialchars($user['username']) ?>!</h2>

    <div class="email-box">
      <i class="fas fa-envelope"></i>
      Email: <?= htmlspecialchars($user['email']) ?>
    </div>

    <a href="dashboard.php" class="back-link">← Back to Dashboard</a>
    <?php if (!empty($already_purchased)): ?>
    <?php foreach ($already_purchased as $p): ?>
        <div style="background-color: #ffecec; padding: 10px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #ffb3b3;">
            <strong>⚠️ Note:</strong> <br>
            The product <strong><?= htmlspecialchars($p['title']) ?></strong> has already been purchased and removed from your cart. <br><br>
            ✅ No worries! If you continue and click **“Pay Now”**, you will only be charged for the products you haven't purchased yet. <br>
            🛑 Already purchased items will not be billed again.
        </div>
    <?php endforeach; ?>
<?php endif; ?>


    <h3>🛒 Your Cart:</h3>

    <?php foreach ($cart as $item): ?>
  <div class="cart-item">
    <strong><?= htmlspecialchars($item['title']) ?></strong>
    ₹<?= number_format($item['price'], 2) ?>

    <?php 
      // Check if item is in already_purchased
      $isPurchased = false;
      foreach ($already_purchased as $p) {
          if ($p['id'] == $item['id']) {
              $isPurchased = true;
              break;
          }
      }
      if ($isPurchased): 
    ?>
      <button class="remove-btn" onclick="removeFromCart(<?= htmlspecialchars($item['id']) ?>)" style="padding: 5px 10px; background-color: #ef4444; color: white; border: none; border-radius: 8px; cursor: pointer;">
      Remove</button>
    <?php endif; ?>
  </div>
<?php endforeach; ?>

<script>
function removeFromCart(productId) {
    fetch('removefromcart.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ product_id: productId })
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            location.reload();
        } else {
            alert('❌ Failed to remove item.');
        }
    });
}
</script>


<p class="total">Total: ₹<?= number_format($total, 2) ?></p>


    <button class="pay-now-btn" id="payNowBtn">Pay Now</button>


    <div class="footer">
      © 2025 AI Prompt Mandi |
      <a href="terms.php">Terms & Conditions</a> |
      <a href="index.php">Home</a>
    </div>
  </div>

  <!-- Optional: FontAwesome CDN for email icon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
<script>
  const cashfree = Cashfree({ mode:"production"  });

 document.getElementById("payNowBtn").addEventListener("click", function () {
  // First save cart to session
  const cartData = <?= json_encode($_SESSION['cart']) ?>;

  fetch('savecart.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ cart: cartData })
  })
  .then(res => res.json())
  .then(saveRes => {
    if (!saveRes.success) {
      alert("❌ Failed to save cart: " + saveRes.message);
      return;
    }

    // Now call create_cashfree_order.php
    return fetch('create_cashfree_order.php', {
      method: 'POST'
    });
  })
  .then(res => res.json())
  .then(data => {
    if (data.payment_session_id) {
      cashfree.checkout({
        paymentSessionId: data.payment_session_id,
        redirectTarget: "_self"
      });
    } else {
      alert("❌ Payment session creation failed: " + (data.message || "Unknown error"));
      console.error(data);
    }
  })
  .catch(err => {
    alert("⚠️ Something went wrong. See console for details.");
    console.error(err);
  });
});

</script>

</body>
</html>