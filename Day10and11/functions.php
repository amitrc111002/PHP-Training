<?php
class Product
{
    protected $id, $name, $price, $pdo;

    public function __construct($pdo, $name='', $price=0, $id=null)
    {
        $this->pdo = $pdo; 
        $this->id = $id; 
        $this->name = $name; 
        $this->price = $price;
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
        if(empty($errors)) {
            $stmt = $this->pdo->prepare("INSERT INTO products (name, price, category_id, stock) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$this->name, $this->price, $categoryId, $stock]);
        }
        return $errors;
    }
    public function updateProduct($stock)
    {
        $errors = $this->validate();
        if(empty($errors)) {
            $stmt = $this->pdo->prepare("UPDATE products SET name = ?, price = ?, stock = ? WHERE id = ?");
            return $stmt->execute([$this->name, $this->price, $stock, $this->id]);
        }
        return $errors;
    }

    public static function deleteProduct($pdo, $id)
    {
        return $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
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