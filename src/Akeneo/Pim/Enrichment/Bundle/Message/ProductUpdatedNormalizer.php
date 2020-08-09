<?php

declare(strict_types=1);

namespace Akeneo\Pim\Enrichment\Bundle\Message;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @copyright 202O Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductUpdatedNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function supportsNormalization($data, $format = null)
    {
        return true;
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return ProductUpdated::class === $type;
    }

    /**
     * @param ProductUpdated $object
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return [
            'code' => $object->getCode(),
            'updated' => $object->getUpdated()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return ProductUpdated
     */
    public function denormalize($data, $type, $format = null, array $context = [])
    {
        return new ProductUpdated(
            $data['code'],
            \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $data['updated'])
        );
    }
}
