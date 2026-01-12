<?php
require 'productconnection.php';
require 'functions.php';
require 'mail.php';

session_start();
if(!isset($_SESSION['user_id']) || empty($_SESSION['cart']))
{
    header("Location: homepage.php");
    exit;
}

$errors = [];
$processing = false;

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment']))
{
    $processing = true;
    usleep(1000000);

    try
    {
        $pdo->beginTransaction();
        $total_price = 0;
        $items_to_process = [];

        foreach($_SESSION['cart'] as $p_id => $qty)
        {
            $stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id = ? FOR UPDATE");
            $stmt->execute([$p_id]);
            $product = $stmt->fetch();

            if(!$product || $product['stock'] < $qty)
            {
                throw new Exception("Stock lost for " . ($product['name'] ?? 'Item') . " during payment.");
            }

            $subtotal = $product['price'] * $qty;
            $total_price += $subtotal;
            $items_to_process[] = [
                'id' => $product['id'], 
                'qty' => $qty, 
                'name' => $product['name'],
                'price' => $product['price']
            ];
        }
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$_SESSION['user_id'], $total_price]);
        $order_id = $pdo->lastInsertId();

        foreach($items_to_process as $item)
        {
            $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmtItem->execute([$order_id, $item['id'], $item['qty'], $item['price']]);

            $stmtStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmtStock->execute([$item['qty'], $item['id']]);
        }

        $pdo->commit();

        $userEmail = $_SESSION['email'] ?? "customer@example.com"; 
        sendOrderConfirmation($userEmail, $_SESSION['username'], $total_price, $items_to_process);
        unset($_SESSION['cart']);
        header("Location: orders.php?success=1");
        exit;

    }
    catch (Exception $e)
    {
        $pdo->rollBack();
        $errors[] = $e->getMessage();
        $processing = false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-6">

    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl border border-slate-100 p-8">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-indigo-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            </div>
            <h1 class="text-2xl font-black text-slate-800">Secure Checkout</h1>
            <p class="text-slate-400 text-sm mt-1">Order #TBD • Finalize your purchase</p>
        </div>

        <?php displayMessages($errors); ?>

        <div class="bg-slate-50 rounded-2xl p-6 mb-8 space-y-4">
            <div class="flex justify-between text-sm">
                <span class="text-slate-500">Customer</span>
                <span class="font-semibold text-slate-700"><?= htmlspecialchars($_SESSION['username']) ?></span>
            </div>
            <div class="border-t border-dashed border-slate-200 pt-4 flex justify-between items-center">
                <span class="text-lg font-bold text-slate-800">Total Amount</span>
                <span class="text-2xl font-black text-indigo-600">$<?= number_format($_POST['total_hidden'] ?? 0, 2) ?></span>
            </div>
        </div>

        <form method="POST">
            <input type="hidden" name="total_hidden" value="<?= htmlspecialchars($_POST['total_hidden'] ?? 0) ?>">
            <button type="submit" name="process_payment" class="w-full bg-slate-900 text-white py-4 rounded-xl font-bold hover:bg-black transition shadow-lg flex items-center justify-center gap-3">
                <span>Pay Now</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                </svg>
            </button>
        </form>

        <p class="text-center text-[10px] text-slate-400 mt-6 uppercase tracking-widest font-bold">Encrypted via 256-bit SSL</p>
    </div>

</body>
</html>