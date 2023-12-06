<?php

use Cmf\Database\Migration;

return Migration::renameColumn('password_tokens', 'id', 'token');
