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
$maaling = isset($_COOKIE['FC_Data_Plot_DEIF_maaling']) ? json_decode($_COOKIE['FC_Data_Plot_DEIF_maaling']) : [];

if ($type == "selectgroup") {
	$sql = "SELECT `deifid` FROM `deif` GROUP BY `deifid` ORDER BY `deifid`;";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			echo "<option value=".$row["deifid"].">".utf8_encode($row["deifid"])."</option>";
		}
	}
} elseif ($type == "selecttype") {
	$sql = "SELECT deifid,name FROM `deif-topics` ORDER BY `name` ASC;";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			echo "<option value=".$row["deifid"].">".$row["name"]."</option>";
		}
	}
} elseif ($type == "group") {
	if (isset($_POST["selectgroup"])) {
		$sql = "SELECT deifid,name FROM `deif-topics` ORDER BY `name` ASC";
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				if (!in_array(array($_POST["selectgroup"],$row["deifid"]), $maaling)) {
				echo "<option value=".$_POST["selectgroup"].",".$row["deifid"].">".utf8_encode($row["name"])."</option>";
				}
			}
		}
	}
} elseif ($type == "type") {
	if (isset($_POST["selecttype"])) {
		$sql = "SELECT `deifid` FROM `deif` GROUP BY `deifid` ORDER BY `deifid`;";
		$result = $conn->query($sql);
		if ($result->num_rows > 0) {
			while($row = $result->fetch_assoc()) {
				if (!in_array(array($row["deifid"],$_POST["selecttype"]), $maaling)) {
				echo "<option value=".$row["deifid"].",".$_POST["selecttype"].">".utf8_encode($row["deifid"])."</option>";
				}
			}
		}
	}	
}

$conn->close();

?>