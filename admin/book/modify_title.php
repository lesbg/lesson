<?php
/**
 * ***************************************************************
 * admin/book/modify_title.php (c) 2010 Jonathan Dieter
 *
 * Change information about book title
 * ***************************************************************
 */

/* Get variables */
$title = "Change title information for " . dbfuncInt2String($_GET['keyname']);
$booktitleindex = safe(dbfuncInt2String($_GET['key']));
$link = "index.php?location=" .
		 dbfuncString2Int("admin/book/new_or_modify_title_action.php") .
		 "&amp;key=" . $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] .
		 "&amp;next=" . $_GET['next'];

include "header.php"; // Show header

/* Check whether user is authorized to change subject */
if ($is_admin) {
	/* Get subject information */
	$fRes = & $db->query(
						"SELECT BookTitle, BookTitleIndex, Cost FROM book_title " .
						 "WHERE BookTitleIndex = '$booktitleindex'");
	if (DB::isError($fRes))
		die($fRes->getDebugInfo()); // Check for errors in query
	if ($fRow = & $fRes->fetchRow(DB_FETCHMODE_ASSOC)) {
		if (isset($errorlist)) {
			echo $errorlist;
		}
		
		if (! isset($_POST['title'])) {
			$_POST['title'] = htmlspecialchars($fRow['BookTitle']);
		} else {
			$_POST['title'] = htmlspecialchars($_POST['title']);
		}
		/*
		 * if(!isset($_POST['id'])) {
		 * $_POST['id'] = htmlspecialchars($fRow['BookTitleIndex']);
		 * } else {
		 * $_POST['id'] = htmlspecialchars($_POST['id']);
		 * }
		 */
		if (! isset($_POST['cost'])) {
			$_POST['cost'] = htmlspecialchars($fRow['Cost']);
		} else {
			$_POST['cost'] = floatval($_POST['cost']);
			if ($_POST['cost'] == 0) {
				$_POST['cost'] = "";
			}
		}
		
		echo "      <form action='$link' method='post'>\n"; // Form method
		echo "         <input type='hidden' name='type' value='modify'>\n";
		echo "         <table class='transparent' align='center'>\n"; // Table headers
		
		/* Show subject type name */
		echo "            <tr>\n";
		echo "               <td><b>Book ID</b></td>\n";
		echo "               <td>$booktitleindex</td>\n";
		echo "            </tr>\n";
		echo "            <tr>\n";
		echo "               <td><b>Book Title</b></td>\n";
		echo "               <td><input type='text' name='title' value='{$_POST['title']}' size=20></td>\n";
		echo "            </tr>\n";
		echo "            <tr>\n";
		echo "               <td><b>Cost (\$)</b></td>\n";
		echo "               <td><input type='text' name='cost' value='{$_POST['cost']}' size=20></td>\n";
		echo "            </tr>\n";
		echo "            <tr>\n";
		echo "               <td><b>Subjects</b></td>\n";
		echo "               <td>\n";
		echo "                  <table align='center' border='1' width='100%'>\n";
		echo "                     <tr>\n";
		echo "                        <th>Subject</th>\n";
		echo "                        <th>&nbsp;</th>\n";
		echo "                     </tr>\n";
		$query = "SELECT book_subject_type.BookSubjectTypeIndex, subjecttype.Title AS SubjectType FROM " .
				 "       book_title INNER JOIN book_subject_type USING (BookTitleIndex) " .
				 "       INNER JOIN subjecttype USING (SubjectTypeIndex) " .
				 "WHERE book_title.BookTitleIndex = '$booktitleindex' " .
				 "ORDER BY subjecttype.Title";
		
		$res = &  $db->query($query);
		if (DB::isError($res))
			die($res->getDebugInfo()); // Check for errors in query
		while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
			echo "                     <tr>\n";
			echo "                        <td>{$row['SubjectType']}</td>\n";
			echo "                        <td><input type='submit' name='del_subject_{$row['BookSubjectTypeIndex']}' value='Remove' />\n</td>\n";
			echo "                     </tr>\n";
		}
		
		echo "                     <tr>\n";
		echo "                        <td>\n";
		echo "                           <select name='subject'>\n";
		echo "                              <option value='NULL'>&nbsp;</option>\n";
		$query = "SELECT subjecttype.SubjectTypeIndex, subjecttype.Title AS SubjectType " .
				 "       FROM subjecttype " . "ORDER BY subjecttype.Title";
		
		$res = &  $db->query($query);
		if (DB::isError($res))
			die($res->getDebugInfo());
		
		while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
			echo "                              <option value='{$row['SubjectTypeIndex']}'>{$row['SubjectType']}</option>\n";
		}
		echo "                           </select>\n";
		echo "                        </td>\n";
		echo "                        <td>\n";
		echo "                          <input type='submit' name='add_subject' value='Add' />\n";
		echo "                        </td>\n";
		echo "                     </tr>\n";
		echo "                  </table>\n";
		echo "               </td>\n";
		echo "            </tr>\n";
		echo "            <tr>\n";
		echo "               <td><b>Classes</b></td>\n";
		echo "               <td>\n";
		echo "                  <table align='center' border='1' width='100%'>\n";
		echo "                     <tr>\n";
		echo "                        <th>Class</th>\n";
		echo "                        <th>Students</th>\n";
		echo "                        <th>&nbsp;</th>\n";
		echo "                     </tr>\n";
		$query = "SELECT book_class.BookClassIndex, book_class.Flags & 0x03 AS Flags, book_class.ClassName FROM " .
				 "       book_title INNER JOIN book_class USING (BookTitleIndex) " .
				 "       LEFT OUTER JOIN class ON (book_class.ClassName = class.ClassName " .
				 "                                 AND class.YearIndex = $yearindex) " .
				 "WHERE book_title.BookTitleIndex = '$booktitleindex' " .
				 "AND   book_class.ClassName IS NOT NULL " .
				 "ORDER BY class.Grade, book_class.ClassName";
		
		$res = &  $db->query($query);
		if (DB::isError($res))
			die($res->getDebugInfo()); // Check for errors in query
		while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
			if ($row['Flags'] == 0) {
				$special = "&nbsp;";
			} elseif ($row['Flags'] == 1) {
				$special = "Regular";
			} elseif ($row['Flags'] == 2) {
				$special = "Special";
			}
			echo "                     <tr>\n";
			echo "                        <td>{$row['ClassName']}</td>\n";
			echo "                        <td>$special</td>\n";
			echo "                        <td><input type='submit' name='del_class_{$row['BookClassIndex']}' value='Remove' />\n</td>\n";
			echo "                     </tr>\n";
		}
		
		echo "                     <tr>\n";
		echo "                        <td>\n";
		echo "                           <select name='class'>\n";
		echo "                              <option value='NULL'>&nbsp;</option>\n";
		$query = "SELECT class.ClassName " . "       FROM class " .
				 "WHERE class.YearIndex = $yearindex " .
				 "ORDER BY class.Grade, class.ClassName ";
		$res = &  $db->query($query);
		if (DB::isError($res))
			die($res->getDebugInfo());
		
		while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
			echo "                              <option value='{$row['ClassName']}'>{$row['ClassName']}</option>\n";
		}
		echo "                           </select>\n";
		echo "                        </td>\n";
		echo "                        <td>\n";
		echo "                           <select name='class_special'>\n";
		echo "                              <option value='0'>All</option>\n";
		echo "                              <option value='1'>Regular</option>\n";
		echo "                              <option value='2'>Special</option>\n";
		echo "                           </select>\n";
		echo "                        </td>\n";
		echo "                        <td>\n";
		echo "                          <input type='submit' name='add_class' value='Add' />\n";
		echo "                        </td>\n";
		echo "                     </tr>\n";
		echo "                  </table>\n";
		echo "               </td>\n";
		echo "            </tr>\n";
		echo "            <tr>\n";
		echo "               <td><b>Grades</b></td>\n";
		echo "               <td>\n";
		echo "                  <table align='center' border='1' width='100%'>\n";
		echo "                     <tr>\n";
		echo "                        <th>Grade</th>\n";
		echo "                        <th>Students</th>\n";
		echo "                        <th>&nbsp;</th>\n";
		echo "                     </tr>\n";
		$query = "SELECT book_class.BookClassIndex, book_class.Flags & 0x03 AS Flags, grade.GradeName FROM " .
				 "       book_title INNER JOIN book_class USING (BookTitleIndex) " .
				 "       LEFT OUTER JOIN grade USING (Grade) " .
				 "WHERE book_title.BookTitleIndex = '$booktitleindex' " .
				 "AND   book_class.Grade IS NOT NULL " . "ORDER BY grade.Grade";
		
		$res = &  $db->query($query);
		if (DB::isError($res))
			die($res->getDebugInfo()); // Check for errors in query
		while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
			if ($row['Flags'] == 0) {
				$special = "&nbsp;";
			} elseif ($row['Flags'] == 1) {
				$special = "Regular";
			} elseif ($row['Flags'] == 2) {
				$special = "Special";
			}
			echo "                     <tr>\n";
			echo "                        <td>{$row['GradeName']}</td>\n";
			echo "                        <td>$special</td>\n";
			echo "                        <td><input type='submit' name='del_class_{$row['BookClassIndex']}' value='Remove' />\n</td>\n";
			echo "                     </tr>\n";
		}
		
		echo "                     <tr>\n";
		echo "                        <td>\n";
		echo "                           <select name='grade'>\n";
		echo "                              <option value='NULL'>&nbsp;</option>\n";
		$query = "SELECT grade.Grade, grade.GradeName " . "       FROM grade " .
				 "ORDER BY grade.Grade ";
		$res = &  $db->query($query);
		if (DB::isError($res))
			die($res->getDebugInfo());
		
		while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
			echo "                              <option value='{$row['Grade']}'>{$row['GradeName']}</option>\n";
		}
		echo "                           </select>\n";
		echo "                        </td>\n";
		echo "                        <td>\n";
		echo "                           <select name='grade_special'>\n";
		echo "                              <option value='0'>All</option>\n";
		echo "                              <option value='1'>Regular</option>\n";
		echo "                              <option value='2'>Special</option>\n";
		echo "                           </select>\n";
		echo "                        </td>\n";
		echo "                        <td>\n";
		echo "                          <input type='submit' name='add_grade' value='Add' />\n";
		echo "                        </td>\n";
		echo "                     </tr>\n";
		echo "                  </table>\n";
		echo "               </td>\n";
		echo "            </tr>\n";
		echo "         </table>\n"; // End of table
		echo "         <p align='center'>\n";
		echo "            <input type='submit' name='action' value='Update' />\n";
		echo "            <input type='submit' name='action' value='Cancel' />\n";
		echo "         </p>\n";
		echo "      </form>\n";
	} else { // Couldn't find $booktitleindex in book_title table
		echo "      <p align='center'>Can't find book title.  Have you deleted it?</p>\n";
		echo "      <p align='center'><a href='$backLink'>Click here to go back</a></p>\n";
	}
	log_event($LOG_LEVEL_EVERYTHING, "admin/book/modify_title.php", $LOG_ADMIN, 
			"Opened book title $title for editing.");
} else {
	log_event($LOG_LEVEL_ERROR, "admin/book/modify_title.php", 
			$LOG_DENIED_ACCESS, 
			"Attempted to change information about the book title $title.");
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
?>