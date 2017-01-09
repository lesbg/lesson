<?php
/**
 * ***************************************************************
 * admin/makeup/list_students.php (c) 2017 Jonathan Dieter
 *
 * List all makeups for students on an assignment
 * ***************************************************************
 */
if(!isset($_GET['key']))
    die("No makeup index set");

$assignment = dbfuncInt2String($_GET['keyname']);
$title = "Students with makeups in " . htmlspecialchars($assignment, ENT_QUOTES);
$makeup_assignment_index = safe(dbfuncInt2String($_GET['key']));

include "header.php"; // Show header

if (!$is_admin) {
   /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "admin/makeup/list_students.php", $LOG_DENIED_ACCESS,
            "Attempted to view list of makeup exams.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    exit(0);
}

$query =    "SELECT CONCAT(user.FirstName, ' ', user.Surname, ' (', user.Username, ')') AS Student, " .
            "       CONCAT_WS(' ', rq_user.Title, rq_user.FirstName, rq_user.Surname, " .
            "                 CONCAT('(', rq_user.Username, ')')) AS Requester, " .
            "       ROUND(mark.Percentage) AS Percentage, makeup_user.Requested, " .
            "       makeup_user.RequestTime, makeup_user.Mandatory, user.Username " .
            "FROM makeup_user INNER JOIN makeup_assignment USING (MakeupAssignmentIndex) " .
            "                 INNER JOIN assignment USING (AssignmentIndex) " .
            "                 INNER JOIN user USING (Username) " .
            "                 LEFT OUTER JOIN user AS rq_user ON (rq_user.Username = makeup_user.RequestUsername) " .
            "                 LEFT OUTER JOIN mark ON " .
            "                  (mark.AssignmentIndex=assignment.AssignmentIndex " .
            "                   AND mark.Username = makeup_user.Username) " .
            "WHERE makeup_user.MakeupAssignmentIndex=$makeup_assignment_index " .
            "ORDER BY user.FirstName, user.Surname, user.Username";
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
echo "            <th>Student</th>\n";
echo "            <th>Original mark</th>\n";
echo "            <th>Mandatory?</th>\n";
echo "            <th>Registered?</th>\n";
echo "         </tr>\n";

$alt_count = 0;
while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
    $alt_count += 1;
    if ($alt_count % 2 == 0) {
        $alt = " class=\"alt\"";
    } else {
        $alt = " class=\"std\"";
    }

    $regdate = date($dateformat, strtotime($row['RequestTime']));
    $regtime = date("g:iA", strtotime($row['RequestTime']));

    $studentlink =  "index.php?location=" .
                 dbfuncString2Int("student/makeups.php") . // link to create a new subject
                 "&amp;key=" . dbfuncString2Int($row['Username']) .
                 "&amp;keyname=" . dbfuncString2Int($row['Student']);

    $name = htmlspecialchars($row['Student'], ENT_QUOTES);
    if($row['Mandatory'] == 1) {
        $mandatory = "Y";
    } else {
        $mandatory = "N";
    }

    if($row['Requested'] == 1) {
        $regname = htmlspecialchars($row['Requester'], ENT_QUOTES);
        $registered = "<a title='Registered by $regname at $regtime on $regdate'>Y</a>";
    } else {
        $registered = "N";
    }

    echo "         <tr$alt>\n";
    echo "            <td>&nbsp;</td>";
    echo "            <td><a href='$studentlink'>$name</a></td>\n";
    echo "            <td>{$row['Percentage']}</td>\n";
    echo "            <td>$mandatory</td>\n";
    echo "            <td>$registered</td>";
    echo "         </tr>\n";
}

echo "         </table>\n";

include "footer.php";
