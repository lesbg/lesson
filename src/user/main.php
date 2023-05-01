<?php
/**
 * ***************************************************************
 * user/main.php (c) 2004-2018 Jonathan Dieter
 *
 * Initial page that shows what classes the user is in, if they
 * are a student or what classes they teach if they are a teacher
 * ***************************************************************
 */

/* Title */
$title = $fullname;
$noJS = true;

/* Logout link */
$homelink = "index.php?location=" . dbfuncString2Int("user/logout.php");
$homebutton = dbfuncGetButton($homelink, "Logout", "small", "logout",
                            "Logout of LESSON");

/* Welcome */
include "header.php";

$main_page = true;
$show_all_deps = true;
include "core/settermandyear.php";
include "core/titletermyear.php";

echo "       <p>&nbsp;</p>\n";
echo "       <div class='button' style='position: absolute; left: 15px'>\n";
/* Check whether Administrator, and show Admin Tool hyperlink if so */
if (dbfuncGetPermission($permissions, $PERM_ADMIN)) {
    $adminToolsLink = "index.php?location=" .
                     dbfuncString2Int("admin/tools.php");
    echo "      <p><a href='$adminToolsLink'>Admin Tools</a></p>\n";
}

/* Provide link for changing password */
$changePWLink = "index.php?location=" .
                 dbfuncString2Int("user/changepassword.php");
echo "      <p><a href='$changePWLink'>Change Password</a></p>\n";

if($activestudent or $activeteacher) {
    $timetableLink = "index.php?location=" .
                     dbfuncString2Int("user/timetable.php") . "&amp;key=" .
                     dbfuncString2Int($username) . "&amp;keyname=" .
                     dbfuncString2Int($fullname);
    echo "      <p><a href='$timetableLink'>Timetable</a></p>\n";
}

$is_staff = False;

/* If user is a class teacher and there are class reports ready, show link */
$query = $pdb->prepare(
    "SELECT class.ClassIndex, class.ClassName, " .
     "       classterm.CanDoReport, MIN(classlist.ReportDone) AS ReportDone " .
     "       FROM class, classterm, classlist " .
     "WHERE class.ClassTeacherUsername = :username " .
     "AND   class.YearIndex            = :yearindex " .
     "AND   classterm.ClassIndex       = class.ClassIndex " .
     "AND   classterm.TermIndex        = :termindex " .
     "AND   classlist.ClassTermIndex   = classterm.ClassTermIndex " .
     "GROUP BY class.ClassIndex"
);
$query->execute(['username' => $username, 'yearindex' => $yearindex,
                 'termindex' => $termindex]);

foreach ( $query as $row ) {
    if($row['CanDoReport'] or $row['ReportDone']) {
        $clLink = "index.php?location=" .
                 dbfuncString2Int("teacher/report/class_list.php") . "&amp;key=" .
                 dbfuncString2Int($row['ClassIndex']) . "&amp;keyname=" .
                 dbfuncString2Int($row['ClassName']);
        echo "      <p><a href='$clLink'>Class reports for {$row['ClassName']}</a></p>\n";
    }
}

/* If user is responsible for any books, show book link */
/*
 * $query = "SELECT book_title.BookTitleIndex FROM book_title, book_title_owner " .
 * "WHERE book_title_owner.Username = '$username' " .
 * "AND book_title_owner.YearIndex = $currentyear " .
 * "AND book_title_owner.BookTitleIndex = book_title.BookTitleIndex " .
 * "AND book_title.Retired = 0";
 * $res =& $db->query($query);
 * if(DB::isError($res)) die($res->getDebugInfo()); // Check for errors in query
 *
 * if($res->numRows() > 0) {
 */
if($activestudent or $activestudent) {
    $bookLink = "index.php?location=" .
                 dbfuncString2Int("teacher/book/book_list.php") . "&amp;key=" .
                 dbfuncString2Int($username) . "&amp;keyname=" .
                 dbfuncString2Int($fullname);
    echo "      <p><a href='$bookLink'>Book list</a></p>\n";
}
// }

/* If user is a hod, show class list hyperlink */
$query = $pdb->prepare(
    "SELECT Username FROM hod WHERE Username=:username"
);
$query->execute(['username' => $username]);
$row = $query->fetch();
if ($row) {
    $is_staff = True;
    $clLink = "index.php?location=" . dbfuncString2Int("admin/class/list.php");
    echo "      <p><a href='$clLink'>Class list</a></p>\n";
}

