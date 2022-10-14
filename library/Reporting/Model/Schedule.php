<?php

namespace Icinga\Module\Reporting\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class Schedule extends Model
{
    public function getTableName()
    {
        return 'schedule';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'report_id',
            'author',
            'start',
            'frequency',
            'action',
            'config',
            'ctime',
            'mtime'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('report', Report::class)
            ->setJoinType('LEFT');
    }
}
