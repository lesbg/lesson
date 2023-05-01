<?php
// FIX CLASS STUFF

/**
 * ***************************************************************
 * admin/print/marks_by_subject_type.php (c) 2005-2007 Jonathan Dieter
 *
 * Show printable marks by a subject type.
 * ***************************************************************
 */

/* Get variables */
$title = dbfuncInt2String($_GET['keyname']) . " " .
		 dbfuncInt2String($_GET['key2name']);
$classindex = dbfuncInt2String($_GET['key']);
$subjecttypeindex = dbfuncInt2String($_GET['key2']);

$showalldeps = true;
include "core/settermandyear.php";

echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" " .
	 "\"http://www.w3.org/TR/html4/loose.dtd\">\n";
echo "<html>\n";
echo "   <head>\n";
echo "      <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">\n";
echo "      <title>$title</title>\n";
echo "      <link rel=\"StyleSheet\" href=\"css/print.css\" title=\"Printable colors\" type=\"text/css\" media=\"screen\">\n";
echo "      <link rel=\"StyleSheet\" href=\"css/print.css\" title=\"Printable colors\" type=\"text/css\" media=\"print\">\n";
echo "   </head>\n";
echo "   <body>\n";
echo "      <table class=\"transparent\" width=\"100%\">\n";
echo "         <tr>\n";
echo "            <td width=\"120px\" class=\"logo\"><img height=\"73\" width=\"75\" alt=\"LESB&G Logo\" src=\"images/lesbg-small.gif\"></td>\n";
echo "            <td class=\"title\">$title</td>\n";
echo "            <td width=\"120px\" class=\"home\">\n";

if (dbfuncGetPermission($permissions, $PERM_ADMIN)) {
	
	/* Get student list */
	$query = "SELECT user.FirstName, user.Surname, user.Username, classlist.ClassOrder, " .
			 "       query.SubjectIndex, query.Average FROM user, classlist LEFT OUTER JOIN " .
			 "         ((SELECT subject.SubjectIndex, subjectstudent.Username, subjectstudent.Average " .
			 "           FROM subject, subjectstudent, class " .
			 "           WHERE  subjectstudent.SubjectIndex = subject.SubjectIndex " .
			 "           AND    subject.SubjectTypeIndex = $subjecttypeindex " .
			 "           AND    subject.Grade = class.Grade " .
			 "           AND    subject.ClassIndex IS NULL " .
			 "           AND    subject.TermIndex = $termindex " .
			 "           AND    class.ClassIndex = $classindex) " .
			 "          UNION " .
			 "          (SELECT subject.SubjectIndex, subjectstudent.Username, subjectstudent.Average " .
			 "           FROM subject, subjectstudent, class " .
			 "           WHERE  subjectstudent.SubjectIndex = subject.SubjectIndex " .
			 "           AND    subject.SubjectTypeIndex = $subjecttypeindex " .
			 "           AND    subject.ClassIndex = class.ClassIndex " .
			 "           AND    subject.Grade IS NULL " .
			 "           AND    subject.TermIndex = $termindex) " .
			 "         ) AS query " .
			 "       ON classlist.Username = query.Username " .
			 "WHERE user.Username = classlist.Username " .
			 "AND   classlist.ClassIndex = $classindex " .
			 "ORDER BY classlist.ClassOrder, user.Username";
	$res = &  $db->query($query);
	if (DB::isError($res))
		die($res->getDebugInfo()); // Check for errors in query
		
	/* Print assignments and scores */
	if ($res->numRows() > 0) {
		$nochangeyt = true;
		$total_average = 0;
		$sum_average = 0;
		
		include "core/titletermyear.php";
		echo "            </td>\n";
		echo "         </tr>\n";
		echo "      </table>\n";
		
		echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
		echo "         <tr>\n";
		echo "            <th>&nbsp;</th>\n";
		echo "            <th>Student</th>\n";
		
		/* For each student, print a row with the student's name and score on each assignment */
		$alt_count = 0;
		$order = 1;
		while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
			$subjectindex = $row['SubjectIndex'];
			$total = 0;
			$studentscore = 0;
			
			echo "         <tr>\n";
			echo "            <td nowrap>$order</td>\n";
			$order += 1;
			echo "            <td nowrap>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
			
			if (! is_null($row['SubjectIndex'])) {
				if ($row['Average'] == - 1 or $row['Average'] == NULL) {
					echo "            <td nowrap align=\"center\">-</td>\n";
				} else {
					$average = round($row['Average']);
					echo "            <td nowrap>$average%</td>\n";
				}
			} else {
				echo "            <td nowrap align=\"center\">-</td>\n";
			}
			echo "         </tr>\n";
		}
		echo "         <tr>\n";
		echo "            <td nowrap>&nbsp;</td>\n";
		echo "            <td nowrap><i>Class Average</i></td>\n";
		if ($total_average > 0) {
			$class_average = round(($sum_average / $total_average) * 100);
			echo "            <td nowrap><i>$class_average%</i></td>\n";
		} else {
			echo "            <td nowrap><i>N/A</i></td>\n";
		}
		echo "         </tr>\n";
		echo "      </table>\n"; // End of table
	} else {
		echo "      <p>No students</p>\n";
	}
	log_event($LOG_LEVEL_EVERYTHING, "admin/print/marks_by_subject_type.php", 
			$LOG_ADMIN, "Accessed printable marks for $title.");
} else { // User isn't authorized to view or change scores.
	/* Log unauthorized access attempt */
	log_event($LOG_LEVEL_ERROR, "admin/print/marks_by_subject_type.php", 
			$LOG_DENIED_ACCESS, "Tried to access printable marks for $title.");
	
	echo "      <p>You do not have permission to access this page</p>\n";
	echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

include "footer.php";
?>
