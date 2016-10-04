<?php
/**
 * ***************************************************************
 * admin/user/modify_action.php (c) 2005, 2015-2016 Jonathan Dieter
 *
 * Run query to modify a user in the database.
 * ***************************************************************
 */

/* Get variables */
$error = false; // Boolean to store any errors
$uname = safe(dbfuncInt2String($_GET['key']));
$fullname = dbfuncInt2String($_GET['keyname']) . " (" . $uname . ")";

/* Check whether user is authorized to modify users */
if ($is_admin) {

    /* Modify user */
    $query = "UPDATE user SET FirstName = '{$_POST['fname']}', " .
             "Surname = '{$_POST['sname']}', " .
             "Gender = '{$_POST['gender']}', " .
             "PhoneNumber = '{$_POST['phone']}', " . "DOB = {$_POST['DOB']}, " .
             "Permissions = {$_POST['perms']}, " . "Title = {$_POST['title']}, " .
             "DateType = {$_POST['datetype']}, " .
             "DateSeparator = {$_POST['datesep']}, " .
             "DepartmentIndex = {$_POST['department']} " .
             "WHERE username = '$uname'";
    $aRes = & $db->query($query);
    if (DB::isError($aRes))
        die($aRes->getDebugInfo()); // Check for errors in query

    if(!isset($_POST['show_family']) || $_POST['show_family'] != '1') {
        /* Remove any family codes we've been removed from */
        $query = "SELECT FamilyListIndex, FamilyCode FROM familylist WHERE Username='$uname'";
        $aRes = & $db->query($query);
        if (DB::isError($aRes))
            die($aRes->getDebugInfo()); // Check for errors in query
        while ( $arow = & $aRes->fetchRow(DB_FETCHMODE_ASSOC) ) {
            $found = False;
            foreach($_POST['fcode'] as $val) {
                if($aRow['FamilyCode'] == $val[0])
                    $found = True;
            }
            if(!$found) {
                $query = "DELETE FROM familylist WHERE FamilyListIndex={$arow['FamilyListIndex']}";
                $bRes = & $db->query($query);
                if (DB::isError($bRes))
                    die($bRes->getDebugInfo()); // Check for errors in query
            }
        }

        /* Add any family codes we've been added to */
        foreach($_POST['fcode'] as $val) {
            $fcode = $val[0];
            $guardian = $val[1];
            $query = "SELECT FamilyListIndex, FamilyCode FROM familylist WHERE Username='$uname' AND FamilyCode='$fcode'";
            $aRes = & $db->query($query);
            if (DB::isError($aRes))
                die($aRes->getDebugInfo()); // Check for errors in query
            if ($aRes->numRows() == 0) {
                $query = "INSERT INTO familylist (Username, FamilyCode, Guardian) VALUES ('$uname', '$fcode', $guardian)";
                $aRes = & $db->query($query);
                if (DB::isError($aRes))
                    die($aRes->getDebugInfo()); // Check for errors in query
            } else {
                $query = "UPDATE familylist SET Guardian=$guardian WHERE Username='$uname' AND FamilyCode='$fcode'";
                $aRes = & $db->query($query);
                if (DB::isError($aRes))
                    die($aRes->getDebugInfo()); // Check for errors in query
            }
        }
    } else {
        if(!isset($_SESSION['post_family'])) {
            $_SESSION['post_family'] = array();
        }
        if(!isset($_SESSION['post_family']['uname'])) {
            $_SESSION['post_family']['uname'] = array();
        }
        if($_POST['new_user_type'] == 'f' ||$_POST['new_user_type'] == 'm') {
            $guardian = 1;
        } else {
            $guardian = 0;
        }
        $_SESSION['post_family']['uname'][] = array($_POST['uname'], $guardian);
    }

    /* Remove any groups we've been removed from */
    $query =    "SELECT groupmem.GroupMemberIndex, groups.GroupID FROM " .
                "       groups, groupmem AS mastergroupmem, groupmem " .
                "WHERE mastergroupmem.Member=CONCAT('@', groups.GroupTypeID) " .
                "AND   mastergroupmem.GroupID='userinfo' " .
                "AND   groups.YearIndex=$yearindex " .
                "AND   groupmem.GroupID=groups.GroupID " .
                "AND   groupmem.Member='$uname' ";
    $aRes = & $db->query($query);
    if (DB::isError($aRes))
        die($aRes->getDebugInfo()); // Check for errors in query
    while ( $arow = & $aRes->fetchRow(DB_FETCHMODE_ASSOC) ) {
        if(!in_array($arow['GroupID'], $_POST['groups'])) {
            $query = "DELETE FROM groupmem WHERE GroupMemberIndex={$arow['GroupMemberIndex']}";
            $bRes = & $db->query($query);
            if (DB::isError($bRes))
                die($bRes->getDebugInfo()); // Check for errors in query
            gen_group_members($arow['GroupID']);
        }
    }

    /* Add any groups we've been added to */
    foreach($_POST['groups'] as $group) {
        $query = "SELECT GroupMemberIndex, GroupID FROM groupmem WHERE Member='$uname' AND GroupID='$group'";
        $aRes = & $db->query($query);
        if (DB::isError($aRes))
            die($aRes->getDebugInfo()); // Check for errors in query
        if ($aRes->numRows() == 0) {
            $query = "INSERT INTO groupmem (Member, GroupID) VALUES ('$uname', '$group')";
            $aRes = & $db->query($query);
            if (DB::isError($aRes))
                die($aRes->getDebugInfo()); // Check for errors in query
        }
        gen_group_members($group);
    }

    /* Remove any phone numbers we've lost */
    foreach($_POST['phone_remove'] as $phone_index) {
        $query = "DELETE FROM phone WHERE PhoneIndex='$phone_index'";
        $aRes = & $db->query($query);
        if (DB::isError($aRes))
            die($aRes->getDebugInfo()); // Check for errors in query
    }

    /* Add any new phone numbers */
    $count = 0;
    foreach($_POST['phone'] as $phone) {
        $count += 1;
        if($phone[3] == "") {
            $comment = "NULL";
        } else {
            $comment = "'${phone[3]}'";
        }
        if($phone[0] < 0) {
            $query =    "INSERT INTO phone (SortOrder, Number, Username, Type, Comment) " .
                        "           VALUES ($count, '${phone[1]}', '$uname', ${phone[2]}, $comment)";
        } else {
            $query =    "UPDATE phone SET SortOrder=$count, Number='${phone[1]}', Username='$uname', " .
                        "                 Type=${phone[2]}, Comment=$comment WHERE PhoneIndex=${phone[0]}";
        }
        $aRes = & $db->query($query);
        if (DB::isError($aRes))
            die($aRes->getDebugInfo()); // Check for errors in query
    }

    /* Add and remove to/from classes */
    $query =    "SELECT ClassTermIndex, DepartmentIndex FROM classlist INNER JOIN classterm USING (ClassTermIndex) " .
                "                     INNER JOIN class USING (ClassIndex) " .
                "WHERE  classlist.Username='$uname' " .
                "AND    class.YearIndex=$yearindex " .
                "ORDER BY classterm.TermIndex DESC " .
                "LIMIT 1";
    $aRes = &  $db->query($query);
    if (DB::isError($aRes))
        die($aRes->getDebugInfo()); // Check for errors in query
    if ( $aRow = & $aRes->fetchRow(DB_FETCHMODE_ASSOC) ) {
        $classdepindex = $aRow['DepartmentIndex'];
        $currentclassterm = $aRow['ClassTermIndex'];
    } else {
        $classdepindex = NULL;
        $currentclassterm = NULL;
    }

    if($_POST['classtermindex'] != $_POST['oldclasstermindex']) {
        if($_POST['oldclasstermindex'] != "NULL") {
            $query = "DELETE FROM classlist WHERE Username='$uname' AND ClassTermIndex='{$_POST['oldclasstermindex']}'";
            $aRes = & $db->query($query);
            if (DB::isError($aRes))
                die($aRes->getDebugInfo()); // Check for errors in query
        }
        if($_POST['classtermindex'] != "NULL") {
            $query =    "INSERT INTO classlist (Username, ClassTermIndex) VALUES ('$uname', {$_POST['classtermindex']})";
            $aRes = & $db->query($query);
            if (DB::isError($aRes))
                die($aRes->getDebugInfo()); // Check for errors in query
        }
    }

    log_event($LOG_LEVEL_ADMIN, "admin/user/modify_action.php", $LOG_ADMIN,
        "Modified {$_POST['fname']} {$_POST['sname']} ($uname).");
} else { // User isn't authorized to view or change users.
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "admin/user/modify_action.php",
            $LOG_DENIED_ACCESS, "Attempted to modify user $fullname.");
    echo "</p>\n      <p>You do not have permission to modify this user.</p>\n      <p>";
    $error = true;
}
?>
