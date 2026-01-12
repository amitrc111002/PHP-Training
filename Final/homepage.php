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

if (isset($_GET['err']) && $_GET['err'] == 'stock')
{
    $errors[] = "Requested quantity exceeds available stock.";
}

if ($isAdmin && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product']))
{
    $imageName = "placeholder.png";
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
            $errors[] = "Failed to upload image.";
        }
    }

    if (empty($errors))
    {
        $productObj = new Product($pdo, $_POST['name'], $_POST['price'], null, $imageName);
        $result = $productObj->addProduct($_POST['category_id'], $_POST['stock'] ?? 10);

        if ($result === true)
        {
            $success_msg[] = "Product added successfully!";
        }
        else
        {
            $errors = $result;
        }
    }
}

if ($isAdmin && isset($_GET['delete']))
{
    Product::deleteProduct($pdo, $_GET['delete']);
    header("Location: homepage.php?msg=deleted");
    exit;
}

if (isset($_GET['msg']))
{
    if ($_GET['msg'] == 'deleted') $success_msg[] = "Product removed from inventory.";
    if ($_GET['msg'] == 'updated') $success_msg[] = "Inventory updated successfully.";
}

$products = $pdo->query("SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen font-sans text-slate-900">

    <nav class="bg-white border-b border-slate-200 sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
                        </svg>
                    </div>
                    <span class="text-xl font-black tracking-tight text-slate-800">STOCK<span class="text-indigo-600">PRO</span></span>
                </div>
                <div class="flex items-center gap-6">
                    <a href="orders.php" class="text-sm font-medium text-slate-600 hover:text-indigo-600 transition">My Orders</a>
                    <a href="cart.php" class="relative group p-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-600 group-hover:text-indigo-600 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                            <span class="absolute top-0 right-0 block h-4 w-4 rounded-full bg-rose-500 text-[10px] font-bold text-white text-center leading-4 ring-2 ring-white">
                                <?= array_sum($_SESSION['cart']) ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <div class="h-8 w-px bg-slate-200"></div>
                    <div class="text-right hidden sm:block">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest leading-none mb-1"><?= $_SESSION['role'] ?></p>
                        <p class="text-sm font-bold text-slate-700 leading-none"><?= htmlspecialchars($_SESSION['username']) ?></p>
                    </div>
                    <a href="user.php?logout=1" class="bg-slate-100 text-slate-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-rose-50 hover:text-rose-600 transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
            
            <?php if ($isAdmin): ?>
            <div class="lg:col-span-4">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 sticky top-24">
                    <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Add New Product
                    </h2>
                    
                    <?php displayMessages($errors); ?>
                    <?php displayMessages($success_msg, 'success'); ?>

                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-600 mb-1">Product Name</label>
                            <input type="text" name="name" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-600 mb-1">Price ($)</label>
                                <input type="number" step="0.01" name="price" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-600 mb-1">Stock Units</label>
                                <input type="number" name="stock" value="10" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-600 mb-1">Category</label>
                            <select name="category_id" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition cursor-pointer">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-600 mb-1">Product Photo</label>
                            <input type="file" name="image" accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                        </div>
                        <button type="submit" name="add_product" class="w-full bg-indigo-600 text-white py-3 rounded-xl font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-100 flex items-center justify-center gap-2">
                            Add to Inventory
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div class="<?= $isAdmin ? 'lg:col-span-8' : 'lg:col-span-12' ?>">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                        <h2 class="text-xl font-bold text-slate-800">Current Inventory</h2>
                        <span class="px-3 py-1 bg-indigo-50 text-indigo-600 text-xs font-bold rounded-full uppercase tracking-wider"><?= count($products) ?> Items</span>
                    </div>

                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Product</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest">Category</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest text-center">Price</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest text-center">Stock</th>
                                <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-widest text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($products as $p): ?>
                            <tr class="hover:bg-slate-50/50 transition group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-xl border border-slate-200 overflow-hidden bg-slate-100">
                                            <img src="uploads/<?= !empty($p['image']) ? $p['image'] : 'placeholder.png' ?>" 
                                                 alt="<?= htmlspecialchars($p['name']) ?>" 
                                                 class="w-full h-full object-cover">
                                        </div>
                                        <span class="font-bold text-slate-700"><?= htmlspecialchars($p['name']) ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500 font-medium">
                                    <span class="px-2.5 py-1 bg-slate-100 rounded-lg text-slate-600"><?= htmlspecialchars($p['cat_name']) ?></span>
                                </td>
                                <td class="px-6 py-4 text-sm font-black text-slate-700 text-center">$<?= number_format($p['price'], 2) ?></td>
                                <td class="px-6 py-4 text-center">
                                    <?php 
                                        $stockClass = $p['stock'] <= 5 ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600';
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-black <?= $stockClass ?>">
                                        <?= $p['stock'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <?php if ($isAdmin): ?>
                                        <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition">
                                            <a href="productedit.php?id=<?= $p['id'] ?>" class="p-2 text-slate-400 hover:text-indigo-600 transition" title="Edit">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <a href="homepage.php?delete=<?= $p['id'] ?>" onclick="return confirm('Archive this item?')" class="p-2 text-slate-400 hover:text-rose-600 transition" title="Delete">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <?php if ($p['stock'] > 0): ?>
                                            <form method="POST" action="cart.php" class="flex items-center justify-end gap-2">
                                                <input type="number" name="qty" value="1" min="1" max="<?= $p['stock'] ?>" class="w-14 px-2 py-1 bg-slate-100 border-none rounded text-sm outline-none focus:ring-1 focus:ring-indigo-500">
                                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                                <button type="submit" name="add_to_cart" class="bg-indigo-600 text-white px-4 py-1.5 rounded-lg text-sm font-bold hover:bg-indigo-700 transition">
                                                    Add
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