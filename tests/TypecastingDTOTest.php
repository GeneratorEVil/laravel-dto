<?php

namespace Betstore\DTO\Tests;

use Betstore\DTO\DTO;
use PHPUnit\Framework\TestCase;

class TypecastingDTO extends DTO
{
    public string $stringProp;
    public int $intProp;
    public bool $boolProp;
    public float $floatProp;
    public array $arrayProp;
}

class NullableDTO extends DTO
{
    public ?string $nullableString;
    public ?int $nullableInt;
    public ?bool $nullableBool;
    public ?float $nullableFloat;
    public ?array $nullableArray;
}

class UnionTypeDTO extends DTO
{
    public string|int $stringOrInt;
    public string|int|null $nullableStringOrInt;
}

class TypecastingDTOTest extends TestCase
{
    public function testTypecastingBasicTypesWithPerformanceMeasurement()
    {
        $startTime = microtime(true);

        $data = [
            'stringProp' => 123, // int to string
            'intProp' => '456', // string to int
            'boolProp' => 1, // int to bool
            'floatProp' => '78.9', // string to float
            'arrayProp' => 'not_array', // string to array (should cast)
        ];

        $dto = new TypecastingDTO($data);

        // Проверяем корректность преобразований типов
        $this->assertIsString($dto->stringProp);
        $this->assertEquals('123', $dto->stringProp);

        $this->assertIsInt($dto->intProp);
        $this->assertEquals(456, $dto->intProp);

        $this->assertIsBool($dto->boolProp);
        $this->assertEquals(true, $dto->boolProp);

        $this->assertIsFloat($dto->floatProp);
        $this->assertEquals(78.9, $dto->floatProp);

        $this->assertIsArray($dto->arrayProp);
        $this->assertEquals(['not_array'], $dto->arrayProp);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // в миллисекундах

        $this->addToAssertionCount(1); // Добавляем assertion для замера времени
        echo "\nTypecasting basic types test execution time: " . number_format($executionTime, 4) . " ms\n";
    }

    public function testNullableTypesWithPerformanceMeasurement()
    {
        $startTime = microtime(true);

        $data = [
            'nullableString' => null,
            'nullableInt' => null,
            'nullableBool' => null,
            'nullableFloat' => null,
            'nullableArray' => null,
        ];

        $dto = new NullableDTO($data);

        // Проверяем что nullable свойства корректно устанавливаются в null
        $this->assertNull($dto->nullableString);
        $this->assertNull($dto->nullableInt);
        $this->assertNull($dto->nullableBool);
        $this->assertNull($dto->nullableFloat);
        $this->assertNull($dto->nullableArray);

        // Проверяем что свойства с типами корректно устанавливаются не-null значения
        $dataWithValues = [
            'nullableString' => 'test',
            'nullableInt' => 42,
            'nullableBool' => true,
            'nullableFloat' => 3.14,
            'nullableArray' => [1, 2, 3],
        ];

        $dtoWithValues = new NullableDTO($dataWithValues);

        $this->assertEquals('test', $dtoWithValues->nullableString);
        $this->assertEquals(42, $dtoWithValues->nullableInt);
        $this->assertEquals(true, $dtoWithValues->nullableBool);
        $this->assertEquals(3.14, $dtoWithValues->nullableFloat);
        $this->assertEquals([1, 2, 3], $dtoWithValues->nullableArray);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // в миллисекундах

        $this->addToAssertionCount(1);
        echo "\nNullable types test execution time: " . number_format($executionTime, 4) . " ms\n";
    }

    public function testUnionTypesWithPerformanceMeasurement()
    {
        $startTime = microtime(true);

        // Тест с string в string|int
        $data1 = ['stringOrInt' => 'test_string'];
        $dto1 = new UnionTypeDTO($data1);
        $this->assertIsString($dto1->stringOrInt);
        $this->assertEquals('test_string', $dto1->stringOrInt);

        // Тест с int в string|int
        $data2 = ['stringOrInt' => 42];
        $dto2 = new UnionTypeDTO($data2);
        $this->assertIsInt($dto2->stringOrInt);
        $this->assertEquals(42, $dto2->stringOrInt);

        // Тест с null в ?string|int (nullable union)
        $data3 = ['nullableStringOrInt' => null];
        $dto3 = new UnionTypeDTO($data3);
        $this->assertNull($dto3->nullableStringOrInt);

        // Тест с string в ?string|int
        $data4 = ['nullableStringOrInt' => 'nullable_test'];
        $dto4 = new UnionTypeDTO($data4);
        $this->assertIsString($dto4->nullableStringOrInt);
        $this->assertEquals('nullable_test', $dto4->nullableStringOrInt);

        // Тест с int в ?string|int
        $data5 = ['nullableStringOrInt' => 99];
        $dto5 = new UnionTypeDTO($data5);
        $this->assertIsInt($dto5->nullableStringOrInt);
        $this->assertEquals(99, $dto5->nullableStringOrInt);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // в миллисекундах

        $this->addToAssertionCount(1);
        echo "\nUnion types test execution time: " . number_format($executionTime, 4) . " ms\n";
    }

}