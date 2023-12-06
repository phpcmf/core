<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('group_user', function (Blueprint $table) {
            $table->timestamp('created_at')->nullable();
        });

        // 手动执行此操作，因为 DBAL 无法识别时间戳列
        $connection = $schema->getConnection();
        $prefix = $connection->getTablePrefix();
        $connection->statement("ALTER TABLE `${prefix}group_user` MODIFY created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP");
    },

    'down' => function (Builder $schema) {
        $schema->table('group_user', function (Blueprint $table) {
            $table->dropColumn('created_at');
        });
    }
];
