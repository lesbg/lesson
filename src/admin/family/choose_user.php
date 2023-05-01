<?php
/**
 * ***************************************************************
 * admin/family/choose_user.php (c) 2016 Jonathan Dieter
 *
 * Choose user to put in family
 * ***************************************************************
 */

/* Get variables */
$title = "Choose user";


if ($is_admin) {
    if(isset($_POST['fname'])) {
        $fname = $_POST['fname'];
    } else {
        if(isset($_SESSION['post_family']['fname'])) {
            $fname = $_SESSION['post_family']['fname'];
        } else {
            $fname = "";
        }
    }

    $showall = False;
    if(isset($_GET['show'])) {
        if(dbfuncInt2String($_GET['show']) == '1') {
            $showall = True;
        }
    }

    if(isset($_GET['next']) && strpos($backLink, "next=") === False) {
        $backLink .= "&amp;next=" . $_GET['next'];
    }
    $next = dbfuncString2Int($backLink);

    $link = "index.php?location=" .
            dbfuncString2Int("admin/family/choose_user_action.php") . "&amp;next=" .
            $next;

    /* Get variables */
    if(isset($_GET['key'])) {
        $link .= "&amp;key="     . $_GET['key'] .
                 "&amp;keyname=" . $_GET['keyname'];
    }

    if(isset($fname)) {
        $title = $title . " for " . htmlspecialchars($fname, ENT_QUOTES) . " family";
    }

    if(!isset($_SESSION['post_family'])) {
        $_SESSION['post_family'] = array();
    }
    foreach($_POST as $key => $value) {
        $_SESSION['post_family'][$key] = $value;
    }

    include "header.php";

    echo "      <form action='$link' method='post'>\n"; // Form method

    $newflink = "index.php?location=" .
            dbfuncString2Int("admin/user/modify.php") .
            "&amp;next=" . $next .
            "&amp;keyname=" . dbfuncString2Int($fname) .
            "&amp;type=" . dbfuncString2Int('f') .
            "&amp;key2=" . dbfuncString2Int('1');
    $newmlink = "index.php?location=" .
            dbfuncString2Int("admin/user/modify.php") .
            "&amp;next=" . $next .
            "&amp;keyname=" . dbfuncString2Int($fname) .
            "&amp;type=" . dbfuncString2Int('m') .
            "&amp;key2=" . dbfuncString2Int('1');
    $newslink = "index.php?location=" .
            dbfuncString2Int("admin/user/modify.php") .
            "&amp;next=" . $next .
            "&amp;keyname=" . dbfuncString2Int($fname) .
            "&amp;type=" . dbfuncString2Int('s') .
            "&amp;key2=" . dbfuncString2Int('1');

    $newfbutton = dbfuncGetButton($newflink, "New father", "medium", "", "Create new father");
    $newmbutton = dbfuncGetButton($newmlink, "New mother", "medium", "", "Create new mother");
    $newsbutton = dbfuncGetButton($newslink, "New student", "medium", "", "Create new student");
    echo "         <p align='center'>$newfbutton $newmbutton $newsbutton</p>\n";

    $showallbutton = dbfuncGetDisabledButton("Show all users", "medium", "");
    if(!$showall) {
        $showalllink = "index.php?location=" .
                dbfuncString2Int("admin/family/choose_user.php") .
                "&amp;next=" . $next .
                "&amp;key=" . $_GET['key'] .
                "&amp;keyname=" . $_GET['keyname'] .
                "&amp;show=" . dbfuncString2Int('1');
        $showallbutton = dbfuncGetButton($showalllink, "Show all users", "medium", "", "Show all users");
    }
    $shownofambutton = dbfuncGetDisabledButton("Show non-family users", "medium", "");
    if($showall) {
        $shownofamlink = "index.php?location=" .
                dbfuncString2Int("admin/family/choose_user.php") .
                "&amp;next=" . $next .
                "&amp;key=" . $_GET['key'] .
                "&amp;keyname=" . $_GET['keyname'] .
                "&amp;show=" . dbfuncString2Int('0');
        $shownofambutton = dbfuncGetButton($shownofamlink, "Show non-family users", "medium", "", "Show users who have no family");
    }
    echo "         <p align='center'>$shownofambutton $showallbutton</p>\n";
    echo "         <p align='center'>\n";
    echo "            <select name='uname'>\n";

    $first = True;
    $second = False;

    $fname_db_lower = safe(strtolower($fname));
    $query =        "SELECT Username, FirstName, Surname, " .
                    "GROUP_CONCAT(DISTINCT FamilyCode ORDER BY FamilyCode SEPARATOR ', ') AS FamilyCodes " .
                    "FROM user LEFT OUTER JOIN familylist USING (Username) ";
    if(!$showall) {
        $query .=   "WHERE familylist.Username IS NULL ";
    } else {
        $query .=   "WHERE 1 = 1 ";
    }
    $query .=       "AND LOWER(user.Surname) = '$fname_db_lower' " .
                    "GROUP BY user.Username " .
                    "ORDER BY Surname, FirstName, Username";
    echo $query;
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        if(is_null($row['Username'])) {
            continue;
        }

        $disabled = "";
        $family = "";
        foreach($_SESSION['post_family']['uname'] as $check_uname) {
            echo "{$check_uname[0]}, {$row['Username']}\n";
            if($row['Username'] == $check_uname[0]) {
                $disabled = " disabled";
                $family = "This family, ";
            }
        }
        if(!$first && !$second) {
            echo "               <option value='' disabled>---</option>\n";
        }
        $first = False;
        $second = True;

        $uname = htmlspecialchars($row['Username'], ENT_QUOTES);
        $firstname = htmlspecialchars($row['FirstName'], ENT_QUOTES);
        $sname = htmlspecialchars($row['Surname'], ENT_QUOTES);
        if(is_null($row['FamilyCodes'])) {
            if($family == "") {
                $fcodes = "No family";
            } else {
                $family = substr($family, 0, -2);
                $fcodes = "";
            }
        } else {
            $fcodes = htmlspecialchars($row['FamilyCodes'], ENT_QUOTES);
        }
        echo "               <option value='$uname' $disabled>$sname, $firstname ($uname) - $family$fcodes</option>\n";
    }

    $second = False;
    $fletter = strtolower(substr($fname, 0, 1));
    $query =        "SELECT Username, FirstName, Surname, " .
                    "GROUP_CONCAT(DISTINCT FamilyCode ORDER BY FamilyCode SEPARATOR ', ') AS FamilyCodes " .
                    "FROM user LEFT OUTER JOIN familylist USING (Username) ";
    if(!$showall) {
        $query .=   "WHERE familylist.Username IS NULL ";
    } else {
        $query .=   "WHERE 1 = 1 ";
    }
    $query .=       "AND LOWER(LEFT(user.Surname, 1)) = '$fletter' " .
                    "GROUP BY user.Username " .
                    "ORDER BY Surname, FirstName, Username";
    echo $query;
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        if(is_null($row['Username'])) {
            continue;
        }

        if(!$first && !$second) {
            echo "               <option value='' disabled>---</option>\n";
        }
        $first = False;
        $second = True;

        $uname = htmlspecialchars($row['Username'], ENT_QUOTES);
        $firstname = htmlspecialchars($row['FirstName'], ENT_QUOTES);
        $sname = htmlspecialchars($row['Surname'], ENT_QUOTES);
        if(is_null($row['FamilyCodes'])) {
            $fcodes = "No family";
        } else {
            $fcodes = htmlspecialchars($row['FamilyCodes'], ENT_QUOTES);
        }
        echo "               <option value='$uname'>$sname, $firstname ($uname) - $fcodes</option>\n";
    }

    $second = False;
    $query =        "SELECT Username, FirstName, Surname, " .
                    "GROUP_CONCAT(DISTINCT FamilyCode ORDER BY FamilyCode SEPARATOR ', ') AS FamilyCodes " .
                    "FROM user LEFT OUTER JOIN familylist USING (Username) ";
    if(!$showall) {
        $query .=   "WHERE familylist.Username IS NULL ";
    } else {
        $query .=   "WHERE 1 = 1 ";
    }
    $query .=       "GROUP BY user.Username " .
                    "ORDER BY Surname, FirstName, Username";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        if(is_null($row['Username'])) {
            continue;
        }

        if(!$first && !$second) {
            echo "               <option value='' disabled>---</option>\n";
        }
        $first = False;
        $second = True;

        $uname = htmlspecialchars($row['Username'], ENT_QUOTES);
        $firstname = htmlspecialchars($row['FirstName'], ENT_QUOTES);
        $sname = htmlspecialchars($row['Surname'], ENT_QUOTES);
        if(is_null($row['FamilyCodes'])) {
            $fcodes = "No family";
        } else {
            $fcodes = htmlspecialchars($row['FamilyCodes'], ENT_QUOTES);
        }
        echo "               <option value='$uname'>$sname, $firstname ($uname) - $fcodes</option>\n";
    }
    echo "            </select>\n";
    echo "         </p>\n";
    echo "         <p align='center'>\n";
    echo "            <input type='submit' name='action' value='Add'>&nbsp;\n";
    echo "            <input type='submit' name='action' value='Cancel'>\n";
    echo "         </p>\n";
    echo "      </form>\n";

    include "footer.php";
} else { // User isn't authorized to view or change users.
    include "header.php"; // Show header
    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    include "footer.php";
}
