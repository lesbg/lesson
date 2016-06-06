<?php
/**
 * ***************************************************************
 * admin/principal/modify.php (c) 2006 Jonathan Dieter
 *
 * Add or remove principals
 * ***************************************************************
 */

/* Get variables */
if (! isset($nextLink))
	$nextLink = $backLink;

$title = "Modify principals";
$link = "index.php?location=" .
		 dbfuncString2Int("admin/principal/modify_action.php") . "&amp;next=" .
		 dbfuncString2Int($nextLink);

include "header.php"; // Show header

/* Check whether user is authorized to change counselors */
if (dbfuncGetPermission($permissions, $PERM_ADMIN)) {
	echo "      <form action=\"$link\" name=\"principal\" method=\"post\">\n"; // Form method
	
	echo "         <table align=\"center\" border=\"1\">\n"; // Table headers
	echo "            <tr>\n";
	echo "               <th>Principals</th>\n";
	echo "               <th>Unassigned teachers</th>\n";
	echo "            </tr>\n";
	echo "            <tr class=\"std\">\n";
	
	/* Get list of students in subject and store in option list */
	echo "               <td>\n";
	echo "                  <select name=\"remove[]\" style=\"width: 200px;\" multiple size=19>\n";
	$res = &  $db->query(
					"SELECT user.FirstName, user.Surname, user.Username, " .
					 "       principal.Level FROM user, principal " .
					 "WHERE principal.Username = user.Username " .
					 "ORDER BY principal.Level, user.Username");
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
		$remUsername = "{$row['Username']}";
		if ($row['Level'] == 2) {
			$pType = "Head of department";
		} else {
			$pType = "Principal";
		}
		echo "                     <option value=\"$remUsername\">{$row['FirstName']} " .
			 "{$row['Surname']} ({$row['Username']}) - $pType\n";
	}
	echo "                  </select>\n";
	echo "               </td>\n";
	
	/* Get list of unassigned teachers */
	echo "               <td>\n";
	echo "                  <label for=\"pt1\"><input id=\"pt1\" type=\"radio\" name=\"level\" value=\"1\">Principal</label><br>\n";
	echo "                  <label for=\"pt2\"><input id=\"pt2\" type=\"radio\" name=\"level\" value=\"2\" checked>Head of department</label><br>\n";
	echo "                  <select name=\"add[]\" style=\"width: 200px;\" multiple size=17>\n";
	
	$query =	"SELECT user.FirstName, user.Surname, user.Username FROM " .
				"       user INNER JOIN groupgenmem ON (user.Username=groupgenmem.Username) " .
				"            INNER JOIN groups USING (GroupID) " .
				"       user LEFT JOIN principal ON " .
				"       user.Username=principal.Username " .
				"WHERE groups.GroupTypeID='activeteacher' " .
				"AND   groups.YearIndex=$yearindex " .
				"AND   principal.Username IS NULL " .
				"ORDER BY user.Username";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
		echo "                     <option value=\"{$row['Username']}\">{$row['FirstName']} " .
			 "{$row['Surname']} ({$row['Username']})\n";
	}
	echo "                  </select><br>\n";
	echo "               </td>\n";
	echo "            </tr>\n";
	echo "            <tr class=\"alt\">\n";
	echo "               <td align=\"center\"><input type=\"submit\" name=\"action\" value=\">\" \></td>\n";
	echo "               <td align=\"center\"><input type=\"submit\" name=\"action\" value=\"<\" \></td>\n";
	echo "            </tr>\n";
	echo "         </table>\n"; // End of table
	echo "         <p align=\"center\"><input type=\"submit\" name=\"action\" value=\"Done\" \></p>\n";
	echo "         <p></p>\n";
	echo "      </form>\n";
} else { // User isn't authorized to change counselors.
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

include "footer.php";
?>