/* If user is a counselor, show class list hyperlink and support teachers hyperlink */
$query = $pdb->prepare(
    "SELECT Username FROM counselorlist WHERE Username=:username"
);
$query->execute(['username' => $username]);
$row = $query->fetch();
if ($row) {
    $is_staff = True;
    $clLink = "index.php?location=" . dbfuncString2Int("admin/class/list.php");
    $supportLink = "index.php?location=" .
                 dbfuncString2Int("admin/support/modify.php") . "&amp;next=" .
                 dbfuncString2Int(
                                "index.php?location=" .
                                 dbfuncString2Int("user/main.php"));
    echo "      <p><a href='$clLink'>Class list</a></p>\n";
    echo "      <p><a href='$supportLink'>Support teachers</a></p>\n";
}

/* If user is an active teacher, show Casenotes and Punishments history hyperlinks */
$query = $pdb->prepare(
    "SELECT user.FirstName, user.Surname, user.Username FROM " .
    "       user INNER JOIN groupgenmem ON (user.Username=groupgenmem.Username) " .
    "            INNER JOIN groups USING (GroupID) " .
    "WHERE user.Username=:username " .
    "AND   groups.GroupTypeID='activeteacher' " .
    "AND   groups.YearIndex=:yearindex " .
    "ORDER BY user.Username"
);
$query->execute(['username' => $username, 'yearindex' => $yearindex]);
$row = $query->fetch();
if($row) {
    $is_staff = True;

    $wlLink = "index.php?location=" .
         dbfuncString2Int("teacher/casenote/watchlist/list.php");

    $query = $pdb->prepare(
        "SELECT COUNT(1) AS RowCount FROM casenotenew " .
        "WHERE  WorkerUsername = :username"
    );
    $query->execute(['username' => $username]);
    $row = $query->fetch();
    if ($row and $row['RowCount'] > 0) {
        echo "      <p><b><a href='$wlLink'>New Casenotes (${row['RowCount']})</a></b></p>\n";
    } else {
        echo "      <p><a href='$wlLink'>New Casenotes (0)</a></p>\n";
    }

    $query = $pdb->prepare(
        "SELECT Permissions FROM disciplineperms WHERE Username=:username"
    );
    $query->execute(['username' => $username]);
    $row = $query->fetch();
    if ($row) {
        $perm = $row['Permissions'];
    } else {
        $perm = $DEFAULT_PUN_PERM;
    }

    if ($perm >= $PUN_PERM_MASS or
         dbfuncGetPermission($permissions, $PERM_ADMIN)) {
        $punLink = "index.php?location=" .
             dbfuncString2Int("admin/punishment/tools.php");
        echo "      <p><a href='$punLink'>Punishment Tools</a></p>\n";
    }
    $disclink = "index.php?location=" .
             dbfuncString2Int("teacher/punishment/list.php") . "&amp;key=" .
             dbfuncString2Int($username) . "&amp;keyname=" .
             dbfuncString2Int($fullname);
    echo "      <p><a href='$disclink'>Issued Punishments</a></p>\n";
}

$query = $pdb->prepare(
    "SELECT CASE WHEN CURDATE() <= MAX(makeup.MakeupDate) THEN 2 " .
    "            WHEN makeup.MakeupDate IS NOT NULL THEN 1 END AS Requested " .
    "FROM makeup_user " .
    "   INNER JOIN makeup_assignment " .
    "       ON (makeup_user.Username=:username " .
    "           AND makeup_assignment.MakeupAssignmentIndex=makeup_user.MakeupAssignmentIndex) " .
    "   INNER JOIN makeup " .
    "       ON (makeup.YearIndex=:yearindex " .
    "           AND makeup.MakeupIndex=makeup_assignment.MakeupIndex) "
);
$query->execute(['username' => $username, 'yearindex' => $yearindex]);
$row = $query->fetch();
if ($row) {
    if(!is_null($row['Requested'])) {
        $makeup_link = "index.php?location=" .
                    dbfuncString2Int("student/makeups.php") .
                    "&amp;key=" .
                    dbfuncString2Int($username) .
                    "&amp;keyname=" .
                    dbfuncString2Int($fullname);
        if($row['Requested'] == 2) {
            $bold = "<strong>";
            $unbold = "</strong>";
        } else {
            $bold = "";
            $unbold = "";
        }
        echo "      <p>$bold<a href='$makeup_link'>Makeups</a>$unbold</p>\n";
    }
}

