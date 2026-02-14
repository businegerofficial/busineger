<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-MDHS95MM');</script>
<!-- End Google Tag Manager -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        *{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        a {
            text-decoration: none;
            color: inherit;
        }
        .container {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .sidebar {
            width: 250px;
            background-color: black;
            color: #fff;
            display: flex;
            flex-direction: column;
            padding: 20px;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            transition: transform 0.3s ease-in-out;
            z-index: 1000;
        }
        .sidebar.active {
            transform: translateX(0);
        }
        .sidebar h2 {
            margin-bottom: 20px;
            text-align: center;
        }
        .sidebar a {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            background-color: #F9D919;
            color: #fff;
            font-size: 16px;
            text-align: center;
            transition: background-color 0.3s;
        }
        .sidebar a:hover {
            background-color:rgb(209, 181, 23);
        }
        .sidebar .close-btn {
            align-self: flex-end;
            font-size: 20px;
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
            margin-bottom: 20px;
            display: none;
        }
        .sidebar .close-btn:hover {
            color: #ccc;
        }

        .main-content {
            flex: 1;
            margin-left: 250px;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            transition: margin-left 0.3s ease-in-out;
        }
        .main-content.sidebar-collapsed {
            margin-left: 0;
        }

        .topbar {
            background-color: #fff;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.1);
        }
        .topbar h1 {
            font-size: 20px;
        }
        .topbar button {
            background-color: #F9D919;
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .topbar button:hover {
            background-color:rgb(206, 177, 8);
        }
        .hamburger {
            display: none; 
            cursor: pointer;
            font-size: 24px;
            background: none;
            border: none;
        }
        .dashboard-content {
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .card {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .card h3 {
            margin-bottom: 10px;
            font-size: 18px;
        }
        .card p {
            font-size: 14px;
            color: #666;
        }

        .date-filters {
            margin: 20px;
        }
        .date-filters label {
            margin-right: 10px;
        }
        .date-filters input {
            padding: 5px;
            margin-right: 10px;
        }
        #filter-date{
            padding: 5px;
            margin-right: 10px;
            background-color: #F9D919;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        #filter-date:hover {
            background-color:rgb(223, 192, 19);
        }
    

        @media (max-width: 768px) {
            .hamburger {
                display: block; 
            }
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .sidebar .close-btn {
                display: block; 
            }
            .dashboard-content {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
                gap: 16px;
                overflow-x: auto;
                padding: 16px; 
            }
        } 
    </style>
</head>
<body>
    <!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MDHS95MM"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
    <div class="container">
        <?php include 'components/sidebar.php'; ?>
        <div class="main-content" id="main-content">
           
         <!-- include header.php -->
            <?php include 'components/header.php'; ?>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <div class="card">
                    <a href = "/admin/user-data.php">
                    <h3>Registration user data</h3>
                    <p>Click here to view contact form</p>
                    </a>
                </div>
                <div class="card">
                <a href = "/admin/contact-messages.php">
                    <h3>Contact form data </h3>
                    <p>Click here to view Pop Up form</p>
                </a>
                </div>
                 <div class="card">
                <a href = "./orders-data.php">
                    <h3>Order  Track</h3>
                    <p>Click here to view order track </p>
                </a>
                </div>
                <!--<div class="card">-->
                <!--<a href = "/admin/mtc-form-data.php">-->
                <!--    <h3>Website Maintenance form</h3>-->
                <!--    <p>Click here to view website mtc form</p>-->
                <!--</a>-->
                <!--</div>-->
            </div>
        </div>
    </div>

    <script src="./static/js/script.js"></script>
</body>
</html>
