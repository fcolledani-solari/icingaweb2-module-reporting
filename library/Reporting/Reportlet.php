<?php

// Icinga Reporting | (c) 2018 Icinga GmbH | GPLv2

namespace Icinga\Module\Reporting;

class Reportlet
{
    /** @var int */
    //protected $id;

    /** @var string */
    //protected $class;

    /**
     * @param Model\Reportlet $model
     *
     * @return Model\Reportlet
     *
     */
    public static function fromModel(Model\Reportlet $model)
    {
        return $model;
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
