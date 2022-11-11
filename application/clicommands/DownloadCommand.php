<?php

namespace Icinga\Module\Reporting\Clicommands;

use Icinga\Application\Icinga;
use Icinga\File\Storage\LocalFileStorage;
use InvalidArgumentException;
use Icinga\Module\Pdfexport\ProvidedHook\Pdfexport;
use Icinga\Module\Reporting\Cli\Command;
use Icinga\Module\Reporting\Report;

class DownloadCommand extends Command
{
    /**
     * Download report with specified ID as PDF, CSV or JSON
     *
     * USAGE:
     *
     *  icingacli reporting download <id> [--format=<pdf|csv|json>]
     *
     * OPTIONS:
     *
     *  --format=<pdf|csv|json> Download report as PDF, CSV or JSON. Defaults to pdf.
     *
     * EXAMPLES:
     *
     *  icingacli reporting download 1
     *
     *  icingacli reporting download 1 --format=csv
     */
    public function defaultAction()
    {
        $id = $this->params->getStandalone();
        if ($id === null) {
            $this->fail($this->translate('Argument id is mandatory'));
        }

        $report = Report::fromDb($id);
        $format = strtolower($this->params->get('format', 'pdf'));
        $name = sprintf(
            '%s (%s) %s',
            $report->getName(),
            $report->getTimeframe()->getName(),
            date('Y-m-d H:i')
        );

        switch ($format) {
            case 'pdf':
                $content = Pdfexport::first()->htmlToPdf($report->toPdf());
                break;
            case 'csv':
                $content = $report->toCsv();
                break;
            case 'json':
                $content = $report->toJson();
                break;
            default:
                throw new InvalidArgumentException(sprintf('Format %s is not supported', $format));
        }

        $storage = new LocalFileStorage(Icinga::app()->getStorageDir(
            join(DIRECTORY_SEPARATOR, ['modules', 'reporting', 'reports'])
        ));

        $filename = "$name.$format";
        $storage->create($filename, $content);
        echo "$filename\n";
    }
}
