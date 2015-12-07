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
	if(isset($_POST['sname'])) {
		$sname = $_POST['sname'];
	} else {
		$sname = "";
	}

	$next = dbfuncString2Int($backLink);
	
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
	
	$newlink = "index.php?location=" .
			dbfuncString2Int("admin/family/new.php") . 
			"&amp;next=" . $next .
			"&amp;keyname=" . dbfuncString2Int($sname);
	$newbutton = dbfuncGetButton($newlink, "New family", "medium", "", "Create new family");
	echo "         <p align='center'>$newbutton</p>\n";
	echo "         <p align='center'>\n";
	echo "            <select name='fcode'>\n";
	
	$query =	"SELECT FamilyCode FROM family " .
				"ORDER BY FamilyCode"; 
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	$first_match = False;
	while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
		$found = False;
		
		if(!$first_match and strcasecmp($sname, $row['FamilyCode']) < 0) {
			$first_match = True;
			$selected = "selected";
		} else {
			$selected = "";
		}
		foreach($_SESSION['post']['fcode'] as $fcode) {
			if($row['FamilyCode'] == $fcode[0]) {
				$found = True;
				break;
			}
		}
		
		$fcode = htmlspecialchars($row['FamilyCode']);
		$disabled = "";
		if($found) {
			$disabled = "disabled";
			if($selected != "") {
				$first_match = False;
				$selected = "";
			}
		}
		echo "               <option value='$fcode' $disabled $selected>$fcode</option>\n";
	}
	echo "            </select>\n";
	echo "         </p>\n";
	echo "         <p align='center'>\n";
	echo "            <input type='submit' name='action' value='Add'>&nbsp;\n";
	echo "            <input type='submit' name='action' value='Cancel'>\n";
	echo "         </p>\n";
	echo "      </form>\n";
	
	include "footer.php";
} else { // User isn't authorized to view or change users.
	include "header.php"; // Show header
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	include "footer.php";
}