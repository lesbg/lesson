<?php
/**
 * ***************************************************************
 * parent/list_children.php (c) 2016 Jonathan Dieter
 *
 * List all children of a parent
 * ***************************************************************
 */

$title = "Children";
include "header.php";

include "core/settermandyear.php";
include "core/titletermyear.php";

if($yearindex > $currentyear) {
	$query =	"SELECT user.Username, user.FirstName, user.Surname, NULL AS SubjectCount, grade.GradeName AS ClassName, NULL AS TermIndex FROM " .
				"		classlist INNER JOIN classterm USING (ClassTermIndex) " .
				"                 INNER JOIN term ON (classterm.TermIndex=term.TermIndex AND term.TermNumber=1) " .
				"                 INNER JOIN class ON (classterm.ClassIndex=class.ClassIndex) " .
				"                 INNER JOIN grade USING (Grade) " .
				"                 INNER JOIN familylist USING (Username) " .
				"                 INNER JOIN user USING (Username) " .
				"                 INNER JOIN familylist AS familylist2 ON (familylist.FamilyCode=familylist2.FamilyCode) " .
				"WHERE class.YearIndex             = $yearindex " .
				"AND   familylist2.Username        = '$username' " .
				"AND   familylist2.Guardian        = 1 " .
				"GROUP BY user.Username " .
				"ORDER BY class.Grade DESC, user.FirstName, user.Surname, user.Username";
} else {
	$query =	"SELECT user.Username, user.FirstName, user.Surname, COUNT(subject.SubjectIndex) AS SubjectCount, class.ClassName, classterm.TermIndex FROM " .
				"		subject INNER JOIN subjectstudent USING (SubjectIndex) " .
				"               INNER JOIN classlist USING (Username) " .
				"               INNER JOIN currentterm USING (TermIndex) " .
				"               INNER JOIN classterm USING (ClassTermIndex) " .
				"               INNER JOIN class ON (classterm.ClassIndex=class.ClassIndex) " .
				"               INNER JOIN familylist USING (Username) " .
				"               INNER JOIN user USING (Username) " .
				"               INNER JOIN familylist AS familylist2 ON (familylist.FamilyCode=familylist2.FamilyCode) " .
				"WHERE subject.ShowInList          = 1 " .
				"AND   subject.YearIndex           = $yearindex " .
				"AND   class.YearIndex             = $yearindex " .
				"AND   classterm.TermIndex         = currentterm.TermIndex " .
				"AND   familylist2.Username        = '$username' " .
				"AND   familylist2.Guardian        = 1 " .
				"GROUP BY user.Username " .
				"ORDER BY class.Grade DESC, user.FirstName, user.Surname, user.Username";
}
$nrs = &  $db->query($query);

if (DB::isError($nrs))
	die($nrs->getDebugInfo()); // Check for errors in query
/* If user is a support teacher for at least one student, show student information */
if ($nrs->numRows() > 0) {
	echo "      <table align='center' border='1'>\n"; // Table headers
	echo "         <tr>\n";
	echo "            <th>Child</th>\n";
	echo "            <th>Class</th>\n";
	echo "            <th>Subjects</th>\n";
	echo "         </tr>\n";

	/* For each class, print a row with the subject name and number of students */
	$alt_count = 0;
	while ( $row = & $nrs->fetchRow(DB_FETCHMODE_ASSOC) ) {
		$alt_count += 1;
		if ($alt_count % 2 == 0) {
			$alt = " class='alt'";
		} else {
			$alt = " class='std'";
		}
		$row['FirstName'] = htmlspecialchars($row['FirstName'], ENT_QUOTES);
		$row['Surname'] = htmlspecialchars($row['Surname'], ENT_QUOTES);

		echo "         <tr$alt>\n";
		
		if(!is_null($row['TermIndex'])) {
			$namelink = "index.php?location=" .
					dbfuncString2Int("admin/subject/list_student.php") . "&amp;key=" .
					dbfuncString2Int($row['Username']) . "&amp;keyname=" .
					dbfuncString2Int("{$row['FirstName']} {$row['Surname']}") . "&amp;key2=" .
					dbfuncString2Int($row['TermIndex']);
			echo "            <td><a href='$namelink'>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</a></td>\n";
		} else {
			echo "            <td>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
		}
		echo "            <td>{$row['ClassName']}</td>\n"; // Print class
		echo "            <td>{$row['SubjectCount']}</td>\n"; // Print subject count
		echo "         </tr>\n";
	}
	echo "      </table>\n"; // End of table
} else {
	echo "      <p>You have no children currently in the school</p>\n";
}

/* Closing tags */
include "footer.php";