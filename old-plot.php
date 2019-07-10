<?php  

require __DIR__ . '/vendor/autoload.php';

$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

//foreach ($_GET as $key => $value) echo "Field ".htmlspecialchars($key)." is ".htmlspecialchars($value)."<br>";

$_GET['selectgroup'] = isset($_GET['selectgroup']) ? $_GET['selectgroup'] : '';
$_GET['selectdevices'] = isset($_GET['selectdevices']) ? $_GET['selectdevices'] : '';
$_GET['selecttype'] = isset($_GET['selecttype']) ? $_GET['selecttype'] : '';
$_GET['selectmes'] = isset($_GET['selectmes']) ? $_GET['selectmes'] : '';
$_GET['remove'] = isset($_GET['remove']) ? $_GET['remove'] : '';
$_GET['plot'] = isset($_GET['plot']) ? $_GET['plot'] : '';
$_GET['type'] = isset($_GET['type']) ? $_GET['type'] : '';
$_GET['data'] = isset($_GET['data']) ? $_GET['data'] : '';

if ($_GET["selectgroup"] && $_GET["selectdevices"]) {

$strenc2= $_GET['data'];
$arr = unserialize(urldecode($strenc2));
//var_dump($arr);

foreach ($_GET['selectdevices'] as $value) {
    //echo $value."\n";
    $arr[] = array(preg_replace("/[^0-9]+/", "", $value),preg_replace("/[^a-z]+/", "", $value));
}


$str = serialize($arr);
$strenc = urlencode($str);
//print $str . "\n";
//print $strenc . "\n";
header("Location: https://it.pf.dk/fagprojekt/old-plot.php?type=".$_GET["type"]."&data=".$strenc);
}

if ($_GET["selecttype"] && $_GET["selectmes"]) {
$strenc2= $_GET['data'];
$arr = unserialize(urldecode($strenc2));
//var_dump($arr);

foreach ($_GET['selectmes'] as $value) {
    //echo $value."\n";
    $arr[] = array(preg_replace("/[^0-9]+/", "", $value),preg_replace("/[^a-z]+/", "", $value));
}


$str = serialize($arr);
$strenc = urlencode($str);
//print $str . "\n";
//print $strenc . "\n";
header("Location: https://it.pf.dk/fagprojekt/old-plot.php?type=".$_GET["type"]."&data=".$strenc);

    }


$strenc2= $_GET['data'];
$arr = unserialize(urldecode($strenc2));
//var_dump($arr);

if ($_GET['remove']) {
  //  echo "testing";
    foreach ($_GET['remove'] as $value) {
     //   echo $value."\n";
        unset($arr[$value]); }

header("Location: https://it.pf.dk/fagprojekt/old-plot.php?type=".$_GET["type"]."&data=".urlencode(serialize($arr)));
}


header('Content-Type: text/html; charset=ISO-8859-1');

//servername, username, password og db name:
include 'datalogin.php'; 

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

$fridgegroups;
$device2group;

$sql = "SELECT ID, name FROM `groups`";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $fridgegroups[$row['ID']] = $row['name'];
    }
}

$sql = "SELECT ID, groupID, name FROM `devices`";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $device2group[$row['ID']]["ID"] = $row['groupID'];
        $device2group[$row['ID']]["name"] = $row['name'];
    }
}

$device2group['0']["name"] = "sammenlagt"; 

?>

<html>
<head>	
<?php //<script src="https://code.jquery.com/jquery-3.1.1.min.js"></script> 
?>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="pace/pace.min.js"></script>
  <link href="pace/pace.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<?php 
if ($_GET["plot"] == "stacked") echo "<script src=\"https://code.highcharts.com/highcharts.js\"></script>";
else echo "<script src=\"https://code.highcharts.com/stock/highstock.js\"></script>";
?>
<script src="https://code.highcharts.com/stock/modules/exporting.js"></script>
  <script>

Highcharts.setOptions({
    global: {
        timezoneOffset: -2 * 60
    }
});

  $(document).ready(function() {

          var last_valid_selection = null;

          $('#selectgroup').change(function(event) {

            var myForm = $(this);
            var data = $(this).serializeArray();//serialize form inputs and pass them to php
            $.post("selectdevice.php",data,function(data){
                $('#selectdevices').html(data);
            });

            if ($(this).val().length > 1) {

              $(this).val(last_valid_selection);
            } else {
              last_valid_selection = $(this).val();
            }
          });

          $('#selecttype').change(function(event) {

            var myForm = $(this);
            var data = $(this).serializeArray();//serialize form inputs and pass them to php
            $.post("selectdevice.php",data,function(data){
                $('#selectmes').html(data);
            });

            if ($(this).val().length > 1) {

              $(this).val(last_valid_selection);
            } else {
              last_valid_selection = $(this).val();
            }
          });
        });
