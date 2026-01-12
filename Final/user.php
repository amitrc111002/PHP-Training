<?php
require 'productconnection.php';
require 'functions.php';

session_start();
$errors = [];
$success_msg = [];

if (isset($_GET['logout']))
{
    session_destroy();
    header("Location: user.php?msg=loggedout");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register']))
{
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'] ?? 'customer';

    if (empty($username) || empty($password))
    {
        $errors[] = "All fields are required.";
    }
    else
    {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        try
        {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hashedPassword, $role]);
            $success_msg[] = "Account created! You can now log in.";
        }
        catch (PDOException $e)
        {
            $errors[] = "Username already exists.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login']))
{
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password']))
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header("Location: homepage.php");
        exit;
    }
    else
    {
        $errors[] = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Authorisation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6" style="font-family: 'Inter', sans-serif;">
    <div class="max-w-4xl w-full grid grid-cols-1 md:grid-cols-2 gap-8">
        
        <div class="bg-white p-8 rounded-2xl shadow-xl border border-slate-200">
            <h2 class="text-2xl font-bold text-slate-800 mb-6">Login</h2>
            <?php 
                if (isset($_GET['msg']) && $_GET['msg'] == 'loggedout') displayMessages(['Successfully logged out'], 'success');
                if (!isset($_POST['register'])) {
                    displayMessages($errors); 
                    if ($success_msg) displayMessages($success_msg, 'success');
                }
            ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1">Username</label>
                    <input type="text" name="username" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>
                <button type="submit" name="login" class="w-full bg-indigo-600 text-white py-3 rounded-xl font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">Sign In</button>
            </form>
        </div>

        <div class="bg-slate-100 p-8 rounded-2xl border border-slate-200">
            <h2 class="text-2xl font-bold text-slate-800 mb-6">Register</h2>
            <?php if (isset($_POST['register'])) displayMessages($errors); ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1">New Username</label>
                    <input type="text" name="username" required class="w-full px-4 py-2 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-2 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1">Role</label>
                    <select name="role" class="w-full px-4 py-2 bg-white border border-slate-200 rounded-lg outline-none">
                        <option value="customer">Customer</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit" name="register" class="w-full bg-slate-800 text-white py-3 rounded-xl font-bold hover:bg-slate-900 transition">Create Account</button>
            </form>
        </div>
    </div>
</body>
</html>