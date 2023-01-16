<?php

namespace Icinga\Module\Reporting\Clicommands;

use Icinga\Module\Reporting\Cli\Command;
use InvalidArgumentException;
use ipl\Sql\Select;

class ListCommand extends Command
{
    /**
     * List reports
     *
     *  USAGE:
     *
     *   icingacli reporting list --sort id|name|author --direction ASC|DESC (default DESC)
     */

    public function indexAction()
    {
        $sort = $this->params->get('sort', 'r.mtime');
        $direction = $this->params->get('direction', 'ASC');

        $select = (new Select())
            ->from('report r')
            ->columns(['r.*', 'timeframe' => 't.name', 'class' => 'rl.class'])
            ->join('timeframe t', 'r.timeframe_id = t.id')
            ->join('reportlet rl', 'r.id = rl.report_id')
            ->orderBy($sort, $direction);

        $reports = $this->getDb()->select($select);

        $headerCallbacks = [
            'Id'     => function ($report) {
                return $report->id;
            },
            'Name'   => function ($report) {
                return $report->name;
            },
            'Author' => function ($report) {
                return $report->author;
            },
            'Type'   => function ($report) {
                return (new $report->class())->getName();
            }
        ];

        $this->processReport($reports, $headerCallbacks);
    }

    protected function processReport($reports, array $headerInfo)
    {
        $headers = [];
        $headerContent = [];
        foreach ($reports as $report) {
            $row = [];
            foreach ($headerInfo as $key => $callback) {
                $value = $callback($report);
                if (! isset($headers[$key])) {
                    $headers[$key] = 0;
                }

                $row[] = $value;
                $headers[$key] = max(mb_strlen($value), $headers[$key]);
            }

            $headerContent[] = $row;
        }

        $idSize = sprintf('%ss', $headers['Id']);
        $nameSize = sprintf('%ss', $headers['Name']);
        $authorSize = sprintf('%ss', $headers['Author']);
        $typeSize = sprintf('%ss', $headers['Type']);

        $format = "| %-$idSize | %-$nameSize | %-$authorSize | %-$typeSize |\n";
        printf($format, ...array_keys($headers));
        $beautifier = sprintf(
            $format,
            str_repeat('-', $headers['Id']),
            str_repeat('-', $headers['Name']),
            str_repeat('-', $headers['Author']),
            str_repeat('-', $headers['Type'])
        );
        printf($beautifier);

        while (! empty($headerContent)) {
            printf($format, ...array_shift($headerContent));
        }

        printf($beautifier);
    }
}
