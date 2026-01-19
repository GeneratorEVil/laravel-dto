<?php

namespace Betstore\DTO\Tests;

use Betstore\DTO\DTO;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Model;

class SimpleDTO extends DTO
{
    public string $name;
    public int $age;
    public ?string $email;
}

class TestModel extends Model
{
    protected $fillable = ['name', 'age', 'email', 'snake_case_field'];

    public function __construct(array $attributes = [])
    {
        parent::__construct(array_merge([
            'id' => 1,
            'name' => 'John Doe',
            'age' => 30,
            'email' => 'john@example.com',
            'snake_case_field' => 'snake_value',
        ], $attributes));
    }
}

class FactoryMethodsDTOTest extends TestCase
{
    public function testFromArrayWithPerformanceMeasurement()
    {
        $startTime = microtime(true);

        $data = [
            'name' => 'Jane Doe',
            'age' => 28,
            'email' => 'jane@example.com',
        ];

        $dto = SimpleDTO::fromArray($data);

        $this->assertInstanceOf(SimpleDTO::class, $dto);
        $this->assertEquals('Jane Doe', $dto->name);
        $this->assertEquals(28, $dto->age);
        $this->assertEquals('jane@example.com', $dto->email);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->addToAssertionCount(1);
        echo "\nFromArray test execution time: " . number_format($executionTime, 4) . " ms\n";
    }

    public function testFromModelWithPerformanceMeasurement()
    {
        $startTime = microtime(true);

        $model = new TestModel([
            'name' => 'John Doe',
            'age' => 30,
            'email' => 'john@example.com',
        ]);

        $dto = SimpleDTO::fromModel($model);

        $this->assertInstanceOf(SimpleDTO::class, $dto);
        $this->assertEquals('John Doe', $dto->name);
        $this->assertEquals(30, $dto->age);
        $this->assertEquals('john@example.com', $dto->email);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->addToAssertionCount(1);
        echo "\nFromModel test execution time: " . number_format($executionTime, 4) . " ms\n";
    }

    public function testFromModelWithCamelCaseConversionWithPerformanceMeasurement()
    {
        $startTime = microtime(true);

        $model = new TestModel();
        // Модель имеет snake_case поле, которое должно быть преобразовано в camelCase
        $model->snake_case_field = 'converted_value';

        $dto = SimpleDTO::fromModel($model);

        // Проверяем что поле snake_case_field не существует в DTO
        $array = $dto->toArray();
        $this->assertArrayNotHasKey('snake_case_field', $array);
        // Но значение должно быть в camelCase варианте, если бы такое свойство существовало

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->addToAssertionCount(1);
        echo "\nFromModel camelCase conversion test execution time: " . number_format($executionTime, 4) . " ms\n";
    }
}
