<?php
/**
 * ***************************************************************
 * admin/subject/list_student.php (c) 2005-2007, 2016 Jonathan Dieter
 *
 * List all subjects that the student is currently in.
 * ***************************************************************
 */
$studentusername = dbfuncInt2String($_GET["key"]);
$studentname = dbfuncInt2String($_GET["keyname"]);
if(isset($_GET['key2'])) {
    $termindex = intval(dbfuncInt2String($_GET['key2']));
    $query = "SELECT DepartmentIndex FROM term WHERE TermIndex=$termindex ";
    $res = & $db->query($query);
    if( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        $depindex = $row['DepartmentIndex'];
    }
}

$title = "Subject List for $studentname";

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

if ($is_admin or $is_principal or $is_hod or $is_counselor or $is_guardian) { // Make sure user has permission to view student's
    $showalldeps = true; // subject list
    $showdeps = false;
    include "core/settermandyear.php";
    include "core/titletermyear.php";

    /* Get classes */
    $query = "SELECT subject.Name, subject.AverageType, subject.AverageTypeIndex, " .
             "       subject.SubjectIndex, subject.Period, " .
             "       subject.ShowAverage, subjectstudent.Average FROM subject, subjectstudent " .
             "WHERE subjectstudent.SubjectIndex = subject.SubjectIndex " .
             "AND   subject.ShowInList          = 1 " .
             "AND   subject.YearIndex           = $yearindex " .
             "AND   subject.TermIndex           = $termindex " .
             "AND   subjectstudent.Username     = \"$studentusername\" " .
             "ORDER BY subject.Period, subject.Name";
    $res = & $db->query($query);

    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    /* If user is a student in at least one class, print out class table */
    if ($res->numRows() > 0) {
        include "student/lateinfo.php";

        /* First give option to show all assignments */
        $alllink = "index.php?location=" . dbfuncString2Int("student/allinfo.php") .
                 "&amp;key=" . dbfuncString2Int($studentusername) . "&amp;keyname=" .
                 dbfuncString2Int($studentname) . "&amp;show=" . dbfuncString2Int("a");
        $hwlink = "index.php?location=" . dbfuncString2Int("student/allinfo.php") .
                 "&amp;key=" . dbfuncString2Int($studentusername) . "&amp;keyname=" .
                 dbfuncString2Int($studentname) . "&amp;show=" . dbfuncString2Int("u");
        $thwlink = "index.php?location=" . dbfuncString2Int("student/allinfo.php") .
                 "&amp;key=" . dbfuncString2Int($studentusername) . "&amp;keyname=" .
                 dbfuncString2Int($studentname) . "&amp;show=" . dbfuncString2Int("t");
        $ttlink = "index.php?location=" .
                 dbfuncString2Int("user/timetable.php") . "&amp;key=" .
                 dbfuncString2Int($studentusername) . "&amp;keyname=" .
                 dbfuncString2Int($studentname);
        $allbutton = dbfuncGetButton($alllink, "View all assignments", "medium", "", "");
        $hwbutton = dbfuncGetButton($hwlink, "View homework", "medium", "", "");
        $thwbutton = dbfuncGetButton($thwlink, "View today's homework", "medium", "",
                                    "");
        $ttbutton = dbfuncGetButton($ttlink, "View timetable", "medium", "", "");
        echo "      <p align='center'>$hwbutton $thwbutton $allbutton $ttbutton</p>";

        echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
        echo "         <tr>\n";
        echo "            <th>Subject</th>\n";
        echo "            <th>Teacher(s)</th>\n";
        echo "            <th>Average</th>\n";
        echo "         </tr>\n";

        /* For each subject, print a row with the subject name and teacher(s) */
        $alt_count = 0;
        while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
            $alt_count += 1;
            if ($alt_count % 2 == 0) {
                $alt = " class=\"alt\"";
            } else {
                $alt = " class=\"std\"";
            }
            $namelink = "index.php?location=" .
                         dbfuncString2Int("student/subjectinfo.php") .
                         "&amp;key=" . dbfuncString2Int($row['SubjectIndex']) .
                         "&amp;key2=" . $_GET['key'] . "&amp;keyname=" .
                         dbfuncString2Int($row['Name']) . "&amp;key2name=" .
                         $_GET['keyname']; // Get link to class
            echo "         <tr$alt>\n";
            echo "            <td><a href=\"$namelink\">{$row['Name']}</a></td>\n";
            echo "            <td>";

            $average_type = $row['AverageType'];
            $average_type_index = $row['AverageTypeIndex'];

            /* Get information about teacher(s) */
            $teacherRes = & $db->query(
                                    "SELECT user.Title, user.FirstName, user.Surname FROM user, subjectteacher " .
                                     "WHERE subjectteacher.SubjectIndex = {$row['SubjectIndex']} " .
                                        /*"AND   subjectteacher.Show         = '1' " .*/
                                        "AND   user.Username               = subjectteacher.Username");
            if (DB::isError($teacherRes))
                die($teacherRes->getDebugInfo()); // Check for errors in query
            if ($teacherRow = & $teacherRes->fetchRow(DB_FETCHMODE_ASSOC)) {
                echo "{$teacherRow['Title']} {$teacherRow['FirstName']} {$teacherRow['Surname']}";

                /* If there's more than one teacher, separate with commas */
                while ( $teacherRow = & $teacherRes->fetchRow(DB_FETCHMODE_ASSOC) ) {
                    echo ", {$teacherRow['Title']} {$teacherRow['FirstName']} {$teacherRow['Surname']}";
                }
            }
            echo "            </td>\n"; // Table footers
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
                            $query = "SELECT Input, Display FROM nonmark_index " .
                             "WHERE NonmarkTypeIndex = $average_type_index " .
                             "AND   NonmarkIndex     = {$row['Average']}";
                            $sres = & $db->query($query);
                            if (DB::isError($sres))
                                die($sres->getDebugInfo()); // Check for errors in query
                            if ($srow = & $sres->fetchRow(DB_FETCHMODE_ASSOC)) {
                                $average = $srow['Display'];
                            } else {
                                $average = "?";
                            }
                            echo "            <td>$average</td>\n";
                        }
                    }
                } else {
                    echo "            <td><i>Hidden</i></td>\n";
                }
            } else {
                echo "            <td><i>N/A</i></td>\n";
            }
            echo "         </tr>\n";
        }
        echo "      </table>\n"; // End of table
        $query =    "SELECT disciplinedate.PunishDate, disciplinetype.DisciplineType, " .
                "       discipline.Date, discipline.Comment, IF(PunishDate=CURDATE(), 1, 0) AS Today " .
                "       FROM discipline INNER JOIN disciplinedate USING (DisciplineDateIndex) " .
                "                       INNER JOIN disciplinetype USING (DisciplineTypeIndex) " .
                "WHERE disciplinedate.Done = 0 " .
                "AND   discipline.DisciplineDateIndex IS NOT NULL " .
                "AND   discipline.Username='$studentusername' " .
                "ORDER BY disciplinedate.PunishDate, discipline.Date, discipline.DisciplineIndex";
        $dRes = &   $db->query($query);
        if (DB::isError($dRes))
            die($dRes->getDebugInfo()); // Check for errors in query
        if($dRes->numRows() > 0) {
            echo "      <p></p>\n";
            echo "      <table align='center' border='1'>\n"; // Table headers
            echo "         <tr>\n";
            echo "            <th>Punishment</th>\n";
            echo "            <th>Punishment date</th>\n";
            echo "            <th>Reason</th>\n";
            echo "            <th>Infraction date</th>\n";
            echo "         </tr>\n";

            /* For each subject, print a row with the subject name and teacher(s) */
            $alt_count = 0;
            while ( $row = & $dRes->fetchRow(DB_FETCHMODE_ASSOC) ) {
                $alt_count += 1;
                if ($alt_count % 2 == 0) {
                    $alt = " class='alt'";
                } else {
                    $alt = " class='std'";
                }

                if($row['Today'] == 1) {
                    $emph = "<strong>";
                    $unemph = "</strong>";
                } else {
                    $emph = "";
                    $unemph = "";
                }
                $pundate = date($dateformat, strtotime($row['PunishDate']));
                $infr_date = date($dateformat, strtotime($row['Date']));
                $comment = htmlspecialchars($row['Comment'], ENT_QUOTES);
                echo "         <tr$alt>\n";
                echo "            <td>$emph{$row['DisciplineType']}$unemph</td>\n";
                echo "            <td>$emph$pundate$unemph</td>\n";
                echo "            <td>$emph$comment$unemph</td>\n";
                echo "            <td>$emph$infr_date$unemph</td>\n";
                echo "         </tr>\n";
            }
            echo "      </table>\n"; // End of table
        }

        echo "      <p></p>\n";
        /* Calculate conduct mark */
        $disclink = "index.php?location=" . dbfuncString2Int("student/discipline.php") .
        "&amp;key=" . dbfuncString2Int($studentusername) . "&amp;keyname=" .
        dbfuncString2Int($studentname);
        $query = "SELECT classlist.Conduct FROM classlist, classterm, class " .
                "WHERE  classlist.Username = '$studentusername' " .
                "AND    classlist.ClassTermIndex = classterm.ClassTermindex " .
                "AND    classterm.TermIndex = $termindex " .
                "AND    classterm.ClassIndex = class.ClassIndex " .
                "AND    class.YearIndex = $yearindex ";
        $conductRes = &   $db->query($query);
        if (DB::isError($conductRes))
            die($conductRes->getDebugInfo()); // Check for errors in query
        if ($conductRow = & $conductRes->fetchrow(DB_FETCHMODE_ASSOC) and
             $conductRow['Conduct'] != "") {
            $conduct = $conductRow['Conduct'];
            if ($conduct < 0)
                $conduct = 0;
            if(is_null($conductRow['Conduct'])) {
                $conduct = "N/A";
            } else {
                $conduct = "$conduct%";
            }
            echo "      <p class='subtitle' align='center'><a href='$disclink'>Conduct: $conduct</a></p>\n";
        }
    }
} else {
    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

include "footer.php";
