<?php

// Icinga Reporting | (c) 2018 Icinga GmbH | GPLv2

namespace Icinga\Module\Reporting\Web;

use Icinga\Authentication\Auth;
use ipl\Html\Form;
use ipl\Orm\Query;
use ipl\Stdlib\Filter;
use ipl\Web\Compat\CompatController;

class Controller extends CompatController
{
    protected function redirectForm(Form $form, $url)
    {
        if (
            $form->hasBeenSubmitted()
            && ((isset($form->valid) && $form->valid === true)
                || $form->isValid())
        ) {
            $this->redirectNow($url);
        }
    }

    /**
     * @param Query $query
     * @param string $column
     * @return void
     */
    protected function applyRestriction(Query $query, string $column)
    {
        $prefixes = Auth::getInstance()->getRestrictions('reporting/prefix');
        if (! empty($prefixes)) {
            $query->filter(Filter::like($column, $prefixes[0] . '*'));
        }
    }
}
