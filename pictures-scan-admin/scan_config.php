<?php
# 数据库主机名/ip地址/域名
define('DB_HOST', 'localhost');
# 数据库名
define('DB_NAME', 'picture-cdn');
# 数据库用户名
define('DB_USER', 'picture-cdn');
# 数据库密码
define('DB_PASS', 'password');
# 图片存储网站域名（后面不要加 / ）
define('URL_PREFIX', '//pictures.mycdn.com');
# 图片存储网站根目录（后面不要加 / ）
define('PATH_PREFIX', '/www/wwwroot/pictures.mycdn.com');

# 格式：['表名', ['文件夹相对于根目录的路径1', '路径2', ...]]
define('CLASS_AND_PATH', [
    ['pixiv', ['/acg/pixiv']],
    ['imas', ['/acg/imas']],
    ['scenery', ['/wallerpapers/Reflectio', '/wallerpapers/Sunny-Sho', '/wallerpapers/china', '/wallerpapers/uwp-windows-jujiao']],
    ['nfs', ['/wallerpapers/nfs18', '/wallerpapers/nfs19']],
    ['gamse', ['/wallerpapers/games']],
    ['astronomy', ['/wallerpapers/astronomy']]
]);


