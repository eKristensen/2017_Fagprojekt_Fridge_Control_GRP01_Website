<?php header('Content-Type: application/json;charset=UTF-8');

$servername = "localhost";
$username = "fagprojekt";
$password = "gf3qAdOPH1l9YtSp";
$dbname = "fagprojekt";

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

echo $_GET["callback"];
if ($_GET["type"] != "MATLAB") echo "(
[
";

$sql = "SELECT `topic`, SUM(`value`) AS `value`, order_time FROM ( SELECT `topic`, AVG(`value`) AS `value`, DATE_FORMAT(FROM_UNIXTIME(`timestamp`), '%Y-%m-%d %H:%i:00') AS order_time FROM `data` WHERE `topic` = 7 GROUP BY order_time, device) AS A GROUP BY order_time ORDER BY `A`.`order_time` ASC;";
$result = $conn->query($sql);
$started = false;


if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
    	if ($started && $_GET["type"] != "MATLAB") {        echo ",
";}
elseif ($started) echo"\n";
        if ($_GET["type"] != "MATLAB") echo "[";
           // echo $row["timestamp"]*1000;
        echo strtotime($row["order_time"]);
        if ($_GET["type"] != "MATLAB") echo ",";
        else echo " ";
        if ($row["topic"] == 6) echo $row["value"]/100; //temp
        elseif ($row["topic"] == 1) echo $row["value"]/10; //voltage
        elseif ($row["topic"] == 2) echo $row["value"]/1000; //current
        elseif ($row["topic"] == 4) echo ($row["value"]-1)/10000; //light
        else echo $row["value"];
        if ($_GET["type"] != "MATLAB") echo "]";
        $started = true;
    }
} else {
    //echo "error";
}

if ($_GET["type"] != "MATLAB") echo "
]);";

 $conn->close();

  ?>


