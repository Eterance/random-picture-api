<?php
// 打开配置文件
require_once('../admin/rpic_config.php');
// 创建数据库连接
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// 检查连接是否成功
if ($conn->connect_error) {
    header("HTTP/1.1 500 Internal Server Error");
    echo "500 Internal Server Error: 数据库连接失败。请联系管理员。";
    exit;
}

function parse_sql_elements($where_terms)
{
    $key_words = ["(", ")", "or", "and", "not"];
    $permitted_column_names = ["size", "width", "height", "ratio", "landscape", "near_square", "big_size", "mid_size", "small_size", "big_res", "mid_res", "small_res", "bjn", "ua"];
    $operators = ["=", "<>", ">", "<", ">=", "<="];

    $validated_sql_parts = [];
    $last_column_name = null;
    
    for ($index=0; $index < count($where_terms); $index++) 
    {
        $where_term = $where_terms[$index];
        if ($where_term == "")
        {
            continue;
        }
        if (in_array($where_term, $key_words) || in_array($where_term, $permitted_column_names) || in_array($where_term, $operators) || is_numeric($where_term)) 
        {
            if (in_array($where_term, $permitted_column_names))
            {
                $last_column_name = $where_term;
                // 为列名加上反引号
                $where_terms[$index] = "`".$where_term."`";
                $where_term = $where_terms[$index];
            }
            if (is_numeric($where_term) && $last_column_name == "bjn" && $where_term == 1)
            {
                header("HTTP/1.1 400 Bad Request");
                echo "400 Bad Request: 不允许项：bjn = 1 。不允许单独查询 bjn 图片。";
                exit;
            }
            // 处理 UA 参数
            // https://www.feiniaomy.com/post/306.html
            if ($where_term == "ua") 
            {
                if (empty($_SERVER['HTTP_USER_AGENT'])) {
                    $is_mobile = false;
                } elseif (
                    strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false
                    || strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false
                    || strpos($_SERVER['HTTP_USER_AGENT'], 'Silk/') !== false
                    || strpos($_SERVER['HTTP_USER_AGENT'], 'Kindle') !== false
                    || strpos($_SERVER['HTTP_USER_AGENT'], 'BlackBerry') !== false
                    || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== false
                    || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mobi') !== false
                ) {
                    $is_mobile = true;
                } else {
                    $is_mobile = false;
                }
                // 判断 UA 是否包含手机或平板设备相关标识
                if ($is_mobile) {
                    // 如果 UA 中包含手机或平板设备标识，则设置 landscape=0
                    $validated_sql_parts[] = "`landscape` = 0";
                } else {
                    // 否则，设置 landscape=1
                    $validated_sql_parts[] = "`landscape` = 1";
                }
            }
            else
            {
                $validated_sql_parts[] = $where_term;
            }
        } 
        else 
        {
            header("HTTP/1.1 400 Bad Request");
            echo "400 Bad Request: 不允许项：" . $where_term;
            exit;
        }
    }
    return $validated_sql_parts;
}


$tableNamesArray = [];


$selectSQL = "SELECT name FROM `table_names`";
    $result = $conn->query($selectSQL);
    if ($result) {
        // 从结果集中获取所有 "name" 列的值并存储在数组中        
        while ($row = $result->fetch_assoc()) {
            $tableNamesArray[] = $row["name"];
        }
    } else {
        echo "500 Internal Server Error: 数据库查询失败。请联系管理员。";;
        $conn->close();
        exit;
}

function build_sql($tableName, $where_raw) {
    try {
        $where_condition = "";
        $where_terms = explode(' ', $where_raw);
        // trim
        for ($i = 0; $i < count($where_terms); $i++) {
            $where_terms[$i] = trim($where_terms[$i]);
        }
        if (count($where_terms) >= 30) {   
            header("HTTP/1.1 400 Bad Request");
            echo "400 Bad Request: 你的 where 条件 ($where_raw) 太长了：".count($where_terms)." 个 >= 30 个。过多的 where 条件会加重服务器负担，请精简你的 sql。";
            exit;
        }
        $validated_sql_parts = parse_sql_elements($where_terms);
        if (count($validated_sql_parts) == 0) {
            $where_condition = "";
        }
        else{
            $where_condition = " where ".implode(" ", $validated_sql_parts);
        }
    } 
    catch (Exception $e) {
        header("HTTP/1.1 500 Internal Server Error");
        echo "500 Internal Server Error: SQL 构建失败。请联系管理员。";
        exit;
    }
    // 构建 SQL 查询语句
    $sql = "select";
    $sql .= isset($_GET['count']) ? " COUNT(*) AS count" : " `url` ";
    $sql .= " from `$tableName` " . $where_condition;
    return $sql;
}

$built_sqls = [];
$is_has_table_name = false;

foreach ($tableNamesArray as $tableName) {    
    if (isset($_GET[$tableName])) {
    $is_has_table_name = true;        
    $built_sqls[] = build_sql($tableName, $_GET[$tableName]);
    }
    else if (isset($_GET["all"])){
        $is_has_table_name = true;        
        $built_sqls[] = build_sql($tableName, $_GET["all"]);
    }
}

if ($is_has_table_name == false){
    foreach(DEFAULT_TABLES as $tableName)
    {
        // 构建 SQL 查询语句
        $sql = "select";
        $sql .= isset($_GET['count']) ? " COUNT(*) AS count" : " `url` ";
        $sql .= " from `$tableName`";
        $built_sqls[] = $sql;
    }
}


if (isset($_GET["debug"])) {
    die(implode("<br>", $built_sqls));
}

$results = [];
try {
    foreach ($built_sqls as $sql) {
        $result = $conn->query($sql);
        if ($result) {
            $results[] = $result;
        } else {
            header("HTTP/1.1 400 Bad Request");
            echo "400 Bad Request: 查询数据库失败。SQL: \"$sql\" 。使用查询项 debug 来查看你的 sql 语句。错误信息：".$conn->error;
            exit;
        }
    }
} 
catch (Exception $e) {    
    header("HTTP/1.1 400 Bad Request");
    echo "400 Bad Request: 查询数据库失败。SQL 不正确。使用查询项 debug 来查看你的 sql 语句。错误信息：".$e->getMessage();
    exit;
}

if (isset($_GET['count'])) {
    $count = 0;
    foreach ($results as $result) {
        $row = $result->fetch_assoc();
        $count += $row['count'];
    }
    echo $count;
    exit;
}

$all_urls = [];
foreach ($results as $result) {
    while ($row = $result->fetch_assoc()) {
        $all_urls[] = $row['url'];
    }
}
$randomImageUrl = "";
if (count($all_urls) > 0) {
    $randomImageUrl = $all_urls[array_rand($all_urls)];
}
if (!empty($randomImageUrl) && $randomImageUrl != '') {    
    $conn->close();
    header("Location: " . $randomImageUrl, true, 302);
    exit;
} else {
    header("HTTP/1.1 400 Bad Request");
    echo "400 Bad Request: 没有找到符合条件的图片。";
    exit;
}

?>