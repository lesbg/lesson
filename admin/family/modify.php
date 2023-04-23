<?php
/**
 * ***************************************************************
 * admin/family/modify.php (c) 2015-2017, 2019 Jonathan Dieter
 *
 * Show fields to fill in for changing a family's information
 * ***************************************************************
 */

/* Get variables */
if(isset($_GET['next'])) {
    $backLink = dbfuncInt2String($_GET['next']);
}

if(isset($_GET['key'])) {
    $fcodem = safe(dbfuncInt2String($_GET['key']));
    $fcode = htmlspecialchars(dbfuncInt2String($_GET['key']), ENT_QUOTES);
    $title = "Modify " . htmlspecialchars(dbfuncInt2String($_GET['keyname']), ENT_QUOTES) . " family ($fcode)";
    $modify = True;
    $link = "index.php?location=" .
            dbfuncString2Int("admin/family/new_or_modify_action.php") . "&amp;key=" .
            $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] . "&amp;next=" .
            dbfuncString2Int($backLink);
} else {
    $title = "Create New Family";
    $modify = False;
    if(isset($_GET['keyname'])) {
        $new_sname = dbfuncInt2String($_GET['keyname']);
    }
    $link = "index.php?location=" .
            dbfuncString2Int("admin/family/new_or_modify_action.php") . "&amp;next=" .
            dbfuncString2Int($backLink);
}

include "header.php"; // Show header

