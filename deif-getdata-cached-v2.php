<?php

$callback = isset($_GET['callback']) ? $_GET['callback'] : '';
$seriesID = isset($_GET['sID']) ? $_GET['sID'] : ''; // Hvad skal der hentes? Måling/signal/gns/avg?
$devicesID = isset($_GET['dID']) ? $_GET['dID'] : ''; // Liste over enheder skal skal med i data. Der vil kun være mere end en, hvis seriesID er gns/avg. Numerisk ID på enheder.
$typeID = isset($_GET['tID']) ? $_GET['tID'] : ''; // Hvad for noget data hentes, hvad er "topic" i databasen. Numerisk ID på det.
$updatemem = false;
$cachetime = strtotime("today midnight"); //midnat dagen før.
$lasttime = 0;
$time = "";

$maxAge = strtotime("now")-$cachetime;

header('Content-Type: application/json;charset=UTF-8');
//header ("Expires: " . date ('D, d M Y H:i:s \G\M\T', strtotime("today midnight")));
//header ("Cache-Control: max-age=" . $maxAge);

//CACHE

$mem = new Memcached();
$mem->addServer("127.0.0.1", 11211);
$memkey = $seriesID.";".$devicesID.";".$typeID;

    $time = $mem->get($memkey."time"."v2")*1000;
    if ($time < $cachetime) $updatemem = true;
    else $updatemem = false;
/*
echo "Update? ".$updatemem."\n";
echo "cachetime ".$cachetime."\n";
echo "time: ".$time."\n";*/

if ($updatemem) {

    //servername, username, password og db name:
include 'datalogin.php'; 

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

if ($seriesID == 2 || $seriesID == 3) {
    $devicesID = explode(",",$devicesID);
    $typeID = explode(",",$typeID);
}

if ($seriesID == 0) {
    $sql = "SELECT ";
    $sql.= "datetime, ";
    if ($typeID == "active_power_a" || $typeID == "active_power_b" || $typeID == "active_power_c" || $typeID == "active_power_sum") $sql.= "ABS(";
    $sql.= "AVG(".$typeID.")";
    if ($typeID == "active_power_a" || $typeID == "active_power_b" || $typeID == "active_power_c" || $typeID == "active_power_sum") $sql.= ")";
    $sql.= " as value FROM `deif` ";
    $sql.= " WHERE `deif`.`deifid` = '".$devicesID."'";
    $sql.= " AND `timestamp`<".$cachetime."000";
    $sql.= " GROUP BY datetime";
    $sql.= " ORDER BY ";
    $sql.= "`deif`.`datetime`";
    $sql.= " ASC;";
} elseif ($seriesID == 2 || $seriesID == 3) { // SUM/AVG samle sql.
    $sql = "SELECT ";
    $sql.= "minute as datetime, ";
    if ($seriesID == 3) $sql.= "SUM";
    else $sql.= "AVG";
    $sql.= "(value) as value FROM (";

    $sql.= "SELECT ";//Inner select)
    $sql.= "minute, ";

    $cases = [[]];
    foreach ($devicesID as $key => $value) {
        $cases[$value][] = $typeID[$key];
    }
    //var_dump($cases);

    $sql.= " CASE ";
    foreach ($cases as $key => $case) {
        if ($key != "0") {
        $sql.= " WHEN deifid='".$key."' THEN (";
        $casn = 0;
        foreach ($case as $keyy => $value) {
            if ($keyy != 0) $sql.= "+";
                if ($value == "active_power_a" || $value == "active_power_b" || $value == "active_power_c" || $value == "active_power_sum") $sql.= "ABS(";
                $sql.= "AVG(".$value.")";
                if ($value == "active_power_a" || $value == "active_power_b" || $value == "active_power_c" || $value == "active_power_sum") $sql.= ")";
                $casn++;
           }
        $sql.= ") ";
        if ($seriesID == 2) $sql.= "/ ".$casn." ";
        }
    }
    
    $sql.= " ELSE NULL END ";

    $sql.= " as value FROM `deif` ";
    $sql.= " WHERE (";
    foreach ($devicesID as $key => $value) {
        if ($key != 0) $sql.= " OR ";
        $sql.= "`deif`.`deifid` = '".$value."'";
    }
    $sql.= ")";
    if ($time != '') $sql.= " AND `timestamp`>".$time."000";
    else $sql.= " AND `timestamp`<".$cachetime."000";
    $sql.= " GROUP BY minute, deifid";

    $sql.= ") AS A";
    $sql.= " GROUP BY minute";
    $sql.= " ORDER BY ";
    $sql.= "`A`.`minute`";
    $sql.= " ASC;";
}

$result = $conn->query($sql);
$started = false;
$content = "";

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
    	if ($started) {        $content.= ",";}
        $content.= "[";
        $lasttime = strtotime($row["datetime"])*1000;
        $content.= $lasttime;
        $content.= ",";
        $content.= (float)$row["value"];
        $content.= "]";
        $started = true;
    }
}

 $conn->close();

if ($lasttime == 0) {
    $lasttime = $cachetime+1000;
    $time = 0;
} 
}

echo $callback;
echo "([";

if ($updatemem) echo $content;
else echo $mem->get($memkey."v2");

if ($updatemem) {
    $mem->set($memkey."v2", $content);
if ($lasttime/1000 > $time) {
    $mem->set($memkey."time"."v2", $lasttime/1000);
}
}

echo "]);";


  ?>


