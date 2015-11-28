<?php
/**
 * ***************************************************************
 * admin/family/list.php (c) 2015 Jonathan Dieter
 *
 * List all family codes
 * ***************************************************************
 */
$title = "Family List";
if(isset($_GET['key2']) && dbfuncInt2String($_GET['key2']) == "1") {
	$show_all = 1;
} else {
	$show_all = 0;
}
$show_str = dbfuncString2Int($show_all);

include "header.php"; // Show header

/*
if ($res->numRows() > 0) {
	$is_counselor = true;
} else {*/
	$is_counselor = false;
/*}*/

if (dbfuncGetPermission($permissions, $PERM_ADMIN)) {
	$is_admin = true;
} else {
	$is_admin = false;
}

if ($is_admin or $is_counselor) { // Make sure user has permission to view and
	$showalldeps = true;
	include "core/settermandyear.php"; // edit students
	include "core/titletermyear.php";
	
	if ($_GET['sort'] == '1') {
		$sortorder = "family.FamilyCode DESC";
	} elseif ($_GET['sort'] == '2') {
		$sortorder = "family.FamilyName, family.FamilyCode";
	} elseif ($_GET['sort'] == '3') {
		$sortorder = "family.FamilyName DESC, family.FamilyCode DESC";
	} elseif ($_GET['sort'] == '4') {
		$sortorder = "family.FatherName, family.FamilyCode";
	} elseif ($_GET['sort'] == '5') {
		$sortorder = "family.FatherName DESC, family.FamilyCode DESC";
	} elseif ($_GET['sort'] == '6') {
		$sortorder = "family.MotherName, family.FamilyCode";
	} elseif ($_GET['sort'] == '7') {
		$sortorder = "family.MotherName DESC, family.FamilyCode DESC";
	} else {
		$sortorder = "family.FamilyCode";
	}
	
	$fcodeAsc = dbfuncGetButton(
								"index.php?location=" .
								 dbfuncString2Int("admin/family/list.php") .
								 "&amp;sort=0&amp;key2=$show_str", "A", "small", "sort", 
								"Sort ascending");
	$fcodeDec = dbfuncGetButton(
								"index.php?location=" .
								 dbfuncString2Int("admin/family/list.php") .
								 "&amp;sort=1&amp;key2=$show_str", "D", "small", "sort", 
								"Sort descending");
	$fnameAsc = dbfuncGetButton(
								"index.php?location=" .
								 dbfuncString2Int("admin/family/list.php") .
								 "&amp;sort=2&amp;key2=$show_str", "A", "small", "sort", 
								"Sort ascending");
	$fnameDec = dbfuncGetButton(
								"index.php?location=" .
								 dbfuncString2Int("admin/family/list.php") .
								 "&amp;sort=3&amp;key2=$show_str", "D", "small", "sort", 
								"Sort descending");
	$dadnameAsc = dbfuncGetButton(
								"index.php?location=" .
								 dbfuncString2Int("admin/family/list.php") .
								 "&amp;sort=4&amp;key2=$show_str", "A", "small", "sort", 
								"Sort ascending");
	$dadnameDec = dbfuncGetButton(
								"index.php?location=" .
								 dbfuncString2Int("admin/family/list.php") .
								 "&amp;sort=5&amp;key2=$show_str", "D", "small", "sort", 
								"Sort descending");
	$momnameAsc = dbfuncGetButton(
								"index.php?location=" .
								 dbfuncString2Int("admin/family/list.php") .
								 "&amp;sort=6&amp;key2=$show_str", "A", "small", "sort", 
								"Sort ascending");
	$momnameDec = dbfuncGetButton(
								"index.php?location=" .
								 dbfuncString2Int("admin/family/list.php") .
								 "&amp;sort=7&amp;key2=$show_str", "D", "small", "sort", 
								"Sort descending");
	
	$newlink = "index.php?location=" .
			dbfuncString2Int("admin/family/new.php") . // link to create a new subject
			"&amp;next=" .
			dbfuncString2Int(
					"index.php?location=" .
					dbfuncString2Int("admin/family/list.php") .
					"&amp;key2=$show_str");
	$newbutton = dbfuncGetButton($newlink, "New family", "medium", "", "Create new family");
	if($show_all == 1) {
		$showlink = "index.php?location=" .
				dbfuncString2Int("admin/family/list.php") . // link to create a new subject
				"&amp;key2=$show_str" .
				dbfuncString2Int("0");
		$showbutton = dbfuncGetButton($showlink, "Show active families", "medium", "", "Show families with active students");
	} else {
		$showlink = "index.php?location=" .
				dbfuncString2Int("admin/family/list.php") . // link to create a new subject
				"&amp;key2=" .
				dbfuncString2Int("1");
		$showbutton = dbfuncGetButton($showlink, "Show all families", "medium", "", "Show all families");
	}
	echo "      <p align=\"center\">$newbutton $showbutton</p>\n";
	
	/* Get student list */
	$query = 		"SELECT family.FamilyCode, family.FamilyName, family.FatherName, family.MotherName " .
		     		"       FROM family LEFT OUTER JOIN (familylist INNER JOIN user USING (Username)) USING (FamilyCode) ";
	if(!$show_all) {
		$query .=	"WHERE user.ActiveStudent=1 ";
	}
	$query .=		"GROUP BY family.FamilyCode " .
		     		"ORDER BY $sortorder";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
	
	/* Print families and their members */
	if ($res->numRows() > 0) {
		echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
		echo "         <tr>\n";
		echo "            <th>&nbsp;</th>\n";
		echo "            <th>Family Code $fcodeAsc $fcodeDec</th>\n";
		echo "            <th>Family Name $fnameAsc $fnameDec</th>\n";
		echo "            <th>Members</th>\n";		
		echo "         </tr>\n";
		
		/* For each family, print a row with the family code and other information */
		$alt_count = 0;
		while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
			$alt_count += 1;
			if ($alt_count % 2 == 0) {
				$alt = " class=\"alt\"";
			} else {
				$alt = " class=\"std\"";
			}
			echo "         <tr$alt>\n";
			
			$editlink = "index.php?location=" .
						 dbfuncString2Int("admin/family/modify.php") . "&amp;key=" .
						 dbfuncString2Int($row['FamilyCode']) . "&amp;keyname=" .
						 dbfuncString2Int("{$row['FamilyName']}");
			
			/* Generate view and edit buttons */
			if ($is_admin) {
				$editbutton = dbfuncGetButton($editlink, "E", "small", "edit", 
											"Edit family");
			} else {
				$editbutton = "";
			}
			
			echo "            <td>$editbutton</td>\n";
			echo "            <td>{$row['FamilyCode']}</td>\n";
			echo "            <td>{$row['FamilyName']}</td>\n";
			echo "            <td>\n";
			$query = "SELECT user.Username, user.FirstName, user.Surname, user.Title, user.ActiveStudent, user.ActiveTeacher, groupgenmem.Username AS GuardianUsername" .
					 "       FROM user INNER JOIN familylist ON familylist.FamilyCode='{$row['FamilyCode']}' AND familylist.Username=user.Username LEFT OUTER JOIN groupgenmem ON groupgenmem.GroupIndex=2 AND groupgenmem.Username=user.Username " .
			         "ORDER BY groupgenmem.Username DESC, user.ActiveTeacher DESC, user.ActiveStudent DESC, user.Username";
			$nres = &  $db->query($query);
			if (DB::isError($nres))
				die($nres->getDebugInfo()); // Check for errors in query
			if($nres->numRows() == 0) {
				echo "&nbsp;";
			}
			while ( $nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC) ) {
				$viewlink = "index.php?location=" .
						dbfuncString2Int("admin/subject/list_student.php") .
						"&amp;key=" . dbfuncString2Int($nrow['Username']) .
						"&amp;keyname=" .
						dbfuncString2Int(
							"{$nrow['FirstName']} {$nrow['Surname']} ({$nrow['Username']})");
				$editlink = "index.php?location=" .
						dbfuncString2Int("admin/user/modify.php") . "&amp;key=" .
						dbfuncString2Int($nrow['Username']) . "&amp;keyname=" .
						dbfuncString2Int(
							"{$nrow['FirstName']} {$nrow['Surname']} ({$nrow['Username']})");
				$cnlink = "index.php?location=" .
						dbfuncString2Int("teacher/casenote/list.php") . "&amp;key=" .
						dbfuncString2Int($nrow['Username']) . "&amp;keyname=" .
						dbfuncString2Int(
							"{$nrow['FirstName']} {$nrow['Surname']} ({$nrow['Username']})") .
				 		"&amp;keyname2=" . dbfuncSTring2Int($nrow['FirstName']);
				if($nrow['ActiveStudent'] == 1) {
					$viewbutton = dbfuncGetButton($viewlink, "V", "small", "view",
							"View student's subjects");
				} else {
					$viewbutton = "";
				}
				$editbutton = dbfuncGetButton($editlink, "E", "small", "edit",
						"Edit student");
				if($nrow['ActiveTeacher'] != 1) {
					$cnbutton = dbfuncGetButton($cnlink, "C", "small", "cn",
							"Casenotes for student");
				} else {
					$cnbutton = "";
				}
				
				echo "$viewbutton $editbutton $cnbutton";
				if($nrow['ActiveStudent'] == 1) {
					echo "<strong>";
				}
				if($nrow['ActiveTeacher'] == 1) {
					echo "<em>{$nrow['Title']} ";
				}
				echo "{$nrow['FirstName']} {$nrow['Surname']} ({$nrow['Username']})";
				if($nrow['ActiveTeacher'] == 1) {
					echo "</em>";
				}
				if($nrow['ActiveStudent'] == 1) {
					echo "</strong>";
				}
				echo "<br />\n";
			}
			echo "         </tr>\n";
		}
		echo "      </table>\n"; // End of table
	} else {
		echo "      <p>There are no families</p>\n";
	}
} else {
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

include "footer.php";
?>