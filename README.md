# Job

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/shippinno/job/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/shippinno/job/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/shippinno/job/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/shippinno/job/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/shippinno/job/badges/build.png?b=master)](https://scrutinizer-ci.com/g/shippinno/job/build-status/master)

## Basic Usage

### Create a job
```php
class EchoMessageJob extends Shippinno\Job\Application\Job
{
    /**
     * @param string $message
     */
    private $message;

    /**
     * @param string $message
     */
    public function __construct(string $message)
    {
        parent::__construct();
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function message(): string
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function jobRunner(): string
    {
        return SomeJobRunner::class;
    }
}
```
