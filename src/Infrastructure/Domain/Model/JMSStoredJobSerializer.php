<?php

namespace Shippinno\Job\Infrastructure\Domain\Model;

use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Shippinno\Job\Domain\Model\StoredJob;
use Shippinno\Job\Domain\Model\StoredJobSerializer;

class JMSStoredJobSerializer implements StoredJobSerializer
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @param SerializerBuilder $serializerBuilder
     */
    public function __construct(SerializerBuilder $serializerBuilder)
    {
        $this->serializer =
            $serializerBuilder
                ->addMetadataDir(
                    __DIR__.'/../../Serialization/JMS/Config',
                    'Shippinno\\Job'
                )
                ->build();
    }

    /**
     * {@inheritdoc}
     */
    public function serialize(StoredJob $storedJob): string
    {
        return $this->serializer->serialize($storedJob, 'json');
    }

    /**
     * {@inheritdoc}
     */
    public function deserialize(string $data): StoredJob
    {
        return $this->serializer->deserialize($data, StoredJob::class, 'json');
    }
}
