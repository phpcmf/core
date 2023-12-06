<?php

use Cmf\Database\Migration;

return Migration::renameColumn('registration_tokens', 'id', 'token');
