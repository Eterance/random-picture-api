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

// 初始化查询条件
$conditions = [];

// 处理 UA 参数
// https://www.feiniaomy.com/post/306.html
if (isset($_GET['ua'])) {
    if (empty($_SERVER['HTTP_USER_AGENT'])) {
        $is_mobile = false;
    } 
    elseif ( strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false 
        || strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false
        || strpos($_SERVER['HTTP_USER_AGENT'], 'Silk/') !== false
        || strpos($_SERVER['HTTP_USER_AGENT'], 'Kindle') !== false
        || strpos($_SERVER['HTTP_USER_AGENT'], 'BlackBerry') !== false
        || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== false
        || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mobi') !== false ) {
        $is_mobile = true;
    }
    else{
        $is_mobile = false;
    }
    // 判断 UA 是否包含手机或平板设备相关标识
    if ($is_mobile) {
        // 如果 UA 中包含手机或平板设备标识，则设置 landscape=0
        $conditions[] = "landscape = 0";
    } else {
        // 否则，设置 landscape=1
        $conditions[] = "landscape = 1";
    }
} 
else {
    // 如果未提供 UA 参数，则根据其他查询参数构建 landscape 条件

    // 处理 landscape 参数
    if (isset($_GET['landscape'])) {
        $landscape = intval(($_GET['landscape'] == 1) ? true : false);
        $conditions[] = "landscape = $landscape";
    }
}

// 处理 near_square 参数
if (isset($_GET['near_square'])) {
    $nearSquare = intval(($_GET['near_square'] == 1) ? true : false);
    $conditions[] = "near_square = $nearSquare";
}

// 处理尺寸参数 (big_size, mid_size, small_size)
$sizeConditions = [];
$sizeParams = ['big_size', 'mid_size', 'small_size'];
foreach ($sizeParams as $param) {
    if (isset($_GET[$param])) {
        $param_value = intval(($_GET[$param] == 1) ? true : false);
        $sizeConditions[] = "$param = $param_value";
    }
}
if (!empty($sizeConditions)) {
    $conditions[] = "(" . implode(" and ", $sizeConditions) . ")";
}

// 处理分辨率参数 (big_res, mid_res, small_res)
$resConditions = [];
$resParams = ['big_res', 'mid_res', 'small_res'];
foreach ($resParams as $param) {
    if (isset($_GET[$param])) {
        $param_value = intval(($_GET[$param] == 1) ? true : false);
        $resConditions[] = "$param = $param_value";
    }
}
if (!empty($resConditions)) {
    $conditions[] = "(" . implode(" and ", $resConditions) . ")";
}

// 处理 bjn 参数
if (isset($_GET['nobjn'])) {
    //$bjn = intval(($_GET['bjn'] == 1) ? true : false);
    // 不允许 bjn 参数为 1 来指定只要蛇图
    $conditions[] = "bjn = 0";
}

$class = DEFAULT_TABLE;
if (isset($_GET['class'])) {
    $class = $_GET['class'];
}

// 构建 SQL 查询语句
$sql = "SELECT";
$sql .= isset($_GET['count'])? " COUNT(*) AS count" : " url";
$sql .= " FROM `$class`";
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" and ", $conditions);
}

//die($sql);

try {
    // 执行查询
    $result = $conn->query($sql);
    $conn->close();
} 
catch (Exception $e) {
    header("HTTP/1.1 500 Internal Server Error");
    echo "500 Internal Server Error: 数据库出错。请联系管理员。";
    exit;
}

if (!$result) {
    header("HTTP/1.1 500 Internal Server Error");
    echo "500 Internal Server Error: 数据库出错。请联系管理员。";
    exit;
}

if (isset($_GET['count'])) {
    $row = $result->fetch_assoc();
    die($row['count']);
}

$randomImageUrl = '';
if ($result->num_rows > 0) {
    $randomIndex = rand(0, $result->num_rows - 1);
    $result->data_seek($randomIndex);
    $row = $result->fetch_assoc();
    $randomImageUrl = $row['url'];
} 
if (!empty($randomImageUrl)) {
    header("Location: " . $randomImageUrl, true, 302);
    exit;
} else {
    header("HTTP/1.1 400 Bad Request");
    echo "400 Bad Request: 没有找到符合条件的图片。";
}

?>