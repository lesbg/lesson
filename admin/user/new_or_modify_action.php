<?php
/**
 * ***************************************************************
 * admin/user/new_or_modify_action.php (c) 2005, 2015-2016 Jonathan Dieter
 *
 * Show common page information for changing or adding a new user
 * and call appropriate second page.
 * ***************************************************************
 */

/* Get variables */
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page

if (!$is_admin) {
    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    include "footer.php";
    exit(0);
}

/* Check which button was pressed */
if ($_POST["action"] == "Test") {
    include "admin/user/new.php";
    exit(0);
} elseif($_POST["action"] == "+") {
    include "admin/user/choose_family.php";
    exit(0);
}

foreach($_POST as $key => $value) {
    if(substr($key, 0, 7) == "action-") {
        $fremove = safe(substr($key, 7));
        if(strlen($fremove) > 0 && $value="-") {
            include "admin/user/remove_family.php";
            exit(0);
        }
    } elseif(substr($key, 0, 12) == "phoneaction-" || $key == "phoneaction") {
        include "admin/user/phone_action.php";
        exit(0);
        $fremove = safe(substr($key, 12));
        if(strlen($fremove) > 0 && $value="-") {
            include "admin/user/phone_action.php";
            exit(0);
        }
    }
}

if (isset($_POST['newpass']) and !is_null($_POST['newpass'])) {
    include "user/wordlist.php";

    $change_pwd = True;

    $origpwd = "^^^^^^^^^^^^^^^^^^^^^^^^^^^";
    if($_POST['newpass'] == "Set password to username") {
        if(isset($_GET['key'])) {
            $origpwd = dbfuncInt2String($_GET['key']);
        } else {
            $origpwd = "username";
        }
    } elseif ($_POST['newpass'] == "Disable user login") {
        $origpwd = "!!";
    } else {
        while(strlen($origpwd) > 13) {
            $origpwd = generate_password(2, $words);
        }
    }

    if(isset($_GET['key'])) {
        $uname = safe(dbfuncInt2String($_GET['key']));
        if($origpwd != "!!") {
            $phash = password_hash($origpwd, PASSWORD_DEFAULT, []);
        } else {
            $phash = "!!";
        }

        $query = "UPDATE user SET Password='$phash', OriginalPassword='$origpwd' WHERE Username='$uname'";
        $aRes = & $db->query($query);
        if (DB::isError($aRes))
            die($aRes->getDebugInfo()); // Check for errors in query
    }

    include "admin/user/modify.php";
    exit(0);
}

