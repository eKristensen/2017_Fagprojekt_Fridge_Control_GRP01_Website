<?php

$provider = require __DIR__ . '/examples/provider.php';

if (!empty($_SESSION['token'])) {
    $token = unserialize($_SESSION['token']);
}

if (empty($token)) {
    header('Location: https://it.pf.dk/fagprojekt/examples/');
    exit;
}

try {

    // We got an access token, let's now get the user's details
    $userDetails = $provider->getResourceOwner($token);

    // Use these details to create a new profile
    //printf('Hello %s!<br/>', $userDetails->getEmail());

    $Gmail = $userDetails->getEmail();

} catch (Exception $e) {

    // Failed to get user details
    exit('Something went wrong: ' . $e->getMessage());

}

if ($Gmail != "kristensen.emil@gmail.com" && $Gmail != "matlrocks12@gmail.com" && $Gmail != "johnnyye9@gmail.com" && $Gmail != "supersejesebnb1@live.dk") {
	echo "You do not have permission to access this page!";
	exit();
}

?>
<?php

require __DIR__ . '/vendor/autoload.php';

$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

$type = isset($_GET['type']) ? $_GET['type'] : '';
$name = isset($_GET['name']) ? $_GET['name'] : '';
$addr = isset($_GET['addr']) ? $_GET['addr'] : '';
$aktiv = isset($_GET['aktiv']) ? $_GET['aktiv'] : '';
$id = isset($_GET['ID']) ? $_GET['ID'] : '';


//servername, username, password og db name:
include 'datalogin.php'; 

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 


