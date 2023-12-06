<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        // 删除不存在用户的行，以便我们能够毫无问题地创建外键。
        $schema->getConnection()
            ->table('email_tokens')
            ->whereNotExists(function ($query) {
                $query->selectRaw(1)->from('users')->whereColumn('id', 'user_id');
            })
            ->delete();

        $schema->table('email_tokens', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('email_tokens', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }
];
