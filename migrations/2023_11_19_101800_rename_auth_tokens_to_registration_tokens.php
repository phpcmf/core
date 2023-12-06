<?php

use Cmf\Database\Migration;

return Migration::renameTable('auth_tokens', 'registration_tokens');
