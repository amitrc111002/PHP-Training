<?php
require 'productconnection.php';
require 'functions.php';
session_start();

if (!isset($_SESSION['user_id'])) header("Location: user.php");

if (isset($_POST['cancel_order']))
{
    $order_id = $_POST['order_id'];
    
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();

    foreach ($items as $item)
    {
        $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")
            ->execute([$item['quantity'], $item['product_id']]);
    }
    $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$order_id]);
    $pdo->commit();
}

$user_id = $_SESSION['user_id'];
$orders = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
$orders->execute([$user_id]);
?>
<div class="max-w-4xl mx-auto p-8">
    <h1 class="text-2xl font-bold mb-6">Your Orders</h1>
    <?php foreach($orders->fetchAll() as $order): ?>
        <div class="bg-white p-4 mb-4 rounded-xl border flex justify-between">
            <div>
                <p class="font-bold">Order #<?= $order['id'] ?> - $<?= number_format($order['total_price'], 2) ?></p>
                <p class="text-sm text-slate-500 italic"><?= $order['status'] ?></p>
            </div>
            <?php if ($order['status'] == 'pending'): ?>
                <form method="POST">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <button type="submit" name="cancel_order" class="text-rose-500 font-bold">Cancel</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>