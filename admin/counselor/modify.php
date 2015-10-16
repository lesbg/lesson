<?php
/**
 * ***************************************************************
 * admin/counselor/modify.php (c) 2006 Jonathan Dieter
 *
 * Add or remove counselors
 * ***************************************************************
 */

/* Get variables */
if (! isset($nextLink))
	$nextLink = $backLink;

$title = "Add or remove counselors";
$link = "index.php?location=" .
		 dbfuncString2Int("admin/counselor/modify_action.php") . "&amp;next=" .
		 dbfuncString2Int($nextLink);

include "core/settermandyear.php";
include "header.php"; // Show header

/* Check whether user is authorized to change counselors */
if (dbfuncGetPermission($permissions, $PERM_ADMIN)) {
	echo "      <form action=\"$link\" name=\"modSubj\" method=\"post\">\n"; // Form method
	
	echo "         <table align=\"center\" border=\"1\">\n"; // Table headers
	echo "            <tr>\n";
	echo "               <th>Counselors</th>\n";
	echo "               <th>Unassigned teachers</th>\n";
	echo "            </tr>\n";
	echo "            <tr class=\"std\">\n";
	
	/* Get list of students in subject and store in option list */
	echo "               <td>\n";
	echo "                  <select name=\"remove[]\" style=\"width: 200px;\" multiple size=19>\n";
	$res = &  $db->query(
					"SELECT user.FirstName, user.Surname, user.Username FROM " .
					 "       user, counselorlist " .
					 "WHERE counselorlist.Username = user.Username " .
					 "ORDER BY user.Username");
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
		$remUsername = "{$row['Username']}";
		echo "                     <option value=\"$remUsername\">{$row['FirstName']} " .
			 "{$row['Surname']} ({$row['Username']})\n";
	}
	echo "                  </select>\n";
	echo "               </td>\n";
	
	/* Get list of unassigned teachers */
	echo "               <td>\n";
	echo "                  <select name=\"add[]\" style=\"width: 200px;\" multiple size=19>\n";
	
	$query = "SELECT user.FirstName, user.Surname, user.Username FROM " .
			 "       user LEFT JOIN counselorlist ON " .
			 "       user.Username=counselorlist.Username " .
			 "WHERE  user.ActiveTeacher = 1 " .
			 "AND    counselorlist.Username IS NULL " . "ORDER BY user.Username";
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