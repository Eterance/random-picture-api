<!DOCTYPE html>
<html>
<head>
    <title>图片文件检索工具</title>
</head>
<body>
    <h1>图片文件检索工具</h1>


    
        
    
    <?php
    // 打开配置文件
    require_once('scan_config.php');
    echo '<form method="post">';
    echo '<button type="submit" name="retrieve" value="all"> 检索全部</button>';
    // 遍历数组并为每个元素创建按钮
    foreach (CLASS_AND_PATH as $label) {
        // 使用echo语句输出HTML按钮代码
        echo '<button type="submit" name="retrieve" value="'.$label[0] . '"> 检索 ' . $label[0] . '</button>';
    }
    echo '</form>';

    // 处理前端请求
    if (isset($_POST['retrieve'])) {
        // 创建数据库连接
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // 检查连接是否成功
        if ($conn->connect_error) {
            die("数据库连接失败: " . $conn->connect_error);
        }


        // 创建 table_names 表
        $createTableQuery = "CREATE TABLE IF NOT EXISTS `table_names` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL
        )";

        if ($conn->query($createTableQuery) === TRUE) {
            echo "表 table_names 已创建或已存在。<br>";
        } else {
            echo "创建表 table_names 失败: " . $conn->error . "<br>";
            $conn->close();
            die();
        }

        // 清空 table_names 表
        $truncateTableQuery = "TRUNCATE TABLE table_names";

        if ($conn->query($truncateTableQuery) === TRUE) {
            echo "表 table_names 已清空。<br>";
        } else {
            echo "清空表 table_names 失败: " . $conn->error . "<br>";
            $conn->close();
            die();
        }

        $insertQuery = "INSERT INTO table_names (name) VALUES (?)";        
        $totalFiles = 0;

        foreach (CLASS_AND_PATH as $cp) {
            $sub_table_name = $cp[0];
            $path_prefixs = $cp[1];
            
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("s", $sub_table_name);
            
            if ($stmt->execute() === TRUE) {
                echo "已插入表名：{$sub_table_name}。<br>";
            } else {
                echo "插入表名失败: " . $stmt->error . "<br>";
                $conn->close();
                die();
            }
            
            $stmt->close();
            
            if ($_POST['retrieve'] != "all" && $_POST['retrieve'] != $cp[0]) {
                continue;
            }
        
            // 创建表（如果不存在）
            $createTableSQL = "CREATE TABLE IF NOT EXISTS `".$sub_table_name."` (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255),
                url VARCHAR(255),
                size INT,
                width INT,
                height INT,
                ratio DECIMAL(5, 3),
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
                echo "数据表 ".$sub_table_name." 重建成功。<br>";
            } else {
                echo ("重建数据表 ".$sub_table_name." 时出错: " . $conn->error . "<br>");
                $conn->close();
                die();
            }

            // 删除备份表（如果存在）
            $dropBackupTableSQL = "DROP TABLE IF EXISTS `".$sub_table_name."_bak`";
            if ($conn->query($dropBackupTableSQL) === TRUE) {
                echo "备份表 ".$sub_table_name."_bak 删除成功。<br>";
            } else {
                echo ("删除备份表 ".$sub_table_name."_bak 时出错: " . $conn->error . "<br>");
                $conn->close();
                die();
            }

            // 复制表
            $copyTableSQL = "CREATE TABLE ".$sub_table_name."_bak AS SELECT * FROM `".$sub_table_name."`";
            if ($conn->query($copyTableSQL) === TRUE) {
                echo "表 ".$sub_table_name." 复制为 ".$sub_table_name."_bak 成功。<br>";
            } else {
                echo ("复制表 ".$sub_table_name." 为 ".$sub_table_name."_bak 时出错: " . $conn->error . "<br>");
                $conn->close();
                die();
            }

            // 清空表的内容
            $truncateTableSQL = "TRUNCATE TABLE `".$sub_table_name."`";
            if ($conn->query($truncateTableSQL) === TRUE) {
                echo "表 ".$sub_table_name." 已清空。<br>";
            } else {
                echo ("清空表 ".$sub_table_name." 时出错: " . $conn->error . "<br>");
                $conn->close();
                die();
            }



            // 检索该目录下 WebP、JPG 和 PNG 文件
            $allowedExtensions = ['webp', 'jpg', 'jpeg', 'png'];
            $sub_table_total_inserted = 0;
            

            foreach ($path_prefixs as $prefix) {
                $fileNum = 0;
                $pictureFiles = [];
                echo "$sub_table_name : 正在检索 $prefix 里的图片文件……<br>";
                foreach ($allowedExtensions as $extension) {
                    $pictureFiles = array_merge($pictureFiles, glob(PATH_PREFIX.$prefix. '/*.'. $extension));
                }
            

            
                // 遍历文件
                foreach ($pictureFiles as $picFile) {
                    $picInfo = getimagesize($picFile);
                    $fileName = basename($picFile);
                    $url = URL_PREFIX. $prefix. "/". $fileName;
                    $fileSize = filesize($picFile);
                    $width = $picInfo[0];
                    $height = $picInfo[1];
                    $ratio = round($width / $height, 3);

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


                    // 使用 INSERT INTO 语句将数据插入到表中
                    $insertSQL = "INSERT INTO `".$sub_table_name."` (name, url, size, width, height, ratio, landscape, near_square, big_size, small_size, mid_size, big_res, small_res, mid_res, bjn) VALUES ('$fileName', '$url', $fileSize, $width, $height, $ratio, $landscape, $near_square, $big_size, $small_size, $mid_size, $big_res, $small_res, $mid_res, $bjn)";
                    //die($insertSQL);

                    // 执行 SQL 语句插入数据
                    if ($conn->query($insertSQL) === TRUE) {
                        $fileNum += 1;
                        $percent = round($fileNum / count($pictureFiles) * 100, 2);
                    } else {
                        die("插入数据时出错: " . $conn->error . "<br>");
                    }
                }
                $totalFiles += $fileNum;
                $sub_table_total_inserted += $fileNum;
                echo "$sub_table_name : 已插入 $fileNum 条数据。<br>";
                echo "<br>";
            }
            echo "$sub_table_name : 总共插入 $sub_table_total_inserted 条数据。<br>";
        }

        echo "检索完成。总共插入 $totalFiles 条数据。<br>";
        // 关闭数据库连接
        $conn->close();
    }
    ?>
</body>
</html>
