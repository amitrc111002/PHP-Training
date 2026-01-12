<?php
require 'productconnection.php';
require 'functions.php';
require 'mail.php';

session_start();

if(!isset($_SESSION['user_id']) || empty($_SESSION['user_id']))
{
    header("Location:homepage.php");
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
            $stmt = $pdo->prepare("SELECT name,price,stock FROM products WHERE id = ? FOR UPDATE");
            $stmt->execute([$p_id]);
            $product = $stmt->fetch();
            if(!$product || $product['stock'] < $qty)
            {
                throw new Exception("Stock lost for " . ($product['name'] ?? 'Item') . " during payment.");

            }

            $total_price += ($product['price'] * $qty);
            $items_to_process[] = ['id' => $p_id, 'qty' => $qty, 'name' => $product['name']];
        }
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$_SESSION['user_id'], $total_price]);
        $order_id = $pdo->lastInsertId();

        foreach($items_to_process as $item)
        {
            $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$item['qty'], $item['id']]);
        }
        $pdo->commit();

        sendOrderConfirmation($_SESSION['username'], $total_price, $items_to_process);
        unset($_SESSION['cart']);
        header("Location: checkout.php?success=1");
        exit;
    }
    catch(Exception $e)
    {
        $pdo->rollBack();
        $errors[] = "Payment failed: " . $e->getMessage();
        $processing = false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Payment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6" style="font-family: 'Inter', sans-serif;">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-slate-200 p-8">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-100 text-indigo-600 rounded-full mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-slate-800">Secure Payment</h2>
            <p class="text-slate-500 text-sm">Review details and confirm transaction</p>
        </div>

        <?php displayMessages($errors); ?>

        <div class="space-y-4 mb-8">
            <div class="flex justify-between text-sm">
                <span class="text-slate-500">Merchant</span>
                <span class="font-semibold text-slate-700">Inventory System Inc.</span>
            </div>
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
        
        <p class="text-center text-[10px] text-slate-400 mt-6 uppercase tracking-widest font-semibold">
            Locked & Encrypted
        </p>
    </div>
</body>
</html>