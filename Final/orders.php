<?php
require 'productconnection.php';
require 'functions.php';
session_start();

if (!isset($_SESSION['user_id']))
{ 
    header("Location: user.php"); exit; 
}

if (isset($_POST['cancel_order'])) 
{
    if (Product::cancelOrder($pdo, $_POST['order_id'])) 
    {
        header("Location: orders.php?msg=cancelled");
        exit;
    }
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT o.*, 
    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count 
    FROM orders o 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Orders | STOCKPRO</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-900 font-sans">

    <nav class="bg-white border-b border-slate-200 p-4 mb-8">
        <div class="max-w-5xl mx-auto flex justify-between items-center">
            <a href="homepage.php" class="flex items-center gap-2">
                <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011-1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" /></svg>
                </div>
                <span class="text-xl font-black tracking-tight">STOCK<span class="text-indigo-600">PRO</span></span>
            </a>
            <a href="homepage.php" class="text-sm font-bold text-slate-500 hover:text-indigo-600 transition">Back to Shopping</a>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-4">
        <header class="mb-10">
            <h1 class="text-3xl font-black text-slate-800">Order History</h1>
            <p class="text-slate-500">Manage your recent purchases and track shipments.</p>
        </header>

        <?php if (isset($_GET['success'])): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl flex items-center gap-3 animate-bounce">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                <span class="font-bold">Payment Successful! Your order is being processed.</span>
            </div>
        <?php endif; ?>

        <div class="space-y-6">
            <?php foreach ($orders as $order): ?>
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden transition hover:shadow-md">
                    <div class="p-6 sm:p-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-slate-50/50 border-b border-slate-100">
                        <div>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Order Reference</span>
                            <h3 class="text-lg font-bold text-slate-700">#SP-ORD-<?= $order['id'] ?></h3>
                            <p class="text-xs text-slate-400"><?= date('F j, Y, g:i a', strtotime($order['created_at'])) ?></p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <p class="text-xs font-bold text-slate-400 uppercase">Total Amount</p>
                                <p class="text-xl font-black text-indigo-600">$<?= number_format($order['total_price'], 2) ?></p>
                            </div>
                            <?php 
                                $statusStyles = [
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'completed' => 'bg-emerald-100 text-emerald-700',
                                    'cancelled' => 'bg-slate-200 text-slate-500'
                                ];
                                $style = $statusStyles[$order['status']] ?? 'bg-slate-100';
                            ?>
                            <span class="px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-tighter <?= $style ?>">
                                <?= $order['status'] ?>
                            </span>
                        </div>
                    </div>

                    <div class="p-6 flex justify-between items-center">
                        <div class="flex items-center gap-2 text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                            <span class="text-sm font-medium"><?= $order['item_count'] ?> items in this order</span>
                        </div>
                        
                        <?php if ($order['status'] === 'pending'): ?>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this order? Stock will be returned.')">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <button type="submit" name="cancel_order" class="text-sm font-bold text-rose-500 hover:text-rose-700 flex items-center gap-1 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    Cancel Order
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($orders)): ?>
                <div class="text-center py-20 bg-white rounded-3xl border-2 border-dashed border-slate-200">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-700">No orders found</h3>
                    <p class="text-slate-400 mb-6">Looks like you haven't bought anything yet.</p>
                    <a href="homepage.php" class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-100">Start Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="mt-20 py-10 border-t border-slate-200 text-center">
        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">© 2023 STOCKPRO Inventory Management</p>
    </footer>

</body>
</html>