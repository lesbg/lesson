<?php
/**
 * ***************************************************************
 * teacher/book/book_list.php (c) 2010-2013 Jonathan Dieter
 *
 * List my books
 * ***************************************************************
 */
if (! isset($_GET['key'])) {
	$checkuser = $username;
} else {
	$checkuser = safe(dbfuncInt2String($_GET['key']));
}
$name = dbfuncInt2String($_GET['keyname']);
$subheader = NULL;
if (isset($_GET['key2']) and ! is_null($_GET['key2']) and $_GET['key2'] != "") {
	$display_type = intval(dbfuncInt2String($_GET['key2']));
} else {
	$_GET['key2'] = "";
	$display_type = 0;
}
if (isset($_GET['key3']) and ! is_null($_GET['key3']) and $_GET['key3'] != "") {
	$classindex = intval(dbfuncInt2String($_GET['key3']));
	$subheader = safe(dbfuncInt2String($_GET['key3name']));
	$subjectindex = $classindex;
} else {
	$_GET['key3'] = "";
	$classindex = NULL;
	$classname = NULL;
	$subjectindex = $classindex;
}
if (isset($_GET['key4']) and ! is_null($_GET['key4']) and $_GET['key4'] != "") {
	$student_username = safe(dbfuncInt2String($_GET['key4']));
	$subheader = "Books for " . safe(dbfuncInt2String($_GET['key4name']));
} else {
	$_GET['key4'] = "";
	$student_username = NULL;
	$student = NULL;
}
if (isset($_GET['key5']) and intval(dbfuncInt2String($_GET['key5'])) == 1) {
	$show_all_books = 1;
} else {
	$_GET['key5'] = dbfuncString2Int('0');
	$show_all_books = 0;
}

$query = "SELECT class.ClassIndex, class.ClassName, class.Grade " .
		 "  FROM class " . "WHERE class.ClassTeacherUsername = '$checkuser' " .
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
		 "WHERE Username = '$checkuser' " . "AND   ActiveTeacher = 1 ";
$res = & $db->query($query);
if (DB::isError($res))
	die($res->getDebugInfo());

if ($res->numRows() > 0) {
	$is_teacher = true;
} else {
	$is_teacher = false;
}

$title = "$name's Books";

include "header.php";