if($is_staff or $is_admin) {
    if($yearindex > $currentyear) {
        $query =    "SELECT user.Username, user.FirstName, user.Surname, NULL AS SubjectCount, grade.GradeName AS ClassName, NULL AS TermIndex FROM " .
                    "       classlist INNER JOIN classterm USING (ClassTermIndex) " .
                    "                 INNER JOIN term ON (classterm.TermIndex=term.TermIndex AND term.TermNumber=1) " .
                    "                 INNER JOIN class ON (classterm.ClassIndex=class.ClassIndex) " .
                    "                 INNER JOIN grade USING (Grade) " .
                    "                 INNER JOIN familylist USING (Username) " .
                    "                 INNER JOIN user USING (Username) " .
                    "                 INNER JOIN familylist AS familylist2 ON (familylist.FamilyCode=familylist2.FamilyCode) " .
                    "WHERE class.YearIndex             = :yearindex " .
                    "AND   familylist2.Username        = :username " .
                    "AND   familylist2.Guardian        = 1 " .
                    "GROUP BY user.Username " .
                    "ORDER BY class.Grade DESC, user.FirstName, user.Surname, user.Username";
    } else {
        /* Get children of parent */
        $query =    "SELECT user.Username, user.FirstName, user.Surname, COUNT(subject.SubjectIndex) AS SubjectCount, class.ClassName, classterm.TermIndex FROM " .
                    "       subject INNER JOIN subjectstudent USING (SubjectIndex) " .
                    "               INNER JOIN classlist USING (Username) " .
                    "               INNER JOIN currentterm USING (TermIndex) " .
                    "               INNER JOIN classterm USING (ClassTermIndex) " .
                    "               INNER JOIN class ON (classterm.ClassIndex=class.ClassIndex) " .
                    "               INNER JOIN familylist USING (Username) " .
                    "               INNER JOIN user USING (Username) " .
                    "               INNER JOIN familylist AS familylist2 ON (familylist.FamilyCode=familylist2.FamilyCode) " .
                    "WHERE subject.ShowInList          = 1 " .
                    "AND   subject.YearIndex           = :yearindex " .
                    "AND   class.YearIndex             = subject.YearIndex " .
                    "AND   classterm.TermIndex         = currentterm.TermIndex " .
                    "AND   familylist2.Username        = :username " .
                    "AND   familylist2.Guardian        = 1 " .
                    "GROUP BY user.Username " .
                    "ORDER BY class.Grade DESC, user.FirstName, user.Surname, user.Username";
    }
    $query = $pdb->prepare($query);
    $query->execute(['username' => $username, 'yearindex' => $yearindex]);
    $row = $query->fetch();
    /* If user is a support teacher for at least one student, show student information */
    if ($row) {
        $childlink = "index.php?location=" .
                dbfuncString2Int("parent/list_children.php");
        echo "      <p><a href='$childlink'>My children</a></p>\n";
    }
}

