<?php

namespace Cmf\Api;

/*
 * This file is part of PHPCmf.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Cmf\Foundation\AbstractValidator;

class ForgotPasswordValidator extends AbstractValidator
{
    /**
     * {@inheritdoc}
     */
    protected $rules = [
        'email' => ['required', 'email']
    ];
}
