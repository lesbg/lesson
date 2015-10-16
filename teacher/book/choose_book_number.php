<?php
/**
 * ***************************************************************
 * teacher/book/choose_book_number.php (c) 2010-2013 Jonathan Dieter
 *
 * Choose book number to check out
 * ***************************************************************
 */
$booktitleindex = dbfuncInt2String($_GET['key']);
$booktitle = dbfuncInt2STring($_GET['keyname']);
if (isset($_GET['key4'])) {
	$student = dbfuncInt2String($_GET['key4']);
} else {
	$student = NULL;
}
$title = "Select book number for " . dbfuncInt2String($_GET['keyname']);

$query = "SELECT class.ClassIndex, class.ClassName, class.Grade " .
		 "  FROM class " . "WHERE class.ClassTeacherUsername = '$username' " .
		 "AND   class.YearIndex = $yearindex ";
$res = & $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo());

if ($res->numRows() > 0) {
	$is_class_teacher = true;
} else {
	$is_class_teacher = false;
}

$query = "SELECT ActiveTeacher " . "  FROM user " .
		 "WHERE Username = '$username' " . "AND   ActiveTeacher = 1 ";
$res = & $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo());

if ($res->numRows() > 0) {
	$is_teacher = true;
} else {
	$is_teacher = false;
}

include "header.php";

$query = "SELECT book_title_owner.Username FROM book_title_owner, book " .
		 "WHERE book_title_owner.BookTitleIndex=book.BookTitleIndex " .
		 "AND   book.BookIndex = '$bookindex' " .
		 "AND   book_title_owner.Username='$username'";
$res = & $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo());

if ($is_admin or $is_class_teacher or $res->numRows() > 0) {
	$query = "SELECT BookState, BookStateIndex FROM book_state ORDER BY BookStateIndex DESC LIMIT 1";
	$res = & $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo());
	
	if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
		$best_status = $row['BookState'];
	} else {
		$best_status = "New";
	}
	
	$query = "SELECT book.BookIndex, book.BookNumber, book_title.BookTitleIndex, BookTitle, " .
			 "       book_status.BookStatusTypeIndex, NULL AS `Date`, book_state.BookState, " .
			 "       subjecttype.Title AS SubjectType " . "  FROM book_title " .
			 "  INNER JOIN book USING (BookTitleIndex) " .
			 "  LEFT OUTER JOIN book_status USING (BookIndex) " .
			 "  LEFT OUTER JOIN book_state ON book_status.State = book_state.BookStateIndex " .
			 "  LEFT OUTER JOIN book_status AS bs2 ON book_status.BookIndex = bs2.BookIndex " .
			 "                AND book_status.Order < bs2.Order " .
			 "  LEFT OUTER JOIN book_subject_type ON (book_subject_type.BookTitleIndex = book_title.BookTitleIndex) " .
			 "  LEFT OUTER JOIN subjecttype USING (SubjectTypeIndex) " .
			 "WHERE (book_status.BookStatusTypeIndex = 1" .
			 "       OR book_status.BookStatusTypeIndex = 3 " .
			 "       OR book_status.BookStatusTypeIndex IS NULL) " .
			 "AND book.Retired = 0 " . "AND book_title.Retired = 0 " .
			 "AND book_title.BookTitleIndex = '$booktitleindex' " .
			 "ORDER BY BookNumber, BookIndex";
	
	$res = & $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo());
	
	if ($res->numRows() > 0) {
		echo "      <table align='center' border='1'>\n"; // Table headers
		echo "         <tr>\n";
		echo "            <th>Number</th>\n";
		echo "            <th>Current State</th>\n";
		echo "         </tr>\n";
		
		while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
			$link = "index.php?location=" .
					 dbfuncString2Int("teacher/book/check_in_out_copy.php") .
					 "&amp;key=" . dbfuncString2Int($row['BookIndex']) .
					 "&amp;key2=" . dbfuncString2Int(2) . "&amp;key4=" .
					 $_GET['key4'] . "&amp;next=" . $_GET['next'];
			
			echo "      <tr>\n";
			echo "         <td><a href='$link'>{$row['BookNumber']}</td>\n";
			if (is_null($row['BookState'])) {
				echo "         <td>$best_status</td>\n";
			} else {
				echo "         <td>{$row['BookState']}</td>\n";
			}
			echo "      </tr>\n";
		}
		echo "         </table>\n";
	} else {
		echo "      <p>This book has no active copies</p>\n";
	}
	log_event($LOG_LEVEL_EVERYTHING, "teacher/book/choose_book_number.php", 
			$LOG_ADMIN, "Chose book number for $booktitle.");
} else {
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "teacher/book/choose_book_number.php", 
			$LOG_DENIED_ACCESS, 
			"Attempted to choose book number for $booktitle.");
	
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";