if($yearindex == $currentyear) {
    /* Check whether teacher is taking attendance for a punishment */
    $query = $pdb->prepare(
        "SELECT disciplinetype.DisciplineType, disciplinedate.DisciplineTypeIndex " .
        "       FROM disciplinedate, disciplinetype " .
        "WHERE disciplinedate.Username = :username " .
        "AND   disciplinedate.DisciplineTypeIndex = disciplinetype.DisciplineTypeIndex " .
        "AND   disciplinedate.Done=0"
    );
    $query->execute(['username' => $username]);
    foreach($query as $row) {
        $link = "index.php?location=" .
                 dbfuncString2Int("teacher/punishment/date/modify.php") . "&amp;type=" .
                 dbfuncString2Int($row['DisciplineTypeIndex']) . "&amp;next=" .
                 dbfuncString2Int(
                                "index.php?location=" .
                                 dbfuncString2Int("user/main.php"));
        $pun_type = strtolower($row['DisciplineType']);
        echo "<p><a href='$link'>Punishment attendance for next $pun_type</a></p>\n";
    }

    $date = date("Y-m-d");
    $datestring = "today";
    $query = $pdb->prepare(
        "SELECT subject.Name, period.PeriodIndex, " .
        "       subject.SubjectIndex FROM timetable, period, subject, subjectteacher " .
        "WHERE  subject.YearIndex           = :yearindex " .
        "AND    subject.TermIndex           = :termindex " .
        "AND    subject.DepartmentIndex     = :depindex " .
        "AND    subjectteacher.SubjectIndex = subject.SubjectIndex " .
        "AND    subjectteacher.Username     = :username " .
        "AND    timetable.SubjectIndex      = subject.SubjectIndex " .
        "AND    timetable.DayIndex          = DAYOFWEEK(:date) - 1 " .
        "AND    period.PeriodIndex          = timetable.PeriodIndex " .
        "AND    period.Period               = 1 " .
        "ORDER BY subject.Name"
    );
    $query->execute(['username' => $username, 'yearindex' => $yearindex,
                     'termindex' => $termindex, 'depindex' => $depindex,
                     'date' => $date]);

    foreach($query as $row) {
        $boldst = "";
        $boldend = "";

        $nquery = $pdb->prepare(
            "SELECT AttendanceDoneIndex FROM attendancedone " .
            "WHERE SubjectIndex=:subjectindex " .
            "AND   PeriodIndex=:periodindex " .
            "AND   Date=:date"
        );
        $nquery->execute(['subjectindex' => $row['SubjectIndex'],
                         'periodindex' => $row['PeriodIndex'],
                         'date' => $date]);
        $nrow = $nquery->fetch();
        if (!$nrow) {
            $boldst = "<strong>";
            $boldend = "</strong>";
        }
        $link = "index.php?location=" .
                 dbfuncString2Int("teacher/attendance/modify.php") . "&amp;key=" .
                 dbfuncString2Int($row['SubjectIndex']) . "&amp;key2=" .
                 dbfuncString2Int($row['PeriodIndex']) . "&amp;key3=" .
                 dbfuncString2Int($date) . "&amp;keyname= " .
                 dbfuncString2Int($row['Name']) . "&amp;next=" .
                 dbfuncString2Int(
                                "index.php?location=" .
                                 dbfuncString2Int("user/main.php"));
        $extra = "";
        if ($query->rowCount() > 1)
            $extra = " for {$row['Name']}";

        echo "$boldst<p><a href='$link'>Attendance$extra</a></p>$boldend\n";
    }
}

/* Get classes */
$query = $pdb->prepare(
    "SELECT subject.Name, subject.SubjectIndex, subject.Period, " .
    "       subject.ShowAverage, subjectstudent.Average, subject.AverageType, " .
    "       subject.AverageTypeIndex FROM subject, subjectstudent " .
    "WHERE subjectstudent.SubjectIndex = subject.SubjectIndex " .
    "AND   subject.ShowInList          = 1 " .
    "AND   subject.YearIndex           = :yearindex " .
    "AND   subject.TermIndex           = :termindex " .
    "AND   subjectstudent.Username     = :username " .
    "ORDER BY subject.Period, subject.Name"
);
$query->execute(['yearindex' => $yearindex, 'termindex' => $termindex,
                 'username' => $username]);
$data = $query->fetchAll();

