<?php

require __DIR__ . '/vendor/autoload.php';
$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

header('Content-Type: application/json;charset=UTF-8');

$series = isset($_COOKIE['FC_Data_Plot_DEIF_series']) ? json_decode($_COOKIE['FC_Data_Plot_DEIF_series']) : [];    // Her hvad hver dataserie gør.
$maaling = isset($_COOKIE['FC_Data_Plot_DEIF_maaling']) ? json_decode($_COOKIE['FC_Data_Plot_DEIF_maaling']) : []; // Her hvilke data der plottes.
$chosen = isset($_COOKIE['FC_Data_Plot_DEIF_chosen']) ? json_decode($_COOKIE['FC_Data_Plot_DEIF_chosen']) : [];    // Her om måling er med i hver serie
$legend = isset($_COOKIE['FC_Data_Plot_DEIF_legend']) ? json_decode($_COOKIE['FC_Data_Plot_DEIF_legend']) : 1;    // om det 24 timer eller alle data der hentes.

$akser = [];
$tID = [];

foreach ($series as $key => $value) {
	if ($series[$key][0] == "0") {
		//echo "Engang i maa/sig ".$key."\n";
		foreach ($maaling as $keyy => $value) {
			if (!in_array(array($series[$key][0],getUnit($maaling[$keyy][1])), $akser) && $chosen[$keyy][$key] == 1) {
				$akser[] = array($series[$key][0],getUnit($maaling[$keyy][1]),$maaling[$keyy][1]);
				$tID[] = $maaling[$keyy][1];
			}
		}
	} elseif ($series[$key][1] != "") {
		//echo "engang i alternativ ".$key."\n";
		$akser[] = array($series[$key][0],getUnit($maaling[$keyy][1]),$series[$key][1]); //sæt maaling/signal/avg/sum , type på akser.
		$tID[] = $maaling[$keyy][1];
	}
	//echo "\n Loop en gang key ".$key."\n\n";
}

//var_dump($akser);

$callback = isset($_GET['callback']) ? $_GET['callback'] : '';

echo $callback;

//x akse indstillinger
echo "([";
echo "{xAxis:{ordinal:false},";
if ($legend == 1) {
echo "legend:{";
echo "enabled:true,";
echo "align:'right',";
echo "backgroundColor:'#FCFFC5',";
echo "borderColor:'black',";
echo "borderWidth:2,";
echo "layout:'vertical',";
echo "verticalAlign:'top',";
echo "y:100,";
echo "shadow:true";
echo "},"; }

echo "chart:{";
echo "events:{";
echo "load:function(){";
echo "var series=this.series;";
echo "startLiveUpdate(series);";
//echo "timer=setInterval(function(){updatePlot(series)},10000);";
echo "}";
echo "}";
echo "},";
echo "rangeSelector:{buttons:[";
echo "{count:10,type:'minute',text:'10M'},";
echo "{count:30,type:'minute',text:'30M'},";
echo "{count:1,type:'hour',text:'1H'},";
echo "{count:6,type:'hour',text:'6H'},";
echo "{count:12,type:'hour',text:'12H'},";
echo "{count:1,type:'day',text:'1D'},";
echo "{count:3,type:'day',text:'3D'},";
echo "{count:5,type:'day',text:'5D'},";
echo "{count:1,type:'week',text:'1W'},";
echo "{type:'all',text:'All'}],";
echo "inputEnabled:true,selected:3},";

//Axis that excist
// List of units, the index is equal to axis id.
$mesAVG = [];
$SUM = [];

//y akser
echo "yAxis:[";
$started = false;
$axisnumber = 0;
foreach ($akser as $key => $value) {
	if (!in_array($akser[$key][1], $mesAVG) && $akser[$key][0] <= 2 || !in_array($akser[$key][1], $SUM) && $akser[$key][0] == 3) {
	//echo "\n\n";
	if ($started) {
		echo ",";
	} else {
		$started = true;
	}
	if ($akser[$key][0] <= 2) $mesAVG[$axisnumber] = $akser[$key][1];
	else $SUM[$axisnumber] = $akser[$key][1];
	$axisnumber++;
	$extra = null;
	if ($akser[$key][0] == 2) $extra = "Average";
	elseif ($akser[$key][0] == 3) $extra = "Sum";
	//getAxis($key,$akser[$key][1],$extra); //Colorfull acxis
	getAxis(1,$akser[$key][1],$extra); //All black
	//echo "\n\n"; 
}
}
echo "],series:seriesOptions}";

