<?php
class Product
{
    protected $id, $name, $price, $pdo;
    protected $image;

    public function __construct($pdo, $name='', $price=0, $id=null, $image='placeholder.png')
    {
        $this->pdo = $pdo; 
        $this->id = $id; 
        $this->name = $name; 
        $this->price = $price;
        $this->image = $image;
    }

    public function validate()
    {
        $errors = [];
        if(empty(trim($this->name))) $errors[] = "Product name is required.";
        if (!is_numeric($this->price) || $this->price <= 0) $errors[] = "Price must be greater than zero.";
        return $errors;
    }
    public function addProduct($categoryId, $stock = 10)
    {
        $errors = $this->validate();
        if(empty($errors))
        {
            $stmt = $this->pdo->prepare("INSERT INTO products (name, price, category_id, stock, image) VALUES (?, ?, ?, ?, ?)");
            return $stmt->execute([$this->name, $this->price, $categoryId, $stock, $this->image]);
        }
        return $errors;
    }

    public function updateProduct($stock)
    {
        $errors = $this->validate();
        if(empty($errors))
        {
            $stmt = $this->pdo->prepare("UPDATE products SET name = ?, price = ?, stock = ?, image = ? WHERE id = ?");
            return $stmt->execute([$this->name, $this->price, $stock, $this->image, $this->id]);
        }
        return $errors;
    }

    public static function deleteProduct($pdo, $id)
    {
        return $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    }
    public static function cancelOrder($pdo, $orderId)
    {
        try
        {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $stmt->execute([$orderId]);
            $items = $stmt->fetchAll();

            foreach ($items as $item)
            {
                $updateStock = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                $updateStock->execute([$item['quantity'], $item['product_id']]);
            }
            $updateOrder = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
            $updateOrder->execute([$orderId]);

            $pdo->commit();
            return true;
        } 
        catch (Exception $e) 
        {
            $pdo->rollBack();
            return false;
        }
    }
}

function displayMessages($messages, $type = 'danger')
{
    if (!empty($messages))
    {
        $bgColor = ($type == 'danger' ? 'bg-rose-50 border-rose-200 text-rose-700' : 'bg-emerald-50 border-emerald-200 text-emerald-700');
        echo "<div class='mb-6 p-4 rounded-xl border $bgColor shadow-sm'>";
        foreach ((array)$messages as $msg)
        {
            echo "<p class='text-sm'>• " . htmlspecialchars($msg) . "</p>";
        }
        echo "</div>";
    }
}
?>