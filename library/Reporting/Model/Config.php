<?php

namespace Icinga\Module\Reporting\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;

class Config extends Model
{
    public function getTableName()
    {
        return 'config';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'reportlet_id',
            'name',
            'value',
            'ctime',
            'mtime'
        ];
    }

    public function createRelations(Relations $relations)
    {
        $relations->belongsTo('reportlet', Reportlet::class);
    }
}
