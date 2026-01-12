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
    $result = $productObj->addProduct($_POST['category_id']);
    if($result === true)
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

if(isset($_GET['msg']) && $_GET['msg'] == 'deleted')
{
    $success_msg = "Product removed.";
}

$products = $pdo->query("SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id = c.id")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= ucfirst($_SESSION['role']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }</style>
</head>
<body class="p-8">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">Inventory Dashboard</h1>
                <p class="text-slate-500">Welcome, <b><?= htmlspecialchars($_SESSION['username']) ?></b> (<?= ucfirst($_SESSION['role']) ?>)</p>
            </div>
            <div class="flex gap-4 items-center">
                <span class="bg-indigo-100 text-indigo-700 px-4 py-1 rounded-full text-sm font-semibold"><?= count($products) ?> Items</span>
                <a href="user.php?logout=1" class="text-rose-500 font-semibold hover:text-rose-700">Logout</a>
            </div>
        </div>

        <?php 
            displayMessages($errors); 
            if ($success_msg) displayMessages([$success_msg], 'success');
        ?>

        <div class="grid grid-cols-1 <?= $isAdmin ? 'lg:grid-cols-3' : '' ?> gap-8">
            <?php if ($isAdmin): ?>
            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
                    <h2 class="text-xl font-semibold mb-4 text-slate-700">Add New Item</h2>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Product Name</label>
                            <input type="text" name="name" class="w-full px-4 py-2 border border-slate-300 rounded-lg outline-none" placeholder="Enter name...">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Price ($)</label>
                            <input type="number" step="0.01" name="price" class="w-full px-4 py-2 border border-slate-300 rounded-lg outline-none" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-600 mb-1">Category</label>
                            <select name="category_id" class="w-full px-4 py-2 border border-slate-300 rounded-lg outline-none bg-white">
                                <?php foreach($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="add_product" class="w-full bg-indigo-600 text-white py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">Save Product</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div class="<?= $isAdmin ? 'lg:col-span-2' : 'w-full' ?>">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4 text-sm font-semibold text-slate-600">Product</th>
                                <th class="px-6 py-4 text-sm font-semibold text-slate-600">Price</th>
                                <th class="px-6 py-4 text-sm font-semibold text-slate-600">Category</th>
                                <?php if ($isAdmin): ?><th class="px-6 py-4 text-sm font-semibold text-slate-600 text-right">Actions</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($products as $p): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4 font-medium text-slate-800"><?= htmlspecialchars($p['name']) ?></td>
                                <td class="px-6 py-4 text-slate-600">$<?= number_format($p['price'], 2) ?></td>
                                <td class="px-6 py-4"><span class="bg-slate-100 text-slate-600 text-xs px-2 py-1 rounded"><?= htmlspecialchars($p['cat_name']) ?></span></td>
                                <?php if ($isAdmin): ?>
                                <td class="px-6 py-4 text-right space-x-3">
                                    <a href="productedit.php?id=<?= $p['id'] ?>" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</a>
                                    <a href="homepage.php?delete=<?= $p['id'] ?>" onclick="return confirm('Delete?')" class="text-rose-500 hover:text-rose-700 text-sm font-medium">Delete</a>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>