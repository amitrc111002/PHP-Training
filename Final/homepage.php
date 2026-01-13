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

if ($isAdmin && isset($_GET['delete'])) 
{
    Product::deleteProduct($pdo, $_GET['delete']);
    header("Location: homepage.php?msg=deleted");
    exit;
}

if ($isAdmin && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) 
{
    $imageName = "placeholder.png";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) 
    {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $imageName = time() . "_" . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $imageName);
    }

    if (empty($errors)) 
    {
        try 
        {
            $catName = trim($_POST['category_name']);
            $catStmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
            $catStmt->execute([$catName]);
            $category = $catStmt->fetch();

            $categoryId = $category ? $category['id'] : null;
            if (!$categoryId) 
            {
                $insertCat = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                $insertCat->execute([$catName]);
                $categoryId = $pdo->lastInsertId();
            }

            $productObj = new Product($pdo, $_POST['name'], $_POST['price'], null, $imageName, $_POST['description']);
            $result = $productObj->addProduct($categoryId, $_POST['stock'] ?? 10);
            
            if ($result === true) 
                $success_msg[] = "Product added successfully!";
            else 
                $errors = $result;
        } 
        catch (Exception $e) 
        { 
            $errors[] = $e->getMessage(); 
        }
    }
}

$products = $pdo->query("SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' ORDER BY p.id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STOCKPRO | Premium Inventory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="bg-[#f8fafc] min-h-screen text-slate-900">

    <nav class="glass border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 h-20 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-200">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <span class="text-2xl font-extrabold tracking-tight">STOCK<span class="text-indigo-600">PRO</span></span>
            </div>
            
            <div class="flex items-center gap-8">
                <?php if (!$isAdmin): ?>
                    <a href="orders.php" class="text-sm font-semibold text-slate-600 hover:text-indigo-600 transition">Orders</a>
                    <a href="cart.php" class="relative p-2 bg-slate-100 rounded-full hover:bg-indigo-50 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                            <span class="absolute -top-1 -right-1 bg-rose-500 text-white text-[10px] w-5 h-5 flex items-center justify-center rounded-full border-2 border-white"><?= array_sum($_SESSION['cart']) ?></span>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <a href="report.php" class="text-sm font-bold text-indigo-600 bg-indigo-50 px-4 py-2 rounded-xl hover:bg-indigo-100 transition">Analytics Reports</a>
                <?php endif; ?>
                
                <div class="text-right">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest leading-none mb-1"><?= $_SESSION['role'] ?></p>
                    <p class="text-sm font-extrabold text-slate-700 leading-none"><?= htmlspecialchars($_SESSION['username']) ?></p>
                </div>
                <a href="user.php?logout=1" class="text-sm font-bold text-rose-500 bg-rose-50 px-4 py-2 rounded-xl hover:bg-rose-100 transition">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-6 py-12">
        <div class="flex flex-col lg:flex-row gap-12">
            
            <?php if ($isAdmin): ?>
            <aside class="lg:w-1/3">
                <div class="bg-white rounded-3xl p-8 shadow-xl border border-slate-100 sticky top-32">
                    <h2 class="text-xl font-extrabold mb-6">Inventory Management</h2>
                    <?php displayMessages($errors); ?>
                    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') displayMessages(["Product archived successfully."], 'success'); ?>
                    
                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="text" name="name" placeholder="Product Name" required class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl outline-none">
                        <div class="grid grid-cols-2 gap-4">
                            <input type="number" step="0.01" name="price" placeholder="Price ($)" required class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                            <input type="number" name="stock" placeholder="Stock" required class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                        </div>
                        <input type="text" name="category_name" placeholder="Category Name" required class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                        <textarea name="description" placeholder="Product Description..." rows="3" class="w-full px-5 py-3.5 bg-slate-50 border border-slate-200 rounded-2xl outline-none"></textarea>
                        
                        <div class="border-2 border-dashed border-slate-200 p-4 rounded-2xl text-center">
                            <input type="file" name="image" class="text-xs text-slate-500">
                        </div>
                        <button type="submit" name="add_product" class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-bold shadow-lg hover:bg-indigo-700 transition">Publish Product</button>
                    </form>
                </div>
            </aside>
            <?php endif; ?>

            <main class="flex-1">
                <header class="mb-10">
                    <h1 class="text-4xl font-extrabold text-slate-900 tracking-tight italic">Our Collection</h1>
                    <p class="text-slate-500 mt-2">Click any item for details and purchase options.</p>
                </header>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <?php foreach ($products as $p): ?>
                    <div onclick="openModal('<?= $p['id'] ?>', '<?= addslashes($p['name']) ?>', '<?= $p['price'] ?>', '<?= $p['cat_name'] ?>', '<?= $p['stock'] ?>', '<?= addslashes($p['description']) ?>')" 
                         class="bg-white rounded-[2.5rem] overflow-hidden border border-slate-100 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 cursor-pointer relative group">
                        
                        <div class="relative h-64 overflow-hidden">
                            <img src="uploads/<?= !empty($p['image']) ? $p['image'] : 'placeholder.png' ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                        </div>

                        <div class="p-8">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600 mb-1 block"><?= htmlspecialchars($p['cat_name']) ?></span>
                                    <h3 class="text-xl font-extrabold text-slate-800"><?= htmlspecialchars($p['name']) ?></h3>
                                </div>
                                <span class="text-2xl font-black text-slate-900">$<?= number_format($p['price'], 2) ?></span>
                            </div>
                            
                            <div class="flex justify-between items-center pt-4 border-t border-slate-50">
                                <span class="text-xs font-bold <?= $p['stock'] <= 5 ? 'text-rose-500' : 'text-slate-400' ?>">
                                    <?= $p['stock'] ?> UNITS AVAILABLE
                                </span>
                                <?php if ($isAdmin): ?>
                                    <div class="flex gap-2">
                                        <a href="productedit.php?id=<?= $p['id'] ?>" onclick="event.stopPropagation()" class="p-2 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-100 transition">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </a>
                                        <a href="homepage.php?delete=<?= $p['id'] ?>" onclick="event.stopPropagation(); return confirm('Archive this item?')" class="p-2 bg-rose-50 text-rose-500 rounded-lg hover:bg-rose-100 transition">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>
    </div>

    <div id="productModal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-md z-[100] hidden flex items-center justify-center p-6" onclick="if(event.target === this) closeModal()">
        <div class="bg-white w-full max-w-2xl rounded-[3rem] shadow-2xl overflow-hidden relative">
            <div class="p-12">
                <button onclick="closeModal()" class="absolute top-8 right-8 p-3 bg-slate-100 rounded-full hover:bg-slate-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" stroke-width="3"/></svg>
                </button>
                <span id="modalCat" class="text-xs font-black uppercase tracking-[0.3em] text-indigo-600 mb-2 block"></span>
                <h2 id="modalTitle" class="text-4xl font-extrabold text-slate-900 mb-6 italic"></h2>
                
                <div class="mb-10">
                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Description</h4>
                    <p id="modalDesc" class="text-slate-600 leading-relaxed italic"></p>
                </div>

                <div class="bg-slate-50 p-8 rounded-[2rem] border border-slate-100">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Unit Price</p>
                            <p id="modalPrice" class="text-3xl font-black text-indigo-600"></p>
                        </div>
                        <div id="stockBadge" class="px-4 py-1.5 bg-white rounded-full text-[10px] font-bold border border-slate-200"></div>
                    </div>

                    <?php if (!$isAdmin): ?>
                    <form method="POST" action="cart.php" class="flex gap-4">
                        <input type="hidden" name="product_id" id="modalProductId">
                        <div class="flex-1">
                            <input type="number" name="qty" id="modalQtyInput" value="1" min="1" class="w-full px-6 py-4 bg-white border-none rounded-2xl text-lg font-black focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <button type="submit" name="add_to_cart" class="bg-indigo-600 text-white px-10 py-4 rounded-2xl font-black uppercase tracking-widest hover:bg-indigo-700 transition shadow-lg">Add to Cart</button>
                    </form>
                    <?php else: ?>
                        <div class="text-center p-4 border border-dashed border-slate-200 rounded-2xl text-xs font-bold text-slate-400 italic">Preview Mode (Admins cannot purchase)</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openModal(id, title, price, category, stock, desc) {
            document.getElementById('modalTitle').innerText = title;
            document.getElementById('modalCat').innerText = category;
            document.getElementById('modalPrice').innerText = '$' + parseFloat(price).toLocaleString(undefined, {minimumFractionDigits: 2});
            document.getElementById('modalProductId').value = id;
            document.getElementById('stockBadge').innerText = stock + ' IN STOCK';
            document.getElementById('modalDesc').innerText = desc ? desc : "No description provided.";
            
            const qtyInput = document.getElementById('modalQtyInput');
            if(qtyInput) { qtyInput.max = stock; qtyInput.value = 1; }
            document.getElementById('productModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        function closeModal() {
            document.getElementById('productModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    </script>
</body>
</html>