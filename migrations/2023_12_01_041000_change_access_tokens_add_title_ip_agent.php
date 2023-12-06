<?php

use Cmf\Database\Migration;

return Migration::addColumns('access_tokens', [
    'title' => ['string', 'length' => 150, 'nullable' => true],

    // 将 IPv4 和 IPv6 作为字符串
    'last_ip_address' => ['string', 'length' => 45, 'nullable' => true],
    
    // 从技术上讲，用户代理的长度没有限制 大多数长度约为 150 个，一般建议似乎低于 200 为了安全起见，我们将使用尽可能长的字符串 仍然会有例外，我们只是截断它们
    'last_user_agent' => ['string', 'length' => 255, 'nullable' => true],
]);
