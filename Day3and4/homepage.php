<?php
require 'productconnection.php';
require 'functions.php';

$errors = [];
$success_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product']))
{
    $name = $_POST['name'];
    $price = $_POST['price'];
    $cat_id = $_POST['category_id'];

    $errors = validateProduct($name, $price);

    if (empty($errors))
    {
        $sql = "INSERT INTO products (name, price, category_id) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $price, $cat_id]);
        $success_msg = "Product added successfully!";
    }
}

if (isset($_GET['delete']))
{
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    header("Location: homepage.php?msg=deleted");
    exit;
}

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted')
{
    $success_msg = "Product removed.";
}

$products = $pdo->query("SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id = c.id")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head><title>Product Manager</title></head>
<body>
    <h1>Product Inventory</h1>

    <?php 
        displayMessages($errors); 
        if ($success_msg) displayMessages([$success_msg], 'success');
    ?>

    <fieldset>
        <legend>Add New Product</legend>
        <form method="POST">
            <input type="text" name="name" placeholder="Product Name" value="<?= isset($name) && !empty($errors) ? htmlspecialchars($name) : '' ?>">
            <input type="number" step="0.01" name="price" placeholder="Price" value="<?= isset($price) && !empty($errors) ? htmlspecialchars($price) : '' ?>">
            <select name="category_id">
                <?php foreach($categories as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="add_product">Save Product</button>
        </form>
    </fieldset>

    <br>

    <table border="1" cellpadding="10">
        <thead>
            <tr>
                <th>ID</th><th>Name</th><th>Price</th><th>Category</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td>$<?= number_format($p['price'], 2) ?></td>
                <td><?= htmlspecialchars($p['cat_name']) ?></td>
                <td>
                    <a href="productedit.php?id=<?= $p['id'] ?>">Edit</a> | 
                    <a href="homepage.php?delete=<?= $p['id'] ?>" onclick="return confirm('Delete this?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>