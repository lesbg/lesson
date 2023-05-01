<?php
/**
 * ***************************************************************
 * student/makeups.php (c) 2017 Jonathan Dieter
 *
 * List all makeups for a student
 * ***************************************************************
 */

$student_username = safe(dbfuncInt2String($_GET['key']));
$student_name = htmlspecialchars(dbfuncInt2String($_GET['keyname']), ENT_QUOTES);
$title = "Makeups for " . $student_name;
if(isset($_GET['key2']))
    $show_all = intval(dbfuncInt2String($_GET['key2']));
else
    $show_all = 0;

include "header.php"; // Show header

/* Check whether current user is principal */
$res = &  $db->query(
                "SELECT Username FROM principal " .
                 "WHERE Username=\"$username\" AND Level=1");
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
    $is_principal = true;
} else {
    $is_principal = false;
}

/* Check whether current user is a counselor */
$res = &  $db->query(
                "SELECT Username FROM counselorlist " .
                 "WHERE Username=\"$username\"");
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
    $is_counselor = true;
} else {
    $is_counselor = false;
}

/* Check whether current user is a hod */
$query = "SELECT hod.Username FROM hod, class, classterm, classlist " .
         "WHERE hod.Username='$username' " .
         "AND hod.DepartmentIndex = class.DepartmentIndex " .
         "AND classlist.Username = '$studentusername' " .
         "AND classlist.ClassTermIndex = classterm.ClassTermIndex " .
         "AND classterm.ClassIndex = class.ClassIndex";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
    $is_hod = true;
} else {
    $is_hod = false;
}

/* Check whether current user is student's guardian */
$query =    "SELECT familylist.Username FROM " .
        "    familylist INNER JOIN familylist AS familylist2 ON (familylist.FamilyCode=familylist2.FamilyCode) " .
        "WHERE familylist.Username         = '$studentusername' " .
        "AND   familylist2.Username        = '$username' " .
        "AND   familylist2.Guardian        = 1 ";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
    $is_guardian = true;
} else {
    $is_guardian = false;
}

if (!$is_admin and !$is_hod and !$is_counselor and !$is_principal and !$is_guardian and
     $student_username != $username) {

   /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "student/makeups.php", $LOG_DENIED_ACCESS,
            "Attempted to view list of $student_name's makeup exams.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    exit(0);
}

// Handle exam registration
if(isset($_POST)) {
    foreach($_POST as $key => $value) {
        if(substr($key, 0, 7) != "action-")
            continue;

        $index = intval(substr($key, 7));
        $query =    "SELECT makeup_user.Username, CURDATE() <= makeup.CloseDate AS RegOpen FROM " .
                    "       makeup_user INNER JOIN makeup_assignment USING (MakeupAssignmentIndex) " .
                    "                   INNER JOIN makeup USING (MakeupIndex) " .
                    "WHERE MakeupUserIndex=$index " .
                    "AND   makeup_user.Username='$student_username'";

        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query

        if(!$row = & $res->fetchRow(DB_FETCHMODE_ASSOC))
            die("Registration index doesn't match username");

        if($row['RegOpen'] != 1 and !$is_admin)
            die("Registration is closed");

        if($value == "Register") {
            $req_value = 1;
        } elseif($value == "Unregister") {
            $req_value = 0;
        } else {
            continue;
        }

        $query =    "UPDATE makeup_user SET " .
                    "   RequestUsername='$username', " .
                    "   RequestTime=NOW(), " .
                    "   Requested=$req_value " .
                    "WHERE MakeupUserIndex=$index";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query
    }
}

$query =    "SELECT assignment.Title, assignment.AssignmentIndex, " .
            "       assignment.Date, mark.Percentage, " .
            "       makeup.MakeupDate, makeup.CloseDate, CURDATE() <= makeup.CloseDate AS RegOpen, " .
            "       subject.Name AS SubjectName, makeup_assignment.MakeupAssignmentIndex, " .
            "       makeup_user.Mandatory, makeup_user.Requested, makeup_user.RequestTime, " .
            "       makeup_user.MakeupUserIndex, " .
            "       CONCAT_WS(' ', rq_user.Title, rq_user.FirstName, rq_user.Surname, " .
            "                 CONCAT('(', rq_user.Username, ')')) AS Requester, " .
            "       GROUP_CONCAT(DISTINCT CONCAT(user.Title, ' ', " .
            "                           user.FirstName, ' ', " .
            "                           user.Surname, " .
            "                           ' (', user.Username, ')') SEPARATOR '<br>') AS Teacher " .
            "FROM makeup_user " .
            "              INNER JOIN makeup_assignment USING (MakeupAssignmentIndex) " .
            "              INNER JOIN makeup USING (MakeupIndex) " .
            "              INNER JOIN assignment USING (AssignmentIndex) " .
            "              INNER JOIN subject ON subject.SubjectIndex=assignment.SubjectIndex " .
            "              INNER JOIN subjectteacher ON subject.SubjectIndex=subjectteacher.SubjectIndex " .
            "              INNER JOIN user ON subjectteacher.Username = user.Username " .
            "              LEFT OUTER JOIN user AS rq_user ON makeup_user.RequestUsername=rq_user.Username " .
            "              LEFT OUTER JOIN mark " .
            "                ON  makeup_user.Username=mark.Username " .
            "                AND mark.AssignmentIndex=assignment.AssignmentIndex " .
            "WHERE makeup_user.Username='$student_username' ";
