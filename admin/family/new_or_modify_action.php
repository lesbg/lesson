<?php
/**
 * ***************************************************************
 * admin/family/new_or_modify_action.php (c) 2015-2017, 2019 Jonathan Dieter
 *
 * Show common page information for changing or adding a new
 * family code and call appropriate second page.
 * ***************************************************************
 */

/* Get variables */
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page

if (isset($_POST['uname']) && count($_POST['uname']) > 0) {
    foreach($_POST['uname'] as $i => $user) {
        if($user[1] === "on" || intval($user[1]) === 1) {
            $_POST['uname'][$i][1] = 1;
        } else {
            $_POST['uname'][$i][1] = 0;
        }
    }
}

if($_POST["action"] == "+") {
    include "admin/family/choose_user.php";
    exit(0);
}

foreach($_POST as $key => $value) {
    if(substr($key, 0, 7) == "action-") {
        $uremove = safe(substr($key, 7));
        if(strlen($uremove) > 0 && $value="-") {
            include "admin/family/remove_user.php";
            exit(0);
        }
    }
}

/* Check which button was pressed */
if ($_POST["action"] == "Save" || $_POST["action"] == "Update") { // If update or save were pressed, print
    $title = "LESSON - Saving changes..."; // common info and go to the appropriate page.
    $noHeaderLinks = true;
    $noJS = true;

    include "header.php"; // Print header

    $error = false;


    $_POST['fcode'] = trim($_POST['fcode']);
    if ((! isset($_POST['fcode']) or $_POST['fcode'] == "") and
         $_POST["action"] == "Save" and
         (! isset($_POST['autofcode']) or $_POST['autofcode'] == "N")) { // Make sure a username was written.
        echo "<p>You need to write a family code.  Press \"Back\" to fix this.</p>\n";
        $error = true;
    } else {
        $_POST['fcode'] = safe($_POST['fcode']);
    }

    $_POST['fname'] = trim($_POST['fname']);
    if (! isset($_POST['fname']) || $_POST['fname'] == "") { // Make sure a surname was written.
        echo "<p>You need to write a surname.  Press \"Back\" to fix this.</p>\n";
        $error = true;
    } else {
        $_POST['fname'] = safe($_POST['fname']);
    }

    if($_POST['house'] != "NULL") {
        $_POST['house'] = safe($_POST['house']);
        $query = "SELECT House FROM house WHERE House='{$_POST['house']}'";
        $res = & $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query

        if($res->numRows() == 0) {
            echo "<p>Invalid house selected.  Press \"Back\" to fix this.</p>\n";
            $error = true;
        } else {
            $_POST['house'] = "'{$_POST['house']}'";
        }
    }

    $_POST['town'] = trim($_POST['town']);
    if (! isset($_POST['town']) || $_POST['town'] == "") { // Set town to null if not set
        $_POST['town'] = 'NULL';
    } else {
        $_POST['town'] = safe($_POST['town']);
        $_POST['town'] = "'{$_POST['town']}'";
    }

    $_POST['regnum'] = trim($_POST['regnum']);
    if (! isset($_POST['regnum']) || $_POST['regnum'] == "") { // Set registration number to null if not set
        $_POST['regnum'] = 'NULL';
    } else {
        $_POST['regnum'] = intval($_POST['regnum']);
    }

    $_POST['address'] = trim($_POST['address']);
    if (! isset($_POST['address']) || $_POST['address'] == "") { // Set address to null if not set
        $_POST['address'] = 'NULL';
    } else {
        $_POST['address'] = safe($_POST['address']);
        $_POST['address'] = "'{$_POST['address']}'";
    }

    if (isset($_POST['remove_uname']) && count($_POST['remove_uname']) > 0) {
        foreach($_POST['remove_uname'] as $i => $uname) {
            $_POST['remove_uname'][$i]= safe($uname);
        }
    } else {
        $_POST['remove_uname'] = array();
    }

    if (isset($_POST['uname']) && count($_POST['uname']) > 0) {
        foreach($_POST['uname'] as $i => $user) {
            $uname = $user[0];
            $unamem = safe($uname);
            $query = "SELECT Username FROM user WHERE Username='$unamem'";
            $res = & $db->query($query);
            if (DB::isError($res))
                die($res->getDebugInfo()); // Check for errors in query

            if($res->numRows() == 0) {
                echo "<p>Invalid username $uname in family.  Press \"Back\" to fix this.</p>\n";
                $error = true;
            } else {
                $_POST['uname'][$i][0] = $unamem;
            }
        }
    } else {
        $_POST['uname'] = array();
    }

    if (! $error) {
        echo "      <p align=\"center\">Saving changes...";

        if ($_POST["action"] == "Save") { // Create new user if "Save" was pressed
            include "admin/family/new_action.php";
        } else {
            include "admin/family/modify_action.php"; // Modify user if "Update" was pressed
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
    include "admin/family/delete_confirm.php";
} else {
    redirect($nextLink);
}
