<?php

namespace Shippinno\Job\Test\Domain\Model;

use Shippinno\Job\Domain\Model\Job;

class ExpendableJob extends Job
{
    protected $isExpendable = true;
}
