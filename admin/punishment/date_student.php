<?php
/**
 * ***************************************************************
 * admin/punishment/date_student.php (c) 2006-2017 Jonathan Dieter
 *
 * Set date of next punishment for all students up to set date
 * ***************************************************************
 */

/* Get variables */
if (! isset($_GET['type'])) {
    if (! isset($_POST['type'])) {
        $link = "index.php?location=" .
                 dbfuncString2Int("admin/punishment/date_student.php") .
                 "&amp;next=" . $_GET['next'];
        include "admin/punishment/choose_type.php";
        exit(0);
    } else {
        $_GET['type'] = dbfuncString2Int($_POST['type']);
    }
}
$dtype = dbfuncInt2String($_GET['type']);

$query = "SELECT user.FirstName, user.Surname, user.Username FROM " .
         "       user INNER JOIN groupgenmem ON (user.Username=groupgenmem.Username) " .
         "            INNER JOIN groups USING (GroupID) " .
         "WHERE user.Username='$username' " .
         "AND   groups.GroupTypeID='activeteacher' " .
         "AND   groups.YearIndex=$yearindex " .
         "ORDER BY user.Username";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($res->numRows() > 0) {
    $is_teacher = true;
} else {
    $is_teacher = false;
}
$query = "SELECT Permissions FROM disciplineperms WHERE Username='$username'";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    $perm = $row['Permissions'];
} else {
    $perm = $DEFAULT_PUN_PERM;
}

$query = "SELECT DisciplineType " . "       FROM disciplinetype " .
         "WHERE  disciplinetype.DisciplineTypeIndex = $dtype ";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    $disc = strtolower($row['DisciplineType']);
} else {
    $disc = "unknown punishment";
}

$query = "SELECT DisciplineDateIndex, PunishDate, EndDate FROM disciplinedate " .
         "WHERE DisciplineTypeIndex = $dtype " .
         "AND   Done = 0";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    $pindex = $row['DisciplineDateIndex'];
    $enddate = $row['EndDate'];
    $pundate = $row['PunishDate'];
} else {
    $_GET['next'] = dbfuncString2Int(
                                    "index.php?location=" .
                                     dbfuncString2Int(
                                                    "admin/punishment/date_student.php") .
                                     "&amp;type=" . $_GET['type'] . "&amp;next=" .
                                     $_GET['next']);
    include "admin/punishment/set_date.php";
    exit(0);
}

$title = "Students to be punished during next $disc";
/* Make sure user has permission to view student's marks for subject */
if (!dbfuncGetPermission($permissions, $PERM_ADMIN) and
     ($perm < $PUN_PERM_ALL or !$is_teacher)) {
    include "header.php";

    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "admin/punishment/date_student.php",
            $LOG_DENIED_ACCESS, "Tried to set next punishment date.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    exit(0);
}

if ($_POST["action"] == "Check all") {
    $check_all = 1;
} elseif ($_POST["action"] == "Uncheck all") {
    $check_all = - 1;
} else {
    $check_all = 0;
}
include "header.php";

$link = "index.php?location=" .
         dbfuncString2Int("admin/punishment/date_student_action.php") .
         "&amp;type=" . $_GET['type'] . "&amp;next=" . $_GET['next'];