/* If user is a student in at least one subject, print out class table */
if ($data) {
    include "student/lateinfo.php";

    /* First give option to show all assignments */
    $alllink = "index.php?location=" . dbfuncString2Int("student/allinfo.php") .
             "&amp;key=" . dbfuncString2Int($username) . "&amp;keyname=" .
             dbfuncString2Int($fullname) . "&amp;show=" . dbfuncString2Int("a");
    $hwlink = "index.php?location=" . dbfuncString2Int("student/allinfo.php") .
             "&amp;key=" . dbfuncString2Int($username) . "&amp;keyname=" .
             dbfuncString2Int($fullname) . "&amp;show=" . dbfuncString2Int("u");
    $thwlink = "index.php?location=" . dbfuncString2Int("student/allinfo.php") .
             "&amp;key=" . dbfuncString2Int($username) . "&amp;keyname=" .
             dbfuncString2Int($fullname) . "&amp;show=" . dbfuncString2Int("t");

    $allbutton = dbfuncGetButton($alllink, "View all assignments", "medium", "", "");
    $hwbutton = dbfuncGetButton($hwlink, "View homework", "medium", "", "");
    $thwbutton = dbfuncGetButton($thwlink, "View today's homework", "medium", "",
                                "");
    echo "      <p align='center'>$hwbutton $thwbutton $allbutton</p>";

    echo "      <table align='center' border='1' style='max-width: 70%'>\n"; // Table headers
    echo "         <tr>\n";
    echo "            <th>Subject</th>\n";
    echo "            <th>Teacher(s)</th>\n";
    echo "            <th>Average</th>\n";
    echo "         </tr>\n";

    /* For each subject, print a row with the subject name and teacher(s) */
    $alt_count = 0;
    foreach($data as $row) {
        $alt_count += 1;
        if ($alt_count % 2 == 0) {
            $alt = " class='alt'";
        } else {
            $alt = " class='std'";
        }
        $namelink = "index.php?location=" .
                     dbfuncString2Int("student/subjectinfo.php") . "&amp;key=" .
                     dbfuncString2Int($row['SubjectIndex']) . "&amp;key2=" .
                     dbfuncString2Int($username) . "&amp;keyname=" .
                     dbfuncString2Int($row['Name']) . "&amp;key2name=" .
                     dbfuncString2Int($fullname); // Get link to class
        echo "         <tr$alt>\n";
        echo "            <td nowrap><a href='$namelink'>{$row['Name']}</a></td>\n";
        echo "            <td>";

        /* Get information about teacher(s) */
        $query = $pdb->prepare(
            "SELECT user.Title, user.FirstName, user.Surname FROM user, subjectteacher " .
            "WHERE subjectteacher.SubjectIndex = :subjectindex " .
            "AND   user.Username = subjectteacher.Username " .
            "ORDER BY user.Surname"
        );
        $query->execute(['subjectindex' => $row['SubjectIndex']]);
        $first = True;
        foreach($query as $teacherRow) {
            if(!$first)
                echo "<br>\n";
            else
                $first = False;
            echo "{$teacherRow['Title']} {$teacherRow['FirstName']} {$teacherRow['Surname']}";
        }
        echo "            </td>\n"; // Table footers
        $average_type = $row['AverageType'];
        $average_type_index = $row['AverageTypeIndex'];

        if ($average_type != $AVG_TYPE_NONE) {
            if ($row['ShowAverage'] == "1") {
                if ($row['Average'] == "-1") {
                    echo "            <td><i>N/A</i></td>\n";
                } else {
                    if ($average_type == $AVG_TYPE_PERCENT) {
                        $average = round($row['Average']);
                        echo "            <td>$average%</td>\n";
                    } elseif ($average_type == $AVG_TYPE_INDEX or
                              $average_type == $AVG_TYPE_GRADE) {
                        $query = $pdb->prepare(
                            "SELECT Input, Display FROM nonmark_index " .
                            "WHERE NonmarkTypeIndex = :average_type_index " .
                            "AND   NonmarkIndex     = :average"
                        );
                        $query->execute(['average_type_index' => $average_type_index,
                                         'average' => $row['Average']]);
                        $srow = $query->fetch();
                        if ($srow) {
                            $average = $srow['Display'];
                        } else {
                            $average = "?";
                        }
                        echo "            <td>$average</td>\n";
                    }
                }
            } else {
                echo "            <td>&nbsp;</td>\n";
            }
        } else {
            echo "            <td><i>N/A</i></td>\n";
        }

        echo "         </tr>\n";
    }
    echo "      </table>\n"; // End of table

    $query = $pdb->prepare(
        "SELECT disciplinedate.PunishDate, disciplinetype.DisciplineType, " .
        "       discipline.Date, discipline.Comment, IF(PunishDate=CURDATE(), 1, 0) AS Today " .
        "       FROM discipline INNER JOIN disciplinedate USING (DisciplineDateIndex) " .
        "                       INNER JOIN disciplinetype USING (DisciplineTypeIndex) " .
        "WHERE disciplinedate.Done = 0 " .
        "AND   discipline.DisciplineDateIndex IS NOT NULL " .
        "AND   discipline.Username=:username " .
        "ORDER BY disciplinedate.PunishDate, discipline.Date, discipline.DisciplineIndex"
    );
    $query->execute(['username' => $username]);
    $pdata = $query->fetchAll();
    if($pdata) {
        echo "      <p></p>\n";
        echo "      <table align='center' border='1'>\n"; // Table headers
        echo "         <tr>\n";
        echo "            <th>Punishment</th>\n";
        echo "            <th>Punishment date</th>\n";
        echo "            <th>Reason</th>\n";
        echo "            <th>Infraction date</th>\n";
        echo "         </tr>\n";

        $alt_count = 0;
        foreach($pdata as $prow) {
            $alt_count += 1;
            if ($alt_count % 2 == 0) {
                $alt = " class='alt'";
            } else {
                $alt = " class='std'";
            }

            if($prow['Today'] == 1) {
                $emph = "<strong>";
                $unemph = "</strong>";
            } else {
                $emph = "";
                $unemph = "";
            }
            $pundate = date($dateformat, strtotime($prow['PunishDate']));
            $infr_date = date($dateformat, strtotime($prow['Date']));
            $comment = htmlspecialchars($prow['Comment'], ENT_QUOTES);
            echo "         <tr$alt>\n";
            echo "            <td>$emph{$prow['DisciplineType']}$unemph</td>\n";
            echo "            <td>$emph$pundate$unemph</td>\n";
            echo "            <td>$emph$comment$unemph</td>\n";
            echo "            <td>$emph$infr_date$unemph</td>\n";
            echo "         </tr>\n";
        }
        echo "      </table>\n"; // End of table
    }

    /* Calculate conduct mark */
    $disclink = "index.php?location=" . dbfuncString2Int("student/discipline.php") .
                 "&amp;key=" . dbfuncString2Int($username) . "&amp;keyname=" .
                 dbfuncString2Int($fullname);
    $query = $pdb->prepare(
        "SELECT ROUND(classlist.Conduct) AS Conduct FROM classlist, classterm, class " .
        "WHERE  classlist.Username = :username " .
        "AND    classlist.ClassTermIndex = classterm.ClassTermindex " .
        "AND    classterm.TermIndex = :termindex " .
        "AND    classterm.ClassIndex = class.ClassIndex " .
        "AND    class.YearIndex = :yearindex"
    );
    $query->execute(['username' => $username, 'termindex' => $termindex,
                     'yearindex' => $yearindex]);
    $crow = $query->fetch();
    if ($crow and $crow['Conduct'] != "") {
        $conduct = $crow['Conduct'];
        if ($conduct < 0)
            $conduct = 0;
        if(is_null($crow['Conduct'])) {
            $conduct = "N/A";
        } else {
            $conduct = "$conduct%";
        }
        echo "      <p class='subtitle' align='center'><a href='$disclink'>Conduct: $conduct</a></p>\n";
    }
    echo "      <p></p>\n";
}

