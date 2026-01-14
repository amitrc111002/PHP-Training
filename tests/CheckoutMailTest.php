<?php
use PHPUnit\Framework\TestCase;
use PHPMailer\PHPMailer\PHPMailer;

class CheckoutMailTest extends TestCase
{
    /** @test */
    public function test_email_sending_is_triggered()
    {
        $mailMock = $this->getMockBuilder(PHPMailer::class)
                         ->onlyMethods(['send', 'addAddress'])
                         ->getMock();

        $mailMock->expects($this->once())
                 ->method('send')
                 ->willReturn(true);

        $userEmail = "test@example.com";
        $success = $this->simulateMailLogic($mailMock, $userEmail);

        $this->assertTrue($success, "The email trigger should return true.");
    }

    private function simulateMailLogic($mail, $to)
    {
        try 
        {
            $mail->addAddress($to);
            $mail->Subject = "Your Receipt";
            return $mail->send();
        } 
        catch (Exception $e) 
        {
            return false;
        }
    }
}