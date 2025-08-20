<?php

declare(strict_types=1);

namespace Vuryss\DoctrineLazyJsonOdm\Serializer;

use Vuryss\DoctrineLazyJsonOdm\TypeMapper\TypeMapperInterface;
use Vuryss\Serializer\ExceptionInterface;
use Vuryss\Serializer\Normalizer;
use Vuryss\Serializer\Normalizer\NormalizerInterface;
use Vuryss\Serializer\Normalizer\ObjectNormalizer;

readonly class BuiltInObjectTypeNormalizer implements NormalizerInterface
{
    public function __construct(
        private TypeMapperInterface $typeMapper,
    ) {
    }

    /**
     * @return array<mixed>
     *
     * @throws ExceptionInterface
     */
    public function normalize(mixed $data, Normalizer $normalizer, array $context): array
    {
        assert(is_object($data));
        $objectNormalizer = new ObjectNormalizer();
        $normalizedObject = $objectNormalizer->normalize($data, $normalizer, $context);

        return [SerializerInterface::TYPE_KEY => $this->typeMapper->getTypeByClass($data::class)] + $normalizedObject;
    }

    public function supportsNormalization(mixed $data): bool
    {
        return is_object($data);
    }
}
