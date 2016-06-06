<?php
/**
 * ***************************************************************
 * admin/punishment/set_date.php (c) 2006-2013 Jonathan Dieter
 *
 * Set date of next punishment
 * ***************************************************************
 */

/* Get variables */
if (isset($_GET['type'])) {
	$dtype = dbfuncInt2String($_GET['type']);
} else {
	if (isset($_POST['type'])) {
		$dtype = $_POST['type'];
		$_GET['type'] = dbfuncString2Int($dtype);
	} else {
		$link = "index.php?location=" .
				 dbfuncString2Int("admin/punishment/set_date.php") . "&amp;next=" .
				 $_GET['next'];
		include "admin/punishment/choose_type.php";
		exit(0);
	}
}

$query = "SELECT DisciplineDateIndex, Username, PunishDate, EndDate FROM disciplinedate " .
		 "WHERE DisciplineTypeIndex = $dtype " . "AND   Done = 0";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
	$pindex = $row['DisciplineDateIndex'];
	$_POST['teacher'] = $row['Username'];
	$pundateinfo = date($dateformat, strtotime($row['PunishDate']));
	$enddateinfo = date($dateformat, strtotime($row['EndDate']));
} else {
	$pindex = "NULL";
	$pundateinfo = date($dateformat);
	$enddateinfo = date($dateformat, mktime(0, 0, 0) - 86400);
}

$query = "SELECT DisciplineType " . "       FROM disciplinetype " .
		 "WHERE  disciplinetype.DisciplineTypeIndex = $dtype ";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
	$disc = strtolower($row['DisciplineType']);
} else {
	$disc = "unknown punishment";
}

$title = "Set punishment date for next $disc";

$link = "index.php?location=" .
		 dbfuncString2Int("admin/punishment/set_date_action.php") . "&amp;type=" .
		 $_GET['type'] . "&amp;key=" . dbfuncString2Int($pindex) . "&amp;next=" .
		 $_GET['next'];

$query = "SELECT user.FirstName, user.Surname, user.Username FROM " .
		 "       user INNER JOIN groupgenmem ON (user.Username=groupgenmem.Username) " .
		 "            INNER JOIN groups USING (GroupID) " .
		 "WHERE user.Username='$username' " .
		 "AND   groups.GroupTypeID='activeteacher' " .
		 "AND   groups.YearIndex=$yearindex " .
		 "ORDER BY user.Username";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query
if ($res->numRows() > 0) {
	$is_teacher = true;
} else {
	$is_teacher = false;
}

$query = "SELECT Permissions FROM disciplineperms WHERE Username=\"$username\"";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
	$perm = $row['Permissions'];
} else {
	$perm = $DEFAULT_PUN_PERM;
}

include "header.php"; // Show header

if (dbfuncGetPermission($permissions, $PERM_ADMIN) or
	 ($perm >= $PUN_PERM_ALL and $is_teacher)) {
	echo "      <form action=\"$link\" method=\"post\" name=\"pundate\">\n"; // Form method
	echo "         <table border=\"0\" class=\"transparent\" align=\"center\">\n";
	$query =	"SELECT user.FirstName, user.Surname, user.Username FROM " .
				"       user INNER JOIN groupgenmem ON (user.Username=groupgenmem.Username) " .
				"            INNER JOIN groups USING (GroupID) " .
				"WHERE groups.GroupTypeID='activeteacher' " .
				"AND   groups.YearIndex=$yearindex " .
				"ORDER BY user.Username";
	$nres = &  $db->query($query);
	if (DB::isError($nres))
		die($nres->getDebugInfo()); // Check for errors in query
	echo "            <tr><td>Punishment supervisor: <select name=\"teacher\">\n";
	while ( $nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC) ) {
		if (isset($_POST['teacher']) and $_POST['teacher'] == $nrow['Username']) {
			$selected = "selected";
		} else {
			$selected = "";
		}
		echo "         <option value=\"{$nrow['Username']}\" $selected>{$nrow['Username']} - {$nrow['FirstName']} {$nrow['Surname']}</option>\n";
	}
	echo "      </select></td></tr>\n";
	echo "            <tr>\n";
	echo "               <td>\n";
	echo "                  Punishment Date:<br>\n";
	echo "                  <input type=\"text\" name=\"pundate\" value=\"$pundateinfo\" id=\"pundatetext\">\n";
	echo "               </td>\n";
	echo "            </tr>\n";
	echo "            <tr>\n";
	echo "               <td>\n";
	echo "                  Last date of rule violation this punishment should apply to:<br>\n";
	echo "                  <input type=\"text\" name=\"enddate\" value=\"$enddateinfo\" id=\"enddatetext\">\n";
	echo "               </td>\n";
	echo "            </tr>\n";
	echo "         </table>\n";
	echo "         <p align=\"center\">\n";
	echo "            <input type=\"submit\" name=\"action\" value=\"Set punishment date\">&nbsp; \n";
	if ($pindex != "NULL") {
		echo "            <input type=\"submit\" name=\"action\" value=\"Delete punishment date\">&nbsp; \n";
	}
	echo "            <input type=\"submit\" name=\"action\" value=\"Cancel\">&nbsp; \n";
	echo "         </p>\n";
	echo "      </form>\n";
} else { // User isn't authorized to create a punishment
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "admin/punishment/set_date.php", 
			$LOG_DENIED_ACCESS, "Tried to set punishment date.");
	
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

include "footer.php";
?>