<?php

use Cmf\Database\Migration;

return Migration::renameColumn('email_tokens', 'id', 'token');
