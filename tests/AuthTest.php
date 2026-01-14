<?php
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase 
{
    /** @test */
    public function test_password_hashing_and_verification() 
    {
        $password = "secret123";
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $this->assertTrue(password_verify($password, $hash));
        $this->assertFalse(password_verify("wrong_password", $hash));
    }

    /** @test */
    public function test_admin_authorization_logic() 
    {
        $userSession = ['role' => 'customer'];
        $adminSession = ['role' => 'admin'];

        $isAdmin = function($session) 
        {
            return isset($session['role']) && $session['role'] === 'admin';
        };

        $this->assertTrue($isAdmin($adminSession));
        $this->assertFalse($isAdmin($userSession));
    }
}