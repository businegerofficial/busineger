 <!-- Topbar -->
<div class="topbar">
                <button class="hamburger" id="hamburger">&#9776;</button>
                <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
                <form method="POST" action="logout.php" style="margin: 0;">
                    <button type="submit" style="background-color: #F9D919;">Logout</button>
                </form>
</div>