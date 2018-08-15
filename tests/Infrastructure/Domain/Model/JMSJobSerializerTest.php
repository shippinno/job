<?php

namespace Shippinno\Job\Test\Infrastructure\Domain\Model;

use DateTime;
use DateTimeImmutable;
use JMS\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;
use Shippinno\Job\Infrastructure\Domain\Model\JMSJobSerializer;
use Shippinno\Job\Test\Domain\Model\NullJob;

class JMSJobSerializerTest extends TestCase
{
    /**
     * @var JMSJobSerializer
     */
    private $jobSerializer;

    public function setUp()
    {
        $this->jobSerializer = new JMSJobSerializer(SerializerBuilder::create());
    }

    public function testSerialize()
    {
        $job = new NullJob;
        $job->setMaxAttempts(3);
        $job->setReattemptDelay(600);
        $json = $this->jobSerializer->serialize($job);
        $array = json_decode($json, true);
        $this->assertSame(4, count($array));
        $this->assertSame($job->maxAttempts(), $array['max_attempts']);
        $this->assertSame($job->reattemptDelay(), $array['reattempt_delay']);
        $this->assertSame($job->createdAt()->format(DateTime::ISO8601), $array['created_at']);
        $this->assertFalse($array['is_expendable']);
    }

    public function testDeserialize()
    {
        $createdAt = new DateTimeImmutable;
        $job = $this->jobSerializer->deserialize(
            json_encode([
                'max_attempts' => 3,
                'reattempt_delay' => 600,
                'created_at' => $createdAt->format(DateTime::ISO8601),
                'is_expendable' => true,
            ]),
            NullJob::class
        );
        $this->assertInstanceOf(NullJob::class, $job);
        $this->assertSame(3, $job->maxAttempts());
        $this->assertSame(600, $job->reattemptDelay());
        $this->assertSame(
            $createdAt->format(DateTime::ISO8601),
            $job->createdAt()->format(DateTime::ISO8601)
        );
        $this->assertTrue($job->isExpendable());
    }
}
