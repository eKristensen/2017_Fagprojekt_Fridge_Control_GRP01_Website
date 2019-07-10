<?php

//echo date("Y-m-d H:i:s",strtotime("today midnight - 1 day"));
//exit();
/*
require __DIR__ . '/vendor/autoload.php';

$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();*/


$callback = isset($_GET['callback']) ? $_GET['callback'] : '';
$seriesID = isset($_GET['sID']) ? $_GET['sID'] : ''; // Hvad skal der hentes? Måling/signal/gns/avg?
$devicesID = isset($_GET['dID']) ? $_GET['dID'] : ''; // Liste over enheder skal skal med i data. Der vil kun være mere end en, hvis seriesID er gns/avg. Numerisk ID på enheder.
$typeID = isset($_GET['tID']) ? $_GET['tID'] : ''; // Hvad for noget data hentes, hvad er "topic" i databasen. Numerisk ID på det.
$updatemem = false;
$cachetime = strtotime("today midnight"); //midnat dagen før.
$content = "";
$lasttime = 0;
$time = "";


$maxAge = strtotime("now")-$cachetime;

header('Content-Type: application/json;charset=UTF-8');
//header ("Expires: " . date ('D, d M Y H:i:s \G\M\T', strtotime("today midnight")));
//header ("Cache-Control: max-age=" . $maxAge);

//CACHE
/*
$mem = new Memcached();
$mem->addServer("127.0.0.1", 11211);
$memkey = $seriesID.";".$devicesID.";".$typeID;
//$cache = $mem->get($memkey);

//echo "result: \"".$mem->get($memkey."time")."\"";

    $time = $mem->get($memkey."time")*1000;
    if ($time < $cachetime) $updatemem = true;
    else $updatemem = false;
/*
echo "Update? ".$updatemem."\n";
echo "cachetime ".$cachetime."\n";
echo "time: ".$time."\n";
*/

//if ($updatemem) {

    //servername, username, password og db name:
include 'datalogin.php'; 

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

if ($seriesID == 2 || $seriesID == 3) {
    $devicesID = json_decode("[".$devicesID."]");
}

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
    $sql.= " AND `timestamp`<".$cachetime;
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
    $sql.= " AND `timestamp`<".$cachetime;
    $sql.= " AND (";
    
    foreach ($devicesID as $key => $value) {
        if ($key != 0) $sql.= " OR ";
        $sql.= "`device` = ".$value;
    }

    $sql.= ") GROUP BY order_time, device) AS A GROUP BY order_time ORDER BY `A`.`order_time` ASC;";
}

//echo $sql;
//exit();
echo $callback;
echo "([";

$result = $conn->query($sql);
$started = false;
$content = "";

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
    	if ($started) echo ",";
        echo "[";
            if ($seriesID == 1 || $seriesID == 2 || $seriesID == 3) $lasttime = strtotime($row["order_time"])*1000;
            else $lasttime = $row["timestamp"]*1000;
        echo$lasttime;
        echo ",";
        if ($seriesID == 1) echo ($row["value"]-255)/2.0;
        elseif ($row["topic"] == 6) echo $row["value"]/100; //temp
        elseif ($row["topic"] == 1) echo $row["value"]/10; //voltage
        elseif ($row["topic"] == 2) echo $row["value"]/1000; //current
        elseif ($row["topic"] == 4) echo ($row["value"]-1)/10000; //light
        else echo $row["value"];
        echo "]";
        $started = true;
    }
} else {
    //echo "error";
}

 $conn->close();

if ($lasttime == 0) {
    $lasttime = $cachetime+1000;
    $time = 0;
} 

//}


/*if ($updatemem) echo $content;
/*else echo $mem->get($memkey);

if ($updatemem) {
    $mem->set($memkey, $content);
    //echo "\nSet time to: ".$lasttime/1000;
    /*
echo "\nlasttime: ".($lasttime/1000);
echo "\ntime: ".$time."\n";*/
/*
if ($lasttime/1000 > $time) {
    $mem->set($memkey."time", $lasttime/1000);
    /*
    echo "Done";
    echo $mem->get($memkey."time");*/
/*
}
}*/

echo "]);";


  ?>


