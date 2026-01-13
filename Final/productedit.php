<?php
require 'productconnection.php';
require 'functions.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: user.php");
    exit;
}

$errors = [];

try {
    if (!isset($_GET['id'])) die("Missing Product ID.");
    
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();

    if (!$data) die("Product not found.");

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newName = $_POST['name'];
        $newPrice = $_POST['price'];
        $newDesc = $_POST['description'];
        
        $addedStock = (int)$_POST['stock_to_add'];
        $totalStock = (int)$data['stock'] + $addedStock;
        
        if(isset($_POST['override_stock']) && $_POST['override_stock'] !== "") {
            $totalStock = (int)$_POST['override_stock'];
        }

        $imageName = $data['image']; 
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $targetDir = "uploads/";
            $imageName = time() . "_" . basename($_FILES['image']['name']);
            move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $imageName);
        }

        $updated = new Product($pdo, $newName, $newPrice, $id, $imageName, $newDesc);
        $result = $updated->updateProduct($totalStock);
        
        if ($result === true) {
            header("Location: homepage.php?msg=updated");
            exit;
        } else { $errors = $result; }
    }
} catch (Exception $e) { die("Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product | STOCKPRO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-4" style="font-family: 'Plus Jakarta Sans', sans-serif;">
    <div class="bg-white p-10 rounded-[2.5rem] shadow-xl border border-slate-100 w-full max-w-md">
        <h2 class="text-2xl font-extrabold text-slate-800 mb-2">Edit Product</h2>
        <p class="text-slate-400 text-sm mb-8">Update collection details.</p>

        <?php displayMessages($errors); ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-5">
            <input type="text" name="name" value="<?= htmlspecialchars($data['name']) ?>" placeholder="Title" class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl outline-none">
            <div class="grid grid-cols-2 gap-4">
                <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($data['price']) ?>" placeholder="Price" class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                <input type="number" name="stock_to_add" value="0" min="0" placeholder="Add Stock" class="w-full px-5 py-3.5 bg-indigo-50 border border-indigo-100 text-indigo-600 font-bold rounded-2xl">
            </div>
            <textarea name="description" rows="3" placeholder="Description" class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl outline-none"><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
            <input type="file" name="image" class="text-xs text-slate-500">
            <div class="flex flex-col gap-3 pt-4">
                <button type="submit" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black uppercase tracking-widest hover:bg-slate-800 transition">Save Changes</button>
                <a href="homepage.php" class="w-full bg-slate-100 text-slate-500 py-4 rounded-2xl font-black uppercase tracking-widest text-center hover:bg-slate-200">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>