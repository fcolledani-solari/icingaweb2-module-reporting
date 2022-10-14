<?php

namespace Icinga\Module\Reporting\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class Report extends Model
{
    public function getTableName()
    {
        return 'report';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'timeframe_id',
            'template_id',
            'author',
            'name',
            'ctime',
            'mtime'
        ];
    }
    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('timeframe', Timeframe::class);
        $relations->belongsTo('template', Template::class)
            ->setJoinType('LEFT');

        $relations->hasOne('schedule', Schedule::class)
            ->setJoinType('LEFT');
        $relations->hasMany('reportlet', Reportlet::class)
            ->setJoinType('LEFT');
    }
}
