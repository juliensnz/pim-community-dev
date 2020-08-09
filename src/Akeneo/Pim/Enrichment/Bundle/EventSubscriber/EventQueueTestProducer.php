<?php

declare(strict_types=1);

namespace Akeneo\Pim\Enrichment\Bundle\EventSubscriber;

use Akeneo\Pim\Enrichment\Bundle\Message\ProductUpdated;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Tool\Component\StorageUtils\StorageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @copyright 202O Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
final class EventQueueTestProducer implements EventSubscriberInterface
{
    private $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StorageEvents::POST_SAVE => ['produceTestMessage', 1000],
        ];
    }

    public function produceTestMessage(GenericEvent $event): void
    {
        /** @var ProductInterface */
        $product = $event->getSubject();
        if (false === $product instanceof ProductInterface) {
            return;
        }

        $event = new ProductUpdated($product->getIdentifier(), $product->getUpdated());
        $this->messageBus->dispatch($event);
    }
}
