<?php

// Icinga Reporting | (c) 2018 Icinga GmbH | GPLv2

namespace Icinga\Module\Reporting;

class Schedule
{
    /** @var int */
    protected $id;

    /** @var int */
    protected $reportId;

    /** @var \DateTime */
    protected $start;

    /** @var string */
    protected $frequency;

    /** @var string */
    protected $action;

    /** @var array */
    protected $config;

    public static function fromModel(Model\Schedule $model): Schedule
    {
        $schedule = new static();

        $schedule->id = $model->id;
        $schedule->reportId = $model->report_id;
        $schedule->start = (new \DateTime())->setTimestamp((int) $model->start / 1000);
        $schedule->frequency = $model->frequency;
        $schedule->action = $model->action;
        $schedule->config = json_decode($model->config, true);

        return $schedule;
    }

    /**
     * @return  int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return  int
     */
    public function getReportId()
    {
        return $this->reportId;
    }

    /**
     * @return  \DateTime
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @return  string
     */
    public function getFrequency()
    {
        return $this->frequency;
    }

    /**
     * @return  string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return  array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return  string
     */
    public function getChecksum()
    {
        return \md5(
            $this->getId()
            . $this->getReportId()
            . $this->getStart()->format('Y-m-d H:i:s')
            . $this->getAction()
            . $this->getFrequency()
            . \json_encode($this->getConfig())
        );
    }
}
