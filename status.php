<?php
/*
require __DIR__ . '/vendor/autoload.php';

$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();*/

//servername, username, password og db name:
include 'datalogin.php'; 

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 


$type = isset($_GET['type']) ? $_GET['type'] : '';
$sqlerr = "";

if ($type == "deif") {
$sql = "SELECT * FROM `deif` WHERE `timestamp` >= ".strtotime('-2 minutes')."000 ORDER BY `timestamp` DESC";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
		echo "DEIF OK";
	} else {

header("HTTP/1.1 500 Internal Server Error");
    echo "DEIF error";
}

} elseif ($type == "data") {

$sql = "SELECT * FROM `data` WHERE `timestamp` >= ".strtotime('-2 minutes')." ORDER BY `timestamp` DESC";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
		echo "data OK";
	} else {

header("HTTP/1.1 500 Internal Server Error");
    echo "data error";
}
 } elseif ($type == "gates") {
 	$sql = "SELECT * FROM `gateways` LEFT JOIN (SELECT MAX(gateway) as gate, MAX(timestamp) as time FROM data ";
 	//if (!isset($_GET["all"]))
 	$sql.= "WHERE timestamp >= ".strtotime('-3 days')." ";
 	$sql.= "GROUP BY gateway) as A ON gateways.ID=A.gate";
 	if (!isset($_GET["all"])) $sql.= " WHERE gateways.monitor=1";
 	$error = false;
	$result = $conn->query($sql);
 		if ($result->num_rows > 0) {
 			while($row = $result->fetch_assoc()) {
 				if (empty($row["time"])) {
 					header('Content-Type: text/html; charset=ISO-8859-1');
 					$text = "No data on ".$row["name"]." with addr: ".$row["device"]." since more than 3 days ago";
 					echo $text."<br>";
 					$sqll = "SELECT * FROM `log` WHERE `event` LIKE '".$text."' ORDER BY `time` DESC";
					$resultt = $conn->query($sqll);
					if (!$resultt->fetch_assoc()) {
						$sqlerr.= "INSERT INTO `log` (`ID`, `time`, `event`) VALUES (NULL, CURRENT_TIMESTAMP, '".$text."');";
					}
 					$error = true;
 				}
 				elseif ($row["time"] <= strtotime('-5 minutes')) {
 					header('Content-Type: text/html; charset=ISO-8859-1');
 					$text = "No data on ".$row["name"]." with addr: ".$row["device"]." since ".date("Y-m-d H:i:s",$row["time"]);
 					echo $text."<br>";
 					$sqll = "SELECT * FROM `log` WHERE `event` LIKE '".$text."' ORDER BY `time` DESC";
					$resultt = $conn->query($sqll);
					if (!$resultt->fetch_assoc()) {
						$sqlerr.= "INSERT INTO `log` (`ID`, `time`, `event`) VALUES (NULL, CURRENT_TIMESTAMP, '".$text."');";
					}
 					$error = true;
 				}
 			}
		if ($error) {

header("HTTP/1.1 500 Internal Server Error");
		} else echo "gateways OK";
	} else {

header("HTTP/1.1 500 Internal Server Error");
    echo "gatewaycheck error";
}
 }	elseif ($type == "devices") {
 	$sql = "SELECT devices.name as name, time, devices.device as device, gate, gateway, type, groups.name as groupname FROM `devices` LEFT JOIN (SELECT MAX(gateway) as gate, MAX(device) as device, MAX(timestamp) as time FROM data WHERE timestamp >= ".strtotime('-3 days')." GROUP BY device) as A ON devices.ID=A.device LEFT JOIN groups ON groups.ID=devices.groupID WHERE devices.type NOT LIKE 'indicator'";
 	if (!isset($_GET["all"])) $sql.= " AND devices.IgnoreErr!='1'";
if (isset($_GET["dev"])) $sql.= " AND devices.ID=".$_GET["dev"];
if (isset($_GET["devtype"])) $sql.= " AND devices.type='".$_GET["devtype"]."'";
//echo $sql;
 	$error = false;
	$result = $conn->query($sql);
 		if ($result->num_rows > 0) {
 			while($row = $result->fetch_assoc()) {
 				if (empty($row["time"])) {
 					header('Content-Type: text/html; charset=ISO-8859-1');
 					$text = "No data ".$row["type"]." on ".$row["name"]." with addr: ".$row["device"]." in 3 days (or never). On group ".$row["groupname"];
 					echo $text."<br>";
 					$sqll = "SELECT * FROM `log` WHERE `event` LIKE '".$text."' ORDER BY `time` DESC";
					$resultt = $conn->query($sqll);
					if (!$resultt->fetch_assoc()) {
						$sqlerr.= "INSERT INTO `log` (`ID`, `time`, `event`) VALUES (NULL, CURRENT_TIMESTAMP, '".$text."');";
					}
 					$error = true;
 				} elseif ($row["gate"] != $row["gateway"]) {
 					header('Content-Type: text/html; charset=ISO-8859-1');
 					$text = "Invalid data ".$row["type"]." on ".$row["name"]." with addr: ".$row["device"].". On group ".$row["groupname"];
 					echo $text."<br>";
 					$sqll = "SELECT * FROM `log` WHERE `event` LIKE '".$text."' ORDER BY `time` DESC";
					$resultt = $conn->query($sqll);
					if (!$resultt->fetch_assoc()) {
						$sqlerr.= "INSERT INTO `log` (`ID`, `time`, `event`) VALUES (NULL, CURRENT_TIMESTAMP, '".$text."');";
					}
 					$error = true;
 				}
 				elseif ($row["time"] <= strtotime('-10 minutes')) {
 					header('Content-Type: text/html; charset=ISO-8859-1');
 					$text = "No data ".$row["type"]." on ".$row["name"]." with addr: ".$row["device"]." since ".date("Y-m-d H:i:s",$row["time"]).". On group ".$row["groupname"];
 					echo $text."<br>";
 					$sqll = "SELECT * FROM `log` WHERE `event` LIKE '".$text."' ORDER BY `time` DESC";
					$resultt = $conn->query($sqll);
					if (!$resultt->fetch_assoc()) {
						$sqlerr.= "INSERT INTO `log` (`ID`, `time`, `event`) VALUES (NULL, CURRENT_TIMESTAMP, '".$text."');";
					}
 					$error = true;
 				}
 			}
		if ($error) {

header("HTTP/1.1 500 Internal Server Error");
		} else echo "devices OK";
	} else {

header("HTTP/1.1 500 Internal Server Error");
    echo "devicecheck error";
}
 }	

if ($sqlerr != "") $conn->multi_query($sqlerr);

 $conn->close();

  ?>


