<?php

session_start();

if (empty($_SESSION['user'])) { 

$provider = require __DIR__ . '/examples/provider.php';

if (!empty($_SESSION['token'])) {
    $token = unserialize($_SESSION['token']);
}

if (empty($token)) {
	echo "This page requires login. Please <a href=https://it.pf.dk/fagprojekt/examples/>login</a> with your authorized Google account.";
    exit;
}

try {

    // We got an access token, let's now get the user's details
    $userDetails = $provider->getResourceOwner($token);

    // Use these details to create a new profile
    //printf('Hello %s!<br/>', $userDetails->getEmail());

    $_SESSION['user'] = $userDetails->getEmail();

} catch (Exception $e) {

    // Failed to get user details
    exit('Something went wrong: ' . $e->getMessage());

}}
$Gmail = $_SESSION['user'];

if ($Gmail != "kristensen.emil@gmail.com" && $Gmail != "matlrocks12@gmail.com" && $Gmail != "johnnyye9@gmail.com" && $Gmail != "supersejesebnb1@live.dk") {
	echo "You, ".$Gmail.", do not have permission to access this page! <a href=https://it.pf.dk/fagprojekt/examples/reset.php>Logout</a>";
	exit();
}

?>
<html>
<head>	
<meta http-equiv="Content-Type" content="text/html;charset=ISO-8859-1">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
  <script>
    $(document).ready(function() {

          var last_valid_selection = null;

          $('#editgateway').change(function(event) {

            var myForm = $(this);
            var data = $(this).serializeArray();//serialize form inputs and pass them to php
            $.post("getedit.php?type=editgate",data,function(data){
                $('#gatewayname').val(data);
            });
          });

          $('#editgroups').change(function(event) {

            var myForm = $(this);
            var data = $(this).serializeArray();//serialize form inputs and pass them to php
            $.post("getedit.php?type=editgroup",data,function(data){
                $('#editgroupcha').html(data);
            });
          });

          $('#editdevices').change(function(event) {

            var myForm = $(this);
            var data = $(this).serializeArray();//serialize form inputs and pass them to php
            $.post("getedit.php?type=editdevice",data,function(data){
                $('#editdevicecha').html(data);
            });
          });
        });
  </script>
</head>
<body>
<h1>Fridge control admin interface</h1>
<p>Logged in as <?php echo $Gmail; ?> <a href=https://it.pf.dk/fagprojekt/examples/reset.php>Logout</a></p>
<h2>Alle grupper med enheder</h2>
<table>
  <tr>
    <th>Name</th>
    <th>ID</th> 
    <th>Type</th> 
    <th>Address</th>
  </tr>
</table>
<div id="Groups">
<table><div>
<?php

require __DIR__ . '/vendor/autoload.php';

$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();


header('Content-Type: text/html; charset=ISO-8859-1');

//servername, username, password og db name:
include 'datalogin.php'; 

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

$sql = "SELECT groups.ID, groups.name as groupname, gateways.device as gateway, devices.name as devicename, devices.type as devicetype, devices.device as deviceaddr, aktiv, result, devices.ID as deviceID, gateways.ID as gatewayID, gateways.name as gatewayname,incap,created,completed FROM `groups` JOIN gateways ON groups.gateway=gateways.ID JOIN devices ON groups.ID=devices.groupID JOIN cmd ON groups.incap=cmd.ID ORDER BY `groups`.`name` ASC";
$result = $conn->query($sql);
$currgroup = 0;
$started = false;
$content = "";

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
    	if ($currgroup != $row['ID']) {
    		if (!$started) {
    			$content.= "</table></div>";
    			$started = true;
    		}
    		$content.= "</table></div>";
    		echo "<button type=button class=\"btn btn-info\" data-toggle=\"collapse\" data-target=\"#f".$row["ID"]."\">".$row["groupname"]."</button>";
    		$content.= "<div id=f".$row["ID"]." class=collapse>";
    		$content.= "<h3>".$row["groupname"]."</h3>";
    		$content.= "<p>Aktiv? ".$row["aktiv"]."</p>";
    		$content.= "<button type=button class=\"btn btn-info\" data-toggle=\"collapse\" data-target=\"#fc".$row["ID"]."\">Console result</button>";
    		$content.= "<div id=fc".$row["ID"]." class=collapse data-parent=#Groups>";
    		$content.= "Request ID: ".$row["incap"]."<br>";
    		$content.= "Request created: ".$row["created"]."<br>";
    		if ($row["result"]) {
		   		$content.= "Request completed: ".$row["completed"]."<br>";
    			$content.=  $row["result"];
    		} 
    		else $content.= "Awaiting run.";
    		$content.= "</div>";
    		$content.= "<p>Gateway (";
    		$content.= $row["gatewayID"].")";
    		$content.= ": ".$row["gateway"]." ";
    		$content.= $row["gatewayname"]."</p>";
    		$content.= "<p>Reload Group+gateway on server? <a href=do.php?type=reload&name=".$row["gatewayID"]."&addr=".$row["gateway"].">Reload / Activate all groups on gateway</a></p>";
    		$content.= "<table>";
    		$currgroup = $row["ID"];
    	} 
    	$content.= "<tr>";
    	$content.= "<td>".$row["devicename"]."</td>";
    	$content.= "<td>".$row["deviceID"]."</td>";
    	$content.= "<td>".$row["devicetype"]."</td>";
    	$content.= "<td>".$row["deviceaddr"]."</td>";
    	$content.= "<td><a href=confirm.php?type=deletedevice&name=".$row["ID"]."&addr=".$row["deviceaddr"].">Delete device</a></td>";
    	$content.= "</tr>";

    }
}

