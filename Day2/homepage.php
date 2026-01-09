<?php
require 'productconnection.php';
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product']))
{
    $name = $_POST['name'];
    $price = $_POST['price'];
    $cat_id = $_POST['category_id'];

    $sql = "INSERT INTO products (name,price,category_id) VALUES(?,?,?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name,$price,$cat_id]);
    header("Location: homepage.php");
    exit;
}
if(isset($_GET['delete']))
{
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    header("Location: homepage.php");
    exit;
}

$products = $pdo->query("SELECT p.*, c.name as cat_name FROM products p JOIN categories c On p.category_id = c.id")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head><title>Product Manager</title></head>
<body>
    <h1>Product Inventory</h1>

    <fieldset>
        <legend>Add New Product</legend>
        <form method="POST">
            <input type="text" name="name" placeholder="Product Name" required>
            <input type="number" step="0.01" name="price" placeholder="Price" required>
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
                <td><?= $p['name'] ?></td>
                <td>$<?= number_format($p['price'], 2) ?></td>
                <td><?= $p['cat_name'] ?></td>
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