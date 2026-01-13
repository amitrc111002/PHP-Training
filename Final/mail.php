<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendOrderConfirmation($toEmail, $username, $total, $items)
{
    $mail = new PHPMailer(true);

    try 
    {
        
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'amit.roychowdhury@innofied.com';
        $mail->Password   = 'kbga arby idzj efpz';   
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('no-reply@stockpro.com', 'STOCKPRO System');
        $mail->addAddress($toEmail, $username); 

        $mail->isHTML(true);
        $mail->Subject = "Order Confirmed: #STK-" . date('His');

        $itemListHtml = "<ul>";
        foreach ($items as $item) 
        {
            $itemListHtml .= "<li>{$item['name']} (x{$item['qty']}) - $" . number_format($item['price'], 2) . "</li>";
        }
        $itemListHtml .= "</ul>";

        $mail->Body = "
            <h2>Hi $username,</h2>
            <p>Your order has been placed successfully!</p>
            <h3>Summary:</h3>
            $itemListHtml
            <p><strong>Total Paid: $" . number_format($total, 2) . "</strong></p>
            <p>Thank you for shopping with us!</p>
        ";

        $mail->send();
        return true;
    } 
    catch (Exception $e) 
    {
        error_log("Mail Error: " . $mail->ErrorInfo);
        return false;
    }
}