var now = Date.now();
var initiallength = 0;
var seriesOptions = [],
    seriesCounter = 0,
    names = [<?php
    if ($arr != "") {
    $first = true;
    foreach ($arr as $value) {
      if (!$first) echo ",";
      if ($value[1] == "temp") { $yax="0"; $enhed = "°C"; $topicid = 6; }
      if ($value[1] == "power") { $yax="1"; $enhed = "Watt"; $topicid = 7; }
      if ($value[1] == "voltage") { $yax="2"; $enhed = "V"; $topicid = 1; }
      if ($value[1] == "current") { $yax="3"; $enhed = "A"; $topicid = 2; }
      if ($value[1] == "light") { $yax="4"; $enhed = ""; $topicid = 4; }
      if ($value[1] == "motion") { $yax="5"; $enhed = ""; $topicid = 3; }
      echo "['".$value[0]."','".$topicid."','".$fridgegroups[$device2group[$value[0]]["ID"]].", ".$value[1]." ".$device2group[$value[0]]["name"]."',".$yax.",'".$enhed."']";
      $first=false;
    } }

    ?>];

    function createChart() {

 <?php
if ($_GET["plot"] == "stacked") echo "Highcharts.chart('container', {
    chart: {
        type: 'area',
        zoomType: 'xy'
    },
        plotOptions: {
        area: {
            stacking: 'normal'
        }
    },series: seriesOptions";
 else echo "   Highcharts.stockChart('container', {


    xAxis: {       
    ordinal: false
},

    rangeSelector: {
        buttons: [{
            count: 10,
            type: 'minute',
            text: '10M'
        }, {
            count: 30,
            type: 'minute',
            text: '30M'
        }, {
            count: 1,
            type: 'hour',
            text: '1H'
        }, {            
            count: 6,
            type: 'hour',
            text: '6H'
        }, {
            count: 12,
            type: 'hour',
            text: '12H'
        }, {
            count: 1,
            type: 'day',
            text: '1D'
        }, {
            count: 3,
            type: 'day',
            text: '3D'
        }, {
            count: 5,
            type: 'day',
            text: '5D'
        }, {
             count: 1,
            type: 'week',
            text: '1W'
        }, {
           type: 'all',
            text: 'All'
        }],
        inputEnabled: true,
        selected: 3
    },
        yAxis: [{ // Primary yAxis
        labels: {
            format: '{value}°C',
            style: {
                color: Highcharts.getOptions().colors[0]
            }
        },
        title: {
            text: 'Temperature',
            style: {
                color: Highcharts.getOptions().colors[0]
            }
        },
        opposite: true

    }, { // Secondary yAxis
        labels: {
            format: '{value} Watt',
            style: {
                color: Highcharts.getOptions().colors[1]
            }
        },
        title: {
            text: 'Effekt',
            style: {
                color: Highcharts.getOptions().colors[1]
            }
        }

    },{ // Secondary yAxis
        labels: {
            format: '{value} V',
            style: {
                color: Highcharts.getOptions().colors[2]
            }
        },
        title: {
            text: 'Voltage',
            style: {
                color: Highcharts.getOptions().colors[2]
            }
        }

    },{ // Secondary yAxis
        labels: {
            format: '{value} A',
            style: {
                color: Highcharts.getOptions().colors[3]
            }
        },
        title: {
            text: 'Current',
            style: {
                color: Highcharts.getOptions().colors[3]
            }
        }

    },{ // Secondary yAxis
        labels: {
            format: '{value}',
            style: {
                color: Highcharts.getOptions().colors[4]
            }
        },
        title: {
            text: 'Light',
            style: {
                color: Highcharts.getOptions().colors[4]
            }
        }

    },{ // Secondary yAxis
        labels: {
            format: '{value}',
            style: {
                color: Highcharts.getOptions().colors[5]
            }
        },
        title: {
            text: 'Motion',
            style: {
                color: Highcharts.getOptions().colors[5]
            }
        }

    },{ // Secondary yAxis
        labels: {
            format: '{value}',
            style: {
                color: Highcharts.getOptions().colors[6]
            }
        },
        title: {
            text: 'Signal',
            style: {
                color: Highcharts.getOptions().colors[6]
            }
        }

    }],

        series: seriesOptions";
    ?>
    });
}

