<?php
//foreach ($_POST as $key => $value) echo "Field ".htmlspecialchars($key)." is ".htmlspecialchars($value)."<br>";


header('Content-Type: text/html; charset=ISO-8859-1');

$servername = "localhost";
$username = "fagprojekt";
$password = "gf3qAdOPH1l9YtSp";
$dbname = "fagprojekt";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($_POST["selectgroup"]) {

$groupID = $_POST["selectgroup"];

$sql = "SELECT ID, name, type FROM `devices` WHERE `groupID` LIKE '".$groupID."'";
$result = $conn->query($sql);

$options;


if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
    		$options[$row["ID"]]["type"] = $row["type"];
    		$options[$row["ID"]]["name"] = $row["name"];
    }
} else {
//    echo "0 results";
}

//var_dump($options);

foreach ($options as $key => $value) {
	if ($value["type"] == "relay") {
		echo "<option value=".$key."current>current ".$value["name"]."</option>";
	}
}

foreach ($options as $key => $value) {
	if ($value["type"] == "relay") {
		echo "<option value=".$key."voltage>voltage ".$value["name"]."</option>";
	}
}

foreach ($options as $key => $value) {
	if ($value["type"] == "relay") {
		echo "<option value=".$key."power>power ".$value["name"]."</option>";
	}
}

foreach ($options as $key => $value) {
	if ($value["type"] == "sensor") {
		echo "<option value=".$key."temp>temp ".$value["name"]."</option>";
	}
}

foreach ($options as $key => $value) {
	if ($value["type"] == "sensor") {
		echo "<option value=".$key."light>light ".$value["name"]."</option>";
	}
}

foreach ($options as $key => $value) {
	if ($value["type"] == "sensor") {
		echo "<option value=".$key."motion>motion ".$value["name"]."</option>";
	}
}

}

elseif ($_POST["selecttype"]) {
	$type = "";
	if ($_POST["selecttype"] == "voltage" || $_POST["selecttype"] == "current" || $_POST["selecttype"] == "power") $type = "relay";
	elseif ($_POST["selecttype"] == "motion" || $_POST["selecttype"] == "light" || $_POST["selecttype"] == "temp") $type = "sensor";
	$sql = "SELECT ID, name, groupID FROM `devices` WHERE `type` LIKE '".$type."'";
$result = $conn->query($sql);

$options;


if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
    		$options[$row["ID"]]["groupID"] = $row["groupID"];
    		$options[$row["ID"]]["name"] = $row["name"];
    }
} else {
//    echo "0 results";
}

$group2name;

$sql = "SELECT ID, name FROM `groups`";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $group2name[$row['ID']] = $row['name'];
    }
}

echo "<option value=0".$_POST["selecttype"].">Sum af gns for hvert minut</option>";

foreach ($options as $key => $value) {
	echo "<option value=".$key.$_POST["selecttype"].">".$group2name[$value["groupID"]]." ".$value["name"]."</option>";
}

}

$conn->close(); 

?>