<?php

declare(strict_types=1);

namespace Vuryss\DoctrineLazyJsonOdm\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\JsonType;
use JsonMachine\Exception\InvalidArgumentException;
use JsonMachine\Items;
use Vuryss\DoctrineLazyJsonOdm\Lazy\LazyJsonArray;
use Vuryss\DoctrineLazyJsonOdm\Serializer\SerializerInterface;
use Vuryss\DoctrineLazyJsonOdm\TypeMapper\TypeMapperInterface;

final class LazyJsonDocumentType extends JsonType
{
    public const string NAME = 'lazy_json_document';

    private SerializerInterface $serializer;
    private TypeMapperInterface $typeMapper;

    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->serializer = $serializer;
    }

    private function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }

    public function setTypeMapper(TypeMapperInterface $typeMapper): void
    {
        $this->typeMapper = $typeMapper;
    }

    #[\Override]
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if ($value instanceof LazyJsonArray) {
            return $this->getSerializer()->serialize($value->getItems());
        }

        return $this->getSerializer()->serialize($value);
    }

    /**
     * @throws \ReflectionException
     * @throws InvalidArgumentException
     */
    #[\Override]
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if ('' === $value || !is_string($value)) {
            return null;
        }

        // Handle arrays, if first character is [
        if (str_starts_with($value, '[')) {
            $reflectionClass = new \ReflectionClass(LazyJsonArray::class);

            return $reflectionClass->newLazyGhost(function (LazyJsonArray $lazyJsonArray) use ($value) {
                /** @var list<object> $deserialized */
                $deserialized = $this->getSerializer()->deserialize($value);

                /* @noinspection PhpExpressionResultUnusedInspection */
                $lazyJsonArray->__construct($deserialized);
            });
        }

        $jsonData = iterator_to_array(Items::fromString($value, ['pointer' => '/#type']));

        if (!isset($jsonData['#type']) || !is_string($jsonData['#type'])) {
            return $this->getSerializer()->deserialize($value);
        }

        return new \ReflectionClass($this->typeMapper->getClassByType($jsonData['#type']))
            ->newLazyProxy(function (object $object) use ($value): object {
                /** @var object $object */
                $object = $this->getSerializer()->deserialize($value);

                return $object;
            });
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
