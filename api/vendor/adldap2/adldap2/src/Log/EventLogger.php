<?php

namespace Adldap\Log;

use Psr\Log\LoggerInterface;
use Adldap\Auth\Events\Failed;
use Adldap\Auth\Events\Event as AuthEvent;
use Adldap\Models\Events\Event as ModelEvent;

class EventLogger
{
    /**
     * The logger instance.
     *
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Logs an authentication event.
     *
     * @param AuthEvent $event
     *
     * @return void
     */
    public function auth(AuthEvent $event)
    {
        if (isset($this->logger)) {
            $operation = get_class($event);

            $connection = $event->getConnection();

            $message = "LDAP ({$connection->getHost()})"
                . " - Connection: {$connection->getName()}"
                . " - Operation: {$operation}"
                . " - Username: {$event->getUsername()}";

            $result = null;
            $type = 'info';

            if (is_a($event, Failed::class)) {
                $type = 'warning';
                $result = " - Reason: {$connection->getLastError()}";
            }
            
            $this->logger->$type($message.$result);
        }
    }

    /**
     * Logs a model event.
     *
     * @param ModelEvent $event
     *
     * @return void
     */
    public function model(ModelEvent $event)
    {
        if (isset($this->logger)) {
            $operation = get_class($event);

            $model = $event->getModel();

            $on = get_class($model);

            $connection = $model->getQuery()->getConnection();

            $message = "LDAP ({$connection->getHost()})"
                . " - Connection: {$connection->getName()}"
                . " - Operation: {$operation}"
                . " - On: {$on}"
                . " - Distinguished Name: {$model->getDn()}";

            $this->logger->info($message);
        }
    }
}
