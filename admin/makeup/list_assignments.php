<?php
/**
 * ***************************************************************
 * admin/makeup/list_assignments.php (c) 2017 Jonathan Dieter
 *
 * List all makeups
 * ***************************************************************
 */
if(!isset($_GET['key']))
    die("No makeup index set");

$makeup_date = dbfuncInt2String($_GET['keyname']);
$title = "Makeup on " . htmlspecialchars($makeup_date, ENT_QUOTES);
$makeup_index = safe(dbfuncInt2String($_GET['key']));

include "header.php"; // Show header

if (!$is_admin) {
   /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "admin/makeup/list_assignments.php", $LOG_DENIED_ACCESS,
            "Attempted to view list of makeup exams.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    exit(0);
}

$query =    "SELECT assignment.Title, assignment.AssignmentIndex, " .
            "       assignment.Date, ROUND(assignment.Average) AS Average, " .
            "       subject.Name AS SubjectName, makeup_assignment.MakeupAssignmentIndex, " .
            "       GROUP_CONCAT(DISTINCT CONCAT(user.Title, ' ', " .
            "                           user.FirstName, ' ', " .
            "                           user.Surname, " .
            "                           ' (', user.Username, ')') SEPARATOR '<br>') AS Teacher, " .
            "       COUNT(DISTINCT subjectstudent.Username) AS StudentCount, " .
            "       COUNT(DISTINCT CASE WHEN makeup_user.Mandatory=1 THEN makeup_user.Username END) " .
            "             AS Mandatory, " .
            "       COUNT(DISTINCT CASE WHEN makeup_user.Mandatory=0 THEN makeup_user.Username END) " .
            "             AS Optional, " .
            "       COUNT(DISTINCT CASE WHEN makeup_user.Requested=1 THEN makeup_user.Username END) " .
            "             AS Requested " .
            "FROM makeup_assignment " .
            "              INNER JOIN assignment USING (AssignmentIndex) " .
            "              INNER JOIN subject ON subject.SubjectIndex=assignment.SubjectIndex " .
            "              INNER JOIN subjectteacher ON subject.SubjectIndex=subjectteacher.SubjectIndex " .
            "              INNER JOIN subjectstudent ON subject.SubjectIndex=subjectstudent.SubjectIndex " .
            "              INNER JOIN user ON subjectteacher.Username = user.Username " .
            "              LEFT OUTER JOIN makeup_user ON " .
            "                (makeup_user.MakeupAssignmentIndex=makeup_assignment.MakeupAssignmentIndex) " .
            "WHERE makeup_assignment.MakeupIndex = $makeup_index " .
            "GROUP BY assignment.AssignmentIndex " .
            "ORDER BY assignment.Date DESC, SubjectName, Title";

$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if($res->numRows() == 0) {
    echo "      <p align='center'>There are no makeups.</p>\n";
    include "footer.php";
    exit(0);
}

echo "      <table align='center' border='1'>\n"; // Table headers
echo "         <tr>\n";
echo "            <th>&nbsp;</th>\n";
echo "            <th>Assignment</th>\n";
echo "            <th>Subject</th>\n";
echo "            <th>Teacher</th>\n";
echo "            <th>Date</th>\n";
echo "            <th>Avg</th>\n";
echo "            <th><a title='Student count'>S</a></th>\n";
echo "            <th><a title='Registered for makeups'>R</a></th>\n";
echo "            <th><a title='Mandatory makeups'>M</a></th>\n";
echo "            <th><a title='Optional makeups'>O</a></th>\n";
echo "         </tr>\n";

$alt_count = 0;
while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
    $alt_count += 1;
    if ($alt_count % 2 == 0) {
        $alt = " class=\"alt\"";
    } else {
        $alt = " class=\"std\"";
    }

    $assignmentdate = date($dateformat, strtotime($row['Date']));

    $viewlink =  "index.php?location=" .
                 dbfuncString2Int("admin/makeup/list_students.php") . // link to create a new subject
                 "&amp;key=" . dbfuncString2Int($row['MakeupAssignmentIndex']) .
                 "&amp;keyname=" . dbfuncString2Int($row['SubjectName'] . ' ' . $row['Title']);

    $viewbutton = dbfuncGetButton($viewlink, "V", "small", "view",
                            "View makeups for assignment");
    echo "         <tr$alt>\n";
    echo "            <td>$viewbutton</td>";
    echo "            <td>{$row['Title']}</td>\n";
    echo "            <td>{$row['SubjectName']}</td>\n";
    echo "            <td>{$row['Teacher']}</td>\n";
    echo "            <td>$assignmentdate</td>";
    echo "            <td>{$row['Average']}</td>\n";
    echo "            <td>{$row['StudentCount']}</td>\n";
    echo "            <td>{$row['Requested']}</td>\n";
    echo "            <td>{$row['Mandatory']}</td>\n";
    echo "            <td>{$row['Optional']}</td>\n";
    echo "         </tr>\n";
}

echo "         </table>\n";

include "footer.php";