/* Get subject information for current teacher */
$query = $pdb->prepare(
    "SELECT Name, SubjectIndex, Average, MAX(StudentCount) AS StudentCount, " .
    "       MIN(ReportDone) AS ReportDone, ClassName, ClassIndex, CanDoReport, " .
    "       MAX(SubjectTeacher) AS SubjectTeacher FROM " .
    "       ((SELECT subject.Name, subject.SubjectIndex, subject.Average, " .
    "                COUNT(subjectstudent.Username) AS StudentCount, " .
    "                MIN(subjectstudent.ReportDone) AS ReportDone, class.ClassName, " .
    "                subject.ClassIndex, subject.CanDoReport, 1 AS SubjectTeacher " .
    "         FROM subject " .
    "         LEFT OUTER JOIN subjectstudent USING (SubjectIndex) " .
    "         LEFT OUTER JOIN class USING (ClassIndex), subjectteacher " .
    "         WHERE subjectteacher.SubjectIndex = subject.SubjectIndex " .
    "         AND subject.YearIndex = :yearindex " .
    "         AND subject.TermIndex = :termindex " .
    "         AND subjectteacher.Username = :username " .
    "         AND subject.ShowInList = 1 " .
    "         GROUP BY subject.SubjectIndex) " .
    "        UNION " .
    "        (SELECT subject.Name, subject.SubjectIndex, subject.Average, " .
    "                COUNT(subjectstudent.Username) AS StudentCount, " .
    "                MIN(subjectstudent.ReportDone) AS ReportDone, class.ClassName, " .
    "                subject.ClassIndex, subject.CanDoReport, 0 AS SubjectTeacher " .
    "         FROM subject " .
    "         INNER JOIN subjectstudent USING (SubjectIndex) " .
    "         INNER JOIN classlist USING (Username) " .
    "         INNER JOIN classterm ON (classterm.ClassTermIndex=classlist.ClassTermIndex AND classterm.TermIndex=subject.TermIndex) " .
    "         INNER JOIN class ON (class.ClassIndex=classterm.ClassIndex AND class.YearIndex=subject.YearIndex) " .
    "         INNER JOIN support_class ON (classterm.ClassTermIndex=support_class.ClassTermIndex) " .
    "         WHERE support_class.Username = :username " .
    "         AND subject.YearIndex = :yearindex " .
    "         AND subject.TermIndex = :termindex " .
    "         AND subject.ShowInList = 1 " .
    "         GROUP BY subject.SubjectIndex)) AS subject_list " .
    "GROUP BY SubjectIndex " . "ORDER BY Name, SubjectIndex "
);
$query->execute(['username' => $username, 'termindex' => $termindex,
                 'yearindex' => $yearindex]);
