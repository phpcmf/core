<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        // 删除不存在组的行，以便我们能够毫无问题地创建外键。
        $schema->getConnection()
            ->table('group_permission')
            ->whereNotExists(function ($query) {
                $query->selectRaw(1)->from('groups')->whereColumn('id', 'group_id');
            })
            ->delete();

        $schema->table('group_permission', function (Blueprint $table) {
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('group_permission', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
        });
    }
];
