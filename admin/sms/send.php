<?php
	/*****************************************************************
	 * admin/sms/send.php  (c) 2007 Jonathan Dieter
	 *
	 * Send an sms to a user
	 *****************************************************************/

	$destusername = safe(dbfuncInt2String($_GET['key']));
	$destfullname = dbfuncInt2String($_GET['keyname']);

	$link         = "index.php?location=" . dbfuncString2Int("admin/sms/send_action.php") .
				    "&amp;key=" .           $_GET['key'] .
				    "&amp;keyname=" .       $_GET['keyname'] .
				    "&amp;next=" .          dbfuncString2Int($backLink);

	$title = "Send SMS to $destfullname";
	
	include "header.php";                                          // Show header

	if(dbfuncGetPermission($permissions, $PERM_ADMIN)) {           // Make sure user has permission to view and
		echo "      <form action=\"$link\" method=\"post\">\n";    // Form method
		echo "         <table class=\"transparent\" align=\"center\">\n";
		echo "            <tr>\n";
		echo "               <td colspan=\"1\">Text:</td>\n";
		echo "               <td colspan=\"2\"><textarea rows=\"4\" cols=\"40\" name=\"text\"></textarea></td>\n";
		echo "            </tr>\n";
		echo "         </table>\n";
		echo "         <p></p>\n";
		
		echo "         <p align=\"center\">\n";
		echo "            <input type=\"submit\" name=\"action\" value=\"Send\" \>&nbsp; \n";
		echo "            <input type=\"submit\" name=\"action\" value=\"Cancel\" \>&nbsp; \n";
		echo "         </p>\n";
		echo "      </form>";
	} else {
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>