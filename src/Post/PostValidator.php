<?php

namespace Cmf\Post;

use Cmf\Foundation\AbstractValidator;

class PostValidator extends AbstractValidator
{
    protected $rules = [
        'content' => [
            'required',
            'max:65535'
        ]
    ];
}
