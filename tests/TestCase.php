<?php

namespace Shippinno\Job\Test;

use JMS\Serializer\SerializerBuilder;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @return SerializerBuilder
     */
    public function serializerBuilder(): SerializerBuilder
    {
        return SerializerBuilder::create()
            ->addMetadataDir(
                __DIR__.'/Infrastructure/Serialization/JMS/Config',
                'Shippinno\\Job\\Test'
            );
    }
}
