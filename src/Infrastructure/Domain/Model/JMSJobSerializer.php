<?php

namespace Shippinno\Job\Infrastructure\Domain\Model;

use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Shippinno\Job\Domain\Model\Job;
use Shippinno\Job\Domain\Model\JobSerializer;

class JMSJobSerializer implements JobSerializer
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
    public function serialize(Job $job): string
    {
        return $this->serializer->serialize($job, 'json');
    }

    /**
     * {@inheritdoc}
     */
    public function deserialize(string $data, string $class): Job
    {
        return $this->serializer->deserialize($data, $class, 'json');
    }
}
