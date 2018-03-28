<?php

namespace Shippinno\Job\Infrastructure\Serialization\JMS;

use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;

trait BuildsSerializer
{
    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @param SerializerBuilder $serializerBuilder
     */
    protected function buildSerializer(SerializerBuilder $serializerBuilder): void
    {
        $this->serializer = $serializerBuilder
            ->addMetadataDir(__DIR__ . '/Config', 'Shippinno\\Job')
            ->build();
    }
}
