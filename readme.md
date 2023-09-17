# 随机图片 API

## 这是什么？

这是一个随机图片 API，可以用来获取随机图片。

支持部署多个图片集，支持使用 SQL 进行复杂的查询。

更加具体的搭建过程参见博客：[玩转云服务（7）：自建随机图片 API](https://blog.baldcoder.top/articles/self-host-random-picture-api/)

## 使用方法

你需要至少两个网站：`pictures.aaa.com` 和 `api.aaa.com`。

> 虽然博客里使用了三个网站：额外添加了 `pictures-admin.aaa.com` 放置扫描php （scan.php），但是若只是出于评估目的，或者自行为扫描php上访问控制，可以不需要。
> 由于 scan.php 可以访问数据库，所以强烈建议对其进行访问控制。

### 建立数据库

在你的数据库主机中建立一个数据库，需要有两个帐户：

- 一个对你新建的数据库有完整的读写权限，用于 scan.php 构建数据库。
- 一个对你新建的数据库有仅有读权限，用于 api 读取数据库。

### 部署图片网站

在 `pictures.aaa.com` 中建立一个或者多个目录放置你的图片。

同时，你需要在 `pictures.aaa.com` 中建立 admin 目录，用于放置 [pictures-scan-admin](https://github.com/Eterance/random-picture-api/tree/main/pictures-scan-admin) 里的内容。

### 设置 `scan_config.php`

在其中填写有完整读写权限的数据库帐户信息、域名信息和网站根目录路径信息。

CLASS_AND_PATH 为一个数组，其中每个元素为一个类名和一个路径数组：

- 类名为图片的类别（也将作为数据表的表名）
- 路径数组为图片所在的目录。也就是说，一个类别可以有多个目录。目录是相对于网站根目录的路径。

### 部署 API 网站

在 `api.aaa.com` 中建立一个 admin 目录，将 [rpic_config.php](https://github.com/Eterance/random-picture-api/blob/main/random-picture-api/rpic_config.php) 保存在其中，并修改其中的数据库帐户信息。

同时，在你认为合适的地方放置 [rpic.php](https://github.com/Eterance/random-picture-api/blob/main/random-picture-api/rpicpro.php) 和 [rpic.php](https://github.com/Eterance/random-picture-api/blob/main/random-picture-api/rpicpro.php)。

需要修改文件开头的地方：

```php
// 打开配置文件
//改成你的配置文件路径
require_once('../admin/rpic_config.php');
```

## 使用 API

参见：

[自己部署文档](https://github.com/Eterance/docusaurus-api-docs)


[自己部署文档的示例网站](https://eterance.github.io/docusaurus-api-docs/)