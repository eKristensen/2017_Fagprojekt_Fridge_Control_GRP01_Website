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

$type = isset($_GET['type']) ? $_GET['type'] : '';
$maaling = isset($_COOKIE['FC_Data_Plot_maaling']) ? json_decode($_COOKIE['FC_Data_Plot_maaling']) : [];

if ($type == "selectgroup") {
	$sql = "SELECT ID, name FROM `groups` ORDER BY `name` ASC;";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			echo "<option value=".$row["ID"].">".utf8_encode($row["name"])."</option>";
		}
	}
} elseif ($type == "selecttype") {
	$sql = "SELECT topic FROM `topics` WHERE `type` NOT LIKE 'all' ORDER BY `topic` ASC";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			echo "<option value=".$row["topic"].">".$row["topic"]."</option>";
		}
	}
} elseif ($type == "group") {
	if (isset($_POST["selectgroup"])) {
		$sql = "SELECT devices.ID as id, topics.topic as topic, devices.name as name FROM devices JOIN topics On devices.type=topics.type WHERE devices.groupID=".$_POST["selectgroup"]." ORDER BY topics.topic ASC,devices.name;";
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				if (!in_array(array($row["id"],$row["topic"]), $maaling)) {
				echo "<option value=".$row["id"].$row["topic"].">".$row["topic"]." ".utf8_encode($row["name"])."</option>";
				}
			}
		}
	}
} elseif ($type == "type") {
	if (isset($_POST["selecttype"])) {
		$sql = "SELECT devices.ID as id, topics.topic as topic, devices.name as devicename, groups.name as name FROM devices JOIN topics On devices.type=topics.type JOIN groups ON devices.groupID=groups.ID WHERE topics.topic='".$_POST["selecttype"]."' ORDER BY `groups`.`name` ASC;";
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				if (!in_array(array($row["id"],$row["topic"]), $maaling)) {
				echo "<option value=".$row["id"].$row["topic"].">".utf8_encode($row["name"])." ".utf8_encode($row["devicename"])."</option>";
				}
			}
		}
	}	
}

$conn->close();

?>