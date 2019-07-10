<?php

require __DIR__ . '/vendor/autoload.php';

$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

$type = isset($_GET['type']) ? $_GET['type'] : '';
$name = isset($_GET['name']) ? $_GET['name'] : '';
$addr = isset($_GET['addr']) ? $_GET['addr'] : '';
$aktiv = isset($_GET['aktiv']) ? $_GET['aktiv'] : '';
$id = isset($_POST['ID']) ? $_POST['ID'] : '';


//servername, username, password og db name:
include 'datalogin.php'; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
/*
foreach ($_POST as $key => $value){
  echo "{$key} = {$value}\r\n";
}*/


header('Content-Type: charset=UTF-8');

if ($type == "editgate") {
	if ($id == "Choose") {
		exit();
	}
	$sql = "SELECT name FROM `gateways` WHERE  `ID` = ".$id.";";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
    	while($row = $result->fetch_assoc()) {
    		echo utf8_encode($row["name"]);
    	}
    } else {
    	echo "ERROR! ". $conn->error;
    }
} elseif ($type == "editgroup") {
	if ($id == "Choose") {
		exit();
	}
	$sql = "SELECT name, aktiv FROM `groups` WHERE  `ID` = ".$id.";";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
    	while($row = $result->fetch_assoc()) {
			echo "Name: <input name=name type=text value=\"".utf8_encode($row["name"])."\"><br>";
			echo "Aktiv? <input name=aktiv type=checkbox ";
			if ($row["aktiv"] == "1") echo "checked";
			echo "><br>";
    	}
    } else {
    	echo "ERROR! ". $conn->error;
    }
} elseif ($type == "editdevice") {
	if ($id == "Choose") {
		exit();
	}
	$sql = "SELECT ID,name,type,device FROM `devices` WHERE  `groupID` = ".$id.";";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
    	while($row = $result->fetch_assoc()) {
    		echo utf8_encode($row["name"])." type: ".$row["type"]." ID: ".$row["ID"]." Address: ".$row["device"]."<br>";
			echo "<input name=name".$row["ID"]." type=text value=\"".utf8_encode($row["name"])."\"><br><br>";
    	}
    } else {
    	echo "ERROR! ". $conn->error;
    }
}

$conn->close();

?>