$data = $query->fetchAll();

/* If user teaches at least one subject, print out teacher table */
if ($data) {
    echo "      <table align='center' border='1'>\n"; // Table headers
    echo "         <tr>\n";
    echo "            <th>Subject</th>\n";
    echo "            <th>Students</th>\n";
    echo "            <th>Average</th>\n";
    echo "         </tr>\n";

    /* For each class, print a row with the subject name and number of students */
    $alt_count = 0;
    foreach($data as $row) {
        $alt_count += 1;
        if ($alt_count % 2 == 0) {
            $alt = " class='alt'";
        } else {
            $alt = " class='std'";
        }
        echo "         <tr$alt>\n";
        $row['Name'] = htmlspecialchars($row['Name']);

        echo "            <td>";
        if ($row['CanDoReport'] == 1) {
            $reportlink = "index.php?location=" .
                     dbfuncString2Int("teacher/report/modify.php") . "&amp;key=" .
                     dbfuncString2Int($row['SubjectIndex']) . "&amp;keyname=" .
                     dbfuncString2Int($row['Name']); // Get link to report
            if ($row['ReportDone'] == 0) {
                $reportbutton = dbfuncGetButton($reportlink, "R", "small", "report",
                                                "Edit report information");
            } else {
                $reportbutton = dbfuncGetButton($reportlink, "V", "small", "report",
                                                "View report information");
            }
            echo "$reportbutton&nbsp;";
        }
        if ($row['ClassIndex'] != NULL) {
            $ttlink = "index.php?location=" . dbfuncString2Int("user/timetable.php") .
                     "&amp;key=" . dbfuncString2Int($row['ClassIndex']) . "&amp;keyname=" .
                     dbfuncString2Int($row['ClassName']) . "&amp;key2=" .
                     dbfuncString2Int("c"); // Get link to report
            $ttbutton = dbfuncGetButton($ttlink, "T", "small", "edit", "Class timetable");
            echo "$ttbutton&nbsp;";
        }
        if ($row['StudentCount'] != NULL and $row['StudentCount'] > 0) {
            $namelink = "index.php?location=" .
                     dbfuncString2Int("teacher/assignment/list.php") . "&amp;key=" .
                     dbfuncString2Int($row['SubjectIndex']) . "&amp;keyname=" .
                     dbfuncString2Int($row['Name']); // Get link to subject

            echo "<a href='$namelink'>{$row['Name']}</a></td>\n";
        } else {
            echo "{$row['Name']}</td>\n";
        }
        echo "            <td>{$row['StudentCount']}</td>\n"; // Print student count
        if ($row['Average'] == "-1") {
            echo "            <td><i>N/A</i></td>\n";
        } else {
            $average = round($row['Average']);
            echo "            <td>$average%</td>\n";
        }
        echo "         </tr>\n";
    }
    echo "      </table>\n"; // End of table
    echo "      <p></p>\n";
}

/* Get subject information for support teacher */
$query = $pdb->prepare(
    "SELECT class.ClassName, class.ClassIndex, COUNT(support.StudentUsername) AS StudentCount " .
    "       FROM user, support, class, classterm, classlist, groupgenmem, groups " .
    "WHERE support.WorkerUsername   = :username " .
    "AND   user.Username            = support.WorkerUsername " .
    "AND   groupgenmem.Username     = user.Username " .
    "AND   groups.GroupID           = groupgenmem.GroupID " .
    "AND   groups.GroupTypeID       = 'supportteacher' " .
    "AND   groups.YearIndex         = :yearindex " .
    "AND   support.StudentUsername  = classlist.Username " .
    "AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
    "AND   classterm.TermIndex      = :termindex " .
    "AND   classterm.ClassIndex     = class.ClassIndex " .
    "AND   class.YearIndex          = groups.YearIndex " .
    "GROUP BY class.ClassName " .
    "ORDER BY class.Grade, class.ClassName, class.ClassIndex"
);
$query->execute(['username' => $username, 'termindex' => $termindex,
                 'yearindex' => $yearindex]);
