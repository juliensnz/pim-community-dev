<?php

declare(strict_types=1);

namespace Akeneo\Pim\Enrichment\Bundle\Message;

/**
 * @copyright 202O Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductUpdated implements BusinessEvent
{
    const TYPE = 'product.updated';

    private $code;
    private $updated;

    public function __construct(string $code, \DateTimeInterface $updated)
    {
        $this->code = $code;
        $this->updated = $updated;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getUpdated(): \DateTimeInterface
    {
        return $this->updated;
    }
}
