<?php

declare(strict_types=1);

namespace Vuryss\DoctrineLazyJsonOdm\Serializer;

use Vuryss\DoctrineLazyJsonOdm\TypeMapper\TypeMapperInterface;
use Vuryss\Serializer\Denormalizer;
use Vuryss\Serializer\Denormalizer\DenormalizerInterface;
use Vuryss\Serializer\Denormalizer\ObjectDenormalizer;
use Vuryss\Serializer\Metadata\BuiltInType;
use Vuryss\Serializer\Metadata\DataType;
use Vuryss\Serializer\Path;

readonly class BuiltInObjectTypeDenormalizer implements DenormalizerInterface
{
    public function __construct(
        public TypeMapperInterface $typeMapper,
    ) {
    }

    public function denormalize(
        mixed $data,
        DataType $type,
        Denormalizer $denormalizer,
        Path $path,
        array $context = [],
    ): mixed {
        assert(is_array($data));

        $objectDenormalizer = new ObjectDenormalizer();

        if (!isset($data[SerializerInterface::TYPE_KEY]) || !is_string($data[SerializerInterface::TYPE_KEY])) {
            throw new \RuntimeException('Missing type key');
        }

        $realClassName = $this->typeMapper->getClassByType($data[SerializerInterface::TYPE_KEY]);
        $dataType = new DataType(BuiltInType::OBJECT, $realClassName);

        unset($data[SerializerInterface::TYPE_KEY]);

        return $objectDenormalizer->denormalize($data, $dataType, $denormalizer, $path, $context);
    }

    public function supportsDenormalization(mixed $data, DataType $type): bool
    {
        return is_array($data) && isset($data[SerializerInterface::TYPE_KEY]);
    }
}
