<!DOCTYPE html>
<html>
<head>
    <title>PHP Functions Example</title>
</head>
<body>
    <h1>PHP Functions Demonstration</h1>
    <?php
    class practise
    {
        public function calculateTotalPrice($a, $b)
        {
            return $a + $b;
        }
        public function loopsExample()
        {
            $products = ["SSD", "HDD", "RAM", "Graphics Card", "Motherboard"];
            foreach($products as $product)
            {
                echo "<li>" . $product . "</li>";
            }
        }
    }
    echo "<h2>Total Price Calculation</h2>";
    $practiseObj = new practise();
    $totalPrice = $practiseObj->calculateTotalPrice(150, 200);
    echo "<p>The total price is: $" . $totalPrice . "</p>";
    $practiseObj->loopsExample();
    ?>
</body>    
</html>