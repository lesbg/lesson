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
		$sortorder = "FamilyCode DESC";
	} elseif ($_GET['sort'] == '2') {
		$sortorder = "FamilyName, FamilyCode";
	} elseif ($_GET['sort'] == '3') {
		$sortorder = "FamilyName DESC, FamilyCode DESC";
	/*} elseif ($_GET['sort'] == '4') {
		$sortorder = "family.FatherName, family.FamilyCode";
	} elseif ($_GET['sort'] == '5') {
		$sortorder = "family.FatherName DESC, family.FamilyCode DESC";
	} elseif ($_GET['sort'] == '6') {
		$sortorder = "family.MotherName, family.FamilyCode";
	} elseif ($_GET['sort'] == '7') {
		$sortorder = "family.MotherName DESC, family.FamilyCode DESC";*/
	} else {
		$sortorder = "FamilyCode";
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
	/*$dadnameAsc = dbfuncGetButton(
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
								"Sort descending");*/
	
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
	$query =		"SELECT user.FirstName, user.Surname, user.Title, user.Username, user.ActiveStudent, " .
					"       familylist.Guardian, familyinfo.*, class.ClassName FROM " .
					"	(SELECT family.FamilyCode, family.FamilyName FROM " .
					"       family LEFT OUTER JOIN " .
					"            (familylist AS familylist2 INNER JOIN user AS user2 USING (Username)) USING (FamilyCode) ";
	if(!$show_all) {
		$query .=	"	 WHERE (user2.ActiveStudent=1 OR familylist2.FamilyCode IS NULL) ";
	}
	$query .=	"    GROUP BY family.FamilyCode) AS familyinfo " . 
				"   LEFT OUTER JOIN (familylist INNER JOIN user USING (Username) " . 
				"          LEFT OUTER JOIN (class INNER JOIN classterm " .
				"               ON (class.YearIndex=12 AND classterm.ClassIndex=class.ClassIndex) " .
				"          INNER JOIN currentterm ON classterm.TermIndex=currentterm.TermIndex " .
				"          INNER JOIN classlist USING (ClassTermIndex)) ON classlist.Username=user.Username) " .
				"   USING (FamilyCode) " .
				"ORDER BY $sortorder, Guardian DESC, IF(Guardian=1, user.Gender, Guardian) DESC, " .
				"         class.Grade DESC, user.Username";
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
		echo "            <th>Guardians</th>\n";
		echo "            <th>Students</th>\n";		
		echo "         </tr>\n";
		
		/* For each family, print a row with the family code and other information */
		$alt_count = 0;
		$row = NULL;
		$prev_family = NULL;
		$prev_guardian = NULL;
		
		while ( true ) {
			if($next_row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				if(is_null($row)) {
					$row = $next_row;
					continue;
				}
			}
			
			if($row['FamilyCode'] != $prev_family) {
				$prev_family = $row['FamilyCode'];
			
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
				
				$fcode = htmlspecialchars($row['FamilyCode']);
				$fname = htmlspecialchars($row['FamilyName']);
				$row['FamilyCode'] = safe($row['FamilyCode']);
				echo "            <td>$editbutton</td>\n";
				echo "            <td>$fcode</td>\n";
				echo "            <td>$fname</td>\n";
			}
			if($row['Guardian'] != $prev_guardian) {
				$prev_guardian = $row['Guardian'];
				echo "            <td>\n";
			}
			
			if($row['Guardian'] == 1) {
				$who = "guardian";
			} else {
				$who = "student";
			}
			
			$viewlink = "index.php?location=" .
					dbfuncString2Int("admin/subject/list_student.php") .
					"&amp;key=" . dbfuncString2Int($row['Username']) .
					"&amp;keyname=" .
					dbfuncString2Int(
						"{$row['FirstName']} {$row['Surname']} ({$row['Username']})");
			$editlink = "index.php?location=" .
					dbfuncString2Int("admin/user/modify.php") . "&amp;key=" .
					dbfuncString2Int($row['Username']) . "&amp;keyname=" .
					dbfuncString2Int(
						"{$row['FirstName']} {$row['Surname']} ({$row['Username']})");
			$cnlink = "index.php?location=" .
					dbfuncString2Int("teacher/casenote/list.php") . "&amp;key=" .
					dbfuncString2Int($row['Username']) . "&amp;keyname=" .
					dbfuncString2Int(
						"{$row['FirstName']} {$row['Surname']} ({$row['Username']})") .
			 		"&amp;keyname2=" . dbfuncSTring2Int($row['FirstName']);
			if($row['ActiveStudent'] == 1 && $row['Guardian'] == 0) {
				$viewbutton = dbfuncGetButton($viewlink, "V", "small", "view",
						"View $who's subjects");
			} else {
				$viewbutton = "";
			}
			$editbutton = dbfuncGetButton($editlink, "E", "small", "edit",
					"Edit $who");

			echo "$viewbutton $editbutton";
			if($row['ActiveStudent'] == 1) {
				echo "<strong>";
			}
			if($row['ActiveTeacher'] == 1 || $row['Guardian'] == 1) {
				echo "<em>";
				if(isset($row['Title']) and $row['Title'] != "") {
					echo "{$row['Title']} ";
				}
			}
			echo "{$row['FirstName']} {$row['Surname']} ({$row['Username']})";
			if($row['ActiveStudent'] == 1) {
				echo " - {$row['ClassName']}";
			}
			if($row['ActiveTeacher'] == 1 || $row['Guardian'] == 1) {
				echo "</em>";
			}
			if($row['ActiveStudent'] == 1) {
				echo "</strong>";
			}
			echo "<br />\n";

			if($next_row['FamilyCode'] != $prev_family) {
				if($prev_guardian != 0) {
					echo "<td>&nbsp;</td>\n";
				}
				$prev_guardian = NULL;
				echo "         </tr>\n";
			}
			if($next_row['Guardian'] != $prev_guardian) {
				echo "            </td>\n";
			}
				
			$row = $next_row;
			if(is_null($row))
				break;
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