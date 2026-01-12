<?php
require 'productconnection.php';
require 'functions.php';

$errors = [];

try
{
    if(!isset($_GET['id']))
    {
        die("Missing ID.");
    }
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();

    if(!$data)
    {
        die("Product not found.");
    }

    $product = new Product($pdo, $data['name'], $data['price'], $id);
    if($_SERVER['REQUEST_METHOD'] === 'POST')
    {
        $updated = new Product($pdo, $_POST['name'], $_POST['price'], $id);
        $result = $updated->updateProduct();
        if($result === true)
        {
            header("Location: homepage.php");
            exit;
        }
        else
        {
            $errors = $result;
        }
    }
}
catch(Exception $e)
{
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-4" style="font-family: 'Inter', sans-serif;">
    <div class="bg-white p-8 rounded-2xl shadow-xl border border-slate-200 w-full max-w-md">
        <h2 class="text-2xl font-bold text-slate-800 mb-6">Edit Product</h2>
        
        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-semibold text-slate-600 mb-2">Item Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($product->getName()) ?>" 
                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-600 mb-2">Item Price ($)</label>
                <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($product->getPrice()) ?>" 
                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            
            <div class="flex gap-4 pt-4">
                <button type="submit" class="flex-1 bg-indigo-600 text-white py-3 rounded-xl font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">Update</button>
                <a href="homepage.php" class="flex-1 bg-slate-100 text-slate-600 py-3 rounded-xl font-bold text-center hover:bg-slate-200 transition">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
