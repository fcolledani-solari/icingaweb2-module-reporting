<?php

// Icinga Reporting | (c) 2018 Icinga GmbH | GPLv2

namespace Icinga\Module\Reporting\Web;

use ipl\Html\Form;
use ipl\Web\Compat\CompatController;

class Controller extends CompatController
{
    protected function redirectForm(Form $form, $url)
    {
        if ($form->hasBeenSubmitted() && $form->isValid()) {
            $this->redirectNow($url);
        }
    }
}