$data = $query->fetchAll();

if ($data) {
    echo "      <table align='center' border='1'>\n"; // Table headers
    echo "         <tr>\n";
    echo "            <th>Class</th>\n";
    echo "            <th>Students</th>\n";
    echo "         </tr>\n";

    /* For each class, print a row with the subject name and number of students */
    $alt_count = 0;
    foreach($data as $row) {
        $alt_count += 1;
        if ($alt_count % 2 == 0) {
            $alt = " class='alt'";
        } else {
            $alt = " class='std'";
        }
        $row['Name'] = htmlspecialchars($row['Name']);

        $namelink = "index.php?location=" .
                     dbfuncString2Int("teacher/support/list.php") . "&amp;key=" .
                     dbfuncString2Int($row['ClassIndex']) . "&amp;keyname=" .
                     dbfuncString2Int($row['ClassName']); // Get link to subject
        echo "         <tr$alt>\n";
        echo "            <td><a href='$namelink'>{$row['ClassName']}</a></td>\n";
        echo "            <td>{$row['StudentCount']}</td>\n"; // Print student count
        echo "         </tr>\n";
    }
    echo "      </table>\n"; // End of table
}

if(!$is_staff and !$is_admin) {
    /* Only show class if we're looking at future years */
    if($yearindex > $currentyear) {
        $query = $pdb->prepare(
            "SELECT user.Username, user.FirstName, user.Surname, NULL AS SubjectCount, " .
            "       grade.GradeName AS ClassName, NULL AS TermIndex FROM " .
            "       classlist INNER JOIN classterm USING (ClassTermIndex) " .
            "                 INNER JOIN term ON (classterm.TermIndex=term.TermIndex AND term.TermNumber=1) " .
            "                 INNER JOIN class ON (classterm.ClassIndex=class.ClassIndex) " .
            "                 INNER JOIN grade USING (Grade) " .
            "                 INNER JOIN familylist USING (Username) " .
            "                 INNER JOIN user USING (Username) " .
            "                 INNER JOIN familylist AS familylist2 ON (familylist.FamilyCode=familylist2.FamilyCode) " .
            "WHERE class.YearIndex             = :yearindex " .
            "AND   familylist2.Username        = :username " .
            "AND   familylist2.Guardian        = 1 " .
            "GROUP BY user.Username " .
            "ORDER BY class.Grade DESC, user.FirstName, user.Surname, user.Username"
        );
    } else {
        $query = $pdb->prepare(
            "SELECT user.Username, user.FirstName, user.Surname, COUNT(subject.SubjectIndex) AS SubjectCount, " .
            "       class.ClassName, classterm.TermIndex FROM " .
            "       subject INNER JOIN subjectstudent USING (SubjectIndex) " .
            "               INNER JOIN classlist USING (Username) " .
            "               INNER JOIN currentterm USING (TermIndex) " .
            "               INNER JOIN classterm USING (ClassTermIndex) " .
            "               INNER JOIN class ON (classterm.ClassIndex=class.ClassIndex) " .
            "               INNER JOIN familylist USING (Username) " .
            "               INNER JOIN user USING (Username) " .
            "               INNER JOIN familylist AS familylist2 ON (familylist.FamilyCode=familylist2.FamilyCode) " .
            "WHERE subject.ShowInList          = 1 " .
            "AND   subject.YearIndex           = :yearindex " .
            "AND   class.YearIndex             = subject.YearIndex " .
            "AND   classterm.TermIndex         = currentterm.TermIndex " .
            "AND   familylist2.Username        = :username " .
            "AND   familylist2.Guardian        = 1 " .
            "GROUP BY user.Username " .
            "ORDER BY class.Grade DESC, user.FirstName, user.Surname, user.Username"
        );
    }
    $query->execute(['username' => $username, 'yearindex' => $yearindex]);
    $data = $query->fetchAll();

    /* If user has children and is not a teacher, show child information */
    if ($data) {
        echo "      <table align='center' border='1'>\n"; // Table headers
        echo "         <tr>\n";
        echo "            <th>Child</th>\n";
        echo "            <th>Class</th>\n";
        echo "            <th>Subjects</th>\n";
        echo "         </tr>\n";

        /* For each class, print a row with the subject name and number of students */
        $alt_count = 0;
        foreach($data as $row) {
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
    }
}

/* Closing tags */
include "footer.php";
