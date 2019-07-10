<?php

require __DIR__ . '/vendor/autoload.php';
$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

$series = isset($_COOKIE['FC_Data_Plot_DEIF_series']) ? json_decode($_COOKIE['FC_Data_Plot_DEIF_series']) : [];    // Her hvad hver dataserie gør.
$maaling = isset($_COOKIE['FC_Data_Plot_DEIF_maaling']) ? json_decode($_COOKIE['FC_Data_Plot_DEIF_maaling']) : []; // Her hvilke data der plottes.
$chosen = isset($_COOKIE['FC_Data_Plot_DEIF_chosen']) ? json_decode($_COOKIE['FC_Data_Plot_DEIF_chosen']) : [];    // Her om måling er med i hver serie
$toggle = isset($_COOKIE['FC_Data_Plot_DEIF_toggle']) ? json_decode($_COOKIE['FC_Data_Plot_DEIF_toggle']) : 0;    // om det 24 timer eller alle data der hentes.
$legend = isset($_COOKIE['FC_Data_Plot_DEIF_legend']) ? json_decode($_COOKIE['FC_Data_Plot_DEIF_legend']) : 1;    // om det 24 timer eller alle data der hentes.

if (isset($_GET["reset"])) {
	setcookie('FC_Data_Plot_DEIF_series', '', time()-3600);
	setcookie('FC_Data_Plot_DEIF_maaling', '', time()-3600);
	setcookie('FC_Data_Plot_DEIF_chosen', '', time()-3600);
	setcookie('FC_Data_Plot_DEIF_toggle', '', time()-3600);
	setcookie('FC_Data_Plot_DEIF_legend', '', time()-3600);
	exit();
}

if ($series == []) {
	$series[] = array(0,"","");
	// 0/1/2/3: Måling/Signal/Gns/Sum
}

if (isset($_POST["selectgroup"])) {
	foreach ($_POST['selectdevices'] as $value) {
	$valarr = explode(",",$value);
	$new = array($valarr[0],$valarr[1]);
	if (!in_array($new, $maaling)) {
		$maaling[] = $new;
		$fill = [];
		foreach ($series as $key => $value) {
			if ($value[0] == 0) $fill[] = 1;
			else $fill[] = 0;
		}
		$chosen[] = $fill;
	}
	}
} elseif (isset($_POST["selecttype"])) {
	foreach ($_POST['selectmes'] as $value) {	
	$valarr = explode(",",$value);
	$new = array($valarr[0],$valarr[1]);
	if (!in_array($new, $maaling)) {
		$maaling[] = $new;
		$fill = [];
		foreach ($series as $key => $value) {
			if ($value[0] == 0) $fill[] = 1;
			else $fill[] = 0;
		}
		$chosen[] = $fill;
	}
	}
} elseif (isset($_POST["addmes"])) {
	$n = 0;
	if ($_POST["addmes"] == "0") {
		foreach ($series as $key => $value) {
			if ($series[$key][0] == "0") {
				$n++;
				break;
			}
		}
	}
	if ($_POST["addmes"] == "1") {
		foreach ($series as $key => $value) {
			if ($series[$key][0] == "1") {
				$n++;
				break;
			}
		}
	}
	if ($n == 0) {
		$name = isset($_POST['name']) ? $_POST['name'] : "";
		$series[] = array($_POST["addmes"],"",$name);
		foreach ($chosen as $key => $value) {
			if ($_POST["addmes"] == "0") $chosen[$key][] = 1;
			else $chosen[$key][] = 0;
		}
	}
} elseif (isset($_GET["remove"])) {
	if (isset($_GET["what"])) {
		if ($_GET["what"] == "maaling") {
			unset($maaling[$_GET["remove"]]);
			unset($chosen[$_GET["remove"]]);
			$maaling = array_values($maaling);
			$chosen = array_values($chosen);
		}
		elseif ($_GET["what"] = "serie" && $_GET["remove"] != 0) {
			unset($series[$_GET["remove"]]);
			foreach ($chosen as $key => $value) {
				unset($chosen[$key][$_GET["remove"]]);
				$chosen[$key] = array_values($chosen[$key]);
			}
			$series = array_values($series);
			$chosen = array_values($chosen);
		}
	}
	
} elseif (isset($_GET["key"]) && isset($_GET["keyy"])) {
	$key = $_GET["key"]; //målingsnummer
	$keyy= $_GET["keyy"];	//serie nummer
	if ($key == "legend" && $keyy == "") {
		if ($legend == 1) $legend = 0;
		else $legend = 1;
	}
	elseif ($key == "amount" && $keyy == "") {
		if ($toggle == 1) $toggle = 0;
		else $toggle = 1;
	}
	elseif ($chosen[$key][$keyy] == 1) {
		$chosen[$key][$keyy] = 0;
		$n=0;
		foreach ($chosen as $do => $value) {
			if ($chosen[$do][$keyy] == 1) $n++;
		}
		if ($n==0) $series[$keyy][1] = "";
	} 
	else {
		$chosen[$key][$keyy] = 1;
		if ($series[$keyy][1] == "") {
			foreach ($chosen as $keyyy => $value) {
				if ($maaling[$key][1] == $maaling[$keyyy][1]) $chosen[$keyyy][$keyy] = 1;
			}
		}
		$series[$keyy][1] = $maaling[$key][1];
	} 
}

