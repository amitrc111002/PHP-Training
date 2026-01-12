<?php
require 'productconnection.php';
require 'functions.php';
session_start();

if (!isset($_SESSION['user_id']) || empty($_SESSION['cart']))
{
    header("Location: homepage.php");
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    try
    {
        $pdo->beginTransaction();

        $total_price = 0;
        $items_to_process = [];

        foreach ($_SESSION['cart'] as $p_id => $qty)
        {
            $stmt = $pdo->prepare("SELECT name, price, stock FROM products WHERE id = ? FOR UPDATE");
            $stmt->execute([$p_id]);
            $product = $stmt->fetch();

            if ($product['stock'] < $qty)
            {
                throw new Exception("Not enough stock for " . $product['name']);
            }

            $subtotal = $product['price'] * $qty;
            $total_price += $subtotal;
            $items_to_process[] = [
                'id' => $p_id,
                'qty' => $qty,
                'price' => $product['price']
            ];
        }

        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $total_price]);
        $order_id = $pdo->lastInsertId();

        foreach ($items_to_process as $item)
        {
            $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$item['qty'], $item['id']]);

            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['id'], $item['qty'], $item['price']]);
        }

        $pdo->commit();
        unset($_SESSION['cart']);
        $success = true;

    }
    catch (Exception $e)
    {
        $pdo->rollBack();
        $errors[] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
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
                <p class="text-slate-500 mt-2">Your transaction was processed successfully.</p>
            </div>
            <a href="homepage.php" class="inline-block bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold">Return Home</a>
        <?php else: ?>
            <h2 class="text-2xl font-bold text-slate-800 mb-4">Complete Your Order</h2>
            <?php displayMessages($errors); ?>
            <p class="text-slate-600 mb-8">Confirming your order will deduct stock and finalize the purchase.</p>
            <form method="POST">
                <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">Confirm and Pay</button>
            </form>
            <a href="cart.php" class="block mt-4 text-slate-400 hover:text-slate-600">Back to Cart</a>
        <?php endif; ?>
    </div>
</body>
</html>