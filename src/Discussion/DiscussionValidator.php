<?php

namespace Cmf\Discussion;

use Cmf\Foundation\AbstractValidator;

class DiscussionValidator extends AbstractValidator
{
    protected $rules = [
        'title' => [
            'required',
            'min:3',
            'max:80'
        ]
    ];
}
