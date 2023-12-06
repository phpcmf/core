<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('access_tokens', function (Blueprint $table) {
            // 将主键替换为唯一索引，以便我们可以创建一个新的主键
            $table->dropPrimary('token');
            $table->unique('token');
        });

        // 这需要在第二个语句中完成，因为 Laravel 运行操作的顺序
        $schema->table('access_tokens', function (Blueprint $table) {
            // 引入新的基于增量的 ID
            $table->increments('id')->first();
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->dropColumn('id');
            $table->dropIndex('token');
            $table->primary('token');
        });
    }
];
