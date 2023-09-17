<?php
# 数据库主机名/ip地址/域名
define('DB_HOST', 'localhost');
# 数据库名
define('DB_NAME', 'picture-cdn');
# 数据库用户名
define('DB_USER', 'picture-cdn-reader');
# 数据库密码
define('DB_PASS', 'password');
# pro版api如果没有指定任何参数，默认从这些表中的所有图片随机选取
define('DEFAULT_TABLES', [
    'pixiv',
    'imas'
]);
# 普通版版api如果没有指定任何参数，默认从表中的所有图片随机选取
define('DEFAULT_TABLE', 'pixiv');
?>
