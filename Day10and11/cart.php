<?php
require 'productconnection.php';
require 'functions.php';
session_start();

if (!isset($_SESSION['user_id']))
{
    header("Location: user.php");
    exit;
}

if (isset($_POST['add_to_cart']))
{
    $p_id = $_POST['product_id'];
    $requested_qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;

    if (!isset($_SESSION['cart']))
        $_SESSION['cart'] = [];
    
    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->execute([$p_id]);
    $product = $stmt->fetch();

    $current_in_cart = isset($_SESSION['cart'][$p_id]) ? $_SESSION['cart'][$p_id] : 0;
    $new_total = $current_in_cart + $requested_qty;

    if ($product && $new_total > $product['stock'])
    {
        header("Location: homepage.php?err=stock");
        exit;
    }

    $_SESSION['cart'][$p_id] = $new_total;
    header("Location: cart.php");
    exit;
}

if (isset($_GET['remove']))
{
    unset($_SESSION['cart'][$_GET['remove']]);
    header("Location: cart.php");
    exit;
}

$cart_items = [];
$total_price = 0;

if (!empty($_SESSION['cart']))
{
    $ids = implode(',', array_keys($_SESSION['cart']));
    $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)");
    while ($row = $stmt->fetch())
    {
        $qty = $_SESSION['cart'][$row['id']];
        $subtotal = $row['price'] * $qty;
        $total_price += $subtotal;
        $row['qty'] = $qty;
        $row['subtotal'] = $subtotal;
        $cart_items[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body class="bg-slate-50 p-4 md:p-8" style="font-family: 'Inter', sans-serif;">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-slate-800">Shopping Cart</h1>
            <a href="homepage.php" class="text-indigo-600 font-semibold hover:text-indigo-800">← Back to Shop</a>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-sm font-semibold text-slate-600">Product</th>
                        <th class="px-6 py-4 text-sm font-semibold text-slate-600 text-center">Quantity</th>
                        <th class="px-6 py-4 text-sm font-semibold text-slate-600">Subtotal</th>
                        <th class="px-6 py-4 text-sm font-semibold text-slate-600 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php if (empty($cart_items)): ?>
                        <tr><td colspan="4" class="px-6 py-12 text-center text-slate-400">Your cart is empty.</td></tr>
                    <?php else: foreach ($cart_items as $item): ?>
                        <tr>
                            <td class="px-6 py-4 font-medium text-slate-800"><?= htmlspecialchars($item['name']) ?></td>
                            <td class="px-6 py-4 text-center">
                                <span class="bg-slate-100 px-3 py-1 rounded-full text-sm font-bold text-slate-700">
                                    <?= $item['qty'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-600">$<?= number_format($item['subtotal'], 2) ?></td>
                            <td class="px-6 py-4 text-right">
                                <a href="cart.php?remove=<?= $item['id'] ?>\" class="text-rose-500 hover:text-rose-700 font-medium">Remove</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
            
            <?php if (!empty($cart_items)): ?>
            <div class="p-6 bg-slate-50 border-t border-slate-200 flex justify-between items-center">
                <div>
                    <span class="text-slate-500">Total Amount:</span>
                    <span class="text-2xl font-bold text-slate-800 ml-2">$<?= number_format($total_price, 2) ?></span>
                </div>
                <a href="checkout.php" class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition">Proceed to Checkout</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>