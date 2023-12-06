<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->dateTime('last_activity_at')->nullable()->change();
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('access_tokens', function (Blueprint $table) {
            // 使last_activity_at不可为空是不可能的，因为它会弄乱现有数据。
        });
    }
];
