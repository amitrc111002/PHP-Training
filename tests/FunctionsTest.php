<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
require dirname(__DIR__) . '/lib/functions.php';
final class FunctionsTest extends TestCase
{
    public static function additionProvider(): array
    {
        return [
            'two positive integers' => [2, 3, 5],
            'two negative integers' => [-2, -3, -5],
            'positive and negative integer' => [3, -2, 1],
            'adding zero' => [3, 0, 3],
        ];
    }
    #[DataProvider('additionProvider')]
    public function testAddIntegers(int $a, int $b, int $expected): void
    {
        $this->assertSame($expected, addIntegers($a, $b));
    }
    public function testAddingIsCommutative(): void
    {
        $this->assertSame(addIntegers(2, 3), addIntegers(3, 2));
    }
}
?>