<?php

use Cmf\Database\Migration;

return Migration::renameColumn('users', 'notification_read_time', 'notifications_read_time');
