<?php

namespace Betstore\DTO;

use Exception;
use Throwable;
use ReflectionClass;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Responsable;

abstract class DTO implements Responsable, Jsonable, Arrayable
{
    protected function rules(): array
    {
        return [];
    }

    /**
     * @doc [key => [null, class]] - для преобразования в class
     * @doc [key => ['array', class]] - для преобразования в array[class]
     * @doc [key => ['collection', class]] - для преобразования в collect(class)
     */
    protected function casts(): array
    {
        return [];
    }

    protected function messages(): array
    {
        return [];
    }

    public function __construct(array $data = [])
    {
        $this->validate($data);

        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties();

        foreach ($data as $propertyName => $propertyValue) {
            $property = collect($properties)->first(fn($property) => $property->getName() === $propertyName);

            if (! $property) {
                continue;
            }

            $type = $property->getType();

            if ($type) {
                $typeName = $type->getName();

                if ($type instanceof ReflectionUnionType) {
                    $types = $type->getTypes();
                    $isConverted = false;

                    foreach ($types as $type) {
                        try {
                            $this->{$propertyName} = $type->getName() === 'null'
                                ? null
                                : new $type->getName($propertyValue);

                            $isConverted = true;

                            break;
                        } catch (Throwable $e) {
                        }
                    }

                    if (! $isConverted) {
                        throw new Exception("Cannot convert {$propertyName} to one of " . implode(', ', collect($types)->map(fn($type) => $type->getName())->toArray()));
                    }
                } else {
                    try {
                        $this->{$propertyName} = match ($typeName) {
                            'string' => (string) $propertyValue,
                            'int' => (int) $propertyValue,
                            'bool' => (bool) $propertyValue,
                            'array' => (array) $propertyValue,
                            'float' => (float) $propertyValue,
                            default => $this->handleEnumAndInstance($typeName, $propertyValue),
                        };
                    } catch (Throwable $e) {
                        throw new Exception("Cannot convert {$propertyName} to {$typeName}");
                    }
                }
            } else {
                $this->{$propertyName} = $propertyValue;
            }

            $casts = $this->casts();

            if ($casts && array_key_exists($propertyName, $casts)) {
                [$castType, $castClass] = $casts[$propertyName];
                $isCollection = $castType === 'collection';
                $isArray = $castType === 'array';

                if (is_null($propertyValue)) {
                    $this->{$propertyName} = null;

                    continue;
                }

                if (($isCollection || $isArray) && ! is_array($propertyValue)) {
                    throw new Exception("Value for {$propertyName} must be an array to cast to " . ($isCollection ? 'collection' : 'array') . " of {$castClass}");
                }

                $this->{$propertyName} = match ($castType) {
                    'collection' => count($propertyValue) > 0
                        ? collect($propertyValue)->map(fn($item) => ($item instanceof $castClass) ? $item : new $castClass($item))
                        : collect(),
                    'array' => count($propertyValue) > 0
                        ? array_map(fn($item) => ($item instanceof $castClass) ? $item : new $castClass($item), $propertyValue)
                        : [],
                    default => new $castClass($propertyValue),
                };
            }
        }
    }

    private function handleEnumAndInstance(string $typeName, $propertyValue)
    {
        if (is_subclass_of($typeName, \UnitEnum::class) && ! $propertyValue instanceof $typeName) {
            foreach ($typeName::cases() as $enumCase) {
                if ($enumCase->value === $propertyValue) {
                    return $enumCase;
                }
            }
            throw new Exception("Invalid enum value for type {$typeName}");
        }

        return $propertyValue instanceof $typeName ? $propertyValue : new $typeName($propertyValue);
    }

    protected function validate(array $data): void
    {
        $rules = $this->rules();

        if ($rules && count($data) > 0) {
            Validator::make($data, $rules, $this->messages())->validate();
        }
    }

    public static function fromArray(array $data)
    {
        return new static($data);
    }

    public static function fromModel(Model $model)
    {
        $dto = new static();

        foreach ($model->getAttributes() as $key => $value) {
            $camelCaseKey = Str::camel($key);
            if (property_exists($dto, $camelCaseKey)) {
                $dto->{$camelCaseKey} = $model->{$key};
            }
        }

        return $dto;
    }

    public function toArray(bool $unsetNulls = false): array
    {
        $reflection = new \ReflectionClass($this);
        $data = [];

        // Собираем все свойства, включая унаследованные
        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true); // Делаем свойство доступным, если оно protected или private
            // Проверяем, инициализировано ли свойство
            $value = $property->isInitialized($this) ? $property->getValue($this) : null;
            $data[$property->getName()] = $value;
        }

        if ($unsetNulls) {
            $data = array_filter($data, fn($value) => ! is_null($value));
        }

        foreach ($data as $key => $value) {
            if ($value instanceof DTO) {
                $data[$key] = $value->toArray($unsetNulls);
            } elseif ($value instanceof \UnitEnum) {
                $data[$key] = $value->value;
            } elseif (is_array($value)) {
                $data[$key] = array_map(function ($item) use ($unsetNulls) {
                    return $item instanceof DTO ? $item->toArray($unsetNulls) : $item;
                }, $value);
            }
        }

        return $data;
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    public function toResponse($request, int $status = 200)
    {
        return response()->json($this->toArray(), $status);
    }
}
