<?php
/**
 * ***************************************************************
 * admin/group/list.php (c) 2016 Jonathan Dieter
 *
 * List members of a group
 * ***************************************************************
 */

$title = "Groups";

$show_indirect = "0";

if(isset($_GET['key'])) {
    $group_type_id = safe(dbfuncInt2String($_GET['key']));
} else {
    $group_type_id = NULL;
}

if(isset($_GET['keyname'])) {
    $group_name = htmlspecialchars(dbfuncInt2String($_GET['keyname']), ENT_QUOTES);
    $title = $group_name;
} else {
    $group_name = NULL;
}

if(isset($_GET['key2']) and dbfuncInt2String($_GET['key2']) == "1")
    $show_indirect = "1";

$type = "html";
if(isset($_GET['key3']))
    $type = dbfuncInt2String($_GET["key3"]);
if($type != "csv")
    $type = "html";

if($type == "csv") {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=students.csv');
} else {
    include "header.php"; // Show header
}

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

if ($is_admin or $is_counselor) {
    $showalldeps = true;
} else {
    $admin_page = true;
}
include "core/settermandyear.php";

/* Check whether current user is a hod */
$res = &  $db->query(
                "SELECT Username FROM hod " . "WHERE Username='$username' " .
                 "AND   DepartmentIndex=$depindex");
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
    $is_hod = true;
} else {
    $is_hod = false;
}

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