if($show_all != 1) {
    $query .=   "AND makeup.MakeupDate >= CURDATE() ";
}
$query .=   "GROUP BY makeup_user.MakeupUserIndex " .
            "ORDER BY makeup.MakeupDate DESC, SubjectName, Title";

$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

$link = "index.php?location=" .
        dbfuncString2Int("student/makeups.php") .
        "&amp;key=" . $_GET['key'] .
        "&amp;keyname=" . $_GET['keyname'] .
        "&amp;key2=" . dbfuncString2Int($show_all);

echo "      <form action='$link' method='post'>\n"; // Form method
if(!$show_all) {
    $show_link = "index.php?location=" .
                 dbfuncString2Int("student/makeups.php") . // link to create a new subject
                 "&amp;key=" . $_GET['key'] .
                 "&amp;keyname=" . $_GET['keyname'] .
                 "&amp;key2=" . dbfuncString2Int(1);
    $show_button = dbfuncGetButton($show_link, "Show all", "medium", "",
                            "Show all makeups");
} else {
    $show_link = "index.php?location=" .
                 dbfuncString2Int("student/makeups.php") . // link to create a new subject
                 "&amp;key=" . $_GET['key'] .
                 "&amp;keyname=" . $_GET['keyname'] .
                 "&amp;key2=" . dbfuncString2Int(0);
    $show_button = dbfuncGetButton($show_link, "Show current", "medium", "",
                            "Show current makeups");
}

echo "         <p align='center'>$show_button</p>\n";

if($res->numRows() == 0) {
    echo "      <p align='center'>There are no makeups.</p>\n";
    include "footer.php";
    exit(0);
}

echo "         <table align='center' border='1'>\n"; // Table headers
echo "            <tr>\n";
echo "               <th>&nbsp;</th>\n";
echo "               <th>Subject</th>\n";
echo "               <th>Assignment</th>\n";
echo "               <th>Original mark</th>\n";
echo "               <th>Makeup date</th>\n";
echo "               <th>Registration deadline</th>\n";
echo "               <th>Registration open</th>\n";
echo "               <th>Mandatory?</th>\n";
echo "               <th>Registered?</th>\n";
echo "            </tr>\n";

$alt_count = 0;
while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
    $alt_count += 1;
    if ($alt_count % 2 == 0) {
        $alt = " class=\"alt\"";
    } else {
        $alt = " class=\"std\"";
    }

    $makeup_date = date($dateformat, strtotime($row['MakeupDate']));
    $close_date = date($dateformat, strtotime($row['CloseDate']));
    $regdate = date($dateformat, strtotime($row['RequestTime']));
    $regtime = date("g:iA", strtotime($row['RequestTime']));

    $name = htmlspecialchars($row['Title'], ENT_QUOTES);
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

    if($row['RegOpen'] == 1 or $is_admin) {
        $regopen = "Y";
        $regdisabled = "";
        $regtitle = "";
    } else {
        $regopen = "N";
        $regdisabled = "disabled";
        $regtitle = "Registration is closed";
    }

    if($row['Mandatory'] == 1) {
        $regdisabled = "disabled";
        $regtitle = "Makeup is mandatory";
    }

    if($row['Requested'] == 1) {
        $reg_link = "<input type='submit' name='action-{$row['MakeupUserIndex']}' value='Unregister' title='$regtitle' $regdisabled/>";
        $em = "<strong>";
        $unem = "</strong>";
    } else {
        $reg_link = "<input type='submit' name='action-{$row['MakeupUserIndex']}' value='Register' title='$regtitle' $regdisabled/>";
        $em = "";
        $unem = "";
    }

    $percentage = round($row['Percentage']);
    echo "            <tr$alt>\n";
    echo "               <td>$em$reg_link$unem</td>";
    echo "               <td>$em{$row['SubjectName']}$unem</td>\n";
    echo "               <td>$em$name$unem</td>\n";
    echo "               <td>$em$percentage$unem</td>\n";
    echo "               <td>$em$makeup_date$unem</td>\n";
    echo "               <td>$em$close_date$unem</td>\n";
    echo "               <td>$em$regopen$unem</td>\n";
    echo "               <td>$em$mandatory$unem</td>\n";
    echo "               <td>$em$registered$unem</td>\n";
    echo "            </tr>\n";
}

echo "         </table>\n";
echo "      </form>\n";

include "footer.php";
