<?php
session_start();
include 'db.php'; // connection file

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = md5(trim($_POST['password'])); // match MD5 stored in DB

    $stmt = $conn->prepare("SELECT * FROM admins WHERE username=? AND password=? LIMIT 1");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $_SESSION['admin_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "❌ Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f9; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); width: 300px; }
        .card h2 { color: maroon; text-align: center; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 8px; }
        button { width: 100%; background: dodgerblue; color: white; border: none; padding: 10px; border-radius: 8px; cursor: pointer; transition: 0.3s; }
        button:hover { background: maroon; transform: scale(1.05); }
        .error { color: red; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <h2>🔐 Admin Login</h2>
        <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="post">
            <input type="text" name="username" placeholder="Enter Username" required>
            <input type="password" name="password" placeholder="Enter Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