echo ",";

//servername, username, password og db name:
include 'datalogin.php'; 

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
$sql = "SELECT devices.ID as id, devices.name as devicename, groups.name as groupname, devices.type as type FROM `devices` JOIN groups ON groups.ID=devices.groupID";
$result = $conn->query($sql);
$names = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $names[$row["id"]] = array($row["devicename"],$row["groupname"],$row["type"]);
    }
}
$sql = "SELECT ID,deifid FROM `deif-topics`;";
$result = $conn->query($sql);
$topics = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $topics[$row["deifid"]] = $row["ID"];
    }
}
$conn->close();

//var_dump($topics);

echo "[";
//echo "\n\n\n";

$started = false;
$mesadded = [];
foreach ($akser as $key => $value) {
	$devices = [];
	$note = "";
	foreach ($chosen as $x => $value) {
		foreach ($chosen[$x] as $y => $value) {
			//echo "\nDEBUG: akserkey: ".$akser[$key][2]. " målingkey: ".$maaling[$x][1];

			if ($chosen[$x][$y] == 1 && $akser[$key][0] == $series[$y][0] && $akser[$key][2] == $maaling[$x][1]) {
				if ($akser[$key][0] == 2 || $akser[$key][0] == 3) {
					$devices[] = $maaling[$x][0];
					$note = $series[$y][2];
				} else {
					if (!in_array($akser[$key][0].$maaling[$x][0], $mesadded)) {

						$mesadded[] = $akser[$key][0].$maaling[$x][0];
						if ($started) {
							echo ",";
						} else {
							$started = true;
						}
						echo "[".$akser[$key][0].",".json_encode($maaling[$x][0]).",'".$maaling[$x][1]."',";
						echo array_search($akser[$key][1], $mesAVG);
						echo ",";
						echo "'".utf8_encode($maaling[$x][0] . " " . $maaling[$x][1] . " ");
						echo "'";
						echo ",'";
						echo $akser[$key][1];
						echo "']";
						//echo "\n\n\n";
					}
				}
			}
		}
	}
	if ($akser[$key][0] == 2 || $akser[$key][0] == 3) {
		if ($started) {
			echo ",";
		} else {
			$started = true;
		}
		echo "[".$akser[$key][0].",".json_encode(($devices)).",'".$akser[$key][2]."',";

		if ($akser[$key][0] == 3) echo array_search($akser[$key][1], $SUM);
		else echo array_search($akser[$key][1], $mesAVG);
		echo ",'";
		if ($akser[$key][0] == 2) echo "AVG";
		elseif ($akser[$key][0] == 3) echo "SUM";
		echo " ".$note;
		echo " ".$akser[$key][2];
		echo "','";
		echo $akser[$key][1];
		echo "']";
		//echo "\n\n\n";
	}
}
echo "]";


echo "])";

function getAxis($number,$kind,$extra) {
	echo "{labels:{format:'{value} ";
	echo $kind;
	echo "',style:{color:Highcharts.getOptions().colors[";
	echo $number;
	echo "]}},title:{text:'";
	if ($extra) echo $extra." ";
	echo $kind;
	echo "',style:{color:Highcharts.getOptions().colors[";
	echo $number;
	echo "]}}}";
}

function getUnit($kind) {
	if (preg_match('/active_power/',$kind)) return "kW";
	elseif (preg_match('/apparent_power/',$kind)) return "kVA";
	elseif (preg_match('/current/',$kind)) return "A";
	elseif (preg_match('/frequency/',$kind)) return "Hz";
	elseif (preg_match('/reactive_power/',$kind)) return "kVAr";
	elseif (preg_match('/voltage/',$kind)) return "V";
}


?>