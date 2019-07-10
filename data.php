<?php

require __DIR__ . '/vendor/autoload.php';

$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

header('Content-Type: application/json;charset=UTF-8');

//servername, username, password og db name:
include 'datalogin.php'; 

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 


$topic = "";
$callback = "";
$type = "";
$id = "";
$topic = isset($_GET['topic']) ? $_GET['topic'] : '';
$callback = isset($_GET['callback']) ? $_GET['callback'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? $_GET['id'] : '';

echo $callback;
if ($type != "MATLAB") echo "(
[
";

if ($id == 0) $sql = "SELECT `topic`, SUM(`value`) AS `value`, order_time FROM ( SELECT `topic`, AVG(`value`) AS `value`, DATE_FORMAT(FROM_UNIXTIME(`timestamp`), '%Y-%m-%d %H:%i:00') AS order_time FROM `data` WHERE `topic` = ".$topic." AND `timestamp`>=".strtotime('-2 months')." GROUP BY order_time, device) AS A GROUP BY order_time ORDER BY `A`.`order_time` ASC;";
elseif ($topic != 8) $sql = "SELECT ID, timestamp,topic,value FROM `data` WHERE `device` LIKE '".$id."' AND `topic` LIKE '".$topic."'  ORDER BY `data`.`timestamp` ASC;;";
else $sql = "SELECT timestamp,signal as value FROM `data` WHERE `device` LIKE '".$id."' AND `topic` LIKE '".$topic."';";
$result = $conn->query($sql);
$started = false;

if ($topic == "") exit();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
    	if ($started && $type != "MATLAB") {        echo ",
";}
elseif ($started) echo"\n";
        if ($type != "MATLAB") echo "[";
            if ($id == 0) echo strtotime($row["order_time"])."000";
            else echo $row["timestamp"]*1000;
        if ($type != "MATLAB") echo ",";
        else echo " ";
        if ($row["topic"] == 6) echo $row["value"]/100; //temp
        elseif ($row["topic"] == 1) echo $row["value"]/10; //voltage
        elseif ($row["topic"] == 2) echo $row["value"]/1000; //current
        elseif ($row["topic"] == 4) echo ($row["value"]-1)/10000; //light
        else echo $row["value"];
        if ($type != "MATLAB") echo "]";
        $started = true;
    }
} else {
    //echo "error";
}

if ($type != "MATLAB") echo "
]);";

 $conn->close();

  ?>


