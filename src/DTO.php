<?php

namespace Betstore\DTO;

use Exception;
use Illuminate\Support\Facades\Validator;
use ReflectionClass;
use Throwable;

abstract class DTO
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

        foreach ($data as $key => $value) {
            $property = collect($properties)->filter(fn($property) => $key === $property->getName())->first();
            $type = $property?->getType();

            if ($type && property_exists($this, $key)) {
                // Log::channel('test')->info($this::class . ': ' . $key . ' => ' . $type);

                if ($type instanceof \ReflectionUnionType) {
                    $types = $type->getTypes();
                    $isConverted = false;
                    foreach ($types as $type) {
                        try {
                            $typeName = $type->getName();

                            if ($typeName === 'null') {
                                if ($value === null) {
                                    $this->{$key} = null;
                                    $isConverted = true;

                                    break;
                                }

                                continue; // Пропускаем, если значение не null
                            }

                            // Проверяем, что значение — массив, если тип — это класс
                            if (! is_array($value)) {
                                throw new Exception("Value for {$key} must be an array to cast to {$typeName}");
                            }

                            $this->{$key} = new $typeName($value);
                            $isConverted = true;

                            break;
                        } catch (Exception $e) {
                            // Log::channel('test')->error($e->getMessage());
                        }
                    }

                    if (! $isConverted) {
                        throw new Exception('Cannot convert ' . $key . ' to one of ' . implode(', ', collect($types)->map(fn($type) => $type->getName())->toArray()));
                    }
                } else {
                    $typeName = $type->getName();
                    // Log::channel('test')->info($this::class . ': ' . $key . ' => ' . $typeName);

                    try {
                        $rc = new \ReflectionEnum($typeName);

                        if ($rc->isEnum()) {
                            $this->{$key} = $typeName::from($value);

                            continue;
                        }
                    } catch (Throwable $e) {
                    }

                    switch ($typeName) {
                        case 'string':
                            $this->{$key} = (string) $value;

                            break;
                        case 'int':
                            $this->{$key} = (int) $value;

                            break;
                        case 'bool':
                            $this->{$key} = (bool) $value;

                            break;
                        case 'array':
                            $this->{$key} = (array) $value;

                            break;
                        case 'float':
                            $this->{$key} = (float) $value;

                            break;
                    }

                    if ($value instanceof $typeName) {
                        $this->{$key} = $value;

                        continue;
                    }

                    if (str_contains($type, '?') && is_null($value)) {
                        $this->{$key} = null;

                        continue;
                    }
                }
            }

            if (! $type && property_exists($this, $key)) {
                $this->{$key} = $value;

                continue;
            }

            $casts = $this->casts();

            if ($casts && array_key_exists($key, $casts)) {
                $castConfig = $casts[$key];
                $class = $castConfig[1];
                $isCollection = $castConfig[0] === 'collection';
                $isArray = $castConfig[0] === 'array';

                // Если значение null, присваиваем null и пропускаем
                if (is_null($value)) {
                    $this->{$key} = null;

                    continue;
                }

                // Проверяем, что значение — массив, если требуется коллекция или массив
                if (($isCollection || $isArray) && ! is_array($value)) {
                    throw new Exception("Value for {$key} must be an array to cast to " . ($isCollection ? 'collection' : 'array') . " of {$class}");
                }

                // Обработка коллекции
                if ($isCollection) {
                    $this->{$key} = count($value) > 0
                        ? collect($value)->map(fn($item) => ($item instanceof $class) ? $item : new $class($item))
                        : collect();

                    continue;
                }

                // Обработка массива
                if ($isArray) {
                    $this->{$key} = count($value) > 0
                        ? array_map(fn($item) => ($item instanceof $class) ? $item : new $class($item), $value)
                        : [];

                    continue;
                }

                // Обработка одиночного объекта
                if (! ($value instanceof $class)) {
                    if (! is_array($value)) {
                        throw new Exception("Value for {$key} must be an array to cast to {$class}");
                    }
                    $this->{$key} = new $class($value);
                } else {
                    $this->{$key} = $value; // Если уже экземпляр класса, просто присваиваем
                }
            }
        }
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

    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
}
