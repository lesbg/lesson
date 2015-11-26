<?php
/**
 * ***************************************************************
 * admin/family/list.php (c) 2015 Jonathan Dieter
 *
 * List all family codes
 * ***************************************************************
 */
$title = "Family List";

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
								 "&amp;sort=0", "A", "small", "sort", 
								"Sort ascending");
	$fcodeDec = dbfuncGetButton(
								"index.php?location=" .
								 dbfuncString2Int("admin/family/list.php") .
								 "&amp;sort=1", "D", "small", "sort", 
								"Sort descending");
	$fnameAsc = dbfuncGetButton(
								"index.php?location=" .
								 dbfuncString2Int("admin/family/list.php") .
								 "&amp;sort=4", "A", "small", "sort", 
								"Sort ascending");
	$fnameDec = dbfuncGetButton(
								"index.php?location=" .
								 dbfuncString2Int("admin/family/list.php") .
								 "&amp;sort=5", "D", "small", "sort", 
								"Sort descending");
	$dadnameAsc = dbfuncGetButton(
								"index.php?location=" .
								 dbfuncString2Int("admin/family/list.php") .
								 "&amp;sort=6", "A", "small", "sort", 
								"Sort ascending");
	$dadnameDec = dbfuncGetButton(
								"index.php?location=" .
								 dbfuncString2Int("admin/family/list.php") .
								 "&amp;sort=7", "D", "small", "sort", 
								"Sort descending");
	$momnameAsc = dbfuncGetButton(
								"index.php?location=" .
								 dbfuncString2Int("admin/family/list.php") .
								 "&amp;sort=2", "A", "small", "sort", 
								"Sort ascending");
	$momnameDec = dbfuncGetButton(
								"index.php?location=" .
								 dbfuncString2Int("admin/family/list.php") .
								 "&amp;sort=3", "D", "small", "sort", 
								"Sort descending");
	
	$newlink = "index.php?location=" .
			dbfuncString2Int("admin/family/new.php") . // link to create a new subject
			"&amp;next=" .
			dbfuncString2Int(
					"index.php?location=" .
					dbfuncString2Int("admin/family/list.php"));
	$newbutton = dbfuncGetButton($newlink, "New family", "medium", "",
			"Create new subject");
	echo "      <p align=\"center\">$newbutton</p>\n";
	
	/* Get student list */
	$query = "SELECT family.FamilyCode, family.FamilyName, family.FatherName, family.MotherName " .
		     "       FROM family";
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
		echo "            <th>Father&apos;s Name $dadnameAsc $dadnameDec</th>\n";
		echo "            <th>Mother&apos;s Name $momnameAsc $momnameDec</th>\n";
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
			echo "            <td>{$row['FatherName']}</td>\n";
			echo "            <td>{$row['MotherName']}</td>\n";
			echo "            <td>&nbsp;</td>\n";
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