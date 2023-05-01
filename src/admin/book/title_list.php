<?php
/**
 * ***************************************************************
 * admin/book/title_list.php (c) 2010-2013 Jonathan Dieter
 *
 * List all available book titles
 * ***************************************************************
 */
$title = "Book Title List";

include "header.php";

if ($is_admin) {
	/* Get category list */
	$query = "SELECT book_title.BookTitle, book_title.BookTitleIndex, book_title.Cost, " .
			 "       GROUP_CONCAT(DISTINCT subjecttype.Title ORDER BY subjecttype.Title SEPARATOR ', ') AS SubjectType, " .
			 "       CONCAT_WS(', ', GROUP_CONCAT(DISTINCT grade.GradeName ORDER BY grade.Grade SEPARATOR ', '), " .
			 "                       GROUP_CONCAT(DISTINCT class.ClassName ORDER BY class.Grade, class.ClassName SEPARATOR ', ')) AS Classes, " .
			 "       COUNT(book.BookTitleIndex) AS Count " .
			 "       FROM book_title LEFT OUTER JOIN book USING (BookTitleIndex) " .
			 "            LEFT OUTER JOIN book_subject_type ON (book_subject_type.BookTitleIndex = book_title.BookTitleIndex) " .
			 "            LEFT OUTER JOIN subjecttype USING (SubjectTypeIndex) " .
			 "            LEFT OUTER JOIN book_class ON (book_class.BookTitleIndex = book_title.BookTitleIndex) " .
			 "            LEFT OUTER JOIN class ON " .
			 "              (class.ClassName = book_class.ClassName " .
			 "               AND class.YearIndex = $yearindex) " .
			 "            LEFT OUTER JOIN grade ON (book_class.Grade = grade.Grade) " .
			 "GROUP BY book_title.BookTitleIndex " .
			 "ORDER BY book_title.BookTitle, book_title.BookTitleIndex";
	$res = & $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo());
	
	$newlink = "index.php?location=" .
			 dbfuncString2Int("admin/book/new_title.php") . "&amp;next=" . dbfuncString2Int(
																							"index.php?location=" .
																							 dbfuncString2Int(
																											"admin/book/title_list.php"));
	$newbutton = dbfuncGetButton($newlink, "New title", "medium", "", 
								"Create new title");
	echo "      <p align=\"center\">$newbutton</p>\n";
	
	if ($res->numRows() > 0) {
		echo "      <table align='center' border='1'>\n"; // Table headers
		echo "         <tr>\n";
		echo "            <th>&nbsp;</th>\n";
		echo "            <th>ID</th>\n";
		echo "            <th>Title</th>\n";
		echo "            <th>Subjects</th>\n";
		echo "            <th>Classes</th>\n";
		echo "            <th>Cost</th>\n";
		echo "            <th>Book Count</th>\n";
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
			$viewlink = "index.php?location=" .
						 dbfuncString2Int("teacher/book/copy_list.php") .
						 "&amp;key=" . dbfuncString2Int($row['BookTitleIndex']) .
						 "&amp;keyname=" . dbfuncString2Int($row['BookTitle']) .
						 "&amp;next=" . dbfuncString2Int(
														"index.php?location=" . dbfuncString2Int(
																								"admin/book/title_list.php"));
			$dellink = "index.php?location=" .
					 dbfuncString2Int("admin/book/delete_title_confirm.php") .
					 "&amp;key=" . dbfuncString2Int($row['BookTitleIndex']) .
					 "&amp;keyname=" . dbfuncString2Int($row['BookTitle']) .
					 "&amp;next=" . dbfuncString2Int(
													"index.php?location=" . dbfuncString2Int(
																							"admin/book/title_list.php"));
			$editlink = "index.php?location=" .
						 dbfuncString2Int("admin/book/modify_title.php") .
						 "&amp;key=" . dbfuncString2Int($row['BookTitleIndex']) .
						 "&amp;keyname=" . dbfuncString2Int($row['BookTitle']) .
						 "&amp;next=" . dbfuncString2Int(
														"index.php?location=" . dbfuncString2Int(
																								"admin/book/title_list.php"));
			
			echo "         <tr$alt>\n";
			/* Generate view and edit buttons */
			$viewbutton = dbfuncGetButton($viewlink, "V", "small", "view", 
										"View copies of this title");
			$editbutton = dbfuncGetButton($editlink, "E", "small", "edit", 
										"Edit title");
			$delbutton = dbfuncGetButton($dellink, "X", "small", "delete", 
										"Delete title");
			echo "            <td>$viewbutton $editbutton</td>\n";
			echo "            <td>{$row['BookTitleIndex']}</td>\n";
			echo "            <td>{$row['BookTitle']}</td>\n";
			echo "            <td>{$row['SubjectType']}</td>\n";
			echo "            <td>{$row['Classes']}</td>\n";
			if (is_null($row['Cost'])) {
				echo "            <td align='center'>-</td>\n";
			} else {
				echo "            <td align='right'>\${$row['Cost']}</td>\n";
			}
			echo "            <td>{$row['Count']}</td>\n";
			echo "            <td align='center'>$delbutton</td>\n";
		}
		echo "      </table>\n"; // End of table
	} else {
		echo "      <p>No titles have been set up.</p>\n";
	}
	log_event($LOG_LEVEL_EVERYTHING, "admin/book/title_list.php", $LOG_ADMIN, 
			"Viewed book titles.");
} else {
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "admin/book/title_list.php", $LOG_DENIED_ACCESS, 
			"Attempted to view book titles.");
	
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
?>