echo $content;
echo "</table></div></div>";

?>

<h2>Add</h2>

<div id="Actions">
<button type=button class="btn btn-info" data-toggle="collapse" data-target="#addgate"  data-parent="#Actions">Add gateway</button>
<button type=button class="btn btn-info" data-toggle="collapse" data-target="#newgroup" data-parent="#Actions">New group</button>
<button type=button class="btn btn-info" data-toggle="collapse" data-target="#adddevice" data-parent="#Actions">Add Devices</button>

 <div class="accordion-group">
<div id=addgate class=collapse>
<h3>Add gateway</h3>
<form action=do.php>
Name: <input name=name type=text><br>
Gateway <input name=addr type=text><br>
<input type=hidden name=type value=addgate>
<input type=submit value="Add gateway">
</form>
</div>

<div id=newgroup class=collapse>
<h3>New group</h3>
<form action=do.php>
  <select name="addr">
  <?php
$sql = "SELECT * FROM `gateways` ORDER BY `name` ASC";
$result = $conn->query($sql);
$currgroup = 0;
$started = false;
$content = "";
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
    		echo "<option value=".$row["ID"].">".$row["name"]." ".$row["device"]."</option>";

    }
} ?>
  </select><br>
Name: <input name=name type=text><br>
Aktiv? <input name=aktiv type=checkbox checked><br>
<input type=hidden name=type value=newgroup>
<input type=submit value="Create group">
</form>
</div>



<div id=adddevice class=collapse>
<h3>Add devices</h3>
<form action=do.php>
  <select name="name">
  <?php
$sql = "SELECT * FROM `groups` ORDER BY `name` ASC";
$result = $conn->query($sql);
$currgroup = 0;
$started = false;
$content = "";
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
    		echo "<option value=".$row["ID"].">".$row["name"]."</option>";

    }
}?>
  </select><br>
  Add devices here. One pr line. Seperate with , Syntax: device,type,name<br>
  Valid types: relay, sensor, indicator<br>
  <textarea name="addr" rows="10" cols="30"></textarea><br>
<input type=hidden name=type value=adddevice>
<input type=submit value="Add devices">
</form>
</div>
</div>
</div>

<h2>Edit</h2>

<div id="Actions">
<button type=button class="btn btn-info" data-toggle="collapse" data-target="#editgate"  data-parent="#Actions">Edit gateway</button>
<button type=button class="btn btn-info" data-toggle="collapse" data-target="#editgroup" data-parent="#Actions">Edit group properties</button>
<button type=button class="btn btn-info" data-toggle="collapse" data-target="#editdevice" data-parent="#Actions">Edit devices names</button>

 <div class="accordion-group">
<div id=editgate class=collapse>
<h3>Edit gateway</h3>
<form action=do.php>
  <select id=editgateway name="ID">
  <option>Choose</option>
  <?php
$sql = "SELECT * FROM `gateways` ORDER BY `name` ASC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
    		echo "<option value=".$row["ID"].">".$row["name"]." ".$row["device"]."</option>";
    }
}?>
  </select><br>
Name: <input id=gatewayname  name=name type=text><br>
<input type=hidden name=type value=editgate>
<input type=submit value="Update gateway">
</form>
</div>

<div id=editgroup class=collapse>
<h3>Edit group properties</h3>
<form action=do.php>
  <select id=editgroups name="ID">
  <option>Choose</option>
  <?php
$sql = "SELECT * FROM `groups` ORDER BY `name` ASC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
    	echo "<option value=".$row["ID"].">".$row["name"]." (".$row["ID"].")</option>";
    }
} ?>
  </select><br>
<div id=editgroupcha></div>
<input type=hidden name=type value=editgroup>
<input type=submit value="Update group">
</form>
</div>



<div id=editdevice class=collapse>
<h3>Edit devices in group</h3>
<form action=do.php>
  <select id=editdevices name="ID">
  <option>Choose</option>
  <?php
$sql = "SELECT * FROM `groups` ORDER BY `name` ASC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
    	echo "<option value=".$row["ID"].">".$row["name"]." (".$row["ID"].")</option>";
    }
}?>
  </select><br>
<div id=editdevicecha></div>
<input type=hidden name=type value=editdevice>
<input type=submit value="Update devices">
</form>
</div>
</div>
</div>

<h2>Clean up</h2>
Use this link to delete all groups and gateways that do not belong to any device. Futhermore, if a gateway is deleted a removed request of that gateway will be created.<br>

<a href="https://it.pf.dk/fagprojekt/confirm.php?type=cleanup">Do this!</a>

<h2>Log data</h2>

  <?php
$sql = "SELECT * FROM `log` ORDER BY `time` DESC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
      echo "<p>".$row["time"].": ".$row["event"];
    }
}?>

<h2>Application command requets (todo)</h2>
See all application requets

</body>
</html>


<?php $conn->close(); ?>