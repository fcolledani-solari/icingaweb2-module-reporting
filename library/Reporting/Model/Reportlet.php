<?php

namespace Icinga\Module\Reporting\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class Reportlet extends Model
{
    public function getTableName()
    {
        return 'reportlet';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'report_id',
            'class',
            'ctime',
            'mtime'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('report', Report::class);

        $relations->hasMany('config', Config::class)
            ->setJoinType('LEFT');
    }
}
