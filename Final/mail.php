<?php
function sendOrderConfirmation($toEmail, $username, $total, $items)
{
    $subject = "Order Confirmed - Inventory System";
    
    $itemList = "";
    foreach ($items as $item)
    {
        $itemList .= "- " . $item['name'] . " (Qty: " . $item['qty'] . ")\n";
    }

    $message = "
    Hello $username,

    Thank you for your purchase! Your payment of $" . number_format($total, 2) . " was successful.
    
    Order Summary:
    $itemList

    Your items will be prepared for shipment shortly.
    
    Regards,
    The Inventory Team
    ";

    $headers = "From: no-reply@inventorysystem.com" . "\r\n" .
               "Reply-To: support@inventorysystem.com" . "\r\n" .
               "X-Mailer: PHP/" . phpversion();

    $mailSent = @mail($toEmail, $subject, $message, $headers);

    if (!$mailSent)
    {
        $logEntry = "[" . date('Y-m-d H:i:s') . "] EMAIL FAILED TO: $toEmail (User: $username) | TOTAL: $$total\n$message\n" . str_repeat("-", 30) . "\n";
        file_put_contents('mail_log.txt', $logEntry, FILE_APPEND);
    }
    
    return $mailSent;
}
?>