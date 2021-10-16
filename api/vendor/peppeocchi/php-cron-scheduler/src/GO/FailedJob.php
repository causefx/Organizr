<?php namespace GO;

use Exception;

class FailedJob
{
    /**
     * @var Job
     */
    private $job;

    /**
     * @var Exception
     */
    private $exception;

    public function __construct(Job $job, Exception $exception)
    {
        $this->job = $job;
        $this->exception = $exception;
    }

    public function getJob(): Job
    {
        return $this->job;
    }

    public function getException(): Exception
    {
        return $this->exception;
    }
}