if ($is_admin) {
    if(!isset($_SESSION['post_family'])) {
        $_SESSION['post_family'] = array();
    }
    foreach($_POST as $key => $value) {
        $_SESSION['post_family'][$key] = $value;
    }

    if(isset($_GET['key2']) && dbfuncInt2String($_GET['key2']) == '1') {
        $_SESSION['post_family']['show_users'] = '1';
        $show_users = False;
    } else {
        if(isset($_SESSION['post_family']['show_users']) && $_SESSION['post_family']['show_users'] == '1') {
            $show_users = False;
        } else {
            $_SESSION['post_family']['show_users'] = '0';
            $show_users = True;
        }
    }

    if ($modify) {
        $res = &  $db->query(
                        "SELECT FamilyCode, FamilyName, Town, RegistrationNumber, Address, House FROM family " .
                         "WHERE FamilyCode = '$fcodem'");
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query

        if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $_SESSION['post_family']['fcode'] = $fcodem;
            if(!isset($_SESSION['post_family']['fname'])) $_SESSION['post_family']['fname'] = $row['FamilyName'];
            if(!isset($_SESSION['post_family']['house']) and !is_null($row['House'])) {
                $_SESSION['post_family']['house'] = $row['House'];
            }
            if(!isset($_SESSION['post_family']['town']) and !is_null($row['Town'])) {
                $_SESSION['post_family']['town'] = $row['Town'];
            }
            if(!isset($_SESSION['post_family']['regnum']) and !is_null($row['RegistrationNumber'])) {
                $_SESSION['post_family']['regnum'] = $row['RegistrationNumber'];
            }
            if(!isset($_SESSION['post_family']['address']) and !is_null($row['Address'])) {
                $_SESSION['post_family']['address'] = $row['Address'];
            }

            if($show_users) {
                if(!isset($_SESSION['post_family']['uname'])) {
                    $_SESSION['post_family']['uname'] = array();

                    $query =        "SELECT user.Username, familylist.Guardian FROM familylist INNER JOIN user USING (Username) " .
                                    "          LEFT OUTER JOIN (class INNER JOIN classterm " .
                                    "               ON (class.YearIndex=$yearindex AND classterm.ClassIndex=class.ClassIndex) " .
                                    "          INNER JOIN currentterm ON classterm.TermIndex=currentterm.TermIndex " .
                                    "          INNER JOIN classlist USING (ClassTermIndex)) ON classlist.Username=user.Username " .
                                    "WHERE familylist.FamilyCode = '$fcodem' " .
                                    "GROUP by user.Username " .
                                    "ORDER BY Guardian DESC, IF(Guardian=1, user.Gender, Guardian) DESC, " .
                                    "         class.Grade DESC, user.Username";
                    $res = &  $db->query($query);
                    if (DB::isError($res))
                        die($res->getDebugInfo()); // Check for errors in query

                    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
                        $_SESSION['post_family']['uname'][] = array($row['Username'], $row['Guardian']);
                    }
                }
            }
        } else {
            echo "      <p align='center'>Error finding family code $fcode.  Have you already removed them?<p>\n";
            echo "      <p align='center'><a href='$backLink'>Click here to go back</a></p>\n";
        }
    } else {
        $fcodem = "";
        $fcode = "";
        if(isset($_SESSION['post_family']['fcode'])) {
            $fcodem = safe($_SESSION['post_family']['fcode']);
            $fcode = htmlspecialchars($_SESSION['post_family']['fcode'], ENT_QUOTES);
        }
        if(isset($new_sname)) {
            if(!isset($_SESSION['post_family']['fname'])) $_SESSION['post_family']['fname'] = $new_sname;
        }
    }

    $fname = htmlspecialchars($_SESSION['post_family']['fname'], ENT_QUOTES);
    $town = htmlspecialchars($_SESSION['post_family']['town'], ENT_QUOTES);
    if(!is_null($_SESSION['post_family']['regnum'])) {
        $regnum = intval($_SESSION['post_family']['regnum']);
    } else {
        $regnum = NULL;
    }
    $address = htmlspecialchars($_SESSION['post_family']['address'], ENT_QUOTES);

    echo "      <form action='$link' method='post'>\n"; // Form method
    echo "         <p align='center'>\n";
    if($modify) {
        echo "            <input type='submit' name='action' value='Update' />&nbsp; \n";
        echo "            <input type='submit' name='action' value='Delete' />&nbsp; \n";
    } else {
        echo "            <input type='submit' name='action' value='Save' />&nbsp; \n";
    }
    echo "            <input type='submit' name='action' value='Cancel' />&nbsp; \n";
    echo "         </p>\n";
    echo "         <table class='transparent' align='center'>\n";
    echo "            <tr>\n";
    echo "               <td colspan='3'><b>Family Code:</b></td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td colspan='3'>\n";
    if(!$modify) {
        echo "                   <input type='radio' name='autofcode' value='Y' checked>Automatic<br>\n";
        echo "                   <input type='radio' name='autofcode' value='N'><input type='text' name='fcode' value='$fcode'>\n";
    } else {
        echo "                   <input type='hidden' name='fcode' value='$fcode' />$fcode\n";
    }
    echo "                   <input type='hidden' name='show_users' value='{$_SESSION['post']['show_users']}'>\n";
    echo "               </td>\n";
    echo "            </tr>\n";
    echo "            <tr><td colspan='3'>&nbsp;</td></tr>\n";
    echo "            <tr>\n";
    echo "               <td colspan='3'><b>Surname:</b></td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td colspan='3'><input type='text' name='fname' value='$fname' size=35></td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td colspan='3'><b>Town:</b></td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td colspan='3'><input type='text' name='town' value='$town' size=35></td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td colspan='3'><b>Registration number:</b></td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td colspan='3'><input type='text' name='regnum' value='$regnum' size=35></td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td colspan='3'><b>Address:</b></td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td colspan='3'><textarea name='address' cols='35' rows='5'>$address</textarea></td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td colspan='3'><b>House:</b></td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td colspan='3'>\n";
    echo "                  <select name='house'>\n";
    echo "                     <option value='NULL'>None</option>\n";
    $houses = array("B"=>"Barouk", "C"=>"Cedars", "H"=>"Hermon", "S"=>"Sannine");
    foreach($houses as $key=>$value) {
        if(isset($_SESSION['post_family']['house']) and $_SESSION['post_family']['house'] == $key) {
            $chc = " selected";
        } else {
            $chc = "";
        }
        echo "                     <option value='$key' $chc>$value</option>\n";
    }
    echo "               </td>\n";
    echo "            </tr>\n";
    echo "            <tr><td colspan='3'>&nbsp;</td></tr>\n";
    if($show_users) {
        echo "            <tr>\n";
        echo "               <td><b>Members</b></td>\n";
        echo "               <td><b>Guardian</b></td>\n";
        echo "               <td>&nbsp;</td>\n";
        echo "            </tr>\n";
        $count = 0;
        if(isset($_SESSION['post_family']['uname'])) {
            foreach($_SESSION['post_family']['uname'] as $key => $user) {
                $uname = $user[0];
                $guardian = $user[1];
                $unamem = safe($uname);
                $uname = htmlspecialchars($uname, ENT_QUOTES);

                $query =    "SELECT Title, FirstName, Surname, ClassName FROM " .
                            "          user " .
                            "          LEFT OUTER JOIN (class INNER JOIN classterm " .
                            "               ON (class.YearIndex=$yearindex AND classterm.ClassIndex=class.ClassIndex) " .
                            "          INNER JOIN currentterm ON classterm.TermIndex=currentterm.TermIndex " .
                            "          INNER JOIN classlist USING (ClassTermIndex)) ON classlist.Username=user.Username " .
                            "WHERE user.Username = '$unamem' ";

                $res = &  $db->query($query);
                if (DB::isError($res))
                    die($res->getDebugInfo()); // Check for errors in query

                if ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
                    $firstname = htmlspecialchars($row['FirstName'], ENT_QUOTES);
                    $surname = htmlspecialchars($row['Surname'], ENT_QUOTES);
                    $title = htmlspecialchars($row['Title'], ENT_QUOTES);
                    $class = htmlspecialchars($row['ClassName'], ENT_QUOTES);

                    $checked = "";
                    if($guardian == 1) {
                        $name = "<em>$title $firstname $surname ($uname)</em>";
                        $checked = " checked";
                    } elseif(is_null($row['ClassName'])) {
                        $name = "$firstname $surname ($uname)";
                    } else {
                        $name = "<strong>$firstname $surname ($uname) - $class</strong>";
                    }
                    echo "            <tr>\n";
                    echo "               <td><input type='hidden' name='uname[$count][0]' value='$uname'>$name</td>\n";
                    echo "               <td><input type='checkbox' name='uname[$count][1]' $checked>\n";
                    echo "               <td><input type='submit' name='action-$uname' value='-' /><td>\n";
                    echo "            </tr>\n";
                    $count += 1;
                } else {
                    unset($_SESSION['post_family']['uname'][$key]);
                    continue;
                }
            }
            echo "            <tr>\n";
            echo "               <td colspan='2'>&nbsp;\n";
            if(isset($_SESSION['post_family']['remove_uname'])) {
                $count = 0;
                foreach($_SESSION['post_family']['remove_uname'] as $key => $uname) {
                    $uname = htmlspecialchars($uname, ENT_QUOTES);
                    echo "                  <input type='hidden' name='remove_uname[$count]' value='$uname' />\n";
                    $count += 1;
                }
            }
            echo "               </td>\n";
        }
        echo "               <td><input type='submit' name='action' value='+' /><td>\n";
        echo "            </tr>\n";
    }
    echo "         </table>\n";
    echo "         <p></p>\n";

    echo "         <p align='center'>\n";
    if($modify) {
        echo "            <input type='submit' name='action' value='Update' />&nbsp; \n";
        echo "            <input type='submit' name='action' value='Delete' />&nbsp; \n";
    } else {
        echo "            <input type='submit' name='action' value='Save' />&nbsp; \n";
    }
    echo "            <input type='submit' name='action' value='Cancel' />&nbsp; \n";
    echo "         </p>\n";
    echo "      </form>";
    unset($_SESSION['post_family']);
} else { // User isn't authorized to view or change family codes.
    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
