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

$callback = isset($_GET['callback']) ? $_GET['callback'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$seriesID = isset($_GET['sID']) ? $_GET['sID'] : ''; // Hvad skal der hentes? Måling/signal/gns/avg?
$devicesID = isset($_GET['dID']) ? $_GET['dID'] : ''; // Liste over enheder skal skal med i data. Der vil kun være mere end en, hvis seriesID er gns/avg. Numerisk ID på enheder.
$typeID = isset($_GET['tID']) ? $_GET['tID'] : ''; // Hvad for noget data hentes, hvad er "topic" i databasen. Numerisk ID på det.
$time = isset($_GET['time']) ? $_GET["time"] : '';
$from = isset($_GET['from']) ? $_GET["from"] : '';
$to = isset($_GET['to']) ? $_GET["to"] : '';
$toggle = isset($_COOKIE['FC_Data_Plot_toggle']) ? json_decode($_COOKIE['FC_Data_Plot_toggle']) : 1;    // om det 24 timer eller alle data der hentes.
if ($type == "MATLAB") $cachetime = 0;
elseif ($toggle == 1) $cachetime = strtotime("-25 hours");
else $cachetime = strtotime("today midnight"); //midnat dagen før.


if ($seriesID == 2 || $seriesID == 3) {
    $devicesID = json_decode("[".$devicesID."]");
}

echo $callback;
if ($type != "MATLAB") echo "([";

if ($seriesID == 0 || $seriesID == 1) {
    $sql = "SELECT ";
    if ($seriesID == 0) {
        $sql.= "topic, ";
        $sql.= "timestamp, ";
        $sql.= "value";
    } else {
        $interval = 10;
        $sql.= "FROM_UNIXTIME(FLOOR( `timestamp`/".$interval." ) * ".$interval.") AS order_time, ";
        $sql.= "AVG(signaldb)";
    }
    $sql.= " as value FROM `data` WHERE `device` LIKE '".$devicesID."'";

    if ($from != '' && $to != '') {
        $sql.= " AND `timestamp` BETWEEN ".$from." AND ".$to."";
    }
    else {
        if ($time != '') $sql.= " AND `timestamp`>".$time;
        else $sql.= " AND `timestamp`>".$cachetime;
    }

    if ($seriesID == 0) $sql.= " AND `topic` LIKE '".$typeID."'";
    else $sql.= " GROUP BY order_time ";
    $sql.= " ORDER BY ";
    if ($seriesID == 0) $sql.= "`data`.`timestamp`";
    else $sql.= "`order_time`";
    $sql.= " ASC;";
} elseif ($seriesID == 2 || $seriesID == 3) { // SUM/AVG samle sql.

    if ($typeID == 3 || $typeID == 4 || $typeID == 6) $interval = 600;
    else $interval = 30;

    $sql = "SELECT `topic`, ";
    
    if ($seriesID == 2) $sql.= "AVG";
    else $sql.= "SUM";
    
    $sql.= "(`value`) AS `value`, order_time FROM ( SELECT `topic`, AVG(`value`) AS `value`, FROM_UNIXTIME(FLOOR( `timestamp`/".$interval." ) * ".$interval.") AS order_time FROM `data` WHERE `topic` = ".$typeID;
    if ($from != '' && $to != '') {
        $sql.= " AND `timestamp` BETWEEN ".$from." AND ".$to."";
    }
    else {
        if ($time != '') $sql.= " AND `timestamp`>".$time;
        else $sql.= " AND `timestamp`>".$cachetime;
    }
    $sql.= " AND (";
    
    foreach ($devicesID as $key => $value) {
        if ($key != 0) $sql.= " OR ";
        $sql.= "`device` = ".$value;
    }

    $sql.= ") GROUP BY order_time, device) AS A GROUP BY order_time ORDER BY `A`.`order_time` ASC;";
}
/*
echo $sql;
exit();*/

$result = $conn->query($sql);
$started = false;

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
    	if ($started && $type != "MATLAB") {        echo ",";}
elseif ($started) echo"\n";
        if ($type != "MATLAB") echo "[";
            if ($seriesID == 1 || $seriesID == 2 || $seriesID == 3) echo strtotime($row["order_time"])."000";
            else echo $row["timestamp"]*1000;
        if ($type != "MATLAB") echo ",";
        else echo " ";
        if ($seriesID == 1) echo ($row["value"]-255)/2.0;
        elseif ($row["topic"] == 6) echo $row["value"]/100; //temp
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

if ($type != "MATLAB") echo "]);";

 $conn->close();

  ?>


