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
$toggle = isset($_COOKIE['FC_Data_Plot_DEIF_toggle']) ? json_decode($_COOKIE['FC_Data_Plot_DEIF_toggle']) : 1;    // om det 24 timer eller alle data der hentes.
if ($type == "MATLAB") $cachetime = 0;
elseif ($toggle == 1) $cachetime = strtotime("-25 hours");
else $cachetime = strtotime("today midnight"); //midnat dagen før.

if ($seriesID == 2 || $seriesID == 3) {
    $devicesID = explode(",",$devicesID);
    $typeID = explode(",",$typeID);
}

echo $callback;
if ($type != "MATLAB") echo "([";

if ($seriesID == 0) {
    $sql = "SELECT ";
    $sql.= "datetime, ";
    if ($typeID == "active_power_a" || $typeID == "active_power_b" || $typeID == "active_power_c" || $typeID == "active_power_sum") $sql.= "ABS(";
    $sql.= "AVG(".$typeID.")";
    if ($typeID == "active_power_a" || $typeID == "active_power_b" || $typeID == "active_power_c" || $typeID == "active_power_sum") $sql.= ")";
    $sql.= " as value";
    $sql.= " FROM `deif` ";
    $sql.= " WHERE `deif`.`deifid` = '".$devicesID."'";
    if ($from != '' && $to != '') {
        $sql.= " AND `timestamp` BETWEEN ".$from." AND ".$to."";
    }
    else {
        if ($time != '') $sql.= " AND `timestamp`>".$time."000";
        else $sql.= " AND `timestamp`>".$cachetime."000";
    }
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
    if ($from != '' && $to != '') {
        $sql.= " AND `timestamp` BETWEEN ".$from." AND ".$to."";
    }
    else {
        if ($time != '') $sql.= " AND `timestamp`>".$time."000";
        else $sql.= " AND `timestamp`>".$cachetime."000";
    }
    $sql.= " GROUP BY minute, deifid";

    $sql.= ") AS A";
    $sql.= " GROUP BY minute";
    $sql.= " ORDER BY ";
    $sql.= "`A`.`minute`";
    $sql.= " ASC;";
}
if (isset($_GET["sql"])){
echo $sql;
exit();}

$result = $conn->query($sql);
$started = false;

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
    	if ($started && $type != "MATLAB") {        echo ",";}
elseif ($started) echo"\n";
        if ($type != "MATLAB") echo "[";
        echo strtotime($row["datetime"])."000";
        if ($type != "MATLAB") echo ",";
        else echo " ";
        echo (float)$row["value"];
        if ($type != "MATLAB") echo "]";
        $started = true;
    }
} else {
    //echo "error";
}

if ($type != "MATLAB") echo "]);";

 $conn->close();

  ?>


