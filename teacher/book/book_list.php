<?php
	/*****************************************************************
	 * teacher/book/book_list.php  (c) 2010 Jonathan Dieter
	 *
	 * List my books
	 *****************************************************************/

	$checkuser = safe(dbfuncInt2String($_GET['key']));
	$name = dbfuncInt2String($_GET['keyname']);
	
	$title = "$name's Books";
		
	include "header.php";

	if($is_admin or $username == $checkuser) {
		/* Get category list */
		$query =	"SELECT book_title.BookTitle, book_title.BookTitleIndex, book_title.Cost, " .
					"       COUNT(book.BookTitleIndex) AS Count " .
					"       FROM book_title LEFT OUTER JOIN book USING (BookTitleIndex), " .
					"       book_title_owner " .
					"WHERE book_title_owner.BookTitleIndex = book_title.BookTitleIndex " .
					"AND   book_title_owner.YearIndex = $yearindex " .
					"AND   book_title_owner.Username = '$checkuser' " .
					"GROUP BY book_title.BookTitleIndex " .
					"ORDER BY book_title.BookTitle, book_title.BookTitleIndex";
		$res =& $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());

		if($res->numRows() > 0) {
			echo "      <table align='center' border='1'>\n"; // Table headers
			echo "         <tr>\n";
			echo "            <th>&nbsp;</th>\n";
			echo "            <th>Title</th>\n";
			echo "            <th>ID</th>\n";
			echo "            <th>Book Count</th>\n";
			echo "         </tr>\n";
			
			/* For each category, print a row with the category's name, # of subjects using it
			   and the subject types it's enabled for */
			$alt_count = 0;
			while ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$alt_count += 1;
				if($alt_count % 2 == 0) {
					$alt = " class='alt'";
				} else {
					$alt = " class='std'";
				}
				$viewlink = "index.php?location=" .  dbfuncString2Int("teacher/book/copy_list.php") .
							"&amp;key=" .            dbfuncString2Int($row['BookTitleIndex']) .
							"&amp;keyname=" .        dbfuncString2Int($row['BookTitle']) .
							"&amp;next=" .           dbfuncString2Int("index.php?location=" . dbfuncString2Int("teacher/book/book_list.php"));

				echo "         <tr$alt>\n";
				/* Generate view and edit buttons */
				$viewbutton = dbfuncGetButton($viewlink, "V", "small", "view", "View copies of this title");
				echo "            <td>$viewbutton</td>\n"; 
				echo "            <td>{$row['BookTitle']}</td>\n";
				echo "            <td>{$row['BookTitleIndex']}</td>\n";
				echo "            <td>{$row['Count']}</td>\n";
			}
			echo "      </table>\n";               // End of table
		} else {
			echo "      <p>No titles have been set up.</p>\n";
		}
		log_event($LOG_LEVEL_EVERYTHING, "teacher/book/book_list.php", $LOG_ADMIN,
				"Viewed book titles.");
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "teacher/book/book_list.php", $LOG_DENIED_ACCESS,
				"Attempted to view book titles.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>