if ($type == "addgate") {
	if (substr($addr, 0,12) == "0015BC001C00" && strlen($addr) == 16) {
		$sql = "SELECT * FROM `gateways` WHERE `device` LIKE '".$addr."'";
		$result = $conn->query($sql);
		if ($result->fetch_assoc()) {
			echo "ERROR! Not new gateway.";
		} 
		else {
			$id = substr(sha1(round(microtime(true) * 1000).$name),-10);
			$sql = "INSERT INTO `gateways` (`ID`, `name`, `device`, `added`, `lastedit`, `cmd`) VALUES (NULL, '".$name."', '".$addr."', CURRENT_TIMESTAMP, NULL, '".$id."');";
			$sql.= "INSERT INTO `cmd` (`ID`, `type`, `cmd`, `result`) VALUES ('".$id."', 'add', '".$addr."', '');";
			if ($conn->multi_query($sql) === TRUE) {
				echo "SUCCESS! Gateway added.";
			} else {
				echo "ERROR! ". $conn->error;
			}
		}
	} else {
		echo "ERROR! Not a gateway.";
	}
} elseif ($type == "newgroup") {
	if (strlen($name) >= 1) {
		$sql = "SELECT * FROM `gateways` WHERE `ID` LIKE '".$addr."'";
		$result = $conn->query($sql);
		$sql2 = "SELECT * FROM `groups` WHERE `name` LIKE '".$name."'";
		$result2 = $conn->query($sql2);
		if (!$result->fetch_assoc()) {
			echo "ERROR! No such gateway.";
		} 
		elseif ($result2->fetch_assoc()) {
			echo "ERROR! Must have uniqe name.";
		}
		else {
			if ($aktiv == "on") $aktiv = "1";
			else $aktiv = "0";
			$sql = "INSERT INTO `groups` (`ID`, `name`, `gateway`, `aktiv`, `incap`) VALUES (NULL, '".$name."', '".$addr."', '".$aktiv."', '1');";
			if ($conn->query($sql) === TRUE) {
				echo "SUCCESS! Group added.";
			} else {
				echo "ERROR! ". $conn->error;
			}
		}
	} else {
		echo "ERROR! Must have a name.";
	}

} elseif ($type == "adddevice") {
	$sql = "SELECT * FROM `groups` WHERE `ID` LIKE '".$name."'";
	$result = $conn->query($sql);
	if (!$result->fetch_assoc()) {
		echo "ERROR! No such group.";
	} else {
    	$your_array = explode("\n", $addr);
    	$data;
    	foreach ($your_array as $key => $value) {
    		$data[] = str_getcsv($value);
    	}
    	$sqladd = "";
    	foreach ($data as $key => $value) {
    		if (empty($value[1]) || empty($value[0])) {
    			echo "ERROR! Invalid devicelist. Address and/or type cannot be empty.";
    			exit();
    		}
    		$value[2] = isset($value[2]) ? $value[2] : ''; //ved tomt navn
    		
    		// $value[2] = device navn
    		// $value[1] = device type
    		// $value[0] = device id / adresse
    		if ((substr($value[0], 0,12) == "0015BC001D02" && strlen($value[0]) == 16 && $value[1] == "relay") || (substr($value[0], 0,12) == "0015BC001A00" && strlen($value[0]) == 16 && $value[1] == "sensor") || (substr($value[0], 0,12) == "0015BC002800" && strlen($value[0]) == 16 && $value[1] == "indicator")) {
    			$sql = "SELECT * FROM `devices` WHERE `device` LIKE '".$value[0]."'";
				$result = $conn->query($sql);
				if ($result->fetch_assoc()) {
					echo "ERROR! You cannot add a device that is in the system.";
					exit();
				}
				else {
					$sqladd.= "INSERT INTO `devices` (`ID`, `name`, `groupID`, `type`, `device`, `added`, `lastedit`) VALUES (NULL, '".$value[2]."', '".$name."', '".$value[1]."', '".$value[0]."', CURRENT_TIMESTAMP, NULL);";
				}
    		} else {
    			echo "ERROR! Invalid devicelist. Types and adresses doesn't fit, or length of addresses is wrong.";
    			exit();
    		}   		
    	}
    	
    	$gateaddr = "";
		$gatesql = "SELECT gateways.device as gateway FROM `groups` JOIN gateways ON groups.gateway=gateways.ID WHERE groups.ID = ".$name;
		$gateresult = $conn->query($gatesql);
 		if ($gateresult->num_rows > 0) {
 			while($row = $gateresult->fetch_assoc()) {
 				$gateaddr = $row["gateway"];
 			}
 		}

		$id = substr(sha1(round(microtime(true) * 1000).$name),-10);
		$sqladd.= "INSERT INTO `cmd` (`ID`, `type`, `cmd`, `result`) VALUES ('".$id."', 'update', '".$gateaddr."', '');";
		$sqladd.= "UPDATE `groups` SET `incap` = '".$id."' WHERE `groups`.`ID` = '".$name."';";
    	if ($conn->multi_query($sqladd) === TRUE) {
			echo "SUCCESS! Devices added.";
		} else {
			echo "ERROR! ". $conn->error;
		}
	}
} elseif ($type == "reload" && false) {
	$sql = "SELECT * FROM `gateways` WHERE `ID` LIKE '".$name."'";
	$result = $conn->query($sql);
	if ($result->fetch_assoc()) {
		$id = substr(sha1(round(microtime(true) * 1000).$name),-10);
		$sql = "UPDATE `cmd` SET `result` = 'cancel' WHERE `cmd`.`cmd` = '".$addr."' AND `result`='';";
		$sql.= "INSERT INTO `cmd` (`ID`, `type`, `cmd`, `result`) VALUES ('".$id."', 'reload', '".$addr."', '');";
		$sql.= "UPDATE `groups` SET `incap` = '".$id."' WHERE `groups`.`gateway` = '".$name."';";
		if ($conn->multi_query($sql) === TRUE) {
			echo "SUCCESS! Command added.";
		} else {
			echo "ERROR! ". $conn->error;
		}
	} else {
		echo "ERROR! Non-existant gateway.";
	}
} elseif ($type == "editgate") {
	$sql = "SELECT * FROM `gateways` WHERE `ID` LIKE '".$id."'";
	$result = $conn->query($sql);
	if ($result->fetch_assoc()) {
		$sql = "UPDATE `gateways` SET `name` = '".$name."' WHERE `gateways`.`ID` = '".$id."';";
		if ($conn->query($sql) === TRUE) {
			echo "SUCCESS! Gateway changed.";
		} else {
			echo "ERROR! ". $conn->error;
		}
	} else {
		echo "ERROR! Non-existant gateway.";
	}
} elseif ($type == "editgroup") {
	$sql = "SELECT * FROM `groups` WHERE `ID` LIKE '".$id."'";
	$result = $conn->query($sql);
	if ($result->fetch_assoc()) {
		if ($aktiv == "on") $aktiv = "1";
		else $aktiv = "0";
		$sql = "UPDATE `groups` SET `name` = '".$name."', `aktiv` = '".$aktiv."' WHERE `groups`.`ID` = '".$id."';";
		if ($conn->query($sql) === TRUE) {
			echo "SUCCESS! Group changed.";
		} else {
			echo "ERROR! ". $conn->error;
		}
	} else {
		echo "ERROR! Non-existant group.";
	}
} elseif ($type == "editdevice") {
	$sql = "SELECT * FROM `groups` WHERE `ID` LIKE '".$id."'";
	$result = $conn->query($sql);
	if ($result->fetch_assoc()) {
		$sql = "";
		foreach($_GET as $key => $value)
			{
				if (preg_replace("/[^a-z]+/", "", $key) == "name") {
					$sql.= "UPDATE `devices` SET `name` = '".$value."' WHERE `devices`.`ID` = '".preg_replace("/[^0-9]+/", "", $key)."';";
				}
			}
		$gateaddr = "";
		$gatesql = "SELECT gateways.device as gateway FROM `groups` JOIN gateways ON groups.gateway=gateways.ID WHERE groups.ID = ".$id;
		$gateresult = $conn->query($gatesql);
 		if ($gateresult->num_rows > 0) {
 			while($row = $gateresult->fetch_assoc()) {
 				$gateaddr = $row["gateway"];
 			}
 		}

		$idd = substr(sha1(round(microtime(true) * 1000).$name),-10);
		$sql.= "INSERT INTO `cmd` (`ID`, `type`, `cmd`, `result`) VALUES ('".$idd."', 'update', '".$gateaddr."', '');";
		$sql.= "UPDATE `groups` SET `incap` = '".$idd."' WHERE `groups`.`ID` = '".$id."';";
		if ($conn->multi_query($sql) === TRUE) {
			echo "SUCCESS! Devices changed.";
		} else {
			echo "ERROR! ". $conn->error;
		}
	} else {
		echo "ERROR! Non-existant group.";
	}
} elseif ($type == "deletedevice") {
	$sql = "SELECT * FROM `groups` WHERE `ID` LIKE '".$name."'";
	$result = $conn->query($sql);
	if ($result->fetch_assoc()) {
		$sql = "DELETE FROM `devices` WHERE `devices`.`device` = '".$addr."';"; // evt dobbelt tjek med device id: `devices`.`ID` = ".$IDPÅDEVICE." AND 
		$sql.= "INSERT INTO `log` (`ID`, `time`, `event`) VALUES (NULL, CURRENT_TIMESTAMP, 'Deleted ".$addr." with name ".$name." from IP ".$_SERVER['REMOTE_ADDR']."');";

		$gateaddr = "";
		$gatesql = "SELECT gateways.device as gateway FROM `groups` JOIN gateways ON groups.gateway=gateways.ID WHERE groups.ID = ".$name;
		$gateresult = $conn->query($gatesql);
 		if ($gateresult->num_rows > 0) {
 			while($row = $gateresult->fetch_assoc()) {
 				$gateaddr = $row["gateway"];
 			}
 		}

		$idd = substr(sha1(round(microtime(true) * 1000).$name),-10);
		$sql.= "INSERT INTO `cmd` (`ID`, `type`, `cmd`, `result`) VALUES ('".$idd."', 'update', '".$gateaddr."', '');";
		$sql.= "UPDATE `groups` SET `incap` = '".$idd."' WHERE `groups`.`ID` = '".$name."';";
		
		if ($conn->multi_query($sql) === TRUE) {
			echo "SUCCESS! Device removed from database.";
		} else {
			echo "ERROR! ". $conn->error;
		}
	} else {
		echo "ERROR! Non-existant group.";
	}
} elseif ($type == "cleanup") {
	$sql = "SELECT groups.ID as id, groups.name as name FROM `groups` LEFT JOIN devices ON devices.groupID=groups.ID WHERE devices.ID IS NULL;"; //groups with no devices
	$result = $conn->query($sql);
	$sqldel = "";
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			$sqldel = "DELETE FROM `groups` WHERE `groups`.`ID` = '".$row["id"]."';"; // evt dobbelt tjek med device id: `devices`.`ID` = ".$IDPÅDEVICE." AND 
			$sqldel.= "INSERT INTO `log` (`ID`, `time`, `event`) VALUES (NULL, CURRENT_TIMESTAMP, 'Deleted group id ".$row["id"]." with name ".$row["name"]." from IP ".$_SERVER['REMOTE_ADDR']."');";
		}
	}
	if ($sqldel != "") { //Delete them if any was found.
		if ($conn->multi_query($sqldel) === TRUE) {
			echo "SUCCESS! Groups with no devices were removed.<br>";
		} else {
			echo "ERROR! ". $conn->error;
			exit();
		}
	}
	$sql = "SELECT gateways.ID as id, gateways.name as name, gateways.device FROM `gateways` LEFT JOIN groups ON groups.gateway=gateways.ID WHERE groups.ID IS NULL;"; //groups with no devices
	$result = $conn->query($sql);
	$sqldel = "";
	if ($result->num_rows > 0) {
		while($row = $result->fetch_assoc()) {
			$id = substr(sha1(round(microtime(true) * 1000).$row["id"].$row["name"]),-10);
			$sqldel = "DELETE FROM `gateways` WHERE `gateways`.`ID` = '".$row["id"]."';"; // evt dobbelt tjek med device id: `devices`.`ID` = ".$IDPÅDEVICE." AND 
			$sqldel.= "INSERT INTO `log` (`ID`, `time`, `event`) VALUES (NULL, CURRENT_TIMESTAMP, 'Deleted gateway with id ".$row["id"].", name ".$row["name"]." and address ".$row["device"]." from IP ".$_SERVER['REMOTE_ADDR'].". Request with id ".$id." to remove gateway from message broker created.');";
			$sqldel.= "INSERT INTO `cmd` (`ID`, `type`, `cmd`, `result`) VALUES ('".$id."', 'delete', '".$row["device"]."', '');";
		}
	}
	if ($sqldel != "") { //Delete them if any was found.
		if ($conn->multi_query($sqldel) === TRUE) {
			echo "SUCCESS! Gateways with no groups were removed.<br>";
		} else {
			echo "ERROR! ". $conn->error;
			exit();
		}
	}
	echo "Done. If no messages above, then no rogue gateways or groups were found and nothing was changed.";
} else {
	echo "ERROR! Invalid input.";
} 

echo "<br><a href=https://it.pf.dk/fagprojekt/admin.php>Back</a>";
echo "<br><br>Remeber any change might require gateway update on message broker.";

$conn->close();

?>