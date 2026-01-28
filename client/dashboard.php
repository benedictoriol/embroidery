<?php
session_start();
if (!isset($_SESSION["user"]) || $_SESSION["user"]["role"] !== "client") {
    header("Location: ../auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Client</title>
<style>
body{font-family:Arial;background:#eef2f5}
.container{width:800px;margin:40px auto}
.card{background:#fff;padding:20px;border-radius:8px}
a{display:block;padding:10px;background:#607d8b;color:#fff;margin:8px 0;border-radius:4px;text-decoration:none}
</style>
</head>
<body>

<div class="container">
<div class="card">
<h2>Welcome, <?php echo $_SESSION["user"]["name"]; ?> (Client)</h2>

<a href="place_order.php">Place Order</a>
<a href="track_order.php">Track Order</a>
<a href="../auth/logout.php">Logout</a>
</div>
</div>

</body>
</html>
