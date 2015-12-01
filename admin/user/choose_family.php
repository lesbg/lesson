<?php
/**
 * ***************************************************************
 * admin/user/choose_family.php (c) 2015 Jonathan Dieter
 *
 * Choose family
 * ***************************************************************
 */

/* Get variables */
$title = "Choose family";


if ($is_admin) {
	if(isset($_GET['next'])) {
		$next = $_GET['next'];
	} else {
		$next = dbfuncString2Int($backLink);
	}
	
	$link = "index.php?location=" .
			dbfuncString2Int("admin/user/choose_family_action.php") . "&amp;next=" .
			$next;

	/* Get variables */
	if(isset($_GET['key'])) {
		$link .= "&amp;key="     . $_GET['key'] .
				 "&amp;keyname=" . $_GET['keyname'];
	}
	
	if(isset($_POST['uname'])) {
		$title = $title . " for " . htmlspecialchars($_POST['uname'], ENT_QUOTES);
	}
	
	if(!isset($_SESSION['post'])) {
		$_SESSION['post'] = array();
	}
	$pval = array();
	foreach($_POST as $key => $value) {
		$_SESSION['post'][$key] = $value;
	}

	include "header.php";
	
	echo "      <form action='$link' method='post'>\n"; // Form method
	echo "         <table class='transparent' align='center'>\n";
	echo "            <tr>\n";
	echo "               <th>Family Code</th>";
	echo "               <th>&nbsp;</th>";
	echo "			  </tr>\n";
	
	$query =	"SELECT FamilyCode FROM family " .
				"ORDER BY FamilyCode"; 
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
		$found = False;
		foreach($_SESSION['post']['fcode'] as $fcode) {
			if($row['FamilyCode'] == $fcode[0]) {
				$found = True;
				break;
			}
		}
		$fcode = htmlspecialchars($row['FamilyCode']);
		$disabled = "";
		if($found) {
			$fcode = "<em>$fcode</em>";
			$disabled = "disabled";
		}
		$button = "<input type='submit' name='select-$fcode' value='+' $disabled>";
		echo "            <tr><td>$fcode</td><td>$button</td></tr>\n";
	}
	echo "         </table>\n";
	echo "      </form>\n";
	
	/*nextLink=dbfuncString2Int($backLink);
	$extraMeta = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$backLink\">\n";
	$noJS = true;
	$noHeaderLinks = true;
	$title = "LESSON - Redirecting...";
	
	include "header.php";
	
	echo "      <p align=\"center\">Redirecting you to <a href=\"$backLink\">$backLink</a>." .
	"</p>\n";
	*/
	include "footer.php";
} else { // User isn't authorized to view or change users.
	include "header.php"; // Show header
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	include "footer.php";
}