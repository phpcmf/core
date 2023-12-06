<?php

namespace Cmf\Group;

use Cmf\Foundation\AbstractValidator;

class GroupValidator extends AbstractValidator
{
    protected $rules = [
        'name_singular' => ['required'],
        'name_plural' => ['required']
    ];
}
