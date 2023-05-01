<?php
/**
 * ***************************************************************
 * admin/punishment/approve_all_pending_confirm.php (c) 2006-2016 Jonathan Dieter
 *
 * Confirm approval of all pending punishment
 * ***************************************************************
 */

/* Get variables */
$nextLink = dbfuncInt2String($_GET['next']);

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

/* Get current user's punishment permissions */
$query = "SELECT Permissions FROM disciplineperms WHERE Username=\"$username\"";
$res = &  $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
	$perm = $row['Permissions'];
} else {
	$perm = $DEFAULT_PUN_PERM;
}

$title = "LESSON - Confirm all pending punishments";
$noJS = true;
$noHeaderLinks = true;

include "core/settermandyear.php";
include "header.php";

/* Check whether current user is authorized to approve all pending punishments */
if (dbfuncGetPermission($permissions, $PERM_ADMIN) or
	 ($perm >= $PUN_PERM_APPROVE and $is_teacher)) {
	$link = "index.php?location=" .
	 dbfuncString2Int("admin/punishment/approve_all_pending.php") . "&amp;next=" .
	 $_GET['next'];

echo "      <p align=\"center\">Are you <b>sure</b> you want to approve <b>all</b> pending punishments?</p>\n";
echo "      <form action=\"$link\" method=\"post\">\n";
echo "         <p align=\"center\">";
echo "            <input type=\"submit\" name=\"action\" value=\"Yes, approve all punishments\" \>&nbsp; \n";
echo "            <input type=\"submit\" name=\"action\" value=\"No, I changed my mind\" \>&nbsp; \n";
echo "         </p>";
echo "      </form>\n";
} else {
log_event($LOG_LEVEL_ERROR, "admin/punishment/approve_all_pending_confirm.php", 
		$LOG_DENIED_ACCESS, "Tried to approve all pending punishments.");
echo "      <p>You do not have the authority to approve this punishment.  <a href=\"$nextLink\">" .
	 "Click here to continue</a>.</p>\n";
}
include "footer.php";
?>