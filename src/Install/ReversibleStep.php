<?php

namespace Cmf\Install;

interface ReversibleStep extends Step
{
    public function revert();
}
