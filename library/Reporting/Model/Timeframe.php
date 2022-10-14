<?php

namespace Icinga\Module\Reporting\Model;

use ipl\Orm\Model;

class Timeframe extends Model
{
    public function getTableName()
    {
        return 'timeframe';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'name',
            'title',
            'start',
            'end',
            'ctime',
            'mtime'
        ];
    }
}
