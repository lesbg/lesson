<?php
	/*****************************************************************
	 * teacher/book/copy_history.php  (c) 2010 Jonathan Dieter
	 *
	 * Show history of a copy of a book
	 *****************************************************************/

	$title           = dbfuncInt2String($_GET['keyname']);
	$bookindex       = dbfuncInt2String($_GET['key']);	

	include "header.php";

	$query =	"SELECT book_title_owner.Username FROM book_title_owner, book " .
				"WHERE book_title_owner.BookTitleIndex=book.BookTitleIndex " .
				"AND   book.BookIndex = $bookindex " .
				"AND   book_title_owner.Username='$username'";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());

	if($is_admin or $res->numRows() > 0) {
		$query =	"SELECT in_book_state.BookState AS InBookState, book_status.InDate, " .
					"       out_book_state.BookState AS OutBookState, book_status.OutDate, " .
					"       user.FirstName, user.Surname, user.Username, book_status.Comment, " .
					"       t1user.Title AS t1Title, t1user.FirstName AS t1FirstName, t1user.Surname AS t1Surname, t1user.Username AS t1Username, " .
					"       t2user.Title AS t2Title, t2user.FirstName AS t2FirstName, t2user.Surname AS t2Surname, t2user.Username AS t2Username " .
					"       FROM user, user AS t1user, book, book_status " .
					"            INNER JOIN book_state AS out_book_state ON (book_status.OutState = out_book_state.BookStateIndex) " .
					"            LEFT OUTER JOIN user AS t2user ON (book_status.InTeacherUsername = t2user.Username) " .
					"            LEFT OUTER JOIN book_state AS in_book_state ON (book_status.InState = in_book_state.BookStateIndex) " . 
					"WHERE book_status.BookIndex = $bookindex " .
					"AND   book_status.Username = user.Username " .
					"AND   book_status.OutTeacherUsername = t1user.Username " .
					"AND   book.BookIndex = $bookindex " .
					"ORDER BY book_status.InState IS NOT NULL, book_status.OutDate DESC, " .
					"         book_status.BookStatusIndex DESC";

		$res =& $db->query($query);
		if(DB::isError($res)) die($res->getDebugInfo());

		if($res->numRows() > 0) {
			echo "      <table align='center' border='1'>\n"; // Table headers
			echo "         <tr>\n";
			echo "            <th>Student</th>\n";
			echo "            <th>&nbsp;</th>\n";
			echo "            <th>Date</th>\n";
			echo "            <th>State</th>\n";
			echo "            <th>Checked by</th>\n";
			echo "            <th>Comment</th>\n";
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
				$viewlink = "index.php?location=" .  dbfuncString2Int("admin/book/copy_history.php") .
							"&amp;key=" .            dbfuncString2Int($row['BookIndex']) .
							"&amp;keyname=" .        dbfuncString2Int($booktitle . " copy #" . $row['BookNumber']) .
							"&amp;next=" .           dbfuncString2Int("index.php?location=" . dbfuncString2Int("teacher/book/copy_list.php") .
																	  "&amp;key=" . $_GET['key'] .
																	  "&amp;keyname=" . $_GET['keyname'] .
																	  "&amp;key2=" . $_GET['key2']);
				$editlink = "index.php?location=" .  dbfuncString2Int("admin/book/modify_copy.php") .
							"&amp;key=" .            dbfuncString2Int($row['BookIndex']) .
							"&amp;keyname=" .        dbfuncString2Int($booktitle . " copy #" . $row['BookNumber']) .
							"&amp;next=" .           dbfuncString2Int("index.php?location=" . dbfuncString2Int("teacher/book/copy_list.php") .
																	  "&amp;key=" . $_GET['key'] .
																	  "&amp;keyname=" . $_GET['keyname'] .
																	  "&amp;key2=" . $_GET['key2']);
				$retlink =  "index.php?location=" .  dbfuncString2Int("admin/book/retire_copy_confirm.php") .
							"&amp;key=" .            dbfuncString2Int($row['BookIndex']) .
							"&amp;keyname=" .        dbfuncString2Int($booktitle . " copy #" . $row['BookNumber']) .
							"&amp;next=" .           dbfuncString2Int("index.php?location=" . dbfuncString2Int("teacher/book/copy_list.php") .
																	  "&amp;key=" . $_GET['key'] .
																	  "&amp;keyname=" . $_GET['keyname'] .
																	  "&amp;key2=" . $_GET['key2']);
				$unrlink =  "index.php?location=" .  dbfuncString2Int("admin/book/unretire_copy_confirm.php") .
							"&amp;key=" .            dbfuncString2Int($row['BookIndex']) .
							"&amp;keyname=" .        dbfuncString2Int($booktitle . " copy #" . $row['BookNumber']) .
							"&amp;next=" .           dbfuncString2Int("index.php?location=" . dbfuncString2Int("teacher/book/copy_list.php") .
																	  "&amp;key=" . $_GET['key'] .
																	  "&amp;keyname=" . $_GET['keyname'] .
																	  "&amp;key2=" . $_GET['key2']);

				echo "         <tr$alt>\n";
				echo "            <td rowspan='2'>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
				$outdate = date($dateformat, strtotime($row['OutDate']));
				echo "            <td><b>Out</b></td>\n";
				echo "            <td>$outdate</td>";
				echo "            <td>{$row['OutBookState']}</td>\n";
				echo "            <td>{$row['t1Title']} {$row['t1FirstName']} {$row['t1Surname']}</td>\n";
				$comment = htmlspecialchars($row['Comment'], ENT_QUOTES);
				echo "            <td rowspan='2'>$comment</td>\n";
				echo "         </tr>\n";
				echo "         <tr$alt>\n";
				echo "            <td><b>In</b></td>\n";
				if(is_null($row['InBookState'])) {
					echo "            <td colspan='3' align='center'><i>Still checked out</i></td>\n";
				} else {
					$indate = date($dateformat, strtotime($row['InDate']));
					echo "            <td>$indate</td>";
					echo "            <td>{$row['InBookState']}</td>\n";
					echo "            <td>{$row['t2Title']} {$row['t2FirstName']} {$row['t2Surname']}</td>\n";
				}
				
			}
			echo "      </table>\n";               // End of table
		} else {
			echo "      <p>This copy has never been checked out</p>\n";
		}
		log_event($LOG_LEVEL_EVERYTHING, "teacher/book/copy_history.php", $LOG_ADMIN,
				"Viewed history of $title.");
	} else {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "teacher/book/copy_history.php", $LOG_DENIED_ACCESS,
				"Attempted to view history of $title.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
	}
	
	include "footer.php";
?>