<?php

$actual_link = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

echo "Do you rellay want to do this? <a href=".str_replace("confirm","do",$actual_link).">Yes</a><br>
<a href=https://it.pf.dk/fagprojekt/admin.php>No take me away!</a>";

?>