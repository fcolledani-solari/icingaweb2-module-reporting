<?php

namespace Icinga\Module\Reporting\Model;

use ipl\Orm\Model;

class Template extends Model
{
    public function getTableName()
    {
        return 'template';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'author',
            'name',
            'settings',
            'ctime',
            'mtime'
        ];
    }
}
