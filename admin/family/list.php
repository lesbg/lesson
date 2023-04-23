<?php
/**
 * ***************************************************************
 * admin/family/list.php (c) 2015-2017, 2019 Jonathan Dieter
 *
 * List all family codes
 * ***************************************************************
 */
$title = "Family List";
if(isset($_GET['key2']) && dbfuncInt2String($_GET['key2']) == "1") {
    $show_all = 1;
} else {
    $show_all = 0;
}
$show_str = intval(dbfuncString2Int($show_all));

include "header.php"; // Show header

/*
if ($res->numRows() > 0) {
    $is_counselor = true;
} else {*/
    $is_counselor = false;
/*}*/

if (dbfuncGetPermission($permissions, $PERM_ADMIN)) {
    $is_admin = true;
} else {
    $is_admin = false;
}

if ($is_admin or $is_counselor) { // Make sure user has permission to view and
    $showalldeps = true;
    include "core/settermandyear.php"; // edit students
    include "core/titletermyear.php";

    if ($_GET['sort'] == '1') {
        $sortorder = "FamilyCode DESC";
    } elseif ($_GET['sort'] == '2') {
        $sortorder = "FamilyName, FamilyCode";
    } elseif ($_GET['sort'] == '3') {
        $sortorder = "FamilyName DESC, FamilyCode DESC";
    /*} elseif ($_GET['sort'] == '4') {
        $sortorder = "family.FatherName, family.FamilyCode";
    } elseif ($_GET['sort'] == '5') {
        $sortorder = "family.FatherName DESC, family.FamilyCode DESC";
    } elseif ($_GET['sort'] == '6') {
        $sortorder = "family.MotherName, family.FamilyCode";
    } elseif ($_GET['sort'] == '7') {
        $sortorder = "family.MotherName DESC, family.FamilyCode DESC";*/
    } else {
        $sortorder = "FamilyCode";
    }

    $fcodeAsc = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/family/list.php") .
                                 "&amp;sort=0&amp;key2=$show_str", "A", "small", "sort",
                                "Sort ascending");
    $fcodeDec = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/family/list.php") .
                                 "&amp;sort=1&amp;key2=$show_str", "D", "small", "sort",
                                "Sort descending");
    $fnameAsc = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/family/list.php") .
                                 "&amp;sort=2&amp;key2=$show_str", "A", "small", "sort",
                                "Sort ascending");
    $fnameDec = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/family/list.php") .
                                 "&amp;sort=3&amp;key2=$show_str", "D", "small", "sort",
                                "Sort descending");
    /*$dadnameAsc = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/family/list.php") .
                                 "&amp;sort=4&amp;key2=$show_str", "A", "small", "sort",
                                "Sort ascending");
    $dadnameDec = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/family/list.php") .
                                 "&amp;sort=5&amp;key2=$show_str", "D", "small", "sort",
                                "Sort descending");
    $momnameAsc = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/family/list.php") .
                                 "&amp;sort=6&amp;key2=$show_str", "A", "small", "sort",
                                "Sort ascending");
    $momnameDec = dbfuncGetButton(
                                "index.php?location=" .
                                 dbfuncString2Int("admin/family/list.php") .
                                 "&amp;sort=7&amp;key2=$show_str", "D", "small", "sort",
                                "Sort descending");*/

    $newlink = "index.php?location=" .
            dbfuncString2Int("admin/family/modify.php") . // link to create a new subject
            "&amp;next=" .
            dbfuncString2Int(
                    "index.php?location=" .
                    dbfuncString2Int("admin/family/list.php") .
                    "&amp;key2=$show_str");
    $newbutton = dbfuncGetButton($newlink, "New family", "medium", "", "Create new family");
    if($show_all == 1) {
        $showlink = "index.php?location=" .
                dbfuncString2Int("admin/family/list.php") . // link to create a new subject
                "&amp;key2=" .
                dbfuncString2Int("0");
        $showbutton = dbfuncGetButton($showlink, "Show active families", "medium", "", "Show families with active students");
    } else {
        $showlink = "index.php?location=" .
                dbfuncString2Int("admin/family/list.php") . // link to create a new subject
                "&amp;key2=" .
                dbfuncString2Int("1");
        $showbutton = dbfuncGetButton($showlink, "Show all families", "medium", "", "Show all families");
    }
    echo "      <p align=\"center\">$newbutton $showbutton</p>\n";

    /* Get student list */
    $query =        "SELECT user.FirstName, user.Surname, user.Title, user.Username, " .
                    "       activestudentinfo.Username AS ActiveStudent, " .
                    "       activeteacherinfo.Username AS ActiveTeacher, familylist.Guardian, familyinfo.*, " .
                    "       class.ClassName, " .
                    "       SUBSTRING_INDEX(" .
                    "        GROUP_CONCAT(phone.Number ORDER BY phone.SortOrder SEPARATOR '#*#*'), " .
                    "        '#*#*', 1) AS Number, " .
                    "       SUBSTRING_INDEX(" .
                    "        GROUP_CONCAT(phone.Type ORDER BY phone.SortOrder SEPARATOR '#*#*'), " .
                    "        '#*#*', 1) AS Type, " .
                    "       SUBSTRING_INDEX(" .
                    "        GROUP_CONCAT(phone.Comment ORDER BY phone.SortOrder SEPARATOR '#*#*'), " .
                    "        '#*#*', 1) AS Comment " .
                    "FROM " .
                    "   (SELECT family.FamilyCode, family.FamilyName, family.RegistrationNumber, family.Town, " .
                    "           house.HouseName, MAX(groupgenmem.Username) AS ActiveStudents FROM " .
                    "       family LEFT OUTER JOIN " .
                    "            (familylist AS familylist2 INNER JOIN user AS user2 USING (Username)) USING (FamilyCode) " .
                    "              LEFT OUTER JOIN house ON family.House=house.House " .
                    "              LEFT OUTER JOIN (groupgenmem INNER JOIN groups " .
                    "                               ON (groups.GroupTypeID='activestudent' " .
                    "                                   AND groupgenmem.GroupID=groups.GroupID " .
                    "                                   AND groups.YearIndex=$yearindex)) " .
                    "                   ON (user2.Username=groupgenmem.Username and familylist2.Guardian=0) ";
    if(!$show_all) {
        $query .=   "    WHERE (groupgenmem.Username IS NOT NULL OR familylist2.FamilyCode IS NULL) ";
    }
    $query .=       "    GROUP BY family.FamilyCode) AS familyinfo " .
                    "   LEFT OUTER JOIN (familylist INNER JOIN user USING (Username) " .
                    "          LEFT OUTER JOIN phone USING (Username) " .
                    "          LEFT OUTER JOIN (groupgenmem AS activestudentinfo INNER JOIN groups AS asgroups " .
                    "                     ON (asgroups.GroupTypeID='activestudent' " .
                    "                         AND activestudentinfo.GroupID=asgroups.GroupID " .
                    "                         AND asgroups.YearIndex=$yearindex)) " .
                    "                   USING (Username) " .
                    "          LEFT OUTER JOIN (groupgenmem AS activeteacherinfo INNER JOIN groups AS atgroups " .
                    "                     ON (atgroups.GroupTypeID='activeteacher' " .
                    "                         AND activeteacherinfo.GroupID=atgroups.GroupID " .
                    "                         AND atgroups.YearIndex=$yearindex)) " .
                    "                   USING (Username) " .
                    "          LEFT OUTER JOIN (class INNER JOIN classterm " .
                    "               ON (class.YearIndex=$yearindex AND classterm.ClassIndex=class.ClassIndex) ";
    if($yearindex == $currentyear) {
        $query .=   "          INNER JOIN currentterm ON classterm.TermIndex=currentterm.TermIndex ";
    } else {
        $query .=   "          INNER JOIN term ON classterm.TermIndex=term.TermIndex AND term.TermNumber=1 ";
    }
    $query .=       "          INNER JOIN classlist USING (ClassTermIndex)) ON classlist.Username=user.Username) " .
                    "   USING (FamilyCode) " .
                    "GROUP by familyinfo.FamilyCode, user.Username " .
                    "ORDER BY $sortorder, Guardian DESC, IF(Guardian=1, user.Gender, Guardian) DESC, " .
                    "         class.Grade DESC, user.Username";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    /* Print families and their members */
    if ($res->numRows() > 0) {
        echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
        echo "         <tr>\n";
        echo "            <th>&nbsp;</th>\n";
        echo "            <th>Family Code $fcodeAsc $fcodeDec</th>\n";
        echo "            <th>Family Name $fnameAsc $fnameDec</th>\n";
        echo "            <th>Town</th>\n";
        echo "            <th>Registration Number</th>\n";
        echo "            <th>House</th>\n";
        echo "            <th>Guardians</th>\n";
        echo "            <th>Students</th>\n";
        echo "         </tr>\n";

        /* For each family, print a row with the family code and other information */
        $alt_count = 0;
        $row = NULL;
        $prev_family = NULL;
        $prev_guardian = 2;

        while ( true ) {
            if($next_row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
                if(is_null($row)) {
                    $row = $next_row;
                    continue;
                }
            }

            if($row['FamilyCode'] != $prev_family) {
                $prev_family = $row['FamilyCode'];

                $alt_count += 1;
                if ($alt_count % 2 == 0) {
                    $alt = " class=\"alt\"";
                } else {
                    $alt = " class=\"std\"";
                }
                echo "         <tr$alt>\n";

                $editlink = "index.php?location=" .
                             dbfuncString2Int("admin/family/modify.php") . "&amp;key=" .
                             dbfuncString2Int($row['FamilyCode']) . "&amp;keyname=" .
                             dbfuncString2Int("{$row['FamilyName']}");

                $removelink = "index.php?location=" .
                             dbfuncString2Int("admin/family/remove_from_school.php") . "&amp;key=" .
                             dbfuncString2Int($row['FamilyCode']) . "&amp;keyname=" .
                             dbfuncString2Int("{$row['FamilyName']}");

                /* Generate edit and remove buttons */
                if ($is_admin) {
                    $editbutton = dbfuncGetButton($editlink, "E", "small", "edit",
                                                "Edit family");
                    if(!is_null($row['ActiveStudents']) and $yearindex >= $currentyear) {
                        $removebutton = dbfuncGetButton($removelink, "-", "small", "delete",
                                                    "Remove all students in family from school");
                    } else {
                        $removebutton = "";
                    }
                } else {
                    $editbutton = "";
                    $removebutton = "";
                }

                $fcode = htmlspecialchars($row['FamilyCode']);
                $fname = htmlspecialchars($row['FamilyName']);
                if(!is_null($row['HouseName'])) {
                    $house = htmlspecialchars($row['HouseName']);
                } else {
                    $house = "<em>None</em>";
                }
                if(!is_null($row['RegistrationNumber'])) {
                    $regnum = htmlspecialchars($row['RegistrationNumber']);
                } else {
                    $regnum = "<em>None</em>";
                }
                if(!is_null($row['Town'])) {
                    $town = htmlspecialchars($row['Town']);
                } else {
                    $town = "<em>None</em>";
                }
                $row['FamilyCode'] = safe($row['FamilyCode']);
                echo "            <td nowrap='nowrap'>$editbutton $removebutton</td>\n";
                echo "            <td>$fcode</td>\n";
                echo "            <td>$fname</td>\n";
                echo "            <td>$town</td>\n";
                echo "            <td>$regnum</td>\n";
                echo "            <td>$house</td>\n";
            }
            if($row['Guardian'] != $prev_guardian && !is_null($row['Guardian'])) {
                $prev_guardian = $row['Guardian'];
                echo "            <td>\n";
            }

            if(!is_null($row['Guardian'])) {
                if($row['Guardian'] == 1) {
                    $who = "guardian";
                } else {
                    $who = "student";
                }

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
                $removelink = "index.php?location=" .
                        dbfuncString2Int("admin/family/remove_student_from_school.php") . "&amp;key=" .
                        dbfuncString2Int($row['Username']) . "&amp;keyname=" .
                        dbfuncString2Int(
                            "{$row['FirstName']} {$row['Surname']} ({$row['Username']})");

                $cnlink = "index.php?location=" .
                        dbfuncString2Int("teacher/casenote/list.php") . "&amp;key=" .
                        dbfuncString2Int($row['Username']) . "&amp;keyname=" .
                        dbfuncString2Int(
                            "{$row['FirstName']} {$row['Surname']} ({$row['Username']})") .
                        "&amp;keyname2=" . dbfuncSTring2Int($row['FirstName']);
                if($is_admin) {
                    if(!is_null($row['ActiveStudent']) && $row['Guardian'] == 0) {
                        $viewbutton = dbfuncGetButton($viewlink, "V", "small", "view",
                                "View $who's subjects");
                        if($yearindex >= $currentyear) {
                            $removebutton = dbfuncGetButton($removelink, "-", "small", "delete",
                                                    "Remove from school");
                        } else {
                            $removebutton = "";
                        }
                    } else {
                        $viewbutton = "";
                        $removebutton = "";
                    }
                    $editbutton = dbfuncGetButton($editlink, "E", "small", "edit",
                            "Edit $who");
                } else {
                    $viewbutton = "";
                    $editbutton = "";
                    $removebutton = "";
                }
                echo "$viewbutton $editbutton $removebutton";
                if(!is_null($row['ActiveStudent'])) {
                    echo "<strong>";
                }
                if($row['Guardian'] == 1) {
                    echo "<em>";
                    if(isset($row['Title']) and $row['Title'] != "") {
                        echo "{$row['Title']} ";
                    }
                }
                echo "{$row['FirstName']} {$row['Surname']} ({$row['Username']})";
                if(!is_null($row['ActiveStudent'])) {
                    echo " - {$row['ClassName']}";
                }
                if($row['Guardian'] == 1) {
                    if($row['Number'] != "") {
                        echo " - {$row['Number']}";
                    }
                    echo "</em>";
                }
                if(!is_null($row['ActiveStudent'])) {
                    echo "</strong>";
                }
                echo "<br />\n";
            }

            if($next_row['FamilyCode'] != $prev_family) {
                if($prev_guardian < 2) { // Last thing we showed was either a student or guardian
                    echo "            </td>";
                } else {
                    echo "            <td>&nbsp;</td>";
                }
                if($prev_guardian > 0) { // Last thing we showed wasn't a student
                    echo "<td>&nbsp;</td>\n";
                } else {
                    echo "\n";
                }
                $prev_guardian = 2;
                echo "         </tr>\n";
            } elseif($next_row['Guardian'] != $prev_guardian) {
                echo "            </td>\n";
            }

            $row = $next_row;
            if(is_null($row))
                break;
        }
        echo "      </table>\n"; // End of table
    } else {
        echo "      <p>There are no families</p>\n";
    }
} else {
    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

include "footer.php";
