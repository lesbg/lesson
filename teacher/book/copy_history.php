<?php
/**
 * ***************************************************************
 * teacher/book/copy_history.php (c) 2010 Jonathan Dieter
 *
 * Show history of a copy of a book
 * ***************************************************************
 */
$title = dbfuncInt2String($_GET['keyname']);
$bookindex = dbfuncInt2String($_GET['key']);

include "header.php";

$query = "SELECT book_title_owner.Username FROM book_title_owner, book " .
		 "WHERE book_title_owner.BookTitleIndex=book.BookTitleIndex " .
		 "AND   book.BookIndex = '$bookindex' " .
		 "AND   book_title_owner.Username='$username'";
$res = & $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo());

if ($is_admin or $res->numRows() > 0) {
	$query = "SELECT BookState FROM book_state ORDER BY BookStateIndex DESC LIMIT 1";
	$res = & $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo());
	
	if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$best_status = $row['BookState'];
	} else {
		$best_status = "New";
	}
	
	$query = "SELECT book.PurchaseDate, book_status.BookStatusTypeIndex, book_status_type.BookStatusType, " .
			 "       book_state.BookState, book_status.Date, " .
			 "       user.FirstName, user.Surname, user.Username, book_status.Comment, " .
			 "       tuser.Title AS TeacherTitle, tuser.FirstName AS TeacherFirstName, " .
			 "       tuser.Surname AS TeacherSurname, tuser.Username AS TeacherUsername " .
			 "       FROM book LEFT OUTER JOIN" . "            (book_status " .
			 "             LEFT OUTER JOIN user USING (Username) " .
			 "             LEFT OUTER JOIN user AS tuser ON (book_status.TeacherUsername = tuser.Username) " .
			 "             LEFT OUTER JOIN book_state ON (book_status.State = book_state.BookStateIndex) " .
			 "			  INNER JOIN book_status_type USING (BookStatusTypeIndex) " .
			 "			 ) USING (BookIndex) " . "WHERE book.BookIndex = '$bookindex' " .
			 "ORDER BY book_status.Order DESC";
	$res = & $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo());
	
	if ($res->numRows() > 0) {
		echo "      <table align='center' border='1'>\n"; // Table headers
		echo "         <tr>\n";
		echo "            <th>Student</th>\n";
		echo "            <th>&nbsp;</th>\n";
		echo "            <th>State</th>\n";
		echo "            <th>Date</th>\n";
		echo "            <th>Checked by</th>\n";
		echo "            <th>Comment</th>\n";
		echo "         </tr>\n";
		
		/*
		 * For each category, print a row with the category's name, # of subjects using it
		 * and the subject types it's enabled for
		 */
		
		$in_username = "";
		$in_student = "";
		$in_state = "";
		$in_date = "";
		$in_comment = "";
		$in_teacher_username = "";
		$in_teacher = "";
		$in_type = "";
		
		$alt_count = 0;
		while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
			$alt_count += 1;
			if ($alt_count % 2 == 0) {
				$alt = " class='alt'";
			} else {
				$alt = " class='std'";
			}
			
			if ($row['BookStatusTypeIndex'] == 1) {
				$alt_count -= 1;
				
				$in_username = $row['Username'];
				$in_student = "{$row['FirstName']} {$row['Surname']} ({$row['Username']})";
				$in_teacher_username = $row['TeacherUsername'];
				$in_teacher = "{$row['TeacherTitle']} {$row['TeacherFirstName']} {$row['TeacherSurname']}";
				$in_state = $row['BookState'];
				$in_type = $row['BookStatusType'];
				$in_date = date($dateformat, strtotime($row['Date']));
				if (! is_null($row['Comment'])) {
					$in_comment = htmlspecialchars($row['Comment'], ENT_QUOTES);
				} else {
					$in_comment = "";
				}
			} elseif ($row['BookStatusTypeIndex'] == 2) {
				$out_date = date($dateformat, strtotime($row['Date']));
				if (! is_null($row['Comment'])) {
					$out_comment = htmlspecialchars($row['Comment'], ENT_QUOTES);
				} else {
					$out_comment = "";
				}
				
				// Show in and out status
				echo "         <tr$alt>\n";
				// Book hasn't been checked in
				if ($in_username == "") {
					$query = "SELECT BookStatusType FROM book_status_type WHERE BookStatusTypeIndex=1";
					$nres = & $db->query($query);
					if (DB::isError($nres))
						die($nres->getDebugInfo());
					
					if ($nrow = & $nres->fetchRow(DB_FETCHMODE_ASSOC)) {
						$book_status_type = $nrow['BookStatusType'];
					} else {
						$book_status_type = "Unknown";
					}
					echo "            <td rowspan='2'>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
					echo "            <td><strong>$book_status_type</strong></td>\n";
					echo "            <td colspan='3' align='center'><em>Still checked out</em></td>\n";
					echo "            <td rowspan='2'>$out_comment</td>\n";
				} else {
					if ($in_username == $row['Username']) {
						echo "            <td rowspan='2'>$in_student</td>\n";
					} else {
						echo "            <td>$in_student</td>\n";
					}
					echo "            <td><strong>$in_type</strong></td>\n";
					echo "            <td>$in_state</td>\n";
					echo "            <td>$in_date</td>\n";
					if ($in_teacher_username == $row['TeacherUsername']) {
						echo "            <td rowspan='2'>$in_teacher</td>\n";
					} else {
						echo "            <td>$in_teacher</td>\n";
					}
					if ($in_comment == $out_comment or $in_comment == "") {
						echo "            <td rowspan='2'>$out_comment</td>\n";
					} elseif ($out_comment == "") {
						echo "            <td rowspan='2'>$in_comment</td>\n";
					} else {
						echo "            <td>$in_comment</td>\n";
					}
				}
				echo "         </tr>\n";
				
				// Show out status
				echo "         <tr$alt>\n";
				if ($in_username != $row['Username'] and $in_username != "") {
					echo "            <td>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
				}
				echo "            <td><strong>{$row['BookStatusType']}</strong></td>\n";
				echo "            <td>{$row['BookState']}</td>\n";
				echo "            <td>$out_date</td>\n";
				if ($in_teacher_username != $row["TeacherUsername"]) {
					echo "            <td>{$row['TeacherTitle']} {$row['TeacherFirstName']} {$row['TeacherSurname']}</td>\n";
				}
				if ($in_comment != $out_comment and $out_comment != "") {
					echo "            <td>$out_comment</td>\n";
				}
				echo "         </tr>\n";
			} elseif ($row['BookStatusTypeIndex'] == 3) {
				if (is_null($row['Date'])) {
					if (! is_null($row['PurchaseDate'])) {
						$row['Date'] = date($dateformat, 
											strtotime($row['PurchaseDate']));
					}
				}
				if (is_null($row['Comment'])) {
					$comment = "<i>Initial purchase</i>";
				} else {
					$comment = htmlspecialchars($row['Comment'], ENT_QUOTES);
				}
				if (is_null($row['TeacherUsername'])) {
					$teacher = NULL;
				} else {
					$teacher = "{$row['TeacherTitle']} {$row['TeacherFirstName']} {$row['TeacherSurname']}";
				}
				if (is_null($row['BookState'])) {
					$row['BookState'] = $best_status;
				}
				
				echo "         <tr$alt>\n";
				echo "            <td>&nbsp;</td>\n";
				echo "            <td><em>{$row['BookStatusType']}</em></td>\n";
				echo "            <td>{$row['BookState']}</td>\n";
				if (is_null($row['Date']) and is_null($teacher)) {
					echo "            <td colspan='3'>$comment</td>\n";
				} elseif (is_null($teacher)) {
					$date = date($dateformat, strtotime($row['Date']));
					echo "            <td>$date</td>\n";
					echo "            <td colspan='2'>$comment</td>\n";
				} else {
					$date = date($dateformat, strtotime($row['Date']));
					echo "            <td>$date</td>\n";
					echo "            <td>$teacher</td>\n";
					echo "            <td>$comment</td>\n";
				}
				echo "         </tr>\n";
			}
			
			$old_status = $row['BookStatusTypeIndex'];
		}
		if ($old_status != 3) {
			$alt_count += 1;
			if ($alt_count % 2 == 0) {
				$alt = " class='alt'";
			} else {
				$alt = " class='std'";
			}
			
			$query = "SELECT BookStatusType FROM book_status_type WHERE BookStatusTypeIndex=3";
			$res = & $db->query($query);
			if (DB::isError($res))
				die($res->getDebugInfo());
			
			if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$book_status_type = $row['BookStatusType'];
			} else {
				$book_status_type = "<strong>Unknown</strong";
			}
			$query = "SELECT PurchaseDate FROM book WHERE BookIndex=$bookindex";
			$res = & $db->query($query);
			if (DB::isError($res))
				die($res->getDebugInfo());
			
			if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
				$date = $row['PurchaseDate'];
			} else {
				$date = NULL;
			}
			
			echo "         <tr$alt>\n";
			echo "            <td>&nbsp;</td>\n";
			echo "            <td><em>$book_status_type</em></td>\n";
			echo "            <td>$best_status</td>\n";
			if (is_null($date)) {
				echo "            <td colspan='3'><em>Initial purchase</em></td>\n";
			} else {
				$date = date($dateformat, strtotime($date));
				echo "            <td>$date</td>\n";
				echo "            <td colspan='2'><em>Initial purchase</em></td>\n";
			}
			echo "         </tr>\n";
		}
		echo "      </table>\n"; // End of table
	} else {
		echo "      <p>Book doesn't exist</p>\n";
	}
	log_event($LOG_LEVEL_EVERYTHING, "teacher/book/copy_history.php", 
			$LOG_ADMIN, "Viewed history of $title.");
} else {
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "teacher/book/copy_history.php", 
			$LOG_DENIED_ACCESS, "Attempted to view history of $title.");
	
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
?>