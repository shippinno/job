<?php

namespace Shippinno\Job\Test\Infrastructure\Ui\Console\Laravel\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Enqueue\Null\NullContext;
use Enqueue\Null\NullMessage;
use Interop\Queue\PsrConsumer;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Shippinno\Job\Application\Job\JobRunnerRegistry;
use Shippinno\Job\Domain\Model\JobSerializer;
use Shippinno\Job\Domain\Model\StoredJobSerializer;
use Shippinno\Job\Infrastructure\Ui\Console\Laravel\Command\JobConsume;
use Shippinno\Job\Test\Application\Job\FakeJobRunner;
use Shippinno\Job\Test\Domain\Model\FakeJob;
use Shippinno\Job\Domain\Model\StoredJob;
use Shippinno\Job\Test\Domain\Model\SimpleJobSerializer;
use Shippinno\Job\Test\Domain\Model\SimpleStoredJobSerializer;

class JobConsumeTest extends TestCase
{

}
