<?php
require 'productconnection.php';
require 'functions.php';
session_start();

if (isset($_GET['remove']))
{
    unset($_SESSION['cart'][$_GET['remove']]);
    if (empty($_SESSION['cart']))
    {
        header("Location: homepage.php");
    }
    else
    {
        header("Location: checkout.php");
    }
    exit;
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['cart']))
{
    header("Location: homepage.php");
    exit;
}

$errors = [];
$success = isset($_GET['success']) ? true : false;
$total_price = 0;
$cart_summary = [];

try
{
    foreach ($_SESSION['cart'] as $p_id => $qty)
    {
        $stmt = $pdo->prepare("SELECT name, price, stock FROM products WHERE id = ?");
        $stmt->execute([$p_id]);
        $product = $stmt->fetch();

        if ($product)
        {
            $subtotal = $product['price'] * $qty;
            $total_price += $subtotal;
            $cart_summary[] = [
                'name' => $product['name'],
                'qty' => $qty,
                'subtotal' => $subtotal
            ];
        }
    }
} 
catch (Exception $e) 
{
    $errors[] = "Error loading cart: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-4" style="font-family: 'Inter', sans-serif;">
    <div class="bg-white p-8 rounded-2xl shadow-xl border border-slate-200 w-full max-w-md text-center">
        
        <?php if ($success): ?>
            <div class="mb-6 text-emerald-500">
                <div class="text-6xl mb-4 text-emerald-400 font-bold">✓</div>
                <h2 class="text-2xl font-bold">Order Placed!</h2>
                <p class="text-slate-500 mt-2">Payment confirmed and stock updated.</p>
                <p class="text-xs text-slate-400 mt-1 italic">An email confirmation has been sent.</p>
            </div>
            <a href="homepage.php" class="inline-block w-full bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-indigo-700 transition">Return Home</a>

        <?php else: ?>
            <h2 class="text-2xl font-bold text-slate-800 mb-4">Complete Your Order</h2>
            
            <?php displayMessages($errors); ?>

            <div class="bg-slate-50 p-4 rounded-xl mb-6 text-left">
                <p class="text-sm text-slate-600 leading-relaxed uppercase font-bold tracking-wider mb-2">Order Summary</p>
                <div class="space-y-2 mb-4">
                    <?php foreach ($cart_summary as $item): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500"><?= htmlspecialchars($item['name']) ?> (x<?= $item['qty'] ?>)</span>
                            <span class="font-medium text-slate-700">$<?= number_format($item['subtotal'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="border-t border-slate-200 pt-2 flex justify-between items-center">
                    <span class="font-bold text-slate-800">Total</span>
                    <span class="text-xl font-bold text-indigo-600">$<?= number_format($total_price, 2) ?></span>
                </div>
            </div>

            <form action="payment.php" method="POST">
                <input type="hidden" name="total_amount" value="<?= $total_price ?>">
                <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition mb-3">
                    Proceed to Payment
                </button>
                <a href="cart.php" class="block text-slate-400 text-sm font-medium hover:text-slate-600">Back to Cart</a>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>