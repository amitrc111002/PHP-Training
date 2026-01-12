<?php
require 'productconnection.php';
require 'functions.php';

session_start();

if (!isset($_SESSION['user_id']))
{
    header("Location: user.php");
    exit;
}

$isAdmin = ($_SESSION['role'] === 'admin');
$errors = [];
$success_msg = [];

if ($isAdmin && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product']))
{
    $productObj = new Product($pdo, $_POST['name'], $_POST['price']);
    $result = $productObj->addProduct($_POST['category_id'], $_POST['stock'] ?? 10);

    if ($result === true)
    {
        $success_msg = "Product added successfully!";
    }
    else
    {
        $errors = $result;
    }
}

if ($isAdmin && isset($_GET['delete']))
{
    Product::deleteProduct($pdo, $_GET['delete']);
    header("Location: homepage.php?msg=deleted");
    exit;
}

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted')
{
    $success_msg = "Product removed from inventory.";
}

$products = $pdo->query("SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard - <?= ucfirst($_SESSION['role']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen p-4 md:p-8">

    <div class="max-w-6xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-800 tracking-tight">System Inventory</h1>
                <p class="text-slate-500">Logged in as: <span class="font-semibold text-indigo-600"><?= htmlspecialchars($_SESSION['username']) ?></span> (<?= ucfirst($_SESSION['role']) ?>)</p>
            </div>
            
            <div class="flex items-center gap-4">
                <?php if (!$isAdmin): ?>
                    <a href="cart.php" class="flex items-center gap-2 bg-white border border-slate-200 px-5 py-2.5 rounded-xl shadow-sm hover:bg-slate-50 transition relative">
                        <span class="text-xl">🛒</span>
                        <span class="font-bold text-slate-700">Cart</span>
                        <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                            <span class="absolute -top-2 -right-2 bg-indigo-600 text-white text-[10px] w-5 h-5 flex items-center justify-center rounded-full border-2 border-white">
                                <?= array_sum($_SESSION['cart']) ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>

                <a href="user.php?logout=1" class="bg-rose-50 text-rose-600 px-5 py-2.5 rounded-xl font-bold hover:bg-rose-100 transition">
                    Logout
                </a>
            </div>
        </div>

        <?php 
            displayMessages($errors); 
            if ($success_msg) displayMessages((array)$success_msg, 'success');
        ?>

        <div class="grid grid-cols-1 <?= $isAdmin ? 'lg:grid-cols-3' : '' ?> gap-8">
            
            <?php if ($isAdmin): ?>
            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 sticky top-8">
                    <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                        <span class="bg-indigo-100 p-1.5 rounded-lg text-indigo-600 text-sm">✚</span>
                        Add New Product
                    </h2>
                    
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-600 mb-1">Product Name</label>
                            <input type="text" name="name" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="e.g. Wireless Mouse">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-600 mb-1">Price ($)</label>
                                <input type="number" step="0.01" name="price" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="0.00">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-600 mb-1">Stock Qty</label>
                                <input type="number" name="stock" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition" placeholder="10">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-600 mb-1">Category</label>
                            <select name="category_id" class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none transition appearance-none">
                                <?php foreach($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" name="add_product" class="w-full bg-indigo-600 text-white py-3 rounded-xl font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-100 mt-2">
                            Add to Inventory
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div class="<?= $isAdmin ? 'lg:col-span-2' : 'w-full' ?>">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-4 text-sm font-semibold text-slate-600 uppercase tracking-wider">Product Info</th>
                                <th class="px-6 py-4 text-sm font-semibold text-slate-600 uppercase tracking-wider text-center">Stock</th>
                                <th class="px-6 py-4 text-sm font-semibold text-slate-600 uppercase tracking-wider">Price</th>
                                <th class="px-6 py-4 text-sm font-semibold text-slate-600 uppercase tracking-wider text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <?php foreach ($products as $p): ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-800"><?= htmlspecialchars($p['name']) ?></div>
                                    <div class="text-xs text-slate-400"><?= htmlspecialchars($p['cat_name']) ?></div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($p['stock'] <= 0): ?>
                                        <span class="bg-rose-50 text-rose-600 text-[10px] font-bold px-2 py-1 rounded-full uppercase">Out of Stock</span>
                                    <?php elseif ($p['stock'] <= 5): ?>
                                        <span class="bg-amber-50 text-amber-600 text-[10px] font-bold px-2 py-1 rounded-full uppercase">Low: <?= $p['stock'] ?></span>
                                    <?php else: ?>
                                        <span class="text-slate-600 text-sm"><?= $p['stock'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-700">
                                    $<?= number_format($p['price'], 2) ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <?php if ($isAdmin): ?>
                                        <div class="flex justify-end gap-3">
                                            <a href="productedit.php?id=<?= $p['id'] ?>" class="text-indigo-600 hover:text-indigo-800 text-sm font-bold">Edit</a>
                                            <a href="homepage.php?delete=<?= $p['id'] ?>" onclick="return confirm('Permanently delete this product?')" class="text-rose-400 hover:text-rose-600 text-sm font-bold">Delete</a>
                                        </div>
                                    <?php else: ?>
                                        <?php if ($p['stock'] > 0): ?>
                                            <form action="cart.php" method="POST">
                                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                                <button type="submit" name="add_to_cart" class="bg-indigo-600 text-white px-4 py-1.5 rounded-lg text-sm font-bold hover:bg-indigo-700 transition">
                                                    Add to Cart
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button disabled class="bg-slate-200 text-slate-400 px-4 py-1.5 rounded-lg text-sm font-bold cursor-not-allowed">
                                                Unavailable
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if (empty($products)): ?>
                        <div class="p-12 text-center text-slate-400">
                            <p>No products found in inventory.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

</body>
</html>