$.each(names, function (i, name) {

    $.getJSON('https://it.pf.dk/fagprojekt/data.php?id=' + name[0].toLowerCase() + '&topic=' + name[1].toLowerCase() + '&type=&callback=?',    function (data) {
initiallength+= data.length;
        seriesOptions[i] = {
            name: name[2],
          <?php  if ($_GET["plot"] != "stacked") echo "yAxis: name[3],
            color: Highcharts.getOptions().colors[name[3]],
            data: data,
                    tooltip: {
            valueSuffix: ' ' + name[4],
            pointFormat: '{series.name}: <b>{point.y:.2f} '+name[4]+'</b><br/>' 
        }"; ?>
        };


        // As we're loading the data asynchronously, we don't know what order it will arrive. So
        // we keep a counter and create the chart when all the data is loaded.
        seriesCounter += 1;

        if (seriesCounter === names.length) {
            createChart();
var done = Date.now();
            var loadtime = done-now;
            console.log(initiallength+" datapoints loaded time: " + loadtime/1000 + " s");
        }
    });
}); 


  </script>



</head>

<body>

<?php

if ($_GET["data"] && sizeof($arr) != 0) {

    echo "<div id=container style=\" height: 100%; width: 100%;  margin: 0 auto;\"></div>
<p><b>Valgte data v2</b></p>
<form>
<select name=\"remove[]\" multiple size=".sizeof($arr).">";
foreach ($arr as $key=>$value){
  echo "<option value=".$key.">" . $fridgegroups[$device2group[$value[0]]["ID"]]." ".$value[1]." ".$device2group[$value[0]]["name"]."</option>";
}
echo "</select>
<input type=\"hidden\" name=\"data\" value=\"". htmlspecialchars($_GET['data'])."\" />
<input type=submit value=\"Fjern valgte\">

</form>"; }
?>

<p><b>Tilføj dataserie(r) ud fra gruppe</b></p>
<form style="width:100%;display:inline-block;">
<select id=selectgroup name=selectgroup multiple size=10>
<?php
asort($fridgegroups);
foreach ($fridgegroups as $key=>$value){
  echo "<option value=".$key.">" . $value."</option>";
}

?>
</select>
<select id=selectdevices name="selectdevices[]" multiple size=10>
</select>
<input type="hidden" name="data" value="<?php echo htmlspecialchars($_GET['data']); ?>" />
<input type=submit value="Tilføj valgte">

</form>

<p><b>Tilføj dataserie(r) ud fra type</b></p>
<form style="width:100%;display:inline-block;">
<select id=selecttype name=selecttype multiple size=10>

<option value=current>current</option>
<option value=voltage>voltage</option>
<option value=power>power</option>
<option value=temp>temp</option>
<option value=light>light</option>
<option value=motion>motion</option>

</select>
<select id=selectmes name="selectmes[]" multiple size=10>
</select>
<input type="hidden" name="data" value="<?php echo htmlspecialchars($_GET['data']); ?>" />
<input type=submit value="Tilføj valgte">

</form>

<p><b>Målingstype (ikke aktiv)</b></p>
Ændring af målingstypen. Andre ændringer på siden vil ikke blive gemt ved ændring.
<form style="width:100%;display:inline-block;">
  <select name="type[]" multiple>
    <option value="normal" <?php if ($_GET["type"] == "normal") echo "selected"; ?>>Målinger</option>
    <option value="signal" <?php if ($_GET["type"] == "signal") echo "selected"; ?>>Signalstyrke</option>
    <option value="sum" <?php if ($_GET["type"] == "sum") echo "selected"; ?>>Sum af valgte af samme type</option>
    <option value="average" <?php if ($_GET["type"] == "average") echo "selected"; ?>>Gennemsnit af valgte af samme type</option>
  </select>
<input type="hidden" name="data" value="<?php echo htmlspecialchars($_GET['data']); ?>" />
<input type=submit value="Vælg typer">
</form>

<?php
if ($_GET["data"] && sizeof($arr) != 0) {

    echo "<p><b>Export af valgte data til MATLAB</b></p>
<p>Brug højreklik og sig gem som</p>";
foreach ($arr as $key=>$value){

      if ($value[1] == "temp") { $yax="0"; $enhed = "°C"; $topicid = 6; }
      if ($value[1] == "power") { $yax="1"; $enhed = "Watt"; $topicid = 7; }
      if ($value[1] == "voltage") { $yax="2"; $enhed = "V"; $topicid = 1; }
      if ($value[1] == "current") { $yax="3"; $enhed = "A"; $topicid = 2; }
      if ($value[1] == "light") { $yax="4"; $enhed = ""; $topicid = 4; }
      if ($value[1] == "motion") { $yax="5"; $enhed = ""; $topicid = 3; }
      echo "<a href=https://it.pf.dk/fagprojekt/data.php?id=".$value[0]."&topic=".$topicid."&type=MATLAB>" . $fridgegroups[$device2group[$value[0]]["ID"]]." ".$value[1]." ".$device2group[$value[0]]["name"]."</a><br>";
}}
?>



</body>
</html>

<?php $conn->close(); ?>


