<?php
function validateProduct($name, $price)
{
    $errors = [];

    if (empty(trim($name)))
    {
        $errors[] = "Product name is required.";
    }

    if (!is_numeric($price) || $price <= 0)
    {
        $errors[] = "Price must be a valid number greater than zero.";
    }

    return $errors;
}

function displayMessages($messages, $type = 'danger')
{
    if (!empty($messages))
    {
        echo "<div style='color: " . ($type == 'danger' ? 'red' : 'green') . "; font-weight: bold;'>";
        foreach ($messages as $msg)
        {
            echo "<p>$msg</p>";
        }
        echo "</div>";
    }
}
?>