<?php

use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        // 手动执行此操作，因为 DBAL 无法识别时间戳列
        $connection = $schema->getConnection();
        $prefix = $connection->getTablePrefix();
        $connection->statement("ALTER TABLE {$prefix}registration_tokens MODIFY created_at DATETIME");
    },

    'down' => function (Builder $schema) {
        $connection = $schema->getConnection();
        $prefix = $connection->getTablePrefix();
        $connection->statement("ALTER TABLE {$prefix}registration_tokens MODIFY created_at TIMESTAMP");
    }
];
