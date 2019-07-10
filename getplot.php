<?php

require __DIR__ . '/vendor/autoload.php';
$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

header('Content-Type: application/json;charset=UTF-8');

$series = isset($_COOKIE['FC_Data_Plot_series']) ? json_decode($_COOKIE['FC_Data_Plot_series']) : [];    // Her hvad hver dataserie gør.
$maaling = isset($_COOKIE['FC_Data_Plot_maaling']) ? json_decode($_COOKIE['FC_Data_Plot_maaling']) : []; // Her hvilke data der plottes.
$chosen = isset($_COOKIE['FC_Data_Plot_chosen']) ? json_decode($_COOKIE['FC_Data_Plot_chosen']) : [];    // Her om måling er med i hver serie
$legend = isset($_COOKIE['FC_Data_Plot_legend']) ? json_decode($_COOKIE['FC_Data_Plot_legend']) : [];    // Her om måling er med i hver serie

$akser = [];
/*
echo "series:";
var_dump($series);

echo "maaling:";
var_dump($maaling);*/

foreach ($series as $key => $value) {
	if ($series[$key][0] == "0") {
		//echo "Engang i maa/sig ".$key."\n";
		foreach ($maaling as $keyy => $value) {
			if (!in_array(array($series[$key][0],$maaling[$keyy][1]), $akser) && $chosen[$keyy][$key] == 1) {
				$akser[] = array($series[$key][0],$maaling[$keyy][1]);
			}
		}
	} elseif ($series[$key][0] == "1") {
		foreach ($maaling as $keyy => $value) {
			if (!in_array(array($series[$key][0],"signal"), $akser) && $chosen[$keyy][$key] == 1) {
				$akser[] = array($series[$key][0],"signal");
			}
		}		
	} elseif ($series[$key][1] != "") {
		//echo "engang i alternativ ".$key."\n";
		$akser[] = array($series[$key][0],$series[$key][1]); //sæt maaling/signal/avg/sum , type på akser.
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
	getAxis($key,$akser[$key][1],$extra);
	//echo "\n\n";
}
}
echo "],series:seriesOptions}";

echo ",";

// urlencode(serialize($arr)); //brug ved seriesid er 2 eller 3

//servername, username, password og db name:
include 'datalogin.php'; 

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 
$sql = "SELECT devices.ID as id, devices.name as devicename, groups.name as groupname, devices.type as type, devices.device as addr FROM `devices` JOIN groups ON groups.ID=devices.groupID";
$result = $conn->query($sql);
$names = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $names[$row["id"]] = array($row["devicename"],$row["groupname"],$row["type"],$row["addr"]);
    }
}
$sql = "SELECT ID,topic FROM `topics`";
$result = $conn->query($sql);
$topics = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $topics[$row["topic"]] = $row["ID"];
    }
}
$conn->close();
echo "[";
//echo "\n\n\n";

