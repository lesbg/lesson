<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
<?php
	require_once "DB.php";                          // Get DB class

	$DSN = "mysql://user@database.example.com/lesson";     // DSN to connect to database
	$db =& DB::connect($DSN);                     // Initiate connection
	if(DB::isError($db)) die($db->getDebugInfo());  // Check for errors in connection	
	
	$res =& $db->query("TRUNCATE calendar");
	for($i=0;$i<=(365*60);$i++) {
		$res =&  $db->query("INSERT INTO calendar SET Date=date_add(\"2004-01-01\",INTERVAL $i DAY)");
		unset($res);
	}

?>
	</head>
</html>