if ($is_admin or $username == $checkuser) {
	if ($is_class_teacher or $is_teacher) {
		if ($is_class_teacher) {
			if ($display_type != 0) {
				$byclass_link = "index.php?location=" .
					 dbfuncString2Int("teacher/book/book_list.php") . "&amp;key=" .
					 $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] .
					 "&amp;key2=" . dbfuncString2Int('0') . "&amp;key5=" .
					 $_GET['key5'];
				$byclass_button = dbfuncGetButton($byclass_link, 
												"Group by class", "medium", "", 
												"Show book list grouped by class");
			} else {
				$byclass_button = dbfuncGetDisabledButton("Group by class", 
														"medium", "");
			}
		} else {
			$byclass_button = "";
			if ($display_type == 0) {
				$display_type = 1;
			}
		}
		
		if ($display_type != 1) {
			$bysubj_link = "index.php?location=" .
				 dbfuncString2Int("teacher/book/book_list.php") . "&amp;key=" .
				 $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] . "&amp;key2=" .
				 dbfuncString2Int('1') . "&amp;key5=" . $_GET['key5'];
		} else {
			$bysubj_link = "";
		}
		if ($display_type != 2) {
			$bybook_link = "index.php?location=" .
				 dbfuncString2Int("teacher/book/book_list.php") . "&amp;key=" .
				 $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] . "&amp;key2=" .
				 dbfuncString2Int('2') . "&amp;key5=" . $_GET['key5'];
		} else {
			$bybook_link = "";
		}
		
		$bybook_button = dbfuncGetButton($bybook_link, "Group by book", 
										"medium", "", 
										"Show book list grouped by book");
		
		$bysubject_button = dbfuncGetButton($bysubj_link, "Group by subject", 
											"medium", "", 
											"Show book list grouped by subject");
		echo "      <p align='center'>$byclass_button $bysubject_button $bybook_button</p>\n";
	} else {
		$display_type = 4;
	}
	
	/* Get category list */
	if ($display_type == 0) {
		// Verify that we have a valid class
		if (! is_null($classindex)) {
			$query = "SELECT class.ClassIndex, class.ClassName, class.Grade " .
				 "  FROM class " .
				 "WHERE class.ClassTeacherUsername = '$checkuser' " .
				 "AND   class.ClassIndex = $classindex " .
				 "AND   class.YearIndex = $yearindex ";
			$res = & $db->query($query);
			if (DB::isError($res))
				die($res->getDebugInfo());
			
			if ($res->numRows() == 0) {
				$classindex = NULL;
			}
		}
		if (is_null($classindex)) {
			$query = "SELECT class.ClassIndex, class.ClassName, class.Grade, COUNT(classlist.ClassListIndex) AS StudentCount " .
					 "  FROM class LEFT OUTER JOIN classterm USING (ClassIndex) " .
					 "             INNER JOIN currentterm " .
					 "               ON  currentterm.TermIndex = classterm.TermIndex " .
					 "               AND currentterm.DepartmentIndex = class.DepartmentIndex " .
					 "             LEFT OUTER JOIN classlist USING (ClassTermIndex) " .
					 "WHERE class.ClassTeacherUsername = '$checkuser' " .
					 "AND   class.YearIndex = $yearindex " .
					 "GROUP BY class.ClassIndex " .
					 "ORDER BY class.Grade, class.ClassName";
			$res = & $db->query($query);
			if (DB::isError($res))
				die($res->getDebugInfo());
			
			if ($res->numRows() == 1) {
				$row = & $res->fetchRow(DB_FETCHMODE_ASSOC);
				$classindex = $row['ClassIndex'];
				$subheader = $row['ClassName'];
			} else {
				echo "      <h2 align='center'>Classes</h2>\n";
				echo "      <table align='center' border='1'>\n"; // Table headers
				echo "         <tr>\n";
				echo "            <th>Class</th>\n";
				echo "            <th>Students</th>\n";
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
					$link = "index.php?location=" .
							 dbfuncString2Int("teacher/book/book_list.php") .
							 "&amp;key=" . $_GET['key'] . "&amp;keyname=" .
							 $_GET['keyname'] . "&amp;key2=" . $_GET['key2'] .
							 "&amp;key3=" .
							 dbfuncString2Int($row['ClassIndex']) .
							 "&amp;key3name=" .
							 dbfuncString2Int($row['ClassName']) . "&amp;key5=" .
							 $_GET['key5'];
					
					echo "         <tr$alt>\n";
					echo "            <td><a href='$link'>{$row['ClassName']}</a></td>\n";
					echo "            <td>{$row['StudentCount']}</td>\n";
					echo "         </tr>\n";
				}
			}
		}
		if (! is_null($classindex) and is_null($student_username)) {
			$query = "SELECT user.FirstName, user.Surname, user.Username, user.User1, user.User2, " .
				 "       COUNT(subjectstudent.SubjectIndex) AS SubjectCount, class.ClassIndex, class.ClassName " .
				 "       FROM class INNER JOIN classterm USING (ClassIndex) " .
				 "            INNER JOIN classlist USING (ClassTermIndex) " .
				 "            INNER JOIN user USING (Username) " .
				 "            INNER JOIN currentterm " .
				 "               ON  currentterm.TermIndex = classterm.TermIndex " .
				 "               AND currentterm.DepartmentIndex = class.DepartmentIndex " .
				 "            LEFT OUTER JOIN (subjectstudent " .
				 "               INNER JOIN subject USING (SubjectIndex)) ON " .
				 "               (subjectstudent.Username = user.Username " .
				 "                AND subject.YearIndex = class.YearIndex " .
				 "                AND subject.TermIndex = classterm.TermIndex) " .
				 "WHERE classterm.ClassIndex = $classindex " .
				 "AND   class.YearIndex = $yearindex " .
				 "GROUP BY user.Username " .
				 "ORDER BY user.FirstName, user.Surname, user.Username";
			$res = &  $db->query($query);
			if (DB::isError($res))
				die($res->getDebugInfo()); // Check for errors in query
				
			/* Print students and their class */
			if ($res->numRows() > 0) {
				echo "      <h2 align='center'>$subheader</h2>\n";
				echo "      <table align='center' border='1''>\n"; // Table headers
				echo "         <tr>\n";
				echo "            <th>&nbsp;</th>\n";
				echo "            <th>Student</th>\n";
				echo "            <th>New</th>\n";
				echo "            <th>Special</th>\n";
				echo "            <th>Subjects</th>\n";
				echo "         </tr>\n";
				
				/* For each student, print a row with the student's name and class information */
				$alt_count = 0;
				$orderNum = 0;
				
				while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
					$alt_count += 1;
					if ($alt_count % 2 == 0) {
						$alt = " class=\"alt\"";
					} else {
						$alt = " class=\"std\"";
					}
					$orderNum ++;
					
					$link = "index.php?location=" .
							 dbfuncString2Int("teacher/book/book_list.php") .
							 "&amp;key=" . $_GET['key'] . "&amp;keyname=" .
							 $_GET['keyname'] . "&amp;key2=" . $_GET['key2'] .
							 "&amp;key3=" .
							 dbfuncString2Int($row['ClassIndex']) .
							 "&amp;key3name=" .
							 dbfuncString2Int($row['ClassName']) . "&amp;key4=" .
							 dbfuncString2Int($row['Username']) .
							 "&amp;key4name=" .
							 dbfuncString2Int(
											"{$row['FirstName']} {$row['Surname']} ({$row['Username']})") .
							 "&amp;key5=" . $_GET['key5'];
					echo "         <tr$alt>\n";
					echo "            <td>$orderNum</td>\n";
					echo "            <td><a href='$link'>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</a></td>\n";
					if ($row['User1'] == 1) {
						echo "            <td>X</td>\n";
					} else {
						echo "            <td>&nbsp;</td>\n";
					}
					if ($row['User2'] == 1) {
						echo "            <td>X</td>\n";
					} else {
						echo "            <td>&nbsp;</td>\n";
					}
					echo "            <td>{$row['SubjectCount']}</td>\n";
					echo "         </tr>\n";
				}
			} else {
				echo "<p>There are no students in this class</p>\n";
			}
		}
	} elseif ($display_type == 1) {
		if (! is_null($subjectindex)) {
			$query = "SELECT subject.SubjectIndex " .
					 "  FROM subject, subjectteacher, currentterm " .
					 "WHERE subjectteacher.Username = '$checkuser' " .
					 "AND   subjectteacher.SubjectIndex = subject.SubjectIndex " .
					 "AND   subject.TermIndex = currentterm.TermIndex " .
					 "AND   subject.YearIndex = $yearindex " .
					 "AND   subject.SubjectIndex = $subjectindex ";
			$res = & $db->query($query);
			if (DB::isError($res))
				die($res->getDebugInfo());
			
			if ($res->numRows() == 0) {
				$subjectindex = NULL;
			}
		}
		if (is_null($subjectindex)) {
			$query = "SELECT subject.Name, COUNT(subjectstudent.SubjectStudentIndex) AS StudentCount, subject.SubjectIndex " .
					 "  FROM subject, subjectstudent, subjectteacher, currentterm " .
					 "WHERE subjectteacher.Username = '$checkuser' " .
					 "AND   subjectteacher.SubjectIndex = subject.SubjectIndex " .
					 "AND   subjectstudent.SubjectIndex = subject.SubjectIndex " .
					 "AND   subject.TermIndex = currentterm.TermIndex " .
					 "AND   subject.YearIndex = $yearindex " .
					 "GROUP BY subject.SubjectIndex " . "ORDER BY subject.Name";
			$res = & $db->query($query);
			if (DB::isError($res))
				die($res->getDebugInfo());
			
			if ($res->numRows() == 1) {
				$row = & $res->fetchRow(DB_FETCHMODE_ASSOC);
				$subjectindex = $row['SubjectIndex'];
				$subheader = $row['Name'];
			} else {
				echo "      <h2 align='center'>Subjects</h2>\n";
				echo "      <table align='center' border='1'>\n"; // Table headers
				echo "         <tr>\n";
				echo "            <th>Subject</th>\n";
				echo "            <th>Students</th>\n";
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
					$link = "index.php?location=" .
							 dbfuncString2Int("teacher/book/book_list.php") .
							 "&amp;key=" . $_GET['key'] . "&amp;keyname=" .
							 $_GET['keyname'] . "&amp;key2=" . $_GET['key2'] .
							 "&amp;key3=" .
							 dbfuncString2Int($row['SubjectIndex']) .
							 "&amp;key3name=" .
							 dbfuncString2Int($row['Name']) . "&amp;key5=" .
							 $_GET['key5'];
					
					echo "         <tr$alt>\n";
					echo "            <td><a href='$link'>{$row['Name']}</a></td>\n";
					echo "            <td>{$row['StudentCount']}</td>\n";
					echo "         </tr>\n";
				}
			}
		}
		
		$subquery = "SELECT MyBookTitle.BookTitleIndex, book.BookIndex, book_status.BookStatusIndex, book_state.BookStateIndex FROM " .
					 "       book INNER JOIN " .
					 "        (SELECT book_title.BookTitleIndex " .
					 "                FROM book_title INNER JOIN book_class USING (BookTitleIndex) " .
					 "                      INNER JOIN class " .
					 "                       ON (class.ClassName = book_class.ClassName " .
					 "                          OR class.Grade   = book_class.Grade) " .
					 "                      INNER JOIN classterm USING (ClassIndex) " .
					 "                      INNER JOIN currentterm" .
					 "                        ON (classterm.TermIndex = currentterm.TermIndex " .
					 "                            AND class.DepartmentIndex = currentterm.DepartmentIndex) " .
					 "                      INNER JOIN classlist USING (ClassTermIndex) " .
					 "                      INNER JOIN user USING (Username) " .
					 "         WHERE class.ClassIndex = $classindex " .
					 "         AND   user.Username    = '$student_username' " .
					 "         AND (" .
					 "          (book_class.Flags & 0x03 = 0 ) " .
					 "          OR (book_class.Flags & 0x03 = 1 AND user.User2 = 0) " .
					 "          OR (book_class.Flags & 0x03 = 2 AND user.User2 = 1))" .
					 "         GROUP BY book_title.BookTitleIndex) AS MyBookTitle " .
					 "        USING (BookTitleIndex) " .
					 "       LEFT OUTER JOIN book_status USING (BookIndex) " .
					 "       LEFT OUTER JOIN book_status AS bs2 ON book_status.BookIndex = bs2.BookIndex " .
					 "                   AND book_status.Order < bs2.Order " .
					 "       LEFT OUTER JOIN book_state ON (book_status.State = book_state.BookStateIndex) " .
					 "WHERE bs2.BookStatusIndex IS NULL";
		$query = "";
	} elseif ($display_type == 2) {
		$query = "SELECT book_title.BookTitle, book_title.BookTitleIndex, class.ClassIndex, class.ClassName, class.Grade " .
				 "  FROM book_title LEFT OUTER JOIN " .
				 "    (book_class INNER JOIN class " .
				 "     ON class.ClassName = book_class.ClassName " .
				 "        OR class.Grade   = book_class.Grade) " .
				 "    USING (BookTitleIndex) " . "   LEFT OUTER JOIN " .
				 "    book_subject_type USING (BookTitleIndex) " .
				 "   LEFT OUTER JOIN " . "    (currentterm INNER JOIN subject " .
				 "      ON currentterm.TermIndex = subject.TermIndex " .
				 "         AND subject.YearIndex = $yearindex " .
				 "     INNER JOIN subjectteacher " .
				 "      ON subjectteacher.SubjectIndex = subject.SubjectIndex) " .
				 "    ON ((subject.Grade = class.Grade " .
				 "         OR subject.ClassIndex = class.ClassIndex) " .
				 "        AND subject.SubjectTypeIndex = book_subject_type.SubjectTypeIndex) " .
				 "WHERE ( " .
				 "       (class.ClassTeacherUsername = '$checkuser' " .
				 "        OR subjectteacher.Username = '$checkuser') " .
				 "       AND   class.YearIndex = $yearindex) " .
				 "GROUP BY book_title.BookTitleIndex " .
				 "ORDER BY book_title.BookTitle, book_title.BookTitleIndex";
	}
	
	if (($display_type == 0 and ! is_null($classindex) and
		 ! is_null($student_username)) or
		 ($display_type == 1 and ! is_null($subjectindex) and
		 ! is_null($classindex) and ! is_null($student_username))) {
		$query = "SET SESSION group_concat_max_len = 1000000";
		$res = & $db->query($query);
		if (DB::isError($res))
			die($res->getDebugInfo());
		
		if ($display_type == 0) {
			$subquery = "SELECT MyBookTitle.BookTitleIndex, book.BookIndex, book_status.BookStatusIndex, book_state.BookStateIndex FROM " .
				 "       book INNER JOIN " .
				 "        (SELECT book_title.BookTitleIndex " .
				 "                FROM book_title INNER JOIN book_class USING (BookTitleIndex) " .
				 "                      INNER JOIN class " .
				 "                       ON (class.ClassName = book_class.ClassName " .
				 "                          OR class.Grade   = book_class.Grade) " .
				 "                      INNER JOIN classterm USING (ClassIndex) " .
				 "                      INNER JOIN currentterm" .
				 "                        ON (classterm.TermIndex = currentterm.TermIndex " .
				 "                            AND class.DepartmentIndex = currentterm.DepartmentIndex) " .
				 "                      INNER JOIN classlist USING (ClassTermIndex) " .
				 "                      INNER JOIN user USING (Username) " .
				 "         WHERE class.ClassIndex = $classindex " .
				 "         AND   user.Username    = '$student_username' " .
				 "         AND (" . "          (book_class.Flags & 0x03 = 0 ) " .
				 "          OR (book_class.Flags & 0x03 = 1 AND user.User2 = 0) " .
				 "          OR (book_class.Flags & 0x03 = 2 AND user.User2 = 1))" .
				 "         GROUP BY book_title.BookTitleIndex) AS MyBookTitle " .
				 "        USING (BookTitleIndex) " .
				 "       LEFT OUTER JOIN book_status USING (BookIndex) " .
				 "       LEFT OUTER JOIN book_status AS bs2 ON book_status.BookIndex = bs2.BookIndex " .
				 "                   AND book_status.Order < bs2.Order " .
				 "       LEFT OUTER JOIN book_state ON (book_status.State = book_state.BookStateIndex) " .
				 "WHERE bs2.BookStatusIndex IS NULL";
			
			$query = "(SELECT book.BookIndex, BookNumber, book_title.BookTitleIndex, BookTitle, " .
					 "         book_status.BookStatusTypeIndex, book_status.Date, BookState, 1 AS `Order`, " .
					 "         GROUP_CONCAT(DISTINCT subjecttype.Title ORDER BY subjecttype.Title SEPARATOR ', ') AS SubjectType " .
					 "  FROM book_title " .
					 "  INNER JOIN book USING (BookTitleIndex) " .
					 "  INNER JOIN book_status USING (BookIndex) " .
					 "  INNER JOIN book_state ON (book_state.BookStateIndex = book_status.State) " .
					 "  LEFT OUTER JOIN book_status AS bs2 ON book_status.BookIndex = bs2.BookIndex " .
					 "                   AND book_status.Order < bs2.Order " .
					 "  LEFT OUTER JOIN book_subject_type ON (book_subject_type.BookTitleIndex = book_title.BookTitleIndex) " .
					 "  LEFT OUTER JOIN subjecttype USING (SubjectTypeIndex) " .
					 "  WHERE book_status.Username = '$student_username' " .
					 "  AND   book_status.BookStatusTypeIndex = 2 " .
					 "  AND   bs2.BookStatusIndex IS NULL " .
					 "  GROUP BY book_title.BookTitleIndex, book_status.BookStatusIndex) " .
					 " UNION ";
			if ($show_all_books == 1) {
				$query .= " (SELECT NULL AS BookIndex, " .
					 "         NULL AS BookNumber, " .
					 "         book_title.BookTitleIndex, BookTitle, " .
					 "         NULL AS BookStatusTypeIndex, NULL AS `Date`, NULL AS BookState, 2 AS `Order`, " .
					 "         GROUP_CONCAT(DISTINCT subjecttype.Title ORDER BY subjecttype.Title SEPARATOR ', ') AS SubjectType " .
					 "  FROM book_title " .
					 "  LEFT OUTER JOIN book_subject_type ON (book_subject_type.BookTitleIndex = book_title.BookTitleIndex) " .
					 "  LEFT OUTER JOIN subjecttype USING (SubjectTypeIndex) " .
					 "  WHERE book_title.Retired = 0 " .
					 "  GROUP BY book_title.BookTitleIndex) " .
					 " ORDER BY BookTitle, BookTitleIndex, `Order`, BookNumber";
			} else {
				$query .= " (SELECT GROUP_CONCAT(BookInfo.BookIndex ORDER BY BookInfo.BookIndex) AS BookIndex, " .
						 "         GROUP_CONCAT(book.BookNumber ORDER BY BookInfo.BookIndex) AS BookNumber, " .
						 "         book_title.BookTitleIndex, BookTitle, " .
						 "         BookStatusTypeIndex, NULL AS `Date`, NULL AS BookState, 2 AS `Order`, " .
						 "         GROUP_CONCAT(DISTINCT subjecttype.Title ORDER BY subjecttype.Title SEPARATOR ', ') AS SubjectType " .
						 "  FROM " . "  ($subquery) AS BookInfo " .
						 "  LEFT OUTER JOIN book_title USING (BookTitleIndex) " .
						 "  LEFT OUTER JOIN book ON (book.BookIndex = BookInfo.BookIndex) " .
						 "  LEFT OUTER JOIN book_status ON (book_status.BookStatusIndex = BookInfo.BookStatusIndex) " .
						 "  LEFT OUTER JOIN book_subject_type ON (book_subject_type.BookTitleIndex = BookInfo.BookTitleIndex) " .
						 "  LEFT OUTER JOIN subjecttype USING (SubjectTypeIndex) " .
						 "  WHERE (book_status.BookStatusTypeIndex = 1" .
						 "         OR book_status.BookStatusTypeIndex = 3 " .
						 "         OR book_status.BookStatusTypeIndex IS NULL) " .
						 "  AND book.Retired = 0 " .
						 "  AND book_title.Retired = 0 " .
						 "  GROUP BY book_title.BookTitleIndex) " .
						 " ORDER BY BookTitle, BookTitleIndex, `Order`, BookNumber";
			}
			/*
			 * "WHERE (BookStatusTypeIndex = 2 AND Username = '$student_username') \n" .
			 * "OR BookStatusTypeIndex = 1 \n" .
			 * "OR BookStatusTypeIndex = 3 \n" .
			 */
			
			$qsuery = "SELECT BookIndex, BookNumber, BookTitleIndex, BookTitle, SubjectType, BookStatusTypeIndex, `Date`, BookState, \n" .
					 "       COUNT(IF(BookStatusTypeIndex = '3', 1, NULL)) + COUNT(IF(BookStatusTypeIndex = '1', 1, NULL)) AS Available \n" .
					 " FROM \n" . " (SELECT BookInfo.* FROM \n" .
					 "   (SELECT book.BookIndex, book.BookNumber, book_status.BookStatusTypeIndex AS Count, MyBookTitle.*, \n" .
					 "           book_status.BookStatusTypeIndex, book_status.Username, book_status.Date, book_state.BookState FROM \n" .
					 "     book INNER JOIN \n" .
					 "      (SELECT book_title.BookTitle, book_title.BookTitleIndex, subjecttype.Title AS SubjectType \n" .
					 "         FROM book_title LEFT OUTER JOIN \n" .
					 "           (book_class INNER JOIN class \n" .
					 "            ON class.ClassName = book_class.ClassName \n" .
					 "               OR class.Grade   = book_class.Grade) \n" .
					 "           USING (BookTitleIndex) \n" .
					 "          LEFT OUTER JOIN \n" .
					 "           (book_subject_type INNER JOIN subjecttype USING (SubjectTypeIndex)) \n" .
					 "             USING (BookTitleIndex) \n" .
					 "       WHERE class.ClassIndex = $classindex \n" .
					 "       GROUP BY book_title.BookTitleIndex) AS MyBookTitle USING (BookTitleIndex) \n" .
					 "    LEFT OUTER JOIN book_status USING (BookIndex) \n" .
					 "    LEFT OUTER JOIN book_state ON (book_status.State = book_state.BookStateIndex) \n" .
					 "    ORDER BY book.BookIndex, book_status.Order DESC) AS BookInfo \n" .
					 "  GROUP BY BookInfo.BookIndex) AS BookInfo \n" .
					 "WHERE (BookStatusTypeIndex = 2 AND Username = '$student_username') \n" .
					 "OR    BookStatusTypeIndex = 1 \n" .
					 "OR    BookStatusTypeIndex = 3 \n" .
					 "GROUP BY BookTitleIndex \n" .
					 "ORDER BY BookTitle, BookTitleIndex\n";
		}
		$res = & $db->query($query);
		if (DB::isError($res))
			die($res->getDebugInfo());
		
		if ($res->numRows() > 0) {
			$back_link = "index.php?location=" .
				 dbfuncString2Int("teacher/book/book_list.php") . "&amp;key=" .
				 $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] . "&amp;key2=" .
				 $_GET['key2'] . "&amp;key3=" . $_GET['key3'] . "&amp;key3name=" .
				 $_GET['key3name'] . "&amp;key5=" . $_GET['key5'];
			$back_button = dbfuncGetButton($back_link, "⇠", "large", "view", 
										"Back to class list");
			
			echo "      <h2 align='center'>$back_button $subheader</h2>\n";
			$show_link = "index.php?location=" .
						 dbfuncString2Int("teacher/book/book_list.php") .
						 "&amp;key=" . $_GET['key'] . "&amp;keyname=" .
						 $_GET['keyname'] . "&amp;key2=" . $_GET['key2'] .
						 "&amp;key3=" . $_GET['key3'] . "&amp;key3name=" .
						 $_GET['key3name'] . "&amp;key4=" . $_GET['key4'] .
						 "&amp;key4name=" . $_GET['key4name'];
			
			if ($show_all_books == 1) {
				$show_link .= "&amp;key5=" . dbfuncString2Int('0');
				
				$show_all_button = dbfuncGetDisabledButton("Show all", "medium", 
														"");
				$show_thisclass_button = dbfuncGetButton($show_link, 
														"Show class", "medium", 
														"", 
														"Show this class's books");
			} else {
				$show_link .= "&amp;key5=" . dbfuncString2Int('1');
				
				$show_all_button = dbfuncGetButton($show_link, "Show all", 
												"medium", "", "Show all books");
				$show_thisclass_button = dbfuncGetDisabledButton("Show class", 
																"medium", "");
			}
			echo "      <p align='center'>$show_thisclass_button $show_all_button</p>\n";
			echo "      <table align='center' border='1'>\n"; // Table headers
			echo "         <tr>\n";
			/* echo " <th>&nbsp;</th>\n"; */
			echo "            <th>Code</th>\n";
			echo "            <th>Subject</th>\n";
			echo "            <th>Title</th>\n";
			echo "            <th>Number</th>\n";
			echo "            <th>Date</th>\n";
			echo "            <th>State</th>\n";
			echo "            <th>Action</th>\n";
			echo "         </tr>\n";
			
			$alt_count = 0;
			$prev_code = - 1;
			
			while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
				// TODO: There must be some way to do this in SQL
				
				/* Don't show checkout rows when students already have books checked out */
				if ($row['BookTitleIndex'] == $prev_code and
					 is_null($row['Date']))
					continue;
				$prev_code = $row['BookTitleIndex'];
				
				$alt_count += 1;
				if ($alt_count % 2 == 0) {
					$alt = " class='alt'";
				} else {
					$alt = " class='std'";
				}
				$link = "index.php?location=" .
						 dbfuncString2Int("teacher/book/check_in_out_copy.php") .
						 "&amp;key4=" . dbfuncString2Int($student_username) .
						 "&amp;next=" .
						 dbfuncString2Int(
										"index.php?location=" .
										 dbfuncString2Int(
														"teacher/book/book_list.php") .
										 "&amp;key=" . $_GET['key'] .
										 "&amp;keyname=" . $_GET['keyname'] .
										 "&amp;key2=" . $_GET['key2'] .
										 "&amp;key3=" . $_GET['key3'] .
										 "&amp;key3name=" . $_GET['key3name'] .
										 "&amp;key4=" . $_GET['key4'] .
										 "&amp;key4name=" . $_GET['key4name'] .
										 "&amp;key5=" . $_GET['key5']);
				
				echo "         <tr$alt>\n";
				echo "            <td>{$row['BookTitleIndex']}</td>\n";
				echo "            <td>{$row['SubjectType']}</td>\n";
				echo "            <td>{$row['BookTitle']}</td>\n";
				if ($row['BookStatusTypeIndex'] == 2) {
					$link .= "&amp;key=" .
						 dbfuncString2Int($row['BookIndex']) . "&amp;keyname=" .
						 dbfuncString2Int(
										$row['BookTitle'] . " copy #" .
										 $row['BookNumber']) . "&amp;key2=" .
						 dbfuncString2Int(1);
					
					echo "            <td>{$row['BookNumber']}</td>\n";
					$date = date($dateformat, strtotime($row['Date']));
					echo "            <td>$date</td>\n";
					echo "            <td>{$row['BookState']}</td>\n";
					echo "            <form action='$link' method='post'>\n";
					echo "               <td><input type='submit' value='Check in'></td>\n";
					echo "            </form>\n";
				} else {
					
					if (! is_null($row['BookIndex'])) {
						$bookindex = explode(',', $row['BookIndex']);
						$booknumber = explode(',', $row['BookNumber']);
						
						$link .= "&amp;key2=" . dbfuncString2Int(2);
						echo "            <form action='$link' method='post'>\n";
						echo "               <td colspan='3' align='center'>\n";
						echo "                  <select name='bookindex'>\n";
						for($i = 0; $i < count($bookindex); $i ++) {
							if (isset($bookindex[$i]) and
										 isset($booknumber[$i])) {
								echo "                     <option value='{$bookindex[$i]}'>{$booknumber[$i]}</option>\n";
							}
						}
						echo "                  </select>\n";
					} else {
						$link = "index.php?location=" .
								 dbfuncString2Int(
												"teacher/book/choose_book_number.php") .
								 "&amp;key=" .
								 dbfuncString2Int($row['BookTitleIndex']) .
								 "&amp;keyname=" .
								 dbfuncString2Int($row['BookTitle']) .
								 "&amp;key4=" .
								 dbfuncString2Int($student_username) .
								 "&amp;next=" .
								 dbfuncString2Int(
												"index.php?location=" .
												 dbfuncString2Int(
																"teacher/book/book_list.php") .
												 "&amp;key=" . $_GET['key'] .
												 "&amp;keyname=" .
												 $_GET['keyname'] . "&amp;key2=" .
												 $_GET['key2'] . "&amp;key3=" .
												 $_GET['key3'] . "&amp;key3name=" .
												 $_GET['key3name'] . "&amp;key4=" .
												 $_GET['key4'] . "&amp;key4name=" .
												 $_GET['key4name'] . "&amp;key5=" .
												 $_GET['key5']);
						echo "            <form action='$link' method='post'>\n";
						echo "               <td colspan='3' align='center'>\n";
					}
					echo "               </td>\n";
					echo "               <td><input type='submit' value='Check out'></td>\n";
					echo "            </form>\n";
				}
				echo "         </tr>\n";
			}
			echo "      </table>\n"; // End of table
		} else {
			echo "      <p>No titles have been set up.</p>\n";
		}
	}
	exit(0);
	
	$query = "SELECT book_title.BookTitle, book_title.BookTitleIndex, class.ClassIndex, class.ClassName, class.Grade " .
			 "  FROM book_title LEFT OUTER JOIN " .
			 "    (book_class INNER JOIN class " .
			 "     ON class.ClassName = book_class.ClassName " .
			 "        OR class.Grade   = book_class.Grade) " .
			 "    USING (BookTitleIndex) " . "   LEFT OUTER JOIN " .
			 "    book_subject_type USING (BookTitleIndex) " .
			 "   LEFT OUTER JOIN " . "    (currentterm INNER JOIN subject " .
			 "      ON currentterm.TermIndex = subject.TermIndex " .
			 "         AND subject.YearIndex = $yearindex " .
			 "     INNER JOIN subjectteacher " .
			 "      ON subjectteacher.SubjectIndex = subject.SubjectIndex) " .
			 "    ON ((subject.Grade = class.Grade " .
			 "         OR subject.ClassIndex = class.ClassIndex) " .
			 "        AND subject.SubjectTypeIndex = book_subject_type.SubjectTypeIndex) " .
			 "WHERE (class.ClassTeacherUsername = '$checkuser' " .
			 "        OR subjectteacher.Username = '$checkuser') " .
			 "       AND   class.YearIndex = $yearindex) " .
			 "GROUP BY book_title.BookTitleIndex " .
			 "ORDER BY book_title.BookTitle, book_title.BookTitleIndex";
	
	$res = & $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo());
	
	if ($res->numRows() > 0) {
		
		echo "      <table align='center' border='1'>\n"; // Table headers
		echo "         <tr>\n";
		echo "            <th>&nbsp;</th>\n";
		echo "            <th>Class</th>\n";
		echo "            <th>Code</th>\n";
		echo "            <th>Title</th>\n";
		echo "            <th>Book Count</th>\n";
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
						 "&amp;key=" .
						 dbfuncString2Int($row['BookTitleIndex']) .
						 "&amp;keyname=" .
						 dbfuncString2Int($row['BookTitle']) . "&amp;key3=" .
						 dbfuncString2Int($row['ClassIndex']) . "&amp;next=" .
						 dbfuncString2Int(
										"index.php?location=" .
										 dbfuncString2Int(
														"teacher/book/book_list.php"));
			
			echo "         <tr$alt>\n";
			/* Generate view and edit buttons */
			$viewbutton = dbfuncGetButton($viewlink, "V", "small", "view", 
										"View copies of this title");
			echo "            <td>$viewbutton</td>\n";
			echo "            <td>{$row['ClassName']}</td>\n";
			echo "            <td>{$row['BookTitleIndex']}</td>\n";
			echo "            <td>{$row['BookTitle']}</td>\n";
			echo "            <td>{$row['Count']}</td>\n";
		}
		echo "      </table>\n"; // End of table
	} else {
		echo "      <p>No titles have been set up.</p>\n";
	}
	log_event($LOG_LEVEL_EVERYTHING, "teacher/book/book_list.php", $LOG_ADMIN, 
			"Viewed book titles.");
} else {
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "teacher/book/book_list.php", 
			$LOG_DENIED_ACCESS, "Attempted to view book titles.");
	
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";