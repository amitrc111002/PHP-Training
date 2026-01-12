<?php
require 'productconnection.php';
require 'functions.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin')
{
    header("Location: user.php");
    exit;
}

$errors = [];

try
{
    if (!isset($_GET['id']))
    {
        die("Missing Product ID.");
    }
    
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();

    if (!$data)
    {
        die("Product not found.");
    }

    $product = new Product($pdo, $data['name'], $data['price'], $id);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST')
    {
        // Day 11: Include stock in the update process
        $updated = new Product($pdo, $_POST['name'], $_POST['price'], $id);
        $result = $updated->updateProduct($_POST['stock']);
        
        if ($result === true)
        {
            header("Location: homepage.php?msg=updated");
            exit;
        }
        else
        {
            $errors = $result;
        }
    }
} 
catch (Exception $e)
{
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-4" style="font-family: 'Inter', sans-serif;">
    <div class="bg-white p-8 rounded-2xl shadow-xl border border-slate-200 w-full max-w-md">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-slate-800">Edit Product</h2>
            <p class="text-sm text-slate-500">Update item details and inventory levels.</p>
        </div>
        
        <?php displayMessages($errors); ?>
        
        <form method="POST" class="space-y-5">
            <div>
                <label class="block text-sm font-semibold text-slate-600 mb-1">Product Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($data['name']) ?>" 
                       class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1">Price ($)</label>
                    <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($data['price']) ?>" 
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-1">Stock Level</label>
                    <input type="number" name="stock" value="<?= htmlspecialchars($data['stock']) ?>" 
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
                </div>
            </div>
            
            <div class="flex flex-col gap-3 pt-4">
                <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-xl font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-100">
                    Save Changes
                </button>
                <a href="homepage.php" class="w-full bg-slate-100 text-slate-600 py-3 rounded-xl font-bold text-center hover:bg-slate-200 transition">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</body>
</html>