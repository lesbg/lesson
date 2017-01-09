<?php
/**
 * ***************************************************************
 * admin/studentlist.php (c) 2004, 2016 Jonathan Dieter
 *
 * List all active students and which class they are in this year
 * ***************************************************************
 */
$title = "Student List";

include "header.php"; // Show header

if(isset($_GET['key2'])) {
    if(dbfuncInt2String($_GET['key2']) == "1") {
        $show_all = 1;
    } elseif(dbfuncInt2String($_GET['key2']) == "2") {
        $show_all = 2;
    } else {
        $show_all = 0;
    }
} else {
    $show_all = 0;
}
$show_str = dbfuncString2Int($show_all);

/* Check whether current user is a counselor */
$res = &  $db->query(
        "SELECT Username FROM counselorlist " .
        "WHERE Username='$username'");
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
    $is_counselor = true;
} else {
    $is_counselor = false;
}

if (dbfuncGetPermission($permissions, $PERM_ADMIN)) {
    $is_admin = true;
} else {
    $is_admin = false;
}

if ($is_admin or $is_counselor) { // Make sure user has permission to view and
    include "core/settermandyear.php"; // edit students

    /*if($show_all == 0) {*/
        $showalldeps = true;
        include "core/titletermyear.php";
    /*} else {
        if($yearindex != $currentyear) {
            $yearindex = $currentyear;
            include "core/settermandyear.php";
        }
        $nochangeyt = true;
        $nochangeyear = true;
        $showterm = false;
        $showdeps = false;
        include "core/titletermyear.php";
    }*/

    if(isset($_GET['sort'])) {
        $sort = "&amp;sort={$_GET['sort']}";
    } else {
        $sort = "";
    }
    if(isset($_GET['key2'])) {
        $section = "&amp;key2={$_GET['key2']}";
    } else {
        $section = "";
    }
    $showlink1 = "index.php?location=" .
            dbfuncString2Int("admin/studentlist.php") .
            "$sort&amp;key2=" .
            dbfuncString2Int("0");
    $showlink2 = "index.php?location=" .
            dbfuncString2Int("admin/studentlist.php") .
            "$sort&amp;key2=" .
            dbfuncString2Int("1");
    $showlink3 = "index.php?location=" .
            dbfuncString2Int("admin/studentlist.php") .
            "$sort&amp;key2=" .
            dbfuncString2Int("2");
    $showbutton1 = dbfuncGetButton($showlink1, "Show students in department", "medium", "", "Show students who are in the current department");
    $showbutton2 = dbfuncGetButton($showlink2, "Show active students", "medium", "", "Show all active students");
    $showbutton3 = dbfuncGetButton($showlink3, "Show all users", "medium", "", "Show all users");

    echo "<p align='center'>\n";
    if($show_all == 0) {
        $showbutton1 = dbfuncGetDisabledButton("Show students in department", "medium", "");
    } elseif($show_all == 1) {
        $showbutton2 = dbfuncGetDisabledButton("Show active students", "medium", "");
    } else {
        $showbutton3 = dbfuncGetDisabledButton("Show all users", "medium", "");
    }
    echo "$showbutton1 $showbutton2 $showbutton3\n";
    echo "</p>\n";

    if ($_GET['sort'] == '1') {
        $sortorder = "user.Username DESC";
    } elseif ($_GET['sort'] == '2') {
        $sortorder = "class.Grade, class.ClassName, user.Username";
    } elseif ($_GET['sort'] == '3') {
        $sortorder = "class.Grade DESC, class.ClassName DESC, user.Username DESC";
    } elseif ($_GET['sort'] == '4') {
        $sortorder = "user.FirstName, user.Surname, user.Username";
    } elseif ($_GET['sort'] == '5') {
        $sortorder = "user.FirstName DESC, user.Surname DESC, user.Username DESC";
    } elseif ($_GET['sort'] == '6') {
        $sortorder = "user.Surname, user.FirstName, user.Username";
    } elseif ($_GET['sort'] == '7') {
        $sortorder = "user.Surname DESC, user.FirstName DESC, user.Username DESC";
    } elseif ($_GET['sort'] == '8') {
        $sortorder = "user.House, user.Username";
    } elseif ($_GET['sort'] == '9') {
        $sortorder = "user.House DESC, user.Username DESC";
    } elseif ($_GET['sort'] == '10') {
        $sortorder = "familylist.FamilyCode, familylist.Guardian DESC, class.Grade, class.ClassName, user.Username";
    } elseif ($_GET['sort'] == '11') {
        $sortorder = "familylist.FamilyCode DESC, familylist.Guardian, class.Grade DESC, class.ClassName DESC, user.Username DESC";
    } elseif ($_GET['sort'] == '12') {
        $sortorder = "familylist.Guardian, familylist.FamilyCode, class.Grade, class.ClassName, user.Username";
    } elseif ($_GET['sort'] == '13') {
        $sortorder = "familylist.Guardian DESC, familylist.FamilyCode DESC, class.Grade DESC, class.ClassName DESC, user.Username DESC";
    } else {
        $sortorder = "user.Username";
    }

    $unameAsc = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/studentlist.php") .
                                 "&amp;sort=0$section", "A", "small", "sort",
                                "Sort ascending");
    $unameDec = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/studentlist.php") .
                                 "&amp;sort=1$section", "D", "small", "sort",
                                "Sort descending");
    $fnameAsc = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/studentlist.php") .
                                 "&amp;sort=4$section", "A", "small", "sort",
                                "Sort ascending");
    $fnameDec = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/studentlist.php") .
                                 "&amp;sort=5$section", "D", "small", "sort",
                                "Sort descending");
    $lnameAsc = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/studentlist.php") .
                                 "&amp;sort=6$section", "A", "small", "sort",
                                "Sort ascending");
    $lnameDec = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/studentlist.php") .
                                 "&amp;sort=7$section", "D", "small", "sort",
                                "Sort descending");
    $classAsc = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/studentlist.php") .
                                 "&amp;sort=2$section", "A", "small", "sort",
                                "Sort ascending");
    $classDec = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/studentlist.php") .
                                 "&amp;sort=3$section", "D", "small", "sort",
                                "Sort descending");
    $houseAsc = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/studentlist.php") .
                                 "&amp;sort=8$section", "A", "small", "sort",
                                "Sort ascending");
    $houseDec = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/studentlist.php") .
                                 "&amp;sort=9$section", "D", "small", "sort",
                                "Sort descending");
    $fcodeAsc = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/studentlist.php") .
                                 "&amp;sort=10$section", "A", "small", "sort",
                                "Sort ascending");
    $fcodeDec = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/studentlist.php") .
                                 "&amp;sort=11$section", "D", "small", "sort",
                                "Sort descending");
    $guardAsc = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/studentlist.php") .
                                 "&amp;sort=12$section", "A", "small", "sort",
                                "Sort ascending");
    $guardDec = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/studentlist.php") .
                                 "&amp;sort=13$section", "D", "small", "sort",
                                "Sort descending");
    /* Get student list */
    if ($show_all == 1 || $show_all == 2) {
        $query =    "SELECT user.FirstName, user.Surname, user.Username, user.OriginalPassword, " .
                    "       newmem.Username AS New, specialmem.Username AS Special, " .
                    "       user.House, class.ClassName, class.Grade, " .
                    "       GROUP_CONCAT(DISTINCT familylist.FamilyCode SEPARATOR '<br>') AS FamilyCode, " .
                    "       MAX(familylist.Guardian) AS Guardian FROM " .
                    "       user LEFT OUTER JOIN " .
                    "       familylist USING (Username) LEFT OUTER JOIN " .
                    "        (class INNER JOIN classterm ON " .
                    "           (class.YearIndex = $yearindex AND classterm.ClassIndex = class.ClassIndex) " .
                    "          INNER JOIN classlist USING (ClassTermIndex)) " .
                    "        USING (Username) " .
                    "       LEFT OUTER JOIN (groupgenmem AS newmem INNER JOIN " .
                    "                         groups AS newgroups ON (newgroups.GroupID=newmem.GroupID " .
                    "                                                 AND newgroups.GroupTypeID='new' " .
                    "                                                AND newgroups.YearIndex=$yearindex)) ON (user.Username=newmem.Username) " .
                    "       LEFT OUTER JOIN (groupgenmem AS specialmem INNER JOIN " .
                    "                         groups AS specgroups ON (specgroups.GroupID=specialmem.GroupID " .
                    "                                                  AND specgroups.GroupTypeID='special' " .
                    "                                                 AND specgroups.YearIndex=$yearindex)) ON (user.Username=specialmem.Username) ";
        if($show_all == 1) {
            $query .=   "        INNER JOIN (groupgenmem INNER JOIN groups " .
                        "                     ON (groups.GroupTypeID='activestudent' " .
                        "                         AND groupgenmem.GroupID=groups.GroupID " .
                        "                         AND groups.YearIndex=$yearindex)) " .
                        "                   ON (user.Username=groupgenmem.Username) ";
        }
        $query .=   "GROUP BY user.Username " .
                    "ORDER BY $sortorder";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query
    } else {
        $query =    "SELECT user.FirstName, user.Surname, user.Username, user.OriginalPassword, " .
                    "       newmem.Username AS New, specialmem.Username AS Special, " .
                    "       user.House, class.ClassName, class.Grade, " .
                    "       GROUP_CONCAT(DISTINCT familylist.FamilyCode SEPARATOR '<br>') AS FamilyCode, " .
                    "       MAX(familylist.Guardian) AS Guardian FROM " .
                    "       user INNER JOIN " .
                    "        (class INNER JOIN classterm ON " .
                    "           (class.YearIndex = $yearindex " .
                    "            AND classterm.ClassIndex = class.ClassIndex " .
                    "            AND classterm.TermIndex = $termindex) " .
                    "          INNER JOIN classlist USING (ClassTermIndex)) " .
                    "        USING (Username) " .
                    "       LEFT OUTER JOIN familylist ON (user.Username=familylist.Username) " .
                    "       LEFT OUTER JOIN (groupgenmem AS newmem INNER JOIN " .
                    "                         groups AS newgroups ON (newgroups.GroupID=newmem.GroupID " .
                    "                                                 AND newgroups.GroupTypeID='new' " .
                    "                                                AND newgroups.YearIndex=$yearindex)) ON (user.Username=newmem.Username) " .
                    "       LEFT OUTER JOIN (groupgenmem AS specialmem INNER JOIN " .
                    "                         groups AS specgroups ON (specgroups.GroupID=specialmem.GroupID " .
                    "                                                  AND specgroups.GroupTypeID='special' " .
                    "                                                 AND specgroups.YearIndex=$yearindex)) ON (user.Username=specialmem.Username) " .
                    "GROUP BY user.Username " .
                    "ORDER BY $sortorder";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query
    }

    /* Print students and their class */
    if ($res->numRows() > 0) {
        $count = $res->numRows();
        if($show_all == 2) {
            echo "      <p align='center'><em>Total users: $count</em>";
        } else {
            echo "      <p align='center'><em>Total students: $count</em>";
        }
        echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
        echo "         <tr>\n";
        echo "            <th>&nbsp;</th>\n";
        echo "            <th>First Name $fnameAsc $fnameDec</th>\n";
        echo "            <th>Last Name $lnameAsc $fnameDec</th>\n";
        echo "            <th>Username $unameAsc $unameDec</th>\n";
        echo "            <th>Password $pwdAsc $pwdDec</th>\n";
        echo "            <th>Family Code $fcodeAsc $fcodeDec</th>\n";
        if($show_all == 2) {
            echo "            <th>Guardian $guardAsc $guardDec</th>\n";
        }
        echo "            <th>Class $classAsc $classDec</th>\n";
        echo "            <th>House $houseAsc $houseDec</th>\n";
        echo "            <th>New</th>\n";
        echo "            <th>Special</th>\n";
        echo "         </tr>\n";

        /* For each student, print a row with the student's name and what class they're in */
        $alt_count = 0;
        while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
            $alt_count += 1;
            if ($alt_count % 2 == 0) {
                $alt = " class=\"alt\"";
            } else {
                $alt = " class=\"std\"";
            }
            echo "         <tr$alt>\n";

            $viewlink = "index.php?location=" .
                         dbfuncString2Int("admin/subject/list_student.php") .
                         "&amp;key=" . dbfuncString2Int($row['Username']) .
                         "&amp;keyname=" .
                         dbfuncString2Int(
                                        "{$row['FirstName']} {$row['Surname']} ({$row['Username']})");
            $editlink = "index.php?location=" .
                         dbfuncString2Int("admin/user/modify.php") . "&amp;key=" .
                         dbfuncString2Int($row['Username']) . "&amp;keyname=" .
                         dbfuncString2Int(
                                        "{$row['FirstName']} {$row['Surname']} ({$row['Username']})");
            $cnlink = "index.php?location=" .
                     dbfuncString2Int("teacher/casenote/list.php") . "&amp;key=" .
                     dbfuncString2Int($row['Username']) . "&amp;keyname=" .
                     dbfuncString2Int(
                                    "{$row['FirstName']} {$row['Surname']} ({$row['Username']})") .
                     "&amp;keyname2=" . dbfuncSTring2Int($row['FirstName']);
            $sublink = "index.php?location=" .
                     dbfuncString2Int("admin/subject/modify_by_student.php") .
                     "&amp;key=" . dbfuncString2Int($row['Username']) .
                     "&amp;keyname=" .
                     dbfuncString2Int(
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
                     dbfuncString2Int($row['Username']) . "&amp;keyname=" .
                     dbfuncString2Int(
                                    $row['FirstName'] . " " . $row['Surname']);

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
            } else {
                $viewbutton = "";
                $ttbutton = "";
                $subbutton = "";
                $editbutton = "";
                $hbutton = "";
                $abutton = "";
            }

            $cnbutton = dbfuncGetButton($cnlink, "C", "small", "cn",
                                        "Casenotes for student");
            echo "            <td>$cnbutton$viewbutton$ttbutton$abutton$subbutton$hbutton$editbutton</td>\n";
            echo "            <td>{$row['FirstName']}</td>\n";
            echo "            <td>{$row['Surname']}</td>\n";
            echo "            <td>{$row['Username']}</td>\n";
            if (is_null($row['OriginalPassword'])) {
                echo "            <td>&nbsp;</td>\n";
            } elseif ($row['OriginalPassword'] == "!!") {
                echo "            <td><em><a title='Login disabled'>X</a></em></td>\n";
            } else {
                echo "            <td><a title='Password needs to be changed'>C</a></td>\n";
            }
            if ($row['FamilyCode'] != NULL) {
                echo "            <td>{$row['FamilyCode']}</td>\n";
            } else {
                echo "            <td><i>None</i></td>\n";
            }
            if($show_all == 2) {
                if ($row['Guardian'] != 1) {
                    echo "            <td>&nbsp;</td>\n";
                } else {
                    echo "            <td>X</td>\n";
                }
            }
            if ($row['ClassName'] != NULL) {
                echo "            <td>{$row['ClassName']}</td>\n";
            } else {
                echo "            <td><i>None</i></td>\n";
            }
            if ($row['House'] != NULL) {
                echo "            <td>{$row['House']}</td>\n";
            } else {
                echo "            <td><i>None</i></td>\n";
            }
            if (!is_null($row['New'])) {
                echo "            <td>X</td>\n";
            } else {
                echo "            <td>&nbsp;</td>\n";
            }
            if (!is_null($row['Special'])) {
                echo "            <td>X</td>\n";
            } else {
                echo "            <td>&nbsp;</td>\n";
            }
            echo "         </tr>\n";
        }
        echo "      </table>\n"; // End of table
    } else {
        echo "      <p>There are no active students</p>\n";
    }
} else {
    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

include "footer.php";
?>