if ($is_admin or $is_principal or $is_hod) {
    if($type != "csv") {
        include "core/titletermyear.php";
    }

    if(is_null($group_type_id)) {
        /* Get list of groups*/
        $query =    "SELECT CONCAT('@', groups.GroupID) AS ID, grouptype.GroupName AS Name, " .
                    "       groups.GroupID AS LinkID, NULL AS MemberCount, NULL AS RealUserCount " .
                    "       FROM groups INNER JOIN grouptype USING (GroupTypeID) " .
                    "WHERE grouptype.PrimaryGroupType=1 " .
                    "AND   (groups.YearIndex = $yearindex OR groups.YearIndex IS NULL) " .
                    "ORDER BY grouptype.GroupName";
    } else {
        $is_primary = false;

        $query =    "SELECT groups.GroupID, grouptype.PrimaryGroupType FROM groups, grouptype " .
                    "WHERE groups.GroupTypeID = '$group_type_id' " .
                    "AND   grouptype.GroupTypeID = groups.GroupTypeID " .
                    "AND   (groups.YearIndex=$yearindex OR groups.YearIndex IS NULL) " .
                    "AND   (groups.TermIndex=$termindex OR groups.TermIndex IS NULL) ";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query
        if ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
            $group_id = $row['GroupID'];
            if($row['PrimaryGroupType'] == 1)
                $is_primary = true;
        } else {
            echo "      <p>There are no groups this year.</p>\n";
            include "footer.php";
            exit(0);
        }

        if($is_primary) {
            $query =    "SELECT 0 AS SortOrder, groups.GroupID AS ID, grouptype.GroupName AS Name, " .
                        "       groups.GroupTypeID AS LinkID, groups.MemberCount, groups.RealUserCount FROM " .
                        "    groupmem INNER JOIN (grouptype INNER JOIN groups " .
                        "                               ON  (    (groups.YearIndex = $yearindex OR groups.YearIndex IS NULL) " .
                        "                                    AND (groups.TermIndex = $termindex OR groups.TermIndex IS NULL) " .
                        "                                    AND  grouptype.GroupTypeID = groups.GroupTypeID)) " .
                        "             ON groupmem.Member = CONCAT('@', grouptype.GroupTypeID) " .
                        "WHERE groupmem.GroupID = '$group_id' ";
        } else {
            if($show_indirect == "1") {
                $query =    "SELECT 0 AS SortOrder, user.Username AS ID, CONCAT(user.FirstName, ' ', user.Surname) AS Name, " .
                            "       NULL AS LinkID, NULL AS MemberCount, NULL AS RealUserCount FROM " .
                            "    groupgenmem INNER JOIN user ON groupgenmem.Username = user.Username " .
                            "WHERE groupgenmem.GroupID = '$group_id' " .
                            "ORDER BY SortOrder, Name, ID";
            } else {
                $query =    "SELECT 0 AS SortOrder, groupmem.Member AS ID, grouptype.GroupName AS Name, " .
                            "       groups.GroupTypeID AS LinkID, groups.MemberCount, groups.RealUserCount FROM " .
                            "    groupmem INNER JOIN groups ON groupmem.Member = CONCAT('@', groups.GroupID) INNER JOIN grouptype USING (GroupTypeID) " .
                            "WHERE groupmem.GroupID = '$group_id' " .
                            "UNION " .
                            "SELECT 1 AS SortOrder, user.Username AS ID, CONCAT(user.FirstName, ' ', user.Surname) AS Name, " .
                            "       NULL AS LinkID, NULL AS MemberCount, NULL AS RealUserCount FROM " .
                            "    groupmem INNER JOIN user ON groupmem.Member = user.Username " .
                            "WHERE groupmem.GroupID = '$group_id' " .
                            "ORDER BY SortOrder, Name, ID";
            }
        }
    }
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    /* Print classes and the # of students in each class */
    if ($res->numRows() > 0) {
        $count = $res->numRows();
        if($type != "csv") {
            echo "       <p align='center'><em>Total count: $count</em></p>\n";
            if($show_indirect == "1") {
                $showlink = "index.php?location=" .
                            dbfuncString2Int("admin/group/list.php") .
                            "&amp;key2=" . dbfuncString2Int("0");
                if(!is_null($group_type_id)) {
                    $showlink .=    "&amp;key=" . $_GET['key'] .
                                    "&amp;keyname=" . $_GET['keyname'];
                }
                $showbutton = dbfuncGetButton($showlink, "Show direct members", "medium", "", "Show only direct members of the group");
            } else {
                $showlink = "index.php?location=" .
                            dbfuncString2Int("admin/group/list.php") .
                            "&amp;key2=" . dbfuncString2Int("1");
                if(!is_null($group_type_id)) {
                    $showlink .=    "&amp;key=" . $_GET['key'] .
                                    "&amp;keyname=" . $_GET['keyname'];
                }
                $showbutton = dbfuncGetButton($showlink, "Show indirect members", "medium", "", "Show all members of the group, including indirect members");
            }
                $exportlink = "index.php?location=" .
                            dbfuncString2Int("admin/group/list.php") .
                            "&amp;key2=" . dbfuncString2Int($show_indirect) .
                            "&amp;key3=" . dbfuncString2Int("csv");
                if(!is_null($group_type_id)) {
                    $exportlink .=  "&amp;key=" . $_GET['key'] .
                                    "&amp;keyname=" . $_GET['keyname'];
                }
                $exportbutton = dbfuncGetButton($exportlink, "Export to CSV", "medium", "", "Export list of group members to CSV file");
            $newbutton = "";
            if($is_admin) {
                $newlink = "index.php?location=" . dbfuncString2Int("admin/group/new.php");
                $newbutton = dbfuncGetButton($newlink, "New group", "medium", "", "Create new group");
            }
            echo "        <p align='center'>$showbutton$exportbutton$newbutton</p>\n";
            echo "        <table align='center' border='1'>\n"; // Table headers
            echo "            <tr>\n";
            echo "                <th>&nbsp;</th>\n";
            echo "                <th>Name</th>\n";
            echo "                <th>ID</th>\n";
            if($show_indirect != "1") {
                echo "                <th><a title='Direct members'>D</a></th>\n";
                echo "                <th><a title='Total members'>T</a></th>\n";
            }
            echo "            </tr>\n";
        } else {
            echo "\"Name\",\"ID\"";
            if($show_indirect != "1")
                echo ",\"Direct members\",\"Indirect members\"";
            echo "\n";
        }

        /* For each subject, print a row with the subject's name, and # of students */
        $alt_count = 0;
        while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
            if($type != "csv") {
                $alt_count += 1;
                if ($alt_count % 2 == 0) {
                    $alt = " class='alt'";
                } else {
                    $alt = " class='std'";
                }
                if (!is_null($row['LinkID'])) {
                    if($is_admin) {
                        $row_id = htmlspecialchars($row['LinkID'], ENT_QUOTES);
                        $editlink = "index.php?location=" .
                                     dbfuncString2Int("admin/group/list.php") .
                                     "&amp;key=" . dbfuncString2Int($row_id) .
                                     "&amp;key2=" . dbfuncString2Int($show_indirect) .
                                     "&amp;keyname=" . dbfuncString2Int($row['Name']);
                        $editbutton = dbfuncGetButton($editlink, "E", "small", "edit",
                                                    "Edit group");
                    } else {
                        $editbutton = "";
                    }
                    $viewlink = "index.php?location=" .
                                 dbfuncString2Int("admin/group/list.php") .
                                 "&amp;key=" . dbfuncString2Int($row_id) .
                                 "&amp;key2=" . dbfuncString2Int($show_indirect) .
                                 "&amp;keyname=" . dbfuncString2Int($row['Name']);
                    $viewbutton = dbfuncGetButton($editlink, "V", "small", "view",
                                                "View group");
                } else {
                    $editbutton = "";
                    $viewbutton = "";
                }

                echo "            <tr$alt>\n";
                /* Generate edit button */
                echo "                <td>$viewbutton$editbutton</td>\n";
                echo "                <td>{$row['Name']}</td>\n"; // Print class name
                echo "                <td>{$row['ID']}</td>\n";
                if($show_indirect != "1") {
                    if(is_null($row['MemberCount'])) {
                        echo "                <td align='center'>-</td>\n";
                    } else {
                        echo "                <td>{$row['MemberCount']}</td>\n";
                    }
                    if(is_null($row['RealUserCount'])) {
                        echo "                <td align='center'>-</td>\n";
                    } else {
                        echo "                <td>{$row['RealUserCount']}</td>\n";
                    }
                }
                echo "            </tr>\n";
            } else {
                echo "\"{$row['Name']}\",\"{$row['ID']}\"";
                if($show_indirect != "1")
                    echo ",{$row['MemberCount']},{$row['RealUserCount']}";
                echo "\n";
            }
        }
        if($type != "csv")
            echo "      </table>\n"; // End of table
    } else {
        if($type != "csv") {
            if(is_null($group_id)) {
                echo "      <p>There are no groups.</p>\n";
            } else {
                echo "      <p>There are no members of this group.</p>\n";
            }
        }
    }
    log_event($LOG_LEVEL_EVERYTHING, "admin/group/list.php", $LOG_ADMIN,
            "Viewed group $title.");
} else {
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "admin/group/list.php", $LOG_DENIED_ACCESS,
            "Attempted to view group $title.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

if($type != "csv")
    include "footer.php";
?>
