<?php

namespace Betstore\DTO\Tests;


use Betstore\DTO\DTO;
use Illuminate\Support\Collection;

class TestDTO extends DTO
{

    public string $name;
    public int $age;
    public ?AddressDTO $address;

    public ?TestEnum $enum;

    public array $arrayTestDTOs;

    public ?Collection $collectionTestDTOs;

    protected function casts(): array
    {
        return [
            'address' => [null, AddressDTO::class],
            'arrayTestDTOs' => ['array', TestDTO::class],
            'collectionTestDTOs' => ['collection', TestDTO::class],
        ];
    }
}
