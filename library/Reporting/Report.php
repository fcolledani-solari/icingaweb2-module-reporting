<?php

// Icinga Reporting | (c) 2018 Icinga GmbH | GPLv2

namespace Icinga\Module\Reporting;

use DateTime;
use Exception;
use Icinga\Module\Pdfexport\PrintableHtmlDocument;
use Icinga\Module\Reporting\Model;
use Icinga\Module\Reporting\Web\Widget\Template;
use ipl\Html\HtmlDocument;
use ipl\Stdlib\Filter;

class Report
{
    use Database;

    /** @var int */
    protected $id;

    /** @var string */
    protected $name;

    /** @var string */
    protected $author;

    /** @var Timeframe */
    protected $timeframe;

    /** @var Reportlet[] */
    protected $reportlets;

    /** @var Template */
    protected $template;

    /** @var Schedule */
    protected $schedule;

    /**
     * @param Model\Report $model
     *
     * @return Report
     */
    public static function fromModel(Model\Report $model): Report
    {
        $report = new static();

        $rs = $model::on($report->getDb())
            ->with([
                'timeframe',
                'template',
                'reportlet',
                'reportlet.config',
                'schedule'
            ])
            ->filter(Filter::equal('id', $model->id));

        /** @var $result Model\Report */
        $result = $rs->first();

        if ($result === null) {
            throw new Exception('Report not found');
        }

        $report->id = $model->id;
        $report->name = $model->name;
        $report->author = $model->author;
        $report->timeframe = Timeframe::fromModel($model->timeframe);
        $report->template = Template::fromModel($model->template);

        /** @var $reportlet Model\Reportlet */
        $reportlet = Model\Reportlet::on($report->getDb())
            ->filter(Filter::equal('report_id', $report->getId()))
            ->first();

        if ($reportlet === null) {
            throw new Exception('No reportlets configured.');
        }

        $reportlet = Reportlet::fromModel($reportlet);

        $report->reportlets = [$reportlet];

        /** @var $schedule Model\Schedule */
        $schedule = Model\Schedule::on($report->getDb())
            ->filter(Filter::equal('report_id', $report->getId()))
            ->first();

        if ($schedule !== null) {
            $schedule = Schedule::fromModel($schedule);
            $report->schedule = $schedule;
        }

        return $report;
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
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return  Timeframe
     */
    public function getTimeframe()
    {
        return $this->timeframe;
    }

    /**
     * @return  Reportlet[]
     */
    public function getReportlets()
    {
        return $this->reportlets;
    }

    /**
     * @param Reportlet[] $reportlets
     *
     * @return  $this
     */
    public function setReportlets(array $reportlets)
    {
        $this->reportlets = $reportlets;

        return $this;
    }

    /**
     * @return  Schedule
     */
    public function getSchedule()
    {
        return $this->schedule;
    }

    /**
     * @return Template
     */
    public function getTemplate()
    {
        return $this->template;
    }

    public function providesData()
    {
        foreach ($this->getReportlets() as $reportlet) {
            $implementation = $reportlet->getImplementation();

            if ($implementation->providesData()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return  HtmlDocument
     */
    public function toHtml()
    {
        $timerange = $this->getTimeframe()->getTimerange();

        $html = new HtmlDocument();

        foreach ($this->getReportlets() as $reportlet) {
            $implementation = $reportlet->getImplementation();

            $html->add($implementation->getHtml($timerange, $reportlet->getConfig()));
        }

        return $html;
    }

    /**
     * @return  string
     */
    public function toCsv()
    {
        $timerange = $this->getTimeframe()->getTimerange();

        $csv = [];

        foreach ($this->getReportlets() as $reportlet) {
            $implementation = $reportlet->getImplementation();

            if ($implementation->providesData()) {
                $data = $implementation->getData($timerange, $reportlet->getConfig());
                $csv[] = array_merge($data->getDimensions(), $data->getValues());
                foreach ($data->getRows() as $row) {
                    $csv[] = array_merge($row->getDimensions(), $row->getValues());
                }

                break;
            }
        }

        return Str::putcsv($csv);
    }

    /**
     * @return  string
     */
    public function toJson()
    {
        $timerange = $this->getTimeframe()->getTimerange();

        $json = [];

        foreach ($this->getReportlets() as $reportlet) {
            $implementation = $reportlet->getImplementation();

            if ($implementation->providesData()) {
                $data = $implementation->getData($timerange, $reportlet->getConfig());
                $dimensions = $data->getDimensions();
                $values = $data->getValues();
                foreach ($data->getRows() as $row) {
                    $json[] = \array_combine($dimensions, $row->getDimensions())
                        + \array_combine($values, $row->getValues());
                }

                break;
            }
        }

        return json_encode($json);
    }

    /**
     * @return PrintableHtmlDocument
     *
     * @throws Exception
     */
    public function toPdf()
    {
        $html = (new PrintableHtmlDocument())
            ->setTitle($this->getName())
            ->addAttributes(['class' => 'icinga-module module-reporting'])
            ->addHtml($this->toHtml());

        if ($this->template !== null) {
            $this->template->setMacros([
                'title'               => $this->name,
                'date'                => (new DateTime())->format('jS M, Y'),
                'time_frame'          => $this->timeframe->getName(),
                'time_frame_absolute' => sprintf(
                    'From %s to %s',
                    $this->timeframe->getTimerange()->getStart()->format('r'),
                    $this->timeframe->getTimerange()->getEnd()->format('r')
                )
            ]);

            $html->setCoverPage($this->template->getCoverPage()->setMacros($this->template->getMacros()));
            $html->setHeader($this->template->getHeader()->setMacros($this->template->getMacros()));
            $html->setFooter($this->template->getFooter()->setMacros($this->template->getMacros()));
        }

        return $html;
    }
}
