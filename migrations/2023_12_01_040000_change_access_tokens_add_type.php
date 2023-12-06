<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->string('type', 100)->index();
        });

        // 由于所有活动会话将由于从 user_id 切换到 access_token 而停止更新，因此我们可以在这里通过终止所有具有先前默认生存期的令牌来做一些简单的事情
        $schema->getConnection()->table('access_tokens')
            ->where('lifetime_seconds', 3600)
            ->delete();

        // 然后，我们将假设所有剩余的令牌都是记住的令牌，这将包括以前具有自定义生存期的令牌
        $schema->getConnection()->table('access_tokens')
            ->update([
                'type' => 'session_remember',
            ]);

        $schema->table('access_tokens', function (Blueprint $table) {
            $table->dropColumn('lifetime_seconds');
        });
    },

    'down' => function (Builder $schema) {
        $schema->table('access_tokens', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->integer('lifetime_seconds');
        });
    }
];
