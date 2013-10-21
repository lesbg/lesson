<?php
	/*****************************************************************
	 * teacher/book/copy_list.php  (c) 2010 Jonathan Dieter
	 *
	 * List copies of a book
	 *****************************************************************/

	$booktitle       = dbfuncInt2String($_GET['keyname']);
	$title           = "Copies of $booktitle";
	$booktitleindex  = dbfuncInt2String($_GET['key']);
	if(!isset($_GET['key2'])) {
		$_GET['key2'] = dbfuncString2Int("0");
	}
	if(!isset($_GET['key3'])) {
		$_GET['key3'] = dbfuncString2Int("-1");
	}
	$showall     = intval(dbfuncInt2String($_GET['key2']));
	$classindex  = intval(dbfuncInt2String($_GET['key3']));

	include "header.php";

	$query =	"SELECT Username FROM book_title_owner " .
				"WHERE BookTitleIndex='$booktitleindex' " .
				"AND   Username='$username'";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());

	if(!$is_admin and $res->numRows() == 0) {
		/* Log unauthorized access attempt */
		log_event($LOG_LEVEL_ERROR, "teacher/book/copy_list.php", $LOG_DENIED_ACCESS,
				"Attempted to view book titles.");
		
		echo "      <p>You do not have permission to access this page</p>\n";
		echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
		
		include "footer.php";
		exit(0);
	}
	
	/* Get category list */

	$query =	"SELECT BookState FROM book_state ORDER BY BookStateIndex DESC LIMIT 1";
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());

	if ($row =& $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$best_status = $row['BookState'];
	} else {
		$best_status = "New";
	}
	
	$newlink =  "index.php?location=" .  dbfuncString2Int("teacher/book/new_copy.php") .
				"&amp;key=" .            $_GET['key'] .
				"&amp;keyname=" .        $_GET['keyname'] .
				"&amp;next=" .           dbfuncString2Int("index.php?location=" . dbfuncString2Int("teacher/book/copy_list.php") .
														  "&amp;key=" . $_GET['key'] .
														  "&amp;keyname=" . $_GET['keyname'] .
														  "&amp;key2=" . $_GET['key2'] . 
														  "&amp;key3=" . $_GET['key3']);
	$newbutton = dbfuncGetButton($newlink, "New copy", "medium", "", "Add new copy of this book");
	if($showall) {
		$salink =	"index.php?location=" . dbfuncString2Int("teacher/book/copy_list.php") .
					"&amp;key=" . $_GET['key'] .
					"&amp;keyname=" . $_GET['keyname'] .
					"&amp;key2=" . dbfuncString2Int("0") .
					"&amp;key3=" . $_GET['key3'];
		$sabutton = dbfuncGetButton($salink, "Show active copies", "medium", "", "Show only active copies and not retired copies");
	} else {
		$salink =	"index.php?location=" . dbfuncString2Int("teacher/book/copy_list.php") .
					"&amp;key=" . $_GET['key'] .
					"&amp;keyname=" . $_GET['keyname'] .
					"&amp;key2=" . dbfuncString2Int("1") .
					"&amp;key3=" . $_GET['key3'];
		$sabutton = dbfuncGetButton($salink, "Show all copies", "medium", "", "Show all copies, including retired ones");
	}
	echo "      <p align='center'>$newbutton $sabutton</p>\n";

	$query =	"SELECT * FROM (" .
				" SELECT book.BookIndex, book.BookNumber, book.PurchaseDate, book.Retired, book_state.BookState AS BookState, book_status.Date, " .
				"        book_status.Comment, book_status.BookStatusTypeIndex, book_status_type.BookStatusType, " .
				"        user.FirstName, user.Surname, user.Username, " .
				"        tuser.Title AS TeacherTitle, tuser.FirstName AS TeacherFirstName, " .
				"        tuser.Surname AS TeacherSurname, tuser.Username AS TeacherUsername " .
				" FROM book LEFT OUTER JOIN" .
				"      (book_status LEFT OUTER JOIN user USING (Username) " .
				"                   LEFT OUTER JOIN user AS tuser ON (book_status.TeacherUsername = tuser.Username) " .
				"                   LEFT OUTER JOIN book_state ON (book_status.State = book_state.BookStateIndex) " .
				"					INNER JOIN book_status_type USING (BookStatusTypeIndex) " .
				"      ) USING (BookIndex) " .
				" WHERE book.BookTitleIndex = '$booktitleindex' ";
	if(!$showall) {
		$query .= " AND book.Retired=0 ";
	}
	$query .=	" ORDER BY book.BookNumber, book.BookIndex, book_status.Order DESC " .
				") AS book_list_sorted GROUP BY BookIndex ";
	
	$res =& $db->query($query);
	if(DB::isError($res)) die($res->getDebugInfo());

	if($res->numRows() > 0) {
		echo "      <table align='center' border='1'>\n"; // Table headers
		echo "         <tr>\n";
		echo "            <th>&nbsp;</th>\n";
		echo "            <th>Number</th>\n";
		echo "            <th>Current State</th>\n";
		echo "            <th>Student</th>\n";
		echo "            <th>Checkout Date</th>\n";
		echo "            <th>Teacher</th>\n";
		echo "            <th>Comment</th>\n";
		if($showall) {
			echo "            <th>Retired</th>\n";
		}
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
			$viewlink = "index.php?location=" .  dbfuncString2Int("teacher/book/copy_history.php") .
						"&amp;key=" .            dbfuncString2Int($row['BookIndex']) .
						"&amp;keyname=" .        dbfuncString2Int($booktitle . " copy #" . $row['BookNumber']) .
						"&amp;next=" .           dbfuncString2Int("index.php?location=" . dbfuncString2Int("teacher/book/copy_list.php") .
																  "&amp;key=" . $_GET['key'] .
																  "&amp;keyname=" . $_GET['keyname'] .
																  "&amp;key2=" . $_GET['key2'] .
																  "&amp;key3=" . $_GET['key3']);
			$retlink =  "index.php?location=" .  dbfuncString2Int("teacher/book/retire_copy_confirm.php") .
						"&amp;key=" .            dbfuncString2Int($row['BookIndex']) .
						"&amp;keyname=" .        dbfuncString2Int($booktitle . " copy #" . $row['BookNumber']) .
						"&amp;next=" .           dbfuncString2Int("index.php?location=" . dbfuncString2Int("teacher/book/copy_list.php") .
																  "&amp;key=" . $_GET['key'] .
																  "&amp;keyname=" . $_GET['keyname'] .
																  "&amp;key2=" . $_GET['key2'] .
																  "&amp;key3=" . $_GET['key3']);
			$unrlink =	"index.php?location=" .  dbfuncString2Int("teacher/book/unretire_copy_confirm.php") .
						"&amp;key=" .            dbfuncString2Int($row['BookIndex']) .
						"&amp;keyname=" .        dbfuncString2Int($booktitle . " copy #" . $row['BookNumber']) .
						"&amp;next=" .           dbfuncString2Int("index.php?location=" . dbfuncString2Int("teacher/book/copy_list.php") .
																  "&amp;key=" . $_GET['key'] .
																  "&amp;keyname=" . $_GET['keyname'] .
																  "&amp;key2=" . $_GET['key2'] .
																  "&amp;key3=" . $_GET['key3']);
			$colink =	"index.php?location=" .  dbfuncString2Int("teacher/book/check_in_out_copy.php") .
						"&amp;key=" .            dbfuncString2Int($row['BookIndex']) .
						"&amp;keyname=" .        dbfuncString2Int($booktitle . " copy #" . $row['BookNumber']) .
						"&amp;key2=" .           dbfuncString2Int(2) .
						"&amp;key3=" .           $_GET['key3'] .
						"&amp;next=" .           dbfuncString2Int("index.php?location=" . dbfuncString2Int("teacher/book/copy_list.php") .
																  "&amp;key=" . $_GET['key'] .
																  "&amp;keyname=" . $_GET['keyname'] .
																  "&amp;key2=" . $_GET['key2'] .
																  "&amp;key3=" . $_GET['key3']);
			$cilink =	"index.php?location=" .  dbfuncString2Int("teacher/book/check_in_out_copy.php") .
						"&amp;key=" .            dbfuncString2Int($row['BookIndex']) .
						"&amp;keyname=" .        dbfuncString2Int($booktitle . " copy #" . $row['BookNumber']) .
						"&amp;key2=" .           dbfuncString2Int(1) .
						"&amp;key3=" .           $_GET['key3'] .
						"&amp;next=" .           dbfuncString2Int("index.php?location=" . dbfuncString2Int("teacher/book/copy_list.php") .
																  "&amp;key=" . $_GET['key'] .
																  "&amp;keyname=" . $_GET['keyname'] .
																  "&amp;key2=" . $_GET['key2'] .
																  "&amp;key3=" . $_GET['key3']);
			echo "         <tr$alt>\n";
			/* Generate view and edit buttons */
			$cobutton   = dbfuncGetButton($colink,   "O", "small", "cn", "Check out copy");
			$cibutton   = dbfuncGetButton($cilink,   "I", "small", "msg", "Check in copy");
			$viewbutton = dbfuncGetButton($viewlink, "V", "small", "view", "View history of this copy");
			$retbutton  = dbfuncGetButton($retlink,  "R", "small", "delete", "Retire copy");
			$unrbutton  = dbfuncGetButton($unrlink,  "U", "small", "msg", "Unretire copy");
			echo "            <td>";
			if($row['Retired']) {
				echo "&nbsp;&nbsp;";
			} else {
				if($row['BookStatusTypeIndex'] == 2) {
					echo "$cibutton ";
				} else {
					echo "$cobutton ";
				}
			}
			echo "$viewbutton ";
			if($row['Retired']) {
				echo "$unrbutton";
			} else {
				if(!is_null($row['BookStatusTypeIndex'])) {
					/*echo "$retbutton";*/
				}
			}
			echo "</td>\n"; 
			echo "            <td>{$row['BookNumber']}</td>\n";
			if(is_null($row['BookStatusTypeIndex'])) { // Brand new book with no status
				echo "            <td>$best_status</td>\n";
				echo "            <td colspan='4' align='center'><i>Never been checked out</i></td>\n";
			} elseif($row['BookStatusTypeIndex'] == 3 or $row['BookStatusTypeIndex'] == 1) {
				if($row['BookStatusTypeIndex'] == 3) { // Initial purchase
					if(is_null($row['Comment'])) {
						$row['Comment'] = "<i>Never been checked out</i>";
					}
					if(is_null($row['BookState'])) {
						$row['BookState'] = $best_status;
					}
				}
				echo "            <td>{$row['BookState']}</td>\n";
				echo "            <td colspan='2' align='center'>Not checked out</td>\n";
				echo "            <td>&nbsp;&nbsp;{$row['TeacherTitle']} {$row['TeacherFirstName']} {$row['TeacherSurname']}&nbsp;&nbsp;</td>\n";
				$comment = htmlspecialchars($row['Comment'], ENT_QUOTES);
				echo "            <td>$comment</td>\n";
			} elseif($row['BookStatusTypeIndex'] == 2) {
				echo "            <td>{$row['BookState']}</td>\n";
				echo "            <td>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
				$date = date($dateformat, strtotime($row['Date']));
				echo "            <td>$date</td>\n";
				echo "            <td>&nbsp;&nbsp;{$row['TeacherTitle']} {$row['TeacherFirstName']} {$row['TeacherSurname']}&nbsp;&nbsp;</td>\n";
				$comment = htmlspecialchars($row['Comment'], ENT_QUOTES);
				echo "            <td>$comment</td>\n";				
			} else {
				echo "            <td colspan='5'><strong>Unknown status!</strong></td>\n";
			}
			if($showall) {
				if($row['Retired']) {
					echo "            <td align='center'>X</td>\n";
				} else {
					echo "            <td>&nbsp;</td>\n";
				}
			}
			echo "         </tr>\n";
		}
		echo "      </table>\n";               // End of table
	} else {
		echo "      <p align='center'>You need to add a new copy of the book.</p>\n";
	}
	log_event($LOG_LEVEL_EVERYTHING, "teacher/book/copy_list.php", $LOG_ADMIN,
			"Viewed book titles.");
	
	include "footer.php";
?>