$started = false;
foreach ($akser as $key => $value) {
	$devices = [];
	$devicesaddr = [];
	$note = "";
	foreach ($chosen as $x => $value) {
		foreach ($chosen[$x] as $y => $value) {
			//echo "\nDEBUG: akserkey: ".$akser[$key][1]. " målingkey: ".$maaling[$x][1];

			if ($chosen[$x][$y] == 1 && $akser[$key][0] == $series[$y][0] && ($akser[$key][1] == $maaling[$x][1] || $akser[$key][0] == 1)) {
				if ($akser[$key][0] == 2 || $akser[$key][0] == 3) {
					$devices[] = $maaling[$x][0];
					$devicesaddr[] = $names[$maaling[$x][0]][3];
					$note = $series[$y][2];
				} else {
					if ($started) {
						echo ",";
					} else {
						$started = true;
					}
					$devices[0] = $maaling[$x][0];
					$devicesaddr[0] = $names[$maaling[$x][0]][3];
					echo "[".$akser[$key][0].",".json_encode(($devices)).",".$topics[$akser[$key][1]].",";

					if ($akser[$key][0] == 1) echo array_search(array(1,"signal"),$akser);
					else echo $key;

					echo ",";
					echo "'".utf8_encode($names[$devices[0]][1] . " " . $akser[$key][1] . " ");
					if ($akser[$key][0] == 0) echo utf8_encode($names[$devices[0]][0]);
					elseif ($akser[$key][0] == 1) echo utf8_encode($names[$devices[0]][0]);
					echo "'";
					echo ",'";
					if ($akser[$key][0] == 1) echo "dBm";
					elseif ($akser[$key][1] == "current") echo "A";
					elseif ($akser[$key][1] == "light") echo ""; //ukendt enhed
					elseif ($akser[$key][1] == "motion") echo ""; //ukendt enhed
					elseif ($akser[$key][1] == "power") echo "Watt";
					elseif ($akser[$key][1] == "relay") echo ""; //ukendt enhed
					elseif ($akser[$key][1] == "temp") echo utf8_encode("°C");
					elseif ($akser[$key][1] == "voltage") echo "V";
					echo "',";
					echo json_encode($devicesaddr);
					echo ",";
					echo "'".$akser[$key][1]."'";
					echo "]";
					//echo "\n\n\n";
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
		echo "[".$akser[$key][0].",".json_encode(($devices)).",".$topics[$akser[$key][1]].",";

		if ($akser[$key][0] == 3) echo array_search($akser[$key][1], $SUM);
		else echo array_search($akser[$key][1], $mesAVG);
		

		echo ",'";
		if ($akser[$key][0] == 1) echo "Signal";
		elseif ($akser[$key][0] == 2) echo "AVG";
		elseif ($akser[$key][0] == 3) echo "SUM";
		echo " ".$note;
		echo " ".$akser[$key][1];
		echo "','";
		if ($akser[$key][1] == "current") echo "A";
		elseif ($akser[$key][1] == "light") echo ""; //ukendt enhed
		elseif ($akser[$key][1] == "motion") echo ""; //ukendt enhed
		elseif ($akser[$key][1] == "power") echo "Watt";
		elseif ($akser[$key][1] == "relay") echo ""; //ukendt enhed
		elseif ($akser[$key][1] == "temp") echo utf8_encode("°C");
		elseif ($akser[$key][1] == "voltage") echo "V";
		echo "',";
		echo json_encode($devicesaddr);
		echo ",";
		echo "'".$akser[$key][1]."'";
		echo "]";
		//echo "\n\n\n";
	}
}
echo "]";


/*
echo utf8_encode("
	['1','7','C. Venstre, power ',1,'Watt'],
	['3','7','C: Øl, power ',1,'Watt'],
	['6','7','O: Venstre, power ',1,'Watt'],
	['9','7','O: Øl, power ',1,'Watt'],
	['12','7','O: Højre, power ',1,'Watt'],
	['15','7','C: Højre, power ',1,'Watt'],
	['19','7','Martin, power ',1,'Watt'],
	['22','7','Johnnys ven, power ',1,'Watt'],
	['25','7','Johnnys resturant drikkevareskab, power ',1,'Watt'],
	['28','7','Johnnys ½bror, power ',1,'Watt']]");*/

echo "])";

function getAxis($number,$kind,$extra) {
	echo "{labels:{format:'{value} ";
	echo getUnit($kind);
	echo "',style:{color:Highcharts.getOptions().colors[";
	echo $number;
	echo "]}},title:{text:'";
	if ($extra) echo $extra." ";
	if ($kind == "current") echo "Current";
	elseif ($kind == "light") echo "Light";
	elseif ($kind == "motion") echo "Motion";
	elseif ($kind == "power") echo "Power";
	elseif ($kind == "relay") echo "Relay";
	elseif ($kind == "temp") echo "Temperature";
	elseif ($kind == "voltage") echo "Voltage";
	elseif ($kind == "signal") echo "Signal";
	echo "',style:{color:Highcharts.getOptions().colors[";
	echo $number;
	echo "]}}}";
}

function getUnit($kind) {
	if ($kind == "current") return "A";
	elseif ($kind == "light") return ""; //ukendt enhed
	elseif ($kind == "motion") return ""; //ukendt enhed
	elseif ($kind == "power") return "Watt";
	elseif ($kind == "relay") return ""; //ukendt enhed
	elseif ($kind == "temp") return utf8_encode("°C");
	elseif ($kind == "voltage") return "V";
	elseif ($kind == "signal") return "dBm";
}


//y akser
/*
echo "yAxis:[";
echo "{labels:{format:'{value}C',style:{color:Highcharts.getOptions().colors[0]}},title:{text:'Temperature',style:{color:Highcharts.getOptions().colors[0]}},opposite:true},";
echo "{labels:{format:'{value}Watt',style:{color:Highcharts.getOptions().colors[1]}},title:{text:'Effekt',style:{color:Highcharts.getOptions().colors[1]}}},";
echo "{labels:{format:'{value}V',style:{color:Highcharts.getOptions().colors[2]}},title:{text:'Voltage',style:{color:Highcharts.getOptions().colors[2]}}},";
echo "{labels:{format:'{value}A',style:{color:Highcharts.getOptions().colors[3]}},title:{text:'Current',style:{color:Highcharts.getOptions().colors[3]}}},";
echo "{labels:{format:'{value}',style:{color:Highcharts.getOptions().colors[4]}},title:{text:'Light',style:{color:Highcharts.getOptions().colors[4]}}},";
echo "{labels:{format:'{value}',style:{color:Highcharts.getOptions().colors[5]}},title:{text:'Motion',style:{color:Highcharts.getOptions().colors[5]}}},";
echo "{labels:{format:'{value}',style:{color:Highcharts.getOptions().colors[6]}},title:{text:'Signal',style:{color:Highcharts.getOptions().colors[6]}}}";
echo "],series:seriesOptions}";
echo "])";
*/



/*
echo "\n";

$names = [];
foreach ($chosen as $key => $value) {
	foreach ($chosen[$key] as $keyy => $value) {
		if ($chosen[$key][$keyy] == 1) {
			//echo "\n".$key." ".$keyy." ".$chosen[$key][$keyy]."\n";
			echo "[".$series[$keyy][0].",".$maaling[$key][0].",".$maaling[$key][1].",".array_search(array($series[$keyy][0],$maaling[$key][1]),$akser).",SURFIX]\n";
			$names[] = array($series[$keyy][0],$maaling[$key][0],$maaling[$key][1],array_search(array($series[$keyy][0],$maaling[$key][1]),$akser));
		}
	}
}

var_dump($names);*/
?>