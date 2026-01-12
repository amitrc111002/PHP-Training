<?php
require 'productconnection.php';
require 'functions.php';

$errors = [];

try
{
    if (!isset($_GET['id']))
    {
        die("Missing ID parameter in URL.");
    }

    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if (!$product)
    {
        die("Product with ID $id not found.");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST')
    {
        $name = $_POST['name'];
        $price = $_POST['price'];
        $errors = validateProduct($name, $price);

        if (empty($errors))
        {
            $sql = "UPDATE products SET name = ?, price = ? WHERE id = ?";
            $pdo->prepare($sql)->execute([$name, $price, $id]);
            header("Location: homepage.php");
            exit;
        }
    }
}
catch (Exception $e)
{
    die("Logic Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head><title>Edit Product</title></head>
<body>
    <h2>Edit Product: <?= htmlspecialchars($product['name']) ?></h2>
    
    <?php displayMessages($errors); ?>

    <form method="POST">
        Name: <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>"><br><br>
        Price: <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($product['price']) ?>"><br><br>
        <button type="submit">Update Changes</button>
        <a href="homepage.php">Cancel</a>
    </form>
</body>
</html>