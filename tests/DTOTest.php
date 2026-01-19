<?php

namespace Betstore\DTO\Tests;



use Betstore\DTO\DTO;
use PHPUnit\Framework\TestCase;

class DTOTest extends TestCase
{

    protected $data;
    protected $checkData;
    protected $dataWithDTO;
    protected function setUp(): void
    {
        $this->dataWithDTO = [
            'name' => 'John',
            'age' => 30,
            'enum' => TestEnum::ONE->value,
            'address' => [
                'street' => 'Main Street',
                'number' => 123
            ],
            'arrayTestDTOs' => [
                new TestDTO(['name' => 'John', 'age' => 30]),
                new TestDTO([
                    'name' => 'Jane',
                    'age' => 25,
                    'address' => [
                        'street' => 'Main Street',
                        'number' => 123
                    ],
                ]),
            ],
            'collectionTestDTOs' => [
                ['name' => 'John', 'age' => 30],
                [
                    'name' => 'Jane',
                    'age' => 25,
                    'address' => [
                        'street' => 'Main Street',
                        'number' => 123
                    ],
                ],
            ],
        ];

        $this->data = [
            'name' => 'John',
            'age' => 30,
            'enum' => TestEnum::ONE->value,
            'address' => [
                'street' => 'Main Street',
                'number' => 123
            ],
            'arrayTestDTOs' => [
                ['name' => 'John', 'age' => 30],
                [
                    'name' => 'Jane',
                    'age' => 25,
                    'address' => [
                        'street' => 'Main Street',
                        'number' => 123
                    ],
                ],
            ],
            'collectionTestDTOs' => [
                ['name' => 'John', 'age' => 30],
                [
                    'name' => 'Jane',
                    'age' => 25,
                    'address' => [
                        'street' => 'Main Street',
                        'number' => 123
                    ],
                ],
            ],
            'noType' => 'test'
        ];

        $this->checkData = [
            'name' => 'John',
            'age' => 30,
            'enum' => TestEnum::ONE->value,
            'address' => [
                'street' => 'Main Street',
                'number' => 123
            ],
            'arrayTestDTOs' => [
                ['name' => 'John', 'age' => 30],
                [
                    'name' => 'Jane',
                    'age' => 25,
                    'address' => [
                        'street' => 'Main Street',
                        'number' => 123
                    ],
                ],
            ],
            'collectionTestDTOs' => collect([
                ['name' => 'John', 'age' => 30],
                [
                    'name' => 'Jane',
                    'age' => 25,
                    'address' => [
                        'street' => 'Main Street',
                        'number' => 123
                    ],
                ],
            ]),
            'noType' => 'test'
        ];
    }
    public function testConstruct()
    {
        $dto = new TestDTO($this->data);
        $this->assertInstanceOf(TestDTO::class, $dto);
    }

    public function testToArray()
    {
        $dto = new TestDTO($this->data);
        $this->assertIsArray($dto->toArray());
    }

    public function testToJson()
    {
        $dto = new TestDTO($this->data);
        $this->assertJson($dto->toJson());
    }

    public function testWithDTOs()
    {
        $dto = new TestDTO($this->dataWithDTO);
        $this->assertInstanceOf(TestDTO::class, $dto);
    }

    public function testInvalidData()
    {
        $this->expectException(\TypeError::class);
        new TestDTO('invalid data');
    }

    public function testToResponse()
    {
        // Пропускаем тест toResponse так как функция response() не доступна в тестовой среде
        $this->markTestSkipped('toResponse test skipped - response() function not available in test environment');
    }
}
