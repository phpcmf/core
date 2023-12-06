<?php

use Cmf\Group\Group;
use Illuminate\Database\Schema\Builder;

$rows = [
    // 客人可以查看该网站
    ['permission' => 'viewSite', 'group_id' => Group::GUEST_ID],

    // 成员可以创建和回复讨论，以及搜索用户
    ['permission' => 'startDiscussion', 'group_id' => Group::MEMBER_ID],
    ['permission' => 'discussion.reply', 'group_id' => Group::MEMBER_ID],
    ['permission' => 'searchUsers', 'group_id' => Group::MEMBER_ID],

    // 版主可以编辑 + 删除内容
    ['permission' => 'discussion.hide', 'group_id' => Group::MODERATOR_ID],
    ['permission' => 'discussion.editPosts', 'group_id' => Group::MODERATOR_ID],
    ['permission' => 'discussion.hidePosts', 'group_id' => Group::MODERATOR_ID],
    ['permission' => 'discussion.rename', 'group_id' => Group::MODERATOR_ID],
    ['permission' => 'discussion.viewIpsPosts', 'group_id' => Group::MODERATOR_ID],
    ['permission' => 'user.viewLastSeenAt', 'group_id' => Group::MODERATOR_ID],
];

return [
    'up' => function (Builder $schema) use ($rows) {
        $db = $schema->getConnection();

        foreach ($rows as $row) {
            if ($db->table('groups')->where('id', $row['group_id'])->doesntExist()) {
                continue;
            }

            if ($db->table('group_permission')->where($row)->doesntExist()) {
                $db->table('group_permission')->insert($row);
            }
        }
    },

    'down' => function (Builder $schema) use ($rows) {
        $db = $schema->getConnection();

        foreach ($rows as $row) {
            $db->table('group_permission')->where($row)->delete();
        }
    }
];
