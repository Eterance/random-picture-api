<!DOCTYPE html>
<html>
<head>
    <title>图片文件检索工具</title>
</head>
<body>
    <h1>图片文件检索工具</h1>
    
    <?php
    // 处理前端请求
    if (isset($_POST['search'])) {
        // 数据库连接凭据
        $config = parse_ini_file('/www/wwwroot/pictures.mycdn.com/admin/scan_config.ini');

        // 指定要检索的路径
        $scanPath = $config['scan_path'];
        
        // 检索 WebP、JPG 和 PNG 文件
        $allowedExtensions = ['webp', 'jpg', 'jpeg', 'png'];
        $webpFiles = [];

        foreach ($allowedExtensions as $extension) {
            $webpFiles = array_merge($webpFiles, glob($scanPath . '/*.' . $extension));
        }

        // 创建数据库连接
        $conn = new mysqli($config['host'], $config['user_name'], $config['pw'], $config['db_name']);

        // 检查连接是否成功
        if ($conn->connect_error) {
            die("数据库连接失败: " . $conn->connect_error);
        }

        // 创建表（如果不存在）
        $createTableSQL = "CREATE TABLE IF NOT EXISTS pics_data (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255),
            path VARCHAR(255),
            size INT,
            width INT,
            height INT,
            ratio DECIMAL(10, 7),
            date DATE,
            landscape BOOLEAN,
            near_square BOOLEAN,
            big_size BOOLEAN,
            small_size BOOLEAN,
            mid_size BOOLEAN,
            big_res BOOLEAN,
            small_res BOOLEAN,
            mid_res BOOLEAN,
            bjn BOOLEAN
        )";

        if ($conn->query($createTableSQL) === TRUE) {
            echo "数据表已创建或已存在。<br>";
        } else {
            echo "创建数据表时出错: " . $conn->error . "<br>";
        }

        $url_prefix = $config['url_prefix'];

        // 备份 pics_data 表之后，清空表中的数据
        // 首先删除 pics_data_bak 表，然后复制 pics_data 表为 pics_data_bak 表
        // 最后清空 pics_data 表的内容

        // 删除 pics_data_bak 表（如果存在）
        $dropBackupTableSQL = "DROP TABLE IF EXISTS pics_data_bak";
        if ($conn->query($dropBackupTableSQL) === TRUE) {
            echo "备份表 pics_data_bak 删除成功。<br>";
        } else {
            echo "删除备份表 pics_data_bak 时出错: " . $conn->error . "<br>";
            $conn->close();
            return;
        }

        // 复制 pics_data 表为 pics_data_bak 表
        $copyTableSQL = "CREATE TABLE pics_data_bak AS SELECT * FROM pics_data";
        if ($conn->query($copyTableSQL) === TRUE) {
            echo "表 pics_data 复制为 pics_data_bak 成功。<br>";
        } else {
            echo "复制表 pics_data 为 pics_data_bak 时出错: " . $conn->error . "<br>";
            $conn->close();
            return;
        }

        // 清空 pics_data 表的内容
        $truncateTableSQL = "TRUNCATE TABLE pics_data";
        if ($conn->query($truncateTableSQL) === TRUE) {
            echo "表 pics_data 已清空。<br>";
        } else {
            echo "清空表 pics_data 时出错: " . $conn->error . "<br>";
            $conn->close();
            return;
        }


        // 遍历 WebP 文件
        foreach ($webpFiles as $webpFile) {
            $webpInfo = getimagesize($webpFile);
            $fileName = basename($webpFile);
            $filePath = $url_prefix . $fileName;
            $fileSize = filesize($webpFile);
            $width = $webpInfo[0];
            $height = $webpInfo[1];
            $ratio = round($width / $height, 7);
            $date = date("Y-m-d", filemtime($webpFile));

            // 不使用 intval，false 导入sql的时候为空
            $landscape = intval($width > $height);
            $near_square = intval($ratio >= 0.9090909 && $ratio <= 1.1);
            $big_size = intval($fileSize > 600000);
            $small_size = intval($fileSize < 100000);
            $mid_size = intval(!$big_size && !$small_size);
            $big_res = intval(min($width, $height) > 1440);
            $small_res = intval(min($width, $height) < 640);
            $mid_res = intval(!$big_res && !$small_res);
            $bjn = intval(strpos($fileName, "bjn") !== false);

            //echo $fileName.", ".$filePath . ", " . $fileSize . ", " . $width . ", " . $height . ", " . $ratio . ", " . $date . ", " . $landscape . ", " . $near_square . ", " . $big_size . ", " . $small_size . ", " . $mid_size . ", " . $big_res . ", " . $small_res . ", " . $mid_res . ", " . $bjn . "<br>";
            // 使用 INSERT INTO 语句将数据插入到数据库中
            $insertSQL = "INSERT INTO pics_data (name, path, size, width, height, ratio, date, landscape, near_square, big_size, small_size, mid_size, big_res, small_res, mid_res, bjn) VALUES ('$fileName', '$filePath', $fileSize, $width, $height, $ratio, '$date', $landscape, $near_square, $big_size, $small_size, $mid_size, $big_res, $small_res, $mid_res, $bjn)";

            // 执行 SQL 语句插入数据
            if ($conn->query($insertSQL) === TRUE) {
                // 数据插入成功
            } else {
                echo "插入数据时出错: " . $conn->error . "<br>";
            }
        }

        echo "检索完成。共计检索到 " . count($webpFiles) . " 个图片文件。<br>";
        // 关闭数据库连接
        $conn->close();


    }
    ?>

    <form method="post">
        <input type="submit" name="search" value="开始检索图片文件">
    </form>
</body>
</html>
