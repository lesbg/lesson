<?php
/**
 * ***************************************************************
 * admin/user/new_action.php (c) 2005, 2016 Jonathan Dieter
 *
 * Run query to insert a new user into the database.
 * ***************************************************************
 */

/* Get variables */
$error = false; // Boolean to store any errors

/* Check whether user is authorized to change scores */
if ($is_admin) {
    $fi = strtolower(substr($_POST['fname'], 0, 1));
    $si = strtolower(substr($_POST['sname'], 0, 1));

    if ($_POST['autouname'] == "Y") {
        $num = 1;
        $query = "SELECT Username FROM user WHERE Username REGEXP '{$fi}{$si}.*' ORDER BY Username";
        while(true) {
            $res = & $db->query($query);

            $found = false;
            while ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
                if(intval(substr($row['Username'], 2)) == $num) {
                    $found = true;
                    $num += 1;
                    break;
                }
            }
            if(!$found) {
                break;
            }
        }

        $_POST['uname'] = sprintf("{$fi}{$si}%04d", $num);
        echo "</p>\n      <p>{$_POST['fname']}'s username is {$_POST['uname']}.</p>\n      <p>";
    }


    /* Check whether a user already exists with new username */
    $res = & $db->query(
                    "SELECT Username FROM user WHERE Username = '{$_POST['uname']}'");
    if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        echo "</p>\n      <p>There is already a user with that username.  " .
             "Press \"Back\" to fix the problem.</p>\n      <p>";
        $error = true;
    } else {
        /* Add new user */
        if(isset($_POST['password']) and strlen($_POST['password']) > 0 and $_POST['password'] != "!!") {
            if($_POST['password'] == 'username')
                $_POST['password'] = $_POST['uname'];
            $passwd = "'" . safe($_POST['password']) . "'";
            $origpwd = $_POST['password'];
        } else {
            $passwd = "NULL";
            $origpwd = "!!";
        }

        $query = "INSERT INTO user (Username, FirstName, Surname, placeofbirth, Gender, DOB, House, OriginalPassword, " .
                 "                  Permissions, Title, DateType, DateSeparator, " .
                 "                  DepartmentIndex) " .
                 "VALUES ('{$_POST['uname']}', '{$_POST['fname']}', '{$_POST['sname']}', '{$_POST['placeofbirth']}', " .
                 "        '{$_POST['gender']}', {$_POST['DOB']}, {$_POST['house']}, " .
                 "        $passwd, " .
                 "        {$_POST['perms']}, {$_POST['title']}, " .
                 "        {$_POST['datetype']}, {$_POST['datesep']}, " .
                 "        {$_POST['department']})";
        $aRes = & $db->query($query);
        if (DB::isError($aRes))
            die($aRes->getDebugInfo()); // Check for errors in query
        $uname = $_POST['uname'];
        add_user($uname, $_POST['fname'], $_POST['sname'], $origpwd);

        if(!isset($_POST['show_family']) || $_POST['show_family'] != '1') {
            /* Remove any family codes we've been removed from */
            $query = "SELECT FamilyListIndex, FamilyCode FROM familylist WHERE Username='{$_POST['uname']}'";
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
        $query = "SELECT GroupMemberIndex, GroupID FROM groupmem WHERE Member='$uname'";
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

        /* Add to any classes */
        if($_POST['classtermindex'] != "NULL") {
            $query =    "INSERT INTO classlist (Username, ClassTermIndex) VALUES ('$uname', {$_POST['classtermindex']})";
            $aRes = & $db->query($query);
            if (DB::isError($aRes))
                die($aRes->getDebugInfo()); // Check for errors in query
        }

        log_event($LOG_LEVEL_ADMIN, "admin/user/new_action.php", $LOG_ADMIN,
            "Added {$_POST['fname']} {$_POST['sname']} ($uname).");
    }
} else { // User isn't authorized to view or change scores.
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "admin/user/new_action.php", $LOG_DENIED_ACCESS,
            "Attempted to create user $fullname.");
    echo "</p>\n      <p>You do not have permission to add a user.</p>\n      <p>";
    $error = true;
}
