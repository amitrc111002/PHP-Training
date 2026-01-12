<?php
require 'productconnection.php';
require 'functions.php';

$errors = [];

try
{
    if(!isset($_GET['id']))
    {
        die("Missing ID.");
    }
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();

    if(!$data)
    {
        die("Product not found.");
    }

    $product = new Product($pdo, $data['name'], $data['price'], $id);
    if($_SERVER['REQUEST_METHOD'] === 'POST')
    {
        $updated = new Product($pdo, $_POST['name'], $_POST['price'], $id);
        $result = $updated->updateProduct();
        if($result === true)
        {
            header("Location: homepage.php");
            exit;
        }
        else
        {
            $errors = $result;
        }
    }
}
catch(Exception $e)
{
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<body>
    <h2>Edit Product</h2>
    <?php displayMessages($errors); ?>
    <form method="POST">
        <input type="text" name="name" value="<?= htmlspecialchars($product->getName()) ?>">
        <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($product->getPrice()) ?>">
        <button type="submit">Update</button>
    </form>
</body>
</html>
