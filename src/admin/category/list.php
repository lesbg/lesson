<?php
/**
 * ***************************************************************
 * admin/category/list.php (c) 2007 Jonathan Dieter
 *
 * List all available category types
 * ***************************************************************
 */
$title = "Category List";

include "header.php";

if ($is_admin) {
	/* Get category list */
	$query = "SELECT category.CategoryIndex, category.CategoryName, " .
			 "       COUNT(categorylist.CategoryListIndex) AS Count" .
			 "       FROM category LEFT OUTER JOIN categorylist USING (CategoryIndex) " .
			 "GROUP BY category.CategoryIndex " .
			 "ORDER BY category.CategoryName, category.CategoryIndex";
	$res = & $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo());
	
	$newlink = "index.php?location=" . dbfuncString2Int(
														"admin/category/new.php") .
			 "&amp;next=" . dbfuncString2Int(
											"index.php?location=" .
											 dbfuncString2Int(
															"admin/category/list.php"));
	$newbutton = dbfuncGetButton($newlink, "New category type", "medium", "", 
								"Create new category type");
	echo "      <p align=\"center\">$newbutton</p>\n";
	
	if ($res->numRows() > 0) {
		echo "      <table align='center' border='1'>\n"; // Table headers
		echo "         <tr>\n";
		echo "            <th>&nbsp;</th>\n";
		echo "            <th>Category Type</th>\n";
		echo "            <th>Subject Count</th>\n";
		echo "            <th>Enabled For</th>\n";
		echo "            <th>Delete</th>\n";
		echo "         </tr>\n";
		
		/*
		 * For each category, print a row with the category's name, # of subjects using it
		 * and the subject types it's enabled for
		 */
		$alt_count = 0;
		while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
			$alt_count += 1;
			if ($alt_count % 2 == 0) {
				$alt = " class='alt'";
			} else {
				$alt = " class='std'";
			}
			/*
			 * $viewlink = "index.php?location=" . dbfuncString2Int("admin/category/list_subjects php") .
			 * "&amp;key=" . dbfuncString2Int($row['CategoryIndex']) .
			 * "&amp;keyname=" . dbfuncString2Int($row['CategoryName']) .
			 * "&amp;next=" . dbfuncString2Int("index.php?location=" . dbfuncString2Int("admin/category/list.php"));
			 */
			$viewlink = "";
			$dellink = "index.php?location=" .
					 dbfuncString2Int("admin/category/delete_confirm.php") .
					 "&amp;key=" . dbfuncString2Int($row['CategoryIndex']) .
					 "&amp;keyname=" . dbfuncString2Int($row['CategoryName']) .
					 "&amp;next=" . dbfuncString2Int(
													"index.php?location=" .
													 dbfuncString2Int(
																	"admin/category/list.php"));
			$editlink = "index.php?location=" .
						 dbfuncString2Int("admin/category/modify.php") .
						 "&amp;key=" . dbfuncString2Int($row['CategoryIndex']) .
						 "&amp;keyname=" . dbfuncString2Int(
															$row['CategoryName']) .
						 "&amp;next=" . dbfuncString2Int(
														"index.php?location=" . dbfuncString2Int(
																								"admin/category/list.php"));
			
			echo "         <tr$alt>\n";
			/* Generate view and edit buttons */
			$viewbutton = dbfuncGetButton($viewlink, "V", "small", "view", 
										"View subjects using this category");
			$editbutton = dbfuncGetButton($editlink, "E", "small", "edit", 
										"Edit category");
			$delbutton = dbfuncGetButton($dellink, "X", "small", "delete", 
										"Delete category");
			echo "            <td>$editbutton</td>\n";
			echo "            <td>{$row['CategoryName']}</td>\n";
			echo "            <td>{$row['Count']}</td>\n";
			
			$query = "SELECT subjecttype.Title " .
					 "       FROM categorytype LEFT OUTER JOIN subjecttype USING (SubjectTypeIndex) " .
					 "WHERE categorytype.CategoryIndex = {$row['CategoryIndex']} " .
					 "ORDER BY subjecttype.Title, subjecttype.SubjectTypeIndex ";
			$aRes = & $db->query($query);
			if (DB::isError($aRes))
				die($aRes->getDebugInfo());
			
			if ($aRow = & $aRes->fetchRow(DB_FETCHMODE_ASSOC)) {
				if (is_null($aRow['Title']))
					$aRow['Title'] = "<i>All subjects</i>";
				echo "            <td>{$aRow['Title']}";
				while ( $aRow = & $aRes->fetchRow(DB_FETCHMODE_ASSOC) ) {
					echo ", {$aRow['Title']}";
				}
				echo "</td>\n";
			}
			echo "            <td align='center'>$delbutton</td>\n";
		}
		echo "      </table>\n"; // End of table
	} else {
		echo "      <p>No category types have been set up.</p>\n";
	}
	log_event($LOG_LEVEL_EVERYTHING, "admin/category/list.php", $LOG_ADMIN, 
			"Viewed category types.");
} else {
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "admin/category/list.php", $LOG_DENIED_ACCESS, 
			"Attempted to view category types.");
	
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
?>