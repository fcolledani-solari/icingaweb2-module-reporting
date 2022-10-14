<?php

// Icinga Reporting | (c) 2018 Icinga GmbH | GPLv2

namespace Icinga\Module\Reporting;

use ipl\Stdlib\Filter;

class Reportlet
{
    use Database;

    /** @var int */
    protected $id;

    /** @var string */
    protected $class;

    /** @var array */
    protected $config;

    /**
     * @param Model\Reportlet $model
     *
     * @return Reportlet
     *
     */
    public static function fromModel(Model\Reportlet $model)
    {
        $reportlet = new static();

        $reportlet->id = $model->id;
        $reportlet->class = $model->class;

        $result = Model\Report::on($reportlet->getDb())
            ->with([
                'reportlet.config',
            ])
            ->filter(Filter::equal('id', $model->report_id));

        /** @var $report Model\Report */
        $report = $result->first();

        $row = $result
            ->filter(Filter::equal('reportlet.config.reportlet_id', $reportlet->getId()));

        $config = [
            'name' => $report->name,
            'id'   => $report->id
        ];

        foreach ($row as $r) {
            $config[$r->reportlet->config->name] = $r->reportlet->config->value;
        }

        $reportlet->config = $config;

        return $reportlet;
    }

    /**
     * @return  int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return  string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return  array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return  \Icinga\Module\Reporting\Hook\ReportHook
     */
    public function getImplementation()
    {
        $class = $this->getClass();

        return new $class();
    }
}
