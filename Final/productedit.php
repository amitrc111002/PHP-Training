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

    if ($_SERVER['REQUEST_METHOD'] === 'POST') 
    {
        $newName = $_POST['name'];
        $newPrice = $_POST['price'];
        
        $addedStock = (int)$_POST['stock_to_add'];
        $currentStock = (int)$data['stock'];
        $totalStock = $currentStock + $addedStock;
        
        if(isset($_POST['override_stock']) && $_POST['override_stock'] !== "") 
        {
            $totalStock = (int)$_POST['override_stock'];
        }

        $imageName = $data['image'];

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) 
        {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) 
            {
                mkdir($targetDir, 0777, true);
            }
            
            $imageName = time() . "_" . basename($_FILES['image']['name']);
            $targetFilePath = $targetDir . $imageName;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) 
            {
                $errors[] = "Failed to upload new image.";
            }
        }

        if (empty($errors)) 
        {
            $updated = new Product($pdo, $newName, $newPrice, $id, $imageName);
            $result = $updated->updateProduct($totalStock);
            
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
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-slate-800">Edit Inventory</h2>
            <p class="text-slate-500 text-sm">Update product details or add new stock.</p>
        </div>

        <?php displayMessages($errors); ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-5">
            
            <div class="flex flex-col items-center mb-4">
                <label class="block text-sm font-semibold text-slate-600 mb-2 w-full text-left">Current Product Image</label>
                <div class="w-32 h-32 rounded-xl border border-slate-200 overflow-hidden bg-slate-100">
                    <img src="uploads/<?= !empty($data['image']) ? $data['image'] : 'placeholder.png' ?>" 
                         class="w-full h-full object-cover">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-600 mb-1">Product Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($data['name']) ?>" 
                       class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-600 mb-1">Price ($)</label>
                <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($data['price']) ?>" 
                       class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-600 mb-1">Change Product Photo</label>
                <input type="file" name="image" accept="image/*" 
                       class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
            </div>

            <div class="p-4 bg-indigo-50 rounded-xl border border-indigo-100">
                <label class="block text-sm font-semibold text-indigo-900 mb-1">Add to Stock</label>
                <div class="flex items-center gap-3">
                    <input type="number" name="stock_to_add" value="0" min="0"
                           class="w-full px-4 py-2.5 bg-white border border-indigo-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition">
                    <span class="text-xs text-indigo-500 font-medium whitespace-nowrap">Current: <?= $data['stock'] ?></span>
                </div>
            </div>

            <details class="group">
                <summary class="text-xs text-slate-400 cursor-pointer hover:text-slate-600 transition list-none flex items-center gap-1">
                    <span>Advanced: Manual Stock Override</span>
                    <svg class="w-3 h-3 group-open:rotate-180 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </summary>
                <div class="mt-2">
                    <input type="number" name="override_stock" placeholder="Set exact total stock..."
                           class="w-full px-4 py-2 text-sm bg-slate-100 border border-slate-200 rounded-lg outline-none">
                </div>
            </details>
            
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