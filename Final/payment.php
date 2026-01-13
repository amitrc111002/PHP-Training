<?php
require 'productconnection.php';
require 'functions.php';
require 'mail.php';

session_start();

if(!isset($_SESSION['user_id']) || empty($_SESSION['cart'])) {
    header("Location: homepage.php");
    exit;
}

$errors = [];

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment']))
{
    $userEmail = trim($_POST['email']);

    if (empty($userEmail) || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) 
    {
        $errors[] = "A valid email address is required to receive your receipt.";
    } 
    else 
    {
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
                    throw new Exception("Stock unavailable for {$product['name']}.");
                }

                $total_price += ($product['price'] * $qty);
                $items_to_process[] = [
                    'id'    => $product['id'], 
                    'qty'   => $qty, 
                    'name'  => $product['name'],
                    'price' => $product['price']
                ];
            }

            $updateUser = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
            $updateUser->execute([$userEmail, $_SESSION['user_id']]);

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

            sendOrderConfirmation($userEmail, $_SESSION['username'], $total_price, $items_to_process);

            unset($_SESSION['cart']);
            header("Location: orders.php?success=1");
            exit;

        } 
        catch (Exception $e) 
        {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout | STOCKPRO</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl p-8 border border-slate-100">
        <h1 class="text-2xl font-black text-slate-800 text-center mb-6">Order Checkout</h1>
        
        <?php displayMessages($errors); ?>

        <form method="POST" class="space-y-6">
            <input type="hidden" name="total_hidden" value="<?= htmlspecialchars($_POST['total_hidden'] ?? 0) ?>">
            
            <div class="bg-indigo-50 p-4 rounded-2xl flex justify-between items-center border border-indigo-100">
                <span class="text-indigo-600 font-bold uppercase text-xs tracking-wider">Amount Due</span>
                <span class="text-2xl font-black text-indigo-700">$<?= number_format($_POST['total_hidden'] ?? 0, 2) ?></span>
            </div>

            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Delivery Email</label>
                <input type="email" name="email" required 
                       placeholder="Enter email for confirmation"
                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 outline-none transition">
                <p class="text-[10px] text-slate-400 mt-2 italic">We will use this address to send your real-time order receipt.</p>
            </div>

            <button type="submit" name="process_payment" class="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">
                Confirm & Pay
            </button>
        </form>
    </div>
</body>
</html>