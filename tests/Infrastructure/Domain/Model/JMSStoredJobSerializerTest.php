<?php

namespace Shippinno\Job\Test\Infrastructure\Domain\Model;

use DateTime;
use DateTimeImmutable;
use JMS\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;
use Shippinno\Job\Domain\Model\JobSerializer;
use Shippinno\Job\Domain\Model\StoredJob;
use Shippinno\Job\Infrastructure\Domain\Model\JMSStoredJobSerializer;
use Shippinno\Job\Test\Domain\Model\NullJob;
use Shippinno\Job\Test\Domain\Model\SimpleJobSerializer;

class JMSStoredJobSerializerTest extends TestCase
{
    /**
     * @var JobSerializer
     */
    private $jobSerializer;

    /**
     * @var JMSStoredJobSerializer
     */
    private $storedJobSerializer;

    public function setUp()
    {
        $this->jobSerializer = new SimpleJobSerializer;
        $this->storedJobSerializer = new JMSStoredJobSerializer(SerializerBuilder::create());
    }

    public function testSerialize()
    {
        $job = new NullJob;
        $body = $this->jobSerializer->serialize($job);
        $storedJob = new StoredJob(get_class($job), $body, $job->createdAt());
        $json = $this->storedJobSerializer->serialize($storedJob);
        $array = json_decode($json, true);
        $this->assertSame(3, count($array));
        $this->assertSame(NullJob::class, $array['name']);
        $this->assertSame($body, $array['body']);
        $this->assertSame($job->createdAt()->format(DateTime::ISO8601), $array['created_at']);
    }

    public function testDeserialize()
    {
        $createdAt = new DateTimeImmutable;
        $storedJob = $this->storedJobSerializer->deserialize(
            json_encode([
                'name' => 'NAME',
                'body' => 'BODY',
                'created_at' => $createdAt->format(DateTime::ISO8601),
            ])
        );
        $this->assertSame('NAME', $storedJob->name());
        $this->assertSame('BODY', $storedJob->body());
        $this->assertSame(
            $createdAt->format(DateTime::ISO8601),
            $storedJob->createdAt()->format(DateTime::ISO8601)
        );
    }
}
