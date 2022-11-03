<?php

// Icinga Reporting | (c) 2018 Icinga GmbH | GPLv2

namespace Icinga\Module\Reporting\Clicommands;

use DateTime;
use Icinga\Application\Logger;
use Icinga\Module\Reporting\Cli\Command;
use Icinga\Module\Reporting\Schedule;
use Icinga\Module\Reporting\Web\Forms\ScheduleForm;
use Icinga\Util\Json;
use ipl\Scheduler\Contract\Task;
use ipl\Scheduler\Cron;
use ipl\Scheduler\OneOff;
use ipl\Scheduler\RRule;
use ipl\Scheduler\Scheduler;
use ipl\Sql\Select;
use React\EventLoop\Loop;
use React\Promise\ExtendedPromiseInterface;
use Recurr\Exception\InvalidRRule;
use Throwable;

class ScheduleCommand extends Command
{
    /**
     * Run all configured reports based on their schedule
     *
     * USAGE:
     *
     *   icingacli reporting schedule run
     */
    public function runAction()
    {
        $scheduler = new Scheduler();
        $this->attachJobsLogging($scheduler);

        /** @var Schedule[] $runningSchedules */
        $runningSchedules = [];
        $watchdog = function () use (&$watchdog, $scheduler, &$runningSchedules) {
            $schedules = $this->fetchSchedules();
            $outdated = array_diff_key($runningSchedules, $schedules);
            foreach ($outdated as $schedule) {
                Logger::info(
                    'Removing %s, as it either no longer exists in the database or its config has been changed',
                    $schedule->getName()
                );

                $scheduler->remove($schedule);
            }

            $newSchedules = array_diff_key($schedules, $runningSchedules);
            foreach ($newSchedules as $schedule) {
                $config = $schedule->getConfig();
                $repeat = $config['frequency'];
                $start = (new DateTime())
                    ->setTimestamp($config['start']);

                if ($repeat === ScheduleForm::NO_REPEAT) {
                    $frequency = new OneOff($start);
                } elseif ($repeat === ScheduleForm::CRON_EXPR) {
                    if (! Cron::isValid($config['schedule'])) {
                        Logger::error(
                            '%s has invalid schedule expression %s',
                            $schedule->getName(),
                            $config['schedule']
                        );

                        continue;
                    }

                    $frequency = new Cron($repeat);
                    $frequency->startAt($start);
                } else {
                    try {
                        if ($repeat === ScheduleForm::CUSTOM_EXPR) {
                            $frequency = new RRule($config['schedule'], $start);
                        } else {
                            $frequency = ScheduleForm::getRRuleFromRegulars($repeat);
                            $frequency->startAt($start);
                        }
                    } catch (InvalidRRule $err) {
                        Logger::error(
                            '%s has invalid schedule expression %s: %s',
                            $schedule->getName(),
                            $repeat === ScheduleForm::CUSTOM_EXPR ? $config['schedule'] : $repeat,
                            $err->getMessage()
                        );

                        continue;
                    }
                }

                $scheduler->schedule($schedule, $frequency);
            }

            $runningSchedules = $schedules;

            Loop::addTimer(5 * 60, $watchdog);
        };
        Loop::futureTick($watchdog);
    }

    /**
     * Fetch schedules from the database
     *
     * @return Schedule[]
     */
    protected function fetchSchedules(): array
    {
        $schedules = [];
        $select = (new Select())
            ->from('schedule')
            ->columns('*');

        $db = $this->getDb();
        foreach ($db->select($select) as $row) {
            $config = Json::decode($row->config, true);
            $config['start'] = (int) $row->start / 1000;

            $schedule = new Schedule("Schedule{$row->id}", $row->report_id, $row->action, $config);
            $schedule->setId($row->id);

            $schedules[$schedule->getUuid()->toString()] = $schedule;
        }

        return $schedules;
    }

    protected function attachJobsLogging(Scheduler $scheduler)
    {
        $scheduler->on(Scheduler::ON_TASK_FAILED, function (Task $job, Throwable $e) {
            Logger::error('Failed to run job %s: %s', $job->getName(), $e->getMessage());
            Logger::debug($e->getTraceAsString());
        });

        $scheduler->on(Scheduler::ON_TASK_RUN, function (Task $job, ExtendedPromiseInterface $_) {
            Logger::info('Running job %s', $job->getName());
        });

        $scheduler->on(Scheduler::ON_TASK_SCHEDULED, function (Task $job, DateTime $dateTime) {
            Logger::info('Scheduling job %s to run at %s', $job->getName(), $dateTime->format('Y-m-d H:i:s'));
        });

        $scheduler->on(Scheduler::ON_TASK_EXPIRED, function (Task $task, DateTime $dateTime) {
            Logger::info(
                sprintf('Detaching expired schedule %s at %s', $task->getName(), $dateTime->format('Y-m-d H:i:s'))
            );
        });
    }
}
