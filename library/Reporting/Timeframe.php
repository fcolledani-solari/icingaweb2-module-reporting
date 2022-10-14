<?php

// Icinga Reporting | (c) 2018 Icinga GmbH | GPLv2

namespace Icinga\Module\Reporting;

use ipl\Orm\Model;

class Timeframe
{
    use Database;

    /** @var int */
    protected $id;

    /** @var string */
    protected $title;

    /** @var string */
    protected $start;

    /** @var string */
    protected $end;

    /**
     * @param Model $model
     * @return  Timeframe
     */
    public static function fromModel(Model $model)
    {
        $timeframe = new static();

        $timeframe->name = $model->name;
        $timeframe->title = $model->title;
        $timeframe->start = $model->start;
        $timeframe->end = $model->end;

        return $timeframe;
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return  string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return  string
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @return  string
     */
    public function getEnd()
    {
        return $this->end;
    }

    public function getTimerange()
    {
        $start = new \DateTime($this->getStart());
        $end = new \DateTime($this->getEnd());

        return new Timerange($start, $end);
    }
}
