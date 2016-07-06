<?php
/**
 * ***************************************************************
 * admin/group/list.php (c) 2016 Jonathan Dieter
 *
 * List members of a group
 * ***************************************************************
 */

$title = "Groups";

if(isset($_GET['key'])) {
    $group_id = safe(dbfuncInt2String($_GET['key']));
} else {
    $group_id = NULL;
}

if(isset($_GET['keyname'])) {
    $group_name = htmlspecialchars(dbfuncInt2String($_GET['keyname']), ENT_QUOTES);
    $title = $group_name;
} else {
    $group_name = NULL;
}

include "header.php"; // Show header

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
    include "core/titletermyear.php";

    if(is_null($group_id)) {
        /* Get list of groups*/
        $query =    "SELECT CONCAT('@', groups.GroupID) AS ID, grouptype.GroupName AS Name FROM groups INNER JOIN grouptype USING (GroupTypeID) " .
                    "WHERE grouptype.PrimaryGroupType=1 " .
                    "AND   (groups.YearIndex = $yearindex OR groups.YearIndex IS NULL) " .
                    "ORDER BY grouptype.GroupName";
    } else {
        $query =    "SELECT groups.GroupID FROM groups " .
                    "WHERE groups.GroupTypeID = '$group_id' " .
                    "AND   (groups.YearIndex = $yearindex OR groups.YearIndex IS NULL) " .
                    "AND   (groups.TermIndex = $termindex OR groups.TermIndex IS NULL) ";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query
        if ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
            $group_id = $row['GroupID'];
        } else {
            echo "      <p>There are no groups this year.</p>\n";
            include "footer.php";
            exit(0);
        }

        $query =    "SELECT 0 AS SortOrder, groupmem.Member AS ID, grouptype.GroupName AS Name FROM " .
                    "    groupmem INNER JOIN (grouptype INNER JOIN groups " .
                    "                               ON  (groups.YearIndex = $yearindex OR groups.YearIndex IS NULL) " .
                    "                               AND grouptype.GroupTypeID = groups.GroupTypeID) " .
                    "             ON groupmem.Member = CONCAT('@', grouptype.GroupTypeID) " .
                    "WHERE groupmem.GroupID = '$group_id' " .
                    "UNION " .
                    "SELECT 1 AS SortOrder, user.Username AS ID, CONCAT(user.FirstName, ' ', user.Surname) AS Name FROM " .
                    "    groupmem INNER JOIN user ON groupmem.Member = user.Username " .
                    "WHERE groupmem.GroupID = '$group_id' " .
                    "ORDER BY SortOrder, Name, ID";
    }
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    /* Print classes and the # of students in each class */
    if ($res->numRows() > 0) {
        $count = $res->numRows();
        echo "       <p align='center'><em>Total count: $count</em></p>\n";
        if($is_admin) {
            $newlink = "index.php?location=" . dbfuncString2Int("admin/group/new.php");
            $newbutton = dbfuncGetButton($newlink, "New group", "medium", "", "Create new group");
            echo "        <p align='center'>$newbutton</p>\n";
        }
        echo "        <table align='center' border='1'>\n"; // Table headers
        echo "            <tr>\n";
        echo "                <th>&nbsp;</th>\n";
        echo "                <th>Name</th>\n";
        echo "                <th>ID</th>\n";
        echo "            </tr>\n";

        /* For each subject, print a row with the subject's name, and # of students */
        $alt_count = 0;
        while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
            $alt_count += 1;
            if ($alt_count % 2 == 0) {
                $alt = " class='alt'";
            } else {
                $alt = " class='std'";
            }
            if ($is_admin and $row['ID'][0] == '@') {
                $row_id = substr($row['ID'], 1);
                $editlink = "index.php?location=" .
                             dbfuncString2Int("admin/group/list.php") .
                             "&amp;key=" . dbfuncString2Int($row_id) .
                             "&amp;keyname=" .
                             dbfuncString2Int($row['Name']);
                $editbutton = dbfuncGetButton($editlink, "E", "small", "edit",
                                            "Edit group");
            } else {
                $editbutton = "";
            }

            echo "            <tr$alt>\n";
            /* Generate edit button */
            echo "                <td>$editbutton</td>\n";
            echo "                <td>{$row['Name']}</td>\n"; // Print class name
            echo "                <td>{$row['ID']}</td>\n";
            echo "            </tr>\n";
        }
        echo "      </table>\n"; // End of table
    } else {
        if(is_null($group_id)) {
            echo "      <p>There are no groups.</p>\n";
        } else {
            echo "      <p>There are no members of this group.</p>\n";
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

include "footer.php";
?>
