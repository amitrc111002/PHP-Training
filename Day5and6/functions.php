<?php
class Product
{
    protected $id;
    protected $name;
    protected $price;
    protected $pdo;

    public function __construct($id, $name, $price)
    {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function validate()
    {
        $errors = [];
        if(empty(trim($this->name)))
        {
            $errors[] = "Product name is required to proceed.";
        }

        if (!is_numeric($this->price) || $this->price <= 0)
        {
            $errors[] = "Price must be a valid number greater than zero.";
        }

        return $errors;
    }
    public function addProduct($categoryId)
    {
        $errors = $this->validate();
        if(empty($errors))
        {
            $sql = "INSERT INTO products (name,price,category_id) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->name, $this->price, $categoryId]);
        }
        return $errors;
    }

    public function updateProduct()
    {
        $errors = $this->validate();
        if(empty($errors))
        {
            $sql = "UPDATE products SET name = ?, price = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$this->name, $this->price, $this->id]);
        }
        return $errors;
    }

    public static function deleteProduct($pdo, $id)
    {
        return $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    }
}

class PhysicalProduct extends Product
{
    private $weight;
    public function setWeight($w)
    {
        $this->weight = $w;
    }
    public function getWeight()
    {
        return $this->weight;
    }
}

class DigitalProduct extends Product
{
    private $fileSize;
    public function setFileSize($fs)
    {
        $this->fileSize = $fs;
    }
    public function getFileSize()
    {
        return $this->fileSize;
    }
}

function displayMessages($messages, $type = 'error')
{
    if (!empty($messages))
    {
        echo "<div style='color: " . ($type == 'danger' ? 'red':'green') . "; font-weight: bold;'>";
        foreach ($messages as $msg)
        {
            echo "<div class='{$type}-message'>{$msg}</div>";
        }
        echo "</div>";
    }
}
?>