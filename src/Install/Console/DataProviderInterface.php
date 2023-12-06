<?php

namespace Cmf\Install\Console;

use Cmf\Install\Installation;

interface DataProviderInterface
{
    public function configure(Installation $installation): Installation;
}
