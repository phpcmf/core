<?php

use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        // 将不存在的实体 ID 设置为 NULL，以便我们能够毫无问题地创建外键。
        $connection = $schema->getConnection();

        $selectId = function ($table, $column) use ($connection) {
            return new Expression(
                '('.$connection->table($table)->whereColumn('id', $column)->select('id')->toSql().')'
            );
        };

        $connection->table('posts')->update([
            'user_id' => $selectId('users', 'user_id'),
            'edited_user_id' => $selectId('users', 'edited_user_id'),
            'hidden_user_id' => $selectId('users', 'hidden_user_id'),
        ]);

        $schema->table('posts', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('edited_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('hidden_user_id')->references('id')->on('users')->onDelete('set null');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('posts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['edited_user_id']);
            $table->dropForeign(['hidden_user_id']);
        });
    }
];
