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

    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    
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

if (isset($_POST['remove_item']))
{
    $id_to_remove = $_POST['product_id'];
    $remove_input = $_POST['remove_qty']; 

    if (isset($_SESSION['cart'][$id_to_remove])) 
    {
        if ($remove_input !== "")
        {
            $remove_qty = (int)$remove_input;
            if ($remove_qty >= $_SESSION['cart'][$id_to_remove])
            {
                unset($_SESSION['cart'][$id_to_remove]);
            }
            else
            {
                $_SESSION['cart'][$id_to_remove] -= $remove_qty;
            }
        }
    }
    header("Location: cart.php");
    exit;
}

if (isset($_POST['delete_all']))
{
    $id_to_remove = $_POST['product_id'];
    unset($_SESSION['cart'][$id_to_remove]);
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
    <title>Shopping Cart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen p-8">

    <div class="max-w-5xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Your Cart</h1>
            <a href="homepage.php" class="text-indigo-600 font-semibold hover:underline">← Back to Shop</a>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-sm font-semibold text-slate-600">Product</th>
                        <th class="px-6 py-4 text-sm font-semibold text-slate-600 text-center">In Cart</th>
                        <th class="px-6 py-4 text-sm font-semibold text-slate-600">Subtotal</th>
                        <th class="px-6 py-4 text-sm font-semibold text-slate-600 text-right">Remove Items</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($cart_items)): ?>
                        <tr><td colspan="4" class="px-6 py-20 text-center text-slate-400">Cart is empty</td></tr>
                    <?php else: foreach ($cart_items as $item): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <span class="font-semibold text-slate-800"><?= htmlspecialchars($item['name']) ?></span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full text-sm font-bold">
                                    <?= $item['qty'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-600 font-medium">$<?= number_format($item['subtotal'], 2) ?></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" class="flex items-center gap-1">
                                        <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                        <input type="number" name="remove_qty" min="1" max="<?= $item['qty'] ?>" placeholder="Qty" 
                                               class="w-16 text-sm border border-slate-200 rounded-lg p-1.5 outline-none focus:ring-2 focus:ring-rose-500 transition">
                                        <button type="submit" name="remove_item" class="bg-rose-50 text-rose-600 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-rose-100 transition">
                                            Remove
                                        </button>
                                    </form>

                                    <form method="POST">
                                        <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                        <button type="submit" name="delete_all" onclick="return confirm('Remove all units of this product?')" 
                                                class="bg-slate-100 text-slate-500 p-1.5 rounded-lg hover:bg-rose-500 hover:text-white transition" title="Delete All">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
            
            <?php if (!empty($cart_items)): ?>
            <div class="p-8 bg-slate-50 border-t border-slate-200 flex justify-between items-center">
                <div class="text-2xl font-bold text-slate-800">Total: $<?= number_format($total_price, 2) ?></div>
                <a href="checkout.php" class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition">
                    Checkout Now
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>