$query =    "SELECT user.Username, user.FirstName, user.Surname, " .
            "       SUBSTRING_INDEX( " .
            "           GROUP_CONCAT(discipline.Date ORDER BY discipline.Date, " .
            "                        discipline.DisciplineIndex SEPARATOR '*/*/'), " .
            "           '*/*/', 1) AS Date, " .
            "       SUBSTRING_INDEX( " .
            "           GROUP_CONCAT(discipline.Comment ORDER BY discipline.Date, " .
            "                        discipline.DisciplineIndex SEPARATOR '*/*/'), " .
            "           '*/*/', 1) AS Comment, " .
            "       SUBSTRING_INDEX( " .
            "           GROUP_CONCAT(discipline.DisciplineIndex ORDER BY discipline.Date, " .
            "                        discipline.DisciplineIndex SEPARATOR '*/*/'), " .
            "           '*/*/', 1) AS DisciplineIndex, " .
            "       SUBSTRING_INDEX( " .
            "           GROUP_CONCAT(CONCAT(teacher.Title, ' ', teacher.FirstName, ' ', teacher.Surname) " .
            "                        ORDER BY discipline.Date, discipline.DisciplineIndex SEPARATOR '*/*/'), " .
            "           '*/*/', 1) AS Teacher, " .
            "       class.ClassName, disciplinedate.PunishDate " .
            "       FROM user INNER JOIN discipline USING (Username) " .
            "                 INNER JOIN user AS teacher ON (teacher.Username=discipline.WorkerUsername) " .
            "                 INNER JOIN disciplineweight USING (DisciplineWeightIndex) " .
            "                 INNER JOIN classlist ON (user.Username=classlist.Username) " .
            "                 INNER JOIN classterm USING (ClassTermIndex) " .
            "                 INNER JOIN class USING (ClassIndex) " .
            "                 LEFT OUTER JOIN disciplinedate USING (DisciplineDateIndex) " .
            "                 LEFT OUTER JOIN (attendance INNER JOIN period USING (PeriodIndex)) " .
            "                 ON ( " .
            "                  discipline.Username = attendance.Username " .
            "                  AND attendance.Date = '$pundate' " .
            "                  AND period.Period = 1 " .
            "                 ) " .
            "                 LEFT OUTER JOIN attendancetype USING (AttendanceTypeIndex) " .
            "WHERE  disciplineweight.YearIndex = $yearindex " .
            "AND    ((disciplinedate.Done=0 OR disciplinedate.PunishDate IS NULL) AND discipline.Date <= '$enddate') " .
            "AND    disciplineweight.DisciplineTypeIndex = $dtype " .
            "AND    class.YearIndex = $yearindex " .
            "AND    classterm.TermIndex = $termindex " .
            "AND    class.DepartmentIndex = $depindex " .
            "GROUP BY user.Username " .
            "ORDER BY class.Grade, class.ClassName, discipline.Username ";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() == 0) {
    echo "      <p align='center' class='subtitle'>No punishments of this type have been issued and not punished yet up to {$_POST['date']}.</p>\n";
    exit(0);
}

/* Print punishments */
echo "      <form action='$link' method='post' name='pundate'>\n"; // Form method

echo "      <p align='center'>\n";
echo "         <input type='submit' name='action' value='Edit'>&nbsp; \n";
echo "         <input type='submit' name='action' value='Check all'>&nbsp; \n";
echo "         <input type='submit' name='action' value='Uncheck all'>&nbsp; \n";
echo "         <input type='submit' name='action' value='Done'> \n";
echo "      </p>\n";
echo "      <table align='center' border='1'>\n"; // Table headers
echo "         <tr>\n";
echo "            <th>&nbsp;</th>\n";
echo "            <th>Student</th>\n";
echo "            <th>Class</th>\n";
echo "            <th>Teacher</th>\n";
echo "            <th>Violation Date</th>\n";
echo "            <th>Reason</th>\n";
echo "         </tr>\n";

/* For each assignment, print a row with the title, date, score and comment */
$alt_count = 0;
while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
    $alt_count += 1;
    if ($alt_count % 2 == 0) {
        $alt_step = "alt";
    } else {
        $alt_step = "std";
    }
    if ($check_all == 0) {
        if (isset($_POST['mass'][$row['Username']])) {
            if ($_POST['mass'][$row['Username']] == "on") {
                $checked = "checked";
            } else {
                $checked = "";
            }
        } else {
            if (! is_null($row['PunishDate'])) {
                $checked = "checked";
            } else {
                $checked = "";
            }
        }
    } elseif ($check_all == 1) {
        $checked = "checked";
    } else {
        $checked = "";
    }
    $alt = " class='$alt_step'";
    echo "         <tr$alt>\n";
    echo "            <td><input type='checkbox' name='mass[]' value='{$row['DisciplineIndex']}' id='check{$row['Username']}' $checked></input></td>\n";
    echo "            <td><label for='check{$row['Username']}'>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</label></td>\n";
    echo "            <td><label for='check{$row['Username']}'>{$row['ClassName']}</label></td>\n";
    echo "            <td nowrap><label for='check{$row['Username']}'>{$row['Teacher']}</label></td>\n";
    $dateinfo = date($dateformat, strtotime($row['Date']));
    echo "            <td><label for='check{$row['Username']}'>$dateinfo</label></td>\n";
    echo "            <td><label for='check{$row['Username']}'>{$row['Comment']}</label></td>\n";
    echo "         </tr>\n";
}
echo "      </table>\n";
echo "      </form>\n";

include "footer.php";
?>
