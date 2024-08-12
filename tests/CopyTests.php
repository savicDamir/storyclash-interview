<?php

declare(strict_types=1);

namespace tests;

use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/functions.php';


class CopyTests extends TestCase
{
    /**
     * @dataProvider dataArgumentValidationTest
     */
    public function test_validate_args(int $argc, array $argv, string $expectedOutput): void
    {
        //execute script bin/copy.php with the given arguments
        $output = shell_exec("php bin/copy.php " . implode(' ', $argv) . " 2>&1");
        $this->assertEquals($expectedOutput, $output);
    }

    public static function dataArgumentValidationTest(): array
    {
        return [
            'no arguments' => [1, [], "Invalid number of arguments\n"],
            'too many arguments' => [5, ['arg0', 'entity', 'arg1', 'arg2', 'arg3'], "Invalid number of arguments\n"],
            'invalid platform' => [2, [ '--only=facebook', 'entity'], "Invalid value for --only. Expected 'instagram' or 'tiktok'.\n"],
            'invalid include-posts' => [2, ['--include-posts=-1', 'entity'], "Invalid value for --include-posts. Must be a positive integer.\n"],
        ];
    }

    public function test_db_connection(): void
    {
        $db = dbConnection('dev');
        $this->assertInstanceOf(\PDO::class, $db);
    }

    public function test_copy_entries(): void
    {
        //mock PDO objects
        $prodPdo = $this->createProdPdoMock();
        $devPdo = $this->createDevPdoMock();

        $args = [
            'id' => 'entity',
            'only' => 'instagram',
            'includePosts' => 10
        ];
        $this->expectOutputString('Entries copied successfully');
        copyEntries($args, $prodPdo, $devPdo);
    }

    private function createDevPdoMock(): PDO
    {
        $pdoMock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);

        $pdoMock->expects($this->once())
            ->method('beginTransaction')
            ->willReturn(true);

        $pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($stmtMock);

        $stmtMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('1');

        $pdoMock->expects($this->once())
            ->method('exec')
            ->with($this->equalTo("INSERT INTO posts (feed_id, url) VALUES (1, 'https://example.com/post1'),(1, 'https://example.com/post2')"))
            ->willReturn(2);

        $pdoMock->expects($this->once())
            ->method('commit')
            ->willReturn(true);

        return $pdoMock;
    }

    private function createProdPdoMock(): PDO
    {
        $pdoMock = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);

        $pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($stmtMock);

        $stmtMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $stmtMock->expects($this->exactly(2))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls(
                ['id' => 1, 'name' => 'entity'],
                ['id' => 1, 'name' => 'johndoe_insta', 'fan_count' => 15000],
                ['id' => 1, 'name' => 'johndoe_tiktok', 'fan_count' => 30000]
            );

        $stmtMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                ['url' => 'https://example.com/post1'],
                ['url' => 'https://example.com/post2']
            ]);

        return $pdoMock;
    }
}
