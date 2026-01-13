<?php
require 'productconnection.php';
require 'functions.php';

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') 
{
    header("Location: user.php");
    exit;
}

try 
{
    $totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

    $totalRevenue = $pdo->query("SELECT SUM(total_price) FROM orders WHERE status != 'cancelled'")->fetchColumn();

    $topProducts = $pdo->query("
        SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        GROUP BY p.id
        ORDER BY total_sold DESC
        LIMIT 5
    ")->fetchAll();

    $categoryData = $pdo->query("
        SELECT c.name, COUNT(oi.id) as order_count
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        GROUP BY c.id
    ")->fetchAll();

    $labels = [];
    $counts = [];
    foreach ($categoryData as $row) 
    {
        $labels[] = $row['name'];
        $counts[] = (int)$row['order_count'];
    }
} 
catch (Exception $e) 
{
    die("Report Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>STOCKPRO | Analytics Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-[#f8fafc] min-h-screen text-slate-900">

    <nav class="bg-white/70 backdrop-blur-md border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 h-20 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-200">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <span class="text-2xl font-extrabold tracking-tight">STOCK<span class="text-indigo-600">PRO</span> Reports</span>
            </div>
            <div class="flex items-center gap-6">
                <a href="homepage.php" class="text-sm font-bold text-slate-600 hover:text-indigo-600 transition">Back to Inventory</a>
                <a href="user.php?logout=1" class="text-sm font-bold text-rose-500 bg-rose-50 px-4 py-2 rounded-xl">Logout</a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-6 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
            <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100">
                <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Total Orders</p>
                <h3 class="text-5xl font-black text-slate-900"><?= number_format($totalOrders) ?></h3>
            </div>
            <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100">
                <p class="text-xs font-black text-slate-400 uppercase tracking-widest mb-2">Total Revenue</p>
                <h3 class="text-5xl font-black text-indigo-600">$<?= number_format($totalRevenue, 2) ?></h3>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            <div class="lg:col-span-2 bg-white p-10 rounded-[2.5rem] shadow-sm border border-slate-100">
                <h2 class="text-xl font-extrabold mb-8 italic">Category Performance</h2>
                <canvas id="categoryChart" height="150"></canvas>
            </div>

            <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-slate-100">
                <h2 class="text-xl font-extrabold mb-6 italic">Top Sellers</h2>
                <div class="space-y-6">
                    <?php foreach ($topProducts as $product): ?>
                    <div class="flex justify-between items-center pb-4 border-b border-slate-50 last:border-0">
                        <div>
                            <p class="font-extrabold text-slate-800"><?= htmlspecialchars($product['name']) ?></p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase"><?= $product['total_sold'] ?> Units Sold</p>
                        </div>
                        <span class="text-sm font-black text-indigo-600">$<?= number_format($product['revenue'], 2) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        const ctx = document.getElementById('categoryChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Orders by Category',
                    data: <?= json_encode($counts) ?>,
                    backgroundColor: '#4f46e5',
                    borderRadius: 12,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>