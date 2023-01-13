<?php

// Icinga Reporting | (c) 2018 Icinga GmbH | GPLv2

namespace Icinga\Module\Reporting\Web\Forms;

use DateTime;
use Icinga\Application\Icinga;
use Icinga\Authentication\Auth;
use Icinga\Module\Reporting\Database;
use Icinga\Module\Reporting\ProvidedActions;
use Icinga\Module\Reporting\Report;
use Icinga\Web\Notification;
use ipl\Html\Form;
use ipl\Scheduler\RRule;
use ipl\Web\Common\BaseScheduleForm;

class ScheduleForm extends BaseScheduleForm
{
    use Database;
    use DecoratedElement;
    use ProvidedActions;

    /** @var Report */
    protected $report;

    protected function init(): void
    {
        parent::init();

        $this->scheduleElement->setIdProtector([Icinga::app()->getRequest(), 'protectId']);
    }

    public function hasBeenSubmitted()
    {
        return $this->hasBeenSent()
            && (
                $this->getPopulatedValue('submit')
                || $this->getPopulatedValue('remove')
            );
    }

    public function setReport(Report $report)
    {
        $this->report = $report;
        $schedule = $report->getSchedule();
        if ($schedule !== null) {
            $repeat = strtoupper($schedule->getFrequency());
            if (substr($repeat, 0, 1) === '@') {
                $repeat = substr($repeat, 1);
            }

            $values = [
                'start'  => $schedule->getStart(),
                'repeat' => isset($this->regulars[$repeat]) ? $repeat : $schedule->getFrequency(),
                'action' => $schedule->getAction()
            ];

            $config = $schedule->getConfig();
            if ($schedule->getFrequency() === static::CUSTOM_EXPR && isset($config['rrule'])) {
                $rrule = new RRule($config['rrule'], $schedule->getStart());
                $this->scheduleElement->setStart($schedule->getStart());

                $values['schedule-element'] = $this->scheduleElement->loadRRule($rrule);
                unset($config['rrule']);
            }

            $this->populate(array_merge($values, $config));
        }

        return $this;
    }

    protected function assemble()
    {
        $this->addElement('select', 'action', [
            'required'    => true,
            'class'       => 'autosubmit',
            'options'     => array_merge([null => $this->translate('Please choose')], $this->listActions()),
            'label'       => $this->translate('Action'),
            'description' => $this->translate('Specifies an action to be triggered by the scheduler')
        ]);

        $values = $this->getValues();
        if (isset($values['action'])) {
            $config = new Form();
//            $config->populate($this->getValues());

            /** @var \Icinga\Module\Reporting\Hook\ActionHook $action */
            $action = new $values['action']();

            $action->initConfigForm($config, $this->report);

            foreach ($config->getElements() as $element) {
                $this->addElement($element);
            }
        }

        $this->assembleCommonParts();
        $this->assembleScheduleRecurrence();

        $schedule = $this->report->getSchedule();
        $this->addElement('submit', 'submit', [
            'label' => $schedule === null ? $this->translate('Create Schedule') : $this->translate('Update Schedule')
        ]);

        if ($schedule !== null) {
            $removeButton = $this->createElement('submit', 'remove', [
                'label'          => 'Remove Schedule',
                'class'          => 'btn-remove',
                'formnovalidate' => true
            ]);
            $this->registerElement($removeButton);
            $this->getElement('submit')->getWrapper()->prepend($removeButton);
        }
    }

    public function onSuccess()
    {
        $db = $this->getDb();
        $schedule = $this->report->getSchedule();

        if ($this->getPressedSubmitElement()->getName() === 'remove') {
            $db->delete('schedule', ['id = ?' => $schedule->getId()]);

            Notification::success('Removed schedule successfully');

            return;
        }

        $values = $this->getValues();
        $now = time() * 1000;
        if (! $values['start'] instanceof DateTime) {
            $values['start'] = DateTime::createFromFormat('Y-m-d H:i:s', $values['start']);
        }

        $repeat = $values['repeat'];
        $repeat = ! isset($this->regulars[$repeat]) ? $repeat : strtolower($repeat);
        $data = [
            'start'     => $values['start']->getTimestamp() * 1000,
            'frequency' => $repeat,
            'action'    => $values['action'],
            'mtime'     => $now
        ];

        unset($values['start']);
        unset($values['repeat']);
        unset($values['action']);

        if (array_key_exists('schedule-recurrences', $values)) {
            unset($values['schedule-recurrences']);
        }

        if ($repeat === static::CUSTOM_EXPR) {
            unset($values['schedule-element']);
            $values['rrule'] = $this->scheduleElement->getRRule()->getRuleString();
        }

        $data['config'] = json_encode($values);

        $db->beginTransaction();

        if ($schedule === null) {
            $db->insert(
                'schedule',
                $data + [
                    'author'    => Auth::getInstance()->getUser()->getUsername(),
                    'report_id' => $this->report->getId(),
                    'ctime'     => $now
                ]
            );
        } else {
            $db->update('schedule', $data, ['id = ?' => $schedule->getId()]);
        }

        $db->commitTransaction();

        $message = $this->report->getSchedule()
            ? $this->translate('Updated schedule successfully')
            : $this->translate('Created schedule successfully');

        Notification::success($message);
    }
}