if ($_POST["action"] == "Save" || $_POST["action"] == "Update") { // If update or save were pressed, print
    $title = "LESSON - Saving changes..."; // common info and go to the appropriate page.
    $noHeaderLinks = true;
    $noJS = true;

    include "header.php"; // Print header

    $error = false;

    if (! isset($_POST['department']))
        $_POST['department'] = "NULL";
    if ($_POST['department'] != "NULL")
        $_POST['department'] = intval($_POST['department']);

    if (! isset($_POST['classtermindex']) || is_null($_POST['classtermindex']))
        $_POST['classtermindex'] = "NULL";
    if ($_POST['classtermindex'] != "NULL")
        $_POST['classtermindex'] = intval($_POST['classtermindex']);

    if (! isset($_POST['oldclasstermindex']) || is_null($_POST['oldclasstermindex']))
        $_POST['oldclasstermindex'] = "NULL";
    if ($_POST['oldclasstermindex'] != "NULL")
        $_POST['oldclasstermindex'] = intval($_POST['oldclasstermindex']);

    $_POST['uname'] = trim($_POST['uname']);
    if ((! isset($_POST['uname']) or $_POST['uname'] == "") and
         $_POST["action"] == "Save" and
         (! isset($_POST['autouname']) or $_POST['autouname'] == "N")) { // Make sure a username was written.
        echo "<p>You need to write a username.  Press \"Back\" to fix this.</p>\n";
        $error = true;
    } else {
        $_POST['uname'] = safe($_POST['uname']);
    }

    $_POST['fname'] = trim($_POST['fname']);
    if (! isset($_POST['fname']) || $_POST['fname'] == "") { // Make sure a first name was written.
        echo "<p>You need to write a first name.  Press \"Back\" to fix this.</p>\n";
        $error = true;
    } else {
        $_POST['fname'] = safe($_POST['fname']);
    }

    $_POST['sname'] = trim($_POST['sname']);
    if (! isset($_POST['sname']) || $_POST['sname'] == "") { // Make sure a surname was written.
        echo "<p>You need to write a first name.  Press \"Back\" to fix this.</p>\n";
        $error = true;
    } else {
        $_POST['sname'] = safe($_POST['sname']);
    }

    if (isset($_POST['phone']) && count($_POST['phone']) > 0) {
        foreach($_POST['phone'] as $i => $phone) {
            $_POST['phone'][$i][0] = intval($phone[0]);
            if($phone[1] == "") {
                if($phone[0] > -1)
                    $_POST['phone_remove'][] = $phone[0];
                unset($_POST['phone'][$i]);
                continue;
            }
            preg_match("/(^|[^\d])\+961[\s-\/]*(\d[\s-\/]*){7}(\d)?($|[^\d])/", $phone[1], $matches);
            if(count($matches) > 0) {
                $phone[1] = preg_replace('/\+961[\s-\/]*(\d)[\s-\/]*(\d)[\s-\/]*(\d)[\s-\/]*(\d)[\s-\/]*(\d)[\s-\/]*(\d)[\s-\/]*(\d)[\s-\/]*(\d)?/', "$1$2$3$4$5$6$7$8", $phone[1]);
                $phone[1] = preg_replace('/0?(\d+)(\d{3})(\d{3})/', "\+961 $1 $2 $3", $phone[1]);
            } else {
                preg_match("/(^|[^\d])(\d[\s-\/]*){8}($|[^\d])/", $phone[1], $matches);
                if(count($matches) > 0) {
                    $phone[1] = preg_replace('/(\d)[\s-\/]*(\d)[\s-\/]*(\d)[\s-\/]*(\d)[\s-\/]*(\d)[\s-\/]*(\d)[\s-\/]*(\d)[\s-\/]*(\d)/', "$1$2$3$4$5$6$7$8", $phone[1]);
                    $phone[1] = preg_replace('/0?(\d+)(\d{3})(\d{3})/', "\+961 $1 $2 $3", $phone[1]);
                }
            }
            $_POST['phone'][$i][1] = safe($phone[1]);
            $_POST['phone'][$i][2] = intval($phone[2]);
            $_POST['phone'][$i][3] = safe($phone[3]);
        }
    } else {
        $_POST['phone'] = array();
    }

    if (isset($_POST['phone_remove']) && count($_POST['phone_remove']) > 0) {
        foreach($_POST['phone_remove'] as $i => $phone) {
            $_POST['phone_remove'][$i]= intval($phone);
        }
    } else {
        $_POST['phone_remove'] = array();
    }

    if (isset($_POST['fcode']) && count($_POST['fcode']) > 0) {
        foreach($_POST['fcode'] as $i => $fcode) {
            $_POST['fcode'][$i][0] = safe($fcode[0]);
            if($fcode[1] === "on" || intval($fcode[1]) === 1) {
                $_POST['fcode'][$i][1] = 1;
            } else {
                $_POST['fcode'][$i][1] = 0;
            }
            $query = "SELECT FamilyCode FROM family WHERE FamilyCode='{$_POST['fcode'][$i][0]}'";
            $res = & $db->query($query);
            if (DB::isError($res))
                die($res->getDebugInfo()); // Check for errors in query
            if($res->numRows() == 0) {
                echo "<p>Invalid family code {$_POST['fcode'][$i][0]}).  Press \"Back\" to fix this.</p>\n";
                $error = true;
            }
        }
    } else {
        $_POST['fcode'] = array();
    }

    if (isset($_POST['groups']) && count($_POST['groups']) > 0) {
        foreach($_POST['groups'] as $i => $fcode) {
            $_POST['groups'][$i] = safe($fcode);
            $query = "SELECT GroupID FROM groups WHERE GroupID='{$_POST['groups'][$i]}'";
            $res = & $db->query($query);
            if (DB::isError($res))
                die($res->getDebugInfo()); // Check for errors in query
            if($res->numRows() == 0) {
                echo "<p>Invalid group id {$_POST['groups'][$i]}).  Press \"Back\" to fix this.</p>\n";
                $error = true;
            }
        }
    } else {
        $_POST['groups'] = array();
    }

    if (! $error) {
        echo "      <p align=\"center\">Saving changes...";

        if (! isset($_POST['perms']) || $_POST['perms'] == "") { // Make sure permissions are in correct format.
            $_POST['perms'] = "0";
        }

        if (! isset($_POST['DOB']) || $_POST['DOB'] == "") { // Make sure DOB is in correct format.
            $_POST['DOB'] = "NULL";
        } else {
            $tmpDate = & dbfuncCreateDate($_POST['DOB']);
            $_POST['DOB'] = "'" . $tmpDate . "'";
        }

        if (! isset($_POST['title']) || $_POST['title'] == "") { // Make sure title is in correct format.
            $_POST['title'] = "NULL";
        } else {
            $_POST['title'] = "'" . $_POST['title'] . "'";
        }

        if ($_POST['datetype'] == "D") // Take care of date type.
            $_POST['datetype'] = "NULL";

        if ($_POST['datesep'] == "D") { // Take care of date separator.
            $_POST['datesep'] = "NULL";
        } else {
            $_POST['datesep'] = "'" . $_POST['datesep'] . "'";
        }

        if ($_POST["action"] == "Save") { // Create new user if "Save" was pressed
            include "admin/user/new_action.php";
        } else {
            include "admin/user/modify_action.php"; // Modify user if "Update" was pressed
        }

        if ($error) { // If we ran into any errors, print failed, otherwise print done
            echo "failed!</p>\n";
        } else {
            echo "done.</p>\n";
        }

        echo "      <p align=\"center\"><a href=\"$nextLink\">Continue</a></p>\n"; // Link to next page
    }

    include "footer.php";
} elseif ($_POST["action"] == 'Delete') { // If delete was pressed, confirm deletion
    include "admin/user/delete_confirm.php";
} else {
    $extraMeta = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";
    $noJS = true;
    $noHeaderLinks = true;
    $title = "LESSON - Cancelling...";

    include "header.php";

    echo "      <p align=\"center\">Cancelling and redirecting you to <a href=\"$nextLink\">$nextLink</a>." .
         "</p>\n";

    include "footer.php";
}
?>
