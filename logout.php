<?php
session_start();
session_destroy();  // clear all sessions
header("Location: index.php"); // redirect to homepage (you can change to login page)
exit;
?>
