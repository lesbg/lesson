<?php
/**
 * ***************************************************************
 * admin/class/list_students.php (c) 2004-2016 Jonathan Dieter
 *
 * List all students in a particular class
 * ***************************************************************
 */

$classindex = dbfuncInt2String($_GET["key"]);
$classname = dbfuncInt2String($_GET["keyname"]);
$type = "html";
if(isset($_GET['key2']))
    $type = dbfuncInt2String($_GET["key2"]);
if($type != "csv")
    $type = "html";

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
$res = &  $db->query(
                "SELECT Username FROM hod, class " .
                 "WHERE Username=\"$username\" " .
                 "AND hod.DepartmentIndex = class.DepartmentIndex " .
                 "AND class.ClassIndex = $classindex");
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
    $is_hod = true;
} else {
    $is_hod = false;
}

if ($is_admin or $is_counselor or $is_hod or $is_principal) {
    include "core/settermandyear.php";

    if($type == "csv") {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=students.csv');
    } else {
        $title = "Student List for $classname";
        include "header.php"; // Show header
        $nochangeyear = true;
        $showdeps = false;
        if ($is_admin or $is_counselor or $is_principal) {
            $showalldeps = true;
        } else {
            $admin_page = true;
        }
        include "core/titletermyear.php";

        $csvlink = "index.php?location=" .
                dbfuncString2Int("admin/class/list_students.php") .
                "&key="     . $_GET['key'] .
                "&keyname=" . $_GET['keyname'] .
                "&key2="    . dbfuncString2Int("csv");

        $csvbutton = dbfuncGetButton($csvlink, "Download csv", "medium", "", "Download csv of class that can be used as a spreadsheet");
        echo "      <p align='center'>$csvbutton</p>\n";
    }

    $query = "SELECT user.FirstName, user.Surname, user.Username, newmem.Username AS NewUser, " .
             "       specialmem.Username AS SpecialUser, user.OriginalPassword, classterm.ClassTermIndex, " .
             "       classlist.Conduct, classlist.Average, classlist.Rank, class.ClassName, " .
             "       COUNT(subjectstudent.SubjectIndex) AS SubjectCount, photo.YearIndex AS PhotoYearIndex, " .
             "       largeimage.FileIndex AS LargeFileIndex, smallimage.FileIndex AS SmallFileIndex " .
             "       FROM class INNER JOIN classterm USING (ClassIndex) INNER JOIN classlist USING (ClassTermIndex) " .
             "            INNER JOIN user USING (Username) " .
             "            LEFT OUTER JOIN (subjectstudent " .
             "               INNER JOIN subject USING (SubjectIndex)) ON " .
             "               (subjectstudent.Username = user.Username " .
             "                AND subject.YearIndex = $yearindex " .
             "                AND subject.TermIndex = $termindex) " .
             "            LEFT OUTER JOIN " .
             "              (groupgenmem AS newmem INNER JOIN groups AS newgroups ON " .
             "                (newgroups.GroupID=newmem.GroupID " .
             "                 AND newgroups.GroupTypeID='new' " .
             "                 AND newgroups.YearIndex=$yearindex)) " .
             "              ON (user.Username=newmem.Username) " .
             "            LEFT OUTER JOIN " .
             "              (groupgenmem AS specialmem INNER JOIN groups AS specgroups ON " .
             "                (specgroups.GroupID=specialmem.GroupID " .
             "                 AND specgroups.GroupTypeID='special' " .
             "                 AND specgroups.YearIndex=$yearindex)) " .
             "              ON (user.Username=specialmem.Username) " .
             "            LEFT OUTER JOIN (SELECT photo.* FROM photo LEFT OUTER JOIN photo AS newphoto " .
             "                             ON (photo.Username=newphoto.Username " .
             "                                 AND photo.YearIndex<newphoto.YearIndex " .
             "                                 AND newphoto.YearIndex<=$yearindex) " .
             "                             WHERE photo.YearIndex<=$yearindex " .
             "                             AND newphoto.YearIndex IS NULL) AS photo ON " .
             "              (user.Username=photo.Username) " .
             "            LEFT OUTER JOIN image AS largeimage ON (photo.LargeImageIndex=largeimage.ImageIndex) " .
             "            LEFT OUTER JOIN image AS smallimage ON (photo.SmallImageIndex=smallimage.ImageIndex) " .
             "WHERE classterm.ClassIndex = $classindex " .
             "AND   classterm.TermIndex = $termindex " .
             "GROUP BY user.Username " .
             "ORDER BY user.FirstName, user.Surname, user.Username";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    /* Print students and their class */
    if ($res->numRows() > 0) {
        $orderNum = 0;
        if($type == "csv") {
            echo "\"Username\",\"First Name\",\"Surname\",";
            if ($is_admin or $is_principal) {
                echo "\"Password\",\"New\",\"Special\",\"Subjects\",";
            }
            if ($is_admin or $is_hod or $is_counselor or $is_principal) {
                echo "\"Average\",\"Rank\",";
            }
            echo "\"Conduct\",\"Absent\",\"Late\",\"Suspended\"\n";
        } else {
            echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
            echo "         <tr>\n";
            echo "            <th>&nbsp;</th>\n";
            echo "            <th>Order</th>\n";
            echo "            <th>Student</th>\n";
            echo "            <th>Picture</th>\n";
            if ($is_admin or $is_principal) {
                echo "            <th>Password</th>\n";
                echo "            <th>New</th>\n";
                echo "            <th>Special</th>\n";
                echo "            <th>Subjects</th>\n";
            }
            if ($is_admin or $is_hod or $is_counselor or $is_principal) {
                echo "            <th>Average</th>\n";
                echo "            <th>Rank</th>\n";
            }
            echo "            <th>Conduct</th>\n";
            echo "            <th>Absent</th>\n";
            echo "            <th>Late</th>\n";
            echo "            <th>Suspended</th>\n";
            echo "         </tr>\n";
        }
        /* For each student, print a row with the student's name and class information */
        $alt_count = 0;

        while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
            if (! is_null($row['Conduct']) and ($row['Conduct'] != - 1)) {
                $conduct = "{$row['Conduct']}%";
                $conduct_val = "{$row['Conduct']}";
            } else {
                $conduct = "N/A";
                $conduct_val = "";
            }
            if ($row['Average'] == - 1) {
                $average = "N/A";
                $average_val = "";
            } else {
                $average_val = round($row['Average']);
                $average_val = "$average_val";
                $average = "{$average_val}%";
            }
            if ($row['Rank'] == - 1) {
                $rank = "N/A";
                $rank_val = "";
            } else {
                $rank = $row['Rank'];
                $rank_val = "$rank";
            }

            $absent = "-";
            $absent_val = "0";
            $late = "-";
            $late_val = "0";
            $suspended = "-";
            $suspended_val = "0";
            // TODO: Clean this up for speed improvement
            $query = "SELECT AttendanceTypeIndex, COUNT(AttendanceIndex) AS Count " .
                     "       FROM attendance INNER JOIN subject USING (SubjectIndex) " .
                     "       INNER JOIN period USING (PeriodIndex) " .
                     "WHERE  attendance.Username = '{$row['Username']}' " .
                     "AND    subject.YearIndex = $yearindex " .
                     "AND    subject.TermIndex = $termindex " .
                     "AND    period.Period = 1 " .
                     "GROUP BY AttendanceTypeIndex ";
            $cRes = &   $db->query($query);
            if (DB::isError($cRes))
                die($cRes->getDebugInfo()); // Check for errors in query
            while ( $cRow = & $cRes->fetchrow(DB_FETCHMODE_ASSOC) ) {
                if ($cRow['AttendanceTypeIndex'] == $ATT_ABSENT) {
                    $absent = $cRow['Count'];
                    $absent_val = "$absent";
                }
                if ($cRow['AttendanceTypeIndex'] == $ATT_LATE) {
                    $late = $cRow['Count'];
                    $late_val = "$late";
                }
                if ($cRow['AttendanceTypeIndex'] == $ATT_SUSPENDED) {
                    $suspended = $cRow['Count'];
                    $suspended_val = "$suspended";
                }
            }

            $alt_count += 1;
            if ($alt_count % 2 == 0) {
                $alt = " class=\"alt\"";
            } else {
                $alt = " class=\"std\"";
            }
            $orderNum ++;

            $viewlink = "index.php?location=" .
                         dbfuncString2Int("admin/subject/list_student.php") .
                         "&amp;key=" . dbfuncString2Int($row['Username']) .
                         "&amp;keyname=" . dbfuncString2Int(
                                                            "{$row['FirstName']} {$row['Surname']} ({$row['Username']})");
            $editlink = "index.php?location=" .
                         dbfuncString2Int("admin/user/modify.php") . "&amp;key=" .
                         dbfuncString2Int($row['Username']) . "&amp;keyname=" . dbfuncString2Int(
                                                                                                "{$row['FirstName']} {$row['Surname']} ({$row['Username']})");
            $cnlink = "index.php?location=" .
                     dbfuncString2Int("teacher/casenote/list.php") . "&amp;key=" .
                     dbfuncString2Int($row['Username']) . "&amp;keyname=" . dbfuncString2Int(
                                                                                            "{$row['FirstName']} {$row['Surname']} ({$row['Username']})") .
                     "&amp;keyname2=" . dbfuncSTring2Int($row['FirstName']);
            $sublink = "index.php?location=" .
                     dbfuncString2Int("admin/subject/modify_by_student.php") .
                     "&amp;key=" . dbfuncString2Int($row['Username']) .
                     "&amp;keyname=" . dbfuncString2Int(
                                                        "{$row['FirstName']} {$row['Surname']} ({$row['Username']})") .
                     "&amp;next=" . dbfuncString2Int($here);
            $hlink = "index.php?location=" .
                     dbfuncString2Int("student/discipline.php") . "&amp;key=" .
                     dbfuncString2Int($row['Username']) . "&amp;keyname=" .
                     dbfuncString2Int("{$row['FirstName']} {$row['Surname']}") .
                     "&amp;next=" . dbfuncString2Int($here);
            $alink = "index.php?location=" .
                     dbfuncString2Int("student/absence.php") . "&amp;key=" .
                     dbfuncString2Int($row['Username']) . "&amp;keyname=" .
                     dbfuncString2Int("{$row['FirstName']} {$row['Surname']}") .
                     "&amp;next=" . dbfuncString2Int($here);
            $ttlink = "index.php?location=" .
                     dbfuncString2Int("user/timetable.php") . "&amp;key=" .
                     dbfuncString2Int($row['Username']) . "&amp;keyname=" . dbfuncString2Int(
                                                                                            $row['FirstName'] .
                                                                                             " " .
                                                                                             $row['Surname']);
            $replink = "index.php?location=" . dbfuncString2Int("teacher/report/class_modify.php") .
                     "&amp;key=" . dbfuncString2Int($row['ClassTermIndex']) .
                     "&amp;keyname=" . dbfuncString2Int($row['ClassName']) .
                     "&amp;key2=" . dbfuncString2Int($row['Username']) .
                     "&amp;keyname2=" . dbfuncString2Int("{$row['FirstName']} {$row['Surname']} ({$row['Username']})");

            /* Generate view and edit buttons */
            if ($is_admin) {
                $viewbutton = dbfuncGetButton($viewlink, "V", "small", "view",
                                            "View student's subjects");
                $ttbutton = dbfuncGetButton($ttlink, "T", "small", "tt",
                                            "View student's timetable");
                $subbutton = dbfuncGetButton($sublink, "S", "small", "home",
                                            "Edit student's subjects");
                $editbutton = dbfuncGetButton($editlink, "E", "small", "edit",
                                            "Edit student");
                $hbutton = dbfuncGetButton($hlink, "H", "small", "view",
                                        "Student's conduct history");
                $abutton = dbfuncGetButton($alink, "A", "small", "view",
                                        "Student's absence history");
                $repbutton = dbfuncGetButton($replink, "R", "small", "home",
                                            "Student's report");
            } else {
                $viewbutton = dbfuncGetButton($viewlink, "V", "small", "view",
                                            "View student's subjects");
                $ttbutton = dbfuncGetButton($ttlink, "T", "small", "tt",
                                            "View student's timetable");
                $subbutton = "";
                $editbutton = "";
                $hbutton = dbfuncGetButton($hlink, "H", "small", "view",
                                        "Student's conduct history");
                $abutton = dbfuncGetButton($alink, "A", "small", "view",
                                        "Student's absence history");
                $repbutton = "";
            }

            $cnbutton = dbfuncGetButton($cnlink, "C", "small", "cn",
                                        "Casenotes for student");
            if($type == "csv") {
                echo "\"{$row['Username']}\",\"{$row['FirstName']}\",\"{$row['Surname']}\",";
                if ($is_admin or $is_principal) {
                    if (is_null($row['OriginalPassword'])) {
                        echo "\"\",";
                    } elseif ($row['OriginalPassword'] == "!!") {
                        echo "\"!!\",";
                    } else {
                        echo "\"{$row['OriginalPassword']}\",";
                    }
                    if (!is_null($row['NewUser'])) {
                        echo "1,";
                    } else {
                        echo "0,";
                    }
                    if (!is_null($row['SpecialUser'])) {
                        echo "1,";
                    } else {
                        echo "0,";
                    }
                    echo "{$row['SubjectCount']},";
                }
                if ($is_admin or $is_principal or $is_hod or $is_counselor) {
                    echo "$average_val,$rank_val,";
                }
                echo "$conduct_val,$absent_val,$late_val,$suspended_val\n";
            } else {
                echo "         <tr$alt>\n";
                echo "            <td>$cnbutton$viewbutton$ttbutton$abutton$subbutton$hbutton$repbutton$editbutton</td>\n";
                echo "            <td>$orderNum</td>\n";
                echo "            <td>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";

                $piclink = "index.php?location=" .
                    dbfuncString2Int("admin/user/photo.php") . "&amp;key=" .
                    dbfuncString2Int($row['Username']) . "&amp;keyname=" . dbfuncString2Int(
                                                                                        $row['FirstName'] .
                                                                                         " " .
                                                                                         $row['Surname']);
                $photo = "<em>None</em>";
                if(!is_null($row['LargeFileIndex'])) {
                    if($row['PhotoYearIndex'] == $yearindex) {
                        $photo = "Current";
                    } else {
                        $photo = "Out of date";
                    }
                }
                $photo = "<a href='$piclink'>$photo</a>";
                echo "            <td>$photo</td>\n";
                if ($is_admin or $is_principal) {
                    if (is_null($row['OriginalPassword'])) {
                        echo "            <td>&nbsp;</td>\n";
                    } elseif ($row['OriginalPassword'] == "!!") {
                        echo "            <td><em><a title='Login disabled'>X</a></em></td>\n";
                    } else {
                        echo "            <td><a title='Password needs to be changed'>C</a></td>\n";
                    }
                    if (!is_null($row['NewUser'])) {
                        echo "            <td>X</td>\n";
                    } else {
                        echo "            <td>&nbsp;</td>\n";
                    }
                    if (!is_null($row['SpecialUser'])) {
                        echo "            <td>X</td>\n";
                    } else {
                        echo "            <td>&nbsp;</td>\n";
                    }
                    echo "            <td>{$row['SubjectCount']}</td>\n";
                }
                if ($is_admin or $is_principal or $is_hod or $is_counselor) {
                    echo "             <td>$average</td>\n";
                    echo "             <td>$rank</td>\n";
                }
                echo "             <td>$conduct</td>\n";
                echo "             <td>$absent</td>\n";
                echo "             <td>$late</td>\n";
                echo "             <td>$suspended</td>\n";
                echo "         </tr>\n";
            }
        }
        if($type == "html") {
            echo "      </table>\n"; // End of table
        }
    } else {
        if($type == "html") {
            echo "      <p>There are no students in this class</p>\n";
        }
    }
    log_event($LOG_LEVEL_EVERYTHING, "admin/class/list_students.php",
            $LOG_ADMIN, "Viewed student list for class $classname.");
} else {
    $title = "Student List for $classname";

    include "header.php"; // Show header

    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "admin/class/list_students.php",
            $LOG_DENIED_ACCESS,
            "Attempted to view student list for class $classname.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

if($type == "html")
    include "footer.php";
