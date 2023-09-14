<?php
// 读取设置文件
$config = parse_ini_file('/www/wwwroot/pictures.myapi.com/admin/rpic_config.ini');

// 创建数据库连接
$conn = new mysqli($config['host'], $config['user_name'], $config['pw'], $config['db_name']);

// 检查连接是否成功
if ($conn->connect_error) {
    header("HTTP/1.1 500 Internal Server Error");
    echo "500 Internal Server Error: 数据库连接失败。请联系管理员。";
    exit;
}

function parse_sql_where($where_terms)
{
    $key_words = ["(", ")", "or", "and", "not"];
    $permitted_column_names = ["size", "width", "height", "ratio", "landscape", "near_square", "big_size", "mid_size", "small_size", "big_res", "mid_res", "small_res", "bjn", "ua"];
    $operators = ["=", "<>", ">", "<", ">=", "<="];
    $is_expected_number = false;
    $next_expected = null;

    $validated_sql_parts = [];
    $current_param_index = 0;
    $last_column_name = null;
    
    foreach ($where_terms as $where_term) 
    {
        if ($where_term == "")
        {
            continue;
        }
        if (in_array($where_term, $key_words) || in_array($where_term, $permitted_column_names) || in_array($where_term, $operators) || is_numeric($where_term)) 
        {
            if (in_array($where_term, $permitted_column_names))
            {
                $last_column_name = $where_term;
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
                    $validated_sql_parts[] = "landscape = 0";
                } else {
                    // 否则，设置 landscape=1
                    $validated_sql_parts[] = "landscape = 1";
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
    return implode(" ", $validated_sql_parts);
}

try {
    $where_name = "sql";
    $where_condition = "";
    if (isset($_GET[$where_name])) {
        $where_terms = explode(' ', $_GET[$where_name]);
        // trim
        for ($i = 0; $i < count($where_terms); $i++) {
            $where_terms[$i] = trim($where_terms[$i]);
        }
        if (count($where_terms) >= 30) {   
            header("HTTP/1.1 400 Bad Request");
            echo "400 Bad Request: 你的 where 条件太长了：".count($where_terms)." 个 >= 30 个。过多的 where 条件会加重服务器负担，请精简你的 where 条件。";
            exit;
        }
        $where_condition = " where ".parse_sql_where($where_terms);
    }
} 
catch (Exception $e) {
    header("HTTP/1.1 500 Internal Server Error");
    echo "500 Internal Server Error: SQL 构建失败。请联系管理员。";
    exit;
}

// 构建 SQL 查询语句
$sql = "SELECT";
$sql .= isset($_GET['count']) ? " COUNT(*) AS count" : " path";
$sql .= " FROM pics_data " . $where_condition;

if (isset($_GET["debug"])) {
    die($where_condition);
}

if (isset($_GET["length"])) {
    die(count($where_terms)." 个");
}

try {
    // 执行查询
    $result = $conn->query($sql);
    $conn->close();
} 
catch (Exception $e) {    
    header("HTTP/1.1 400 Bad Request");
    echo "400 Bad Request: 查询数据库失败。SQL 不正确。使用查询项 debug 来查看你的 sql 语句 where 条件。";
    exit;
}

if (!$result) {    
    header("HTTP/1.1 400 Bad Request");
    echo "400 Bad Request: 查询数据库失败。SQL 不正确。使用查询项 debug 来查看你的 sql 语句 where 条件。";
    exit;
}

if (isset($_GET['count'])) {
    $row = $result->fetch_assoc();
    die($row['count']);
}

$randomImagePath = '';
if ($result->num_rows > 0) {
    $randomIndex = rand(0, $result->num_rows - 1);
    $result->data_seek($randomIndex);
    $row = $result->fetch_assoc();
    $randomImagePath = $row['path'];
}
if (!empty($randomImagePath) && $randomImagePath != '') {
    header("Location: " . $randomImagePath, true, 302);
    exit;
} else {
    header("HTTP/1.1 400 Bad Request");
    echo "400 Bad Request: 没有找到符合条件的图片。";
    exit;
}

?>