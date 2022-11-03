<?php

// Icinga Reporting | (c) 2018 Icinga GmbH | GPLv2

namespace Icinga\Module\Reporting;

use Exception;
use Icinga\Module\Reporting\Hook\ActionHook;
use Icinga\Util\Json;
use ipl\Scheduler\Common\TaskProperties;
use ipl\Scheduler\Contract\Task;
use Ramsey\Uuid\Uuid;
use React\EventLoop\Loop;
use React\Promise\Deferred;
use React\Promise\ExtendedPromiseInterface;

use function md5;

class Schedule implements Task
{
    use TaskProperties;

    /** @var int */
    protected $id;

    /** @var int */
    protected $reportId;

    /** @var string */
    protected $action;

    /** @var array */
    protected $config = [];

    public function __construct(string $name, int $reportId, string $action, array $config)
    {
        $this->setName($name);
        $this->setAction($action);
        $this->setReportId($reportId);
        $this->setConfig($config);
        $this->setUuid(Uuid::fromBytes($this->getChecksum()));
    }

    /**
     * Get the DB id of this schedule
     *
     * @return  int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the DB id of this schedule
     *
     * @param int $id
     *
     * @return  $this
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the report id of this schedule
     *
     * @return  int
     */
    public function getReportId(): int
    {
        return $this->reportId;
    }

    /**
     * Set the report id of this schedule
     *
     * @param int $id
     *
     * @return  $this
     */
    public function setReportId(int $id): self
    {
        $this->reportId = $id;

        return $this;
    }

    /**
     * Get the action hook class of this schedule
     *
     * @return  string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Se the action hook class of this schedule
     *
     * @param string $action
     *
     * @return  $this
     */
    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get the config of this schedule
     *
     * @return  array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Se the config of this schedule
     *
     * @param array $config
     *
     * @return  $this
     */
    public function setConfig(array $config): self
    {
        $this->config = $config;
        ksort($this->config);

        return $this;
    }

    /**
     * Get the checksum of this schedule
     *
     * @return  string
     */
    public function getChecksum(): string
    {
        return md5($this->getName() . $this->reportId . $this->getAction() . Json::encode($this->getConfig()), true);
    }

    public function run(): ExtendedPromiseInterface
    {
        $deferred = new Deferred();
        Loop::futureTick(function () use ($deferred) {
            $action = $this->getAction();
            /** @var ActionHook $actionHook */
            $actionHook = new $action();

            try {
                $actionHook->execute(Report::fromDb($this->getReportId()), $this->getConfig());
            } catch (Exception $err) {
                $deferred->reject($err);
                return;
            }

            $deferred->resolve();
        });

        return $deferred->promise();
    }
}
