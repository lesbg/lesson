<?php
	/*****************************************************************
	 * admin/punishment/tools.php  (c) 2006 Jonathan Dieter
	 *
	 * Punishment tools
	 *****************************************************************/

	$title = "Punishment Tools";
	
	include "header.php";                                          // Show header
	
	$query =    "SELECT Permissions FROM disciplineperms WHERE Username='$username'";
	$res =&  $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());           // Check for errors in query
	if($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$perm = $row['Permissions'];
	} else {
		$perm = 0;
	}

	if(!$is_admin and $perm < $PUN_PERM_MASS) {
		log_event($LOG_LEVEL_ERROR, "admin/punishment/tools.php", $LOG_DENIED_ACCESS, "Tried to access punishment tools.");
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
		include "footer.php";
		exit(0);
	}

	log_event($LOG_LEVEL_EVERYTHING, "admin/punishment/tools.php", $LOG_ADMIN, "Accessed punishment tools.");
	
	$masspunLink       =	"index.php?location=" . dbfuncString2Int("admin/punishment/mass.php") .
							"&amp;next="          . dbfuncString2Int("index.php?location=" .
														dbfuncString2Int("admin/punishment/tools.php"));
	$reviewpendpunLink =	"index.php?location=" . dbfuncString2Int("admin/punishment/review_pending.php") .
							"&amp;next="          . dbfuncString2Int("index.php?location=" .
														dbfuncString2Int("admin/punishment/tools.php"));
	$punlistLink       =	"index.php?location=" . dbfuncString2Int("admin/punishment/list.php");
	$viewpunLink       =	"index.php?location=" . dbfuncString2Int("admin/punishment/list_date.php");
	$setdateLink       =	"index.php?location=" . dbfuncString2Int("admin/punishment/date_student.php") .
							"&amp;next="          . dbfuncString2Int("index.php?location=" .
														dbfuncString2Int("admin/punishment/tools.php"));
	$proxypunLink      =	"index.php?location=" . dbfuncString2Int("admin/punishment/proxy.php") .
							"&amp;next="          . dbfuncString2Int("index.php?location=" .
														dbfuncString2Int("admin/punishment/tools.php"));
	$attendanceLink    =	"index.php?location=" . dbfuncString2Int("admin/attendance/first_period.php");
	$printattLink      =	"index.php?location=" . dbfuncString2Int("admin/print/attendance.php");
	$lowconductLink    =	"index.php?location=" . dbfuncString2Int("admin/punishment/low.php");
	$alLink            =	"index.php?location=" . dbfuncString2Int("admin/attendance/list.php");
	echo "      <div class='button' style='position: absolute; width: 300px; left: 50%; margin-left: -150px;'>\n";
	echo "      <p><a href='$masspunLink'>Issue mass punishment</a></p>\n";
	if($perm >= $PUN_PERM_SEE or $is_admin) {
		echo "      <p><a href='$punlistLink'>View punishments for this term</a></p>\n";
		echo "      <p><a href='$viewpunLink'>Print punished student list</a></p>\n";
	}
	if($perm >= $PUN_PERM_APPROVE or $is_admin) {
		echo "      <p><a href='$reviewpendpunLink'>Review pending punishments</a></p>\n";
	}
	if($perm >= $PUN_PERM_ALL or $is_admin) {
		echo "      <p><a href='$setdateLink'>Set/edit punishment date</a></p>\n";
		echo "      <p><a href='$lowconductLink'>Low conduct marks</a></p>\n";
	}
	if($perm >= $PUN_PERM_PROXY or $is_admin) {
		echo "      <p><a href='$proxypunLink'>Issue punishments on behalf of teachers</a></p>\n";
		echo "      <p><a href='$attendanceLink'>Attendance for today</a></p>\n";
		echo "      <p><a href='$printattLink'>Print attendance for today</a></p>\n";
		echo "      <p><a href='$alLink'>Attendance list for term</a></p>\n";
	}
	include "footer.php";
?>