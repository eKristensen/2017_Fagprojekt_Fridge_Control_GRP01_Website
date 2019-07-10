<?php

require __DIR__ . '/vendor/autoload.php';

$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

//servername, username, password og db name:
include 'datalogin.php'; 

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

 	$sql = "SELECT ID,timestamp FROM `data` WHERE ID >= 1700000 ORDER BY `data`.`ID` ASC LIMIT 800000";
 	$prev = null;
 	$previd=null;
 	$started = false;
	$result = $conn->query($sql);
 		if ($result->num_rows > 0) {
 			while($row = $result->fetch_assoc()) {
 				
 				if (!$started) {
 					$prev = $row["timestamp"];
 					$started = true;
 				}
 				if ($prev > $row["timestamp"]) {
 					echo $previd . " is larger than " . $row["ID"];
 					exit();
 				} //else echo "OK";
 				$previd = $row["ID"];
 				$prev = $row["timestamp"];
 			}
	} 


 $conn->close();

  ?>


