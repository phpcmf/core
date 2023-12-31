<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('users', function (Blueprint $table) {
            $table->index('joined_at');
            $table->index('last_seen_at');
            $table->index('discussion_count');
            $table->index('comment_count');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('users', function (Blueprint $table) {
            $table->dropIndex(['joined_at']);
            $table->dropIndex(['last_seen_at']);
            $table->dropIndex(['discussion_count']);
            $table->dropIndex(['comment_count']);
        });
    }
];