include 'datalogin.php'; 
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
$sql = "SELECT devices.ID as id, devices.name as devicename, groups.name as groupname FROM `devices` JOIN groups ON groups.ID=devices.groupID";
$result = $conn->query($sql);
$names = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $names[$row["id"]] = array($row["devicename"],$row["groupname"]);
    }
}
$conn->close();

echo "How much data do you want to see? <input type=checkbox data-toggle=toggle data-on=\"24 hours\" data-off=\"All data\" data-onstyle=\"success\" data-offstyle=\"primary\" onchange=\"toggle('amount','');\"";
if ($toggle == 1) echo " checked";
echo ">"; 

echo "<br>";

echo "Do you want a legend? <input type=checkbox data-toggle=toggle data-on=\"Yes\" data-off=\"No\" data-onstyle=\"success\" data-offstyle=\"primary\" onchange=\"toggle('legend','');\"";
if ($legend == 1) echo " checked";
echo ">"; 

echo "<table>";
echo "<tr>";
echo "<td></td>";
foreach ($series as $key => $value) {
	echo "<td>";
	if ($value[0] == 0) echo "Measurement";
	elseif ($value[0] == 1) echo "Signal";
	elseif ($value[0] == 2) echo "Average";
	elseif ($value[0] == 3) echo "Sum";
	if ($value[2] != "") echo "<br>Series name:<br>".$value[2];
	//echo "Type:<br>".$value[1];
	echo "</td>";
}
echo "</tr>";

$n=0;
foreach ($maaling as $key => $value) {
	echo "<tr>";
	echo "<td>";
	echo utf8_encode($maaling[$key][0]) . " ";
	echo $maaling[$key][1] . " ";
//	echo utf8_encode($names[$maaling[$key][0]][0]);
	echo "</td>";
	foreach ($series as $keyy => $value) {
		echo "<td>";
		echo "<input type=\"checkbox\" onchange=\"toggle('".$key."','".$keyy."');\" data-toggle=\"toggle\"";
		if ($chosen[$key][$keyy] == 1) {
			echo " checked";
			$n++;
		} 
		if ($series[$keyy][1] != $maaling[$key][1] && $series[$keyy][0] != 0 && $series[$keyy][0] != 0 && $series[$keyy][1] != "") echo " disabled";
		echo ">";
		echo "</td>";
	}
	echo "<td>";
	echo "<button type=\"button\" class=\"btn btn-danger\" onclick=\"remove('maaling','".$key."');\">Remove</button>";
	echo "</td>";
	echo "</tr>";
}
echo "<tr>";
echo "<td></td>";
foreach ($series as $key => $value) {
	echo "<td>";
	if ($value[0] != 0) echo "<button type=\"button\" class=\"btn btn-danger\" onclick=\"remove('series','".$key."');\">Remove</button>";
	echo "</td>";
}
echo "<td><button type=\"button\" class=\"btn btn-success\" onclick=\"getplot();\"";
if ($n == 0) echo " disabled";
echo ">Get plot</button></td>";
echo "</tr>";
echo "</table>";

//echo "<input type=\"checkbox\" checked data-toggle=\"toggle\">";


/*
//Debugging, se arrays...
echo "<br><br><br><br>series<br>";
print_r($series);
echo "<br>Size of ".sizeof($series);

echo "<br><br>Måling<br>";
print_r($maaling);

echo "<br><br>Chosen<br>";
print_r($chosen);

echo "jsonencode:".json_encode($chosen)."<br>";/*
echo "serialize:".serialize($chosen)."<br>";

echo "jsondecode:";
dump(json_decode(json_encode($chosen)));*/

setcookie('FC_Data_Plot_DEIF_series', json_encode($series), time()+ (10 * 365 * 24 * 60 * 60));
setcookie('FC_Data_Plot_DEIF_maaling', json_encode($maaling), time()+ (10 * 365 * 24 * 60 * 60));
setcookie('FC_Data_Plot_DEIF_chosen', json_encode($chosen), time()+ (10 * 365 * 24 * 60 * 60));
setcookie('FC_Data_Plot_DEIF_toggle', $toggle, time()+ (10 * 365 * 24 * 60 * 60));
setcookie('FC_Data_Plot_DEIF_legend', $legend, time()+ (10 * 365 * 24 * 60 * 60));

?>
