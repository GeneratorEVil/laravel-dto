<?php

namespace Betstore\DTO\Tests;

use Betstore\DTO\DTO;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Collection;

class SerializationDTO extends DTO
{
    public string $name;
    public ?string $nullableName;
    public int $age;
    public ?int $nullableAge;
    public ?AddressDTO $nullableAddress;
    public array $items;
    public ?Collection $nullableCollection;

    protected function casts(): array
    {
        return [
            'nullableCollection' => ['collection', SimpleDTO::class],
        ];
    }
}

class SerializationDTOTest extends TestCase
{
    public function testSerializationWithNullsWithPerformanceMeasurement()
    {
        $startTime = microtime(true);

        $data = [
            'name' => 'John',
            'nullableName' => null,
            'age' => 25,
            'nullableAge' => null,
            'items' => [1, 2, 3],
            'nullableCollection' => null,
        ];

        $dto = new SerializationDTO($data);
        $arrayWithNulls = $dto->toArray();

        // Проверяем что null значения присутствуют
        $this->assertArrayHasKey('nullableName', $arrayWithNulls);
        $this->assertNull($arrayWithNulls['nullableName']);
        $this->assertArrayHasKey('nullableAge', $arrayWithNulls);
        $this->assertNull($arrayWithNulls['nullableAge']);
        $this->assertArrayHasKey('nullableAddress', $arrayWithNulls);
        $this->assertNull($arrayWithNulls['nullableAddress']);
        $this->assertArrayHasKey('nullableCollection', $arrayWithNulls);
        $this->assertNull($arrayWithNulls['nullableCollection']);

        // Проверяем что не-null значения присутствуют
        $this->assertEquals('John', $arrayWithNulls['name']);
        $this->assertEquals(25, $arrayWithNulls['age']);
        $this->assertEquals([1, 2, 3], $arrayWithNulls['items']);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->addToAssertionCount(1);
        echo "\nSerialization with nulls test execution time: " . number_format($executionTime, 4) . " ms\n";
    }

    public function testSerializationWithoutNullsWithPerformanceMeasurement()
    {
        $startTime = microtime(true);

        $data = [
            'name' => 'John',
            'nullableName' => null,
            'age' => 25,
            'nullableAge' => null,
            'items' => [1, 2, 3],
            'nullableCollection' => null,
        ];

        $dto = new SerializationDTO($data);
        $arrayWithoutNulls = $dto->toArray(true); // unsetNulls = true

        // Проверяем что null значения отсутствуют
        $this->assertArrayNotHasKey('nullableName', $arrayWithoutNulls);
        $this->assertArrayNotHasKey('nullableAge', $arrayWithoutNulls);
        $this->assertArrayNotHasKey('nullableAddress', $arrayWithoutNulls);
        $this->assertArrayNotHasKey('nullableCollection', $arrayWithoutNulls);

        // Проверяем что не-null значения присутствуют
        $this->assertEquals('John', $arrayWithoutNulls['name']);
        $this->assertEquals(25, $arrayWithoutNulls['age']);
        $this->assertEquals([1, 2, 3], $arrayWithoutNulls['items']);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->addToAssertionCount(1);
        echo "\nSerialization without nulls test execution time: " . number_format($executionTime, 4) . " ms\n";
    }

    public function testSerializationWithNestedDTOsWithPerformanceMeasurement()
    {
        $startTime = microtime(true);

        $data = [
            'name' => 'John',
            'nullableName' => 'Not null',
            'age' => 25,
            'nullableAge' => 30,
            'nullableAddress' => [
                'street' => 'Main St',
                'number' => 123,
            ],
            'items' => [1, 2, 3],
            'nullableCollection' => [[
                'name' => 'Nested User',
                'age' => 30,
                'email' => 'nested@example.com',
            ]],
        ];

        $dto = new SerializationDTO($data);
        $arrayWithNulls = $dto->toArray();
        $arrayWithoutNulls = $dto->toArray(true);

        // Проверяем вложенные объекты - nullableAddress должен быть массивом, а не объектом AddressDTO
        $this->assertIsArray($arrayWithNulls['nullableAddress']);
        $this->assertEquals('Main St', $arrayWithNulls['nullableAddress']['street']);
        $this->assertEquals(123, $arrayWithNulls['nullableAddress']['number']);

        $this->assertInstanceOf(Collection::class, $arrayWithNulls['nullableCollection']);
        $this->assertCount(1, $arrayWithNulls['nullableCollection']);
        $this->assertInstanceOf(SimpleDTO::class, $arrayWithNulls['nullableCollection']->first());

        // Проверяем что во второй версии все не-null значения на месте
        $this->assertEquals('John', $arrayWithoutNulls['name']);
        $this->assertEquals('Not null', $arrayWithoutNulls['nullableName']);
        $this->assertEquals(25, $arrayWithoutNulls['age']);
        $this->assertEquals(30, $arrayWithoutNulls['nullableAge']);
        $this->assertIsArray($arrayWithoutNulls['nullableAddress']);
        $this->assertEquals([1, 2, 3], $arrayWithoutNulls['items']);
        $this->assertInstanceOf(Collection::class, $arrayWithoutNulls['nullableCollection']);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->addToAssertionCount(1);
        echo "\nSerialization with nested DTOs test execution time: " . number_format($executionTime, 4) . " ms\n";
    }
}