<?php
/**
 * ***************************************************************
 * admin/user/modify.php (c) 2005, 2015-2016 Jonathan Dieter
 *
 * Show fields to fill in for a user
 * ***************************************************************
 */

include "user/wordlist.php";

if(isset($_GET['next'])) {
    $backLink = dbfuncInt2String($_GET['next']);
}

$link = "index.php?location=" .
        dbfuncString2Int("admin/user/new_or_modify_action.php") . "&amp;next=" .
        dbfuncString2Int($backLink);

/* Get variables */
if(isset($_GET['key'])) {
    $title = "Modify " . htmlspecialchars(dbfuncInt2String($_GET['keyname']), ENT_QUOTES);
    $uname = safe(dbfuncInt2String($_GET['key']));
    $modify = True;
    $link .= "&amp;key="     . $_GET['key'] .
             "&amp;keyname=" . $_GET['keyname'];
} else {
    $title = "Create New User";
    if(isset($_GET['keyname'])) {
        $new_fname = dbfuncInt2String($_GET['keyname']);
    }
    $modify = False;
}

if(isset($_GET['key4']))
    $fcode = safe(dbfuncInt2String($_GET['key4']));
else
    unset($fcode);

include "core/settermandyear.php";

$switchlink = "index.php?location=" .
        dbfuncString2Int("admin/user/switch_user.php") . "&amp;key=" .
        dbfuncString2Int($uname);
$subtitle = "<a href='$switchlink'>Become this user</a>";
include "header.php"; // Show header

if ($is_admin) {

    echo "   <script>\n";
    echo "      var words = [";
    $first = true;
    foreach($words as $word) {
        if(!$first)
            echo ",";
        else
            $first = false;
        echo "'$word'";
    }
    echo "]\n";
    echo "   </script>\n";
    if(!isset($_SESSION['post'])) {
        $_SESSION['post'] = array();
    }
    $pval = array();
    foreach($_POST as $key => $value) {
        $_SESSION['post'][$key] = $value;
    }

    if(isset($_GET['key2']) && dbfuncInt2String($_GET['key2']) == '1') {
        $_SESSION['post']['show_family'] = '1';
        $show_family = False;
    } else {
        if(isset($_SESSION['post']['show_family']) && $_SESSION['post']['show_family'] == '1') {
            $show_family = False;
        } else {
            $_SESSION['post']['show_family'] = '0';
            $show_family = True;
        }
    }
    if(isset($_GET['type'])) {
        $_SESSION['post']['new_user_type'] = dbfuncInt2String($_GET['type']);
        $new_user_type = $_SESSION['post']['new_user_type'];
    } else {
        if(isset($_SESSION['post']['new_user_type'])) {
            $new_user_type = $_SESSION['post']['new_user_type'];
        } else {
            $new_user_type = 'a';
        }
    }

    $pwd2 = NULL;
    if(!isset($change_pwd))
        $change_pwd = False;
    if(!isset($disable_pwd))
        $disable_pwd = False;
    if(!isset($origpwd))
        $origpwd = NULL;

    if($modify) {
        $query =    "SELECT Username, FirstName, Surname, Gender, DOB, Permissions, user.DepartmentIndex, " .
                    "       Title, DateType, DateSeparator, Password, OriginalPassword, PhoneNumber " .
                    "FROM user " .
                    "WHERE Username = '$uname' ";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query

        if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $_SESSION['post']['uname'] = $uname;
            if(!isset($_SESSION['post']['fname'])) $_SESSION['post']['fname'] = $row['FirstName'];
            if(!isset($_SESSION['post']['sname'])) $_SESSION['post']['sname'] = $row['Surname'];
            if(!isset($_SESSION['post']['gender'])) $_SESSION['post']['gender'] = $row['Gender'];
            if(!isset($_SESSION['post']['DOB'])) $_SESSION['post']['DOB'] = $row['DOB'];
            if(!isset($_SESSION['post']['perms'])) $_SESSION['post']['perms'] = $row['Permissions'];
            if(!isset($_SESSION['post']['department'])) $_SESSION['post']['department'] = $row['DepartmentIndex'];
            if(!isset($_SESSION['post']['title'])) $_SESSION['post']['title'] = $row['Title'];
            if(!isset($_SESSION['post']['datetype'])) {
                if(!is_null($row['DateType']))
                    $_SESSION['post']['datetype'] = $row['DateType'];
                else
                    $_SESSION['post']['datetype'] = 'D';
            }
            if(!isset($_SESSION['post']['datesep'])) {
                if(!is_null($row['DateSeparator']))
                    $_SESSION['post']['datesep'] = $row['DateSeparator'];
                else
                    $_SESSION['post']['datesep'] = 'D';
            }
            $pwd2 = $row['Password2'];
            if(is_null($origpwd)) {
                $origpwd = $row['OriginalPassword'];
            }

            if($show_family) {
                if(!isset($_SESSION['post']['fcode'])) {
                    $_SESSION['post']['fcode'] = array();

                    $query =    "SELECT FamilyCode, Guardian FROM familylist " .
                                "WHERE Username='$uname' " .
                                "ORDER BY FamilyCode";
                    $res = &  $db->query($query);
                    if (DB::isError($res))
                        die($res->getDebugInfo()); // Check for errors in query

                    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
                        $_SESSION['post']['fcode'][] = array($row['FamilyCode'], $row['Guardian']);
                    }
                }
            }
            if(!isset($_SESSION['post']['groups'])) {
                $_SESSION['post']['groups'] = array();

                $query =    "SELECT GroupID FROM groupgenmem " .
                            "WHERE Username='$uname' " .
                            "ORDER BY GroupID";
                $res = &  $db->query($query);
                if (DB::isError($res))
                    die($res->getDebugInfo()); // Check for errors in query

                while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
                    $_SESSION['post']['groups'][] = $row['GroupID'];
                }
            }
            if(!isset($_SESSION['post']['phone'])) {
                $_SESSION['post']['phone'] = array();

                $query =    "SELECT PhoneIndex, Number, SortOrder, Type, Comment FROM phone " .
                            "WHERE Username='$uname' " .
                            "ORDER BY SortOrder";
                $res = &  $db->query($query);
                if (DB::isError($res))
                    die($res->getDebugInfo()); // Check for errors in query

                while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
                    $_SESSION['post']['phone'][] = array($row['PhoneIndex'], $row['Number'], $row['Type'], $row['Comment']);
                }
                $_SESSION['post']['phone'][] = array(-1, "", 2, "");
            }
            if(!isset($_SESSION['post']['phone_remove'])) {
                $_SESSION['post']['phone_remove'] = array();
            }
        }
    } else {
        if($new_user_type != 'a') {
            if(!isset($_SESSION['post']['sname'])) $_SESSION['post']['sname'] = $new_fname;
            if($new_user_type == 'f') {
                if(!isset($_SESSION['post']['gender'])) {
                    $_SESSION['post']['title'] = "Mr.";
                    $_SESSION['post']['gender'] = "M";
                }
            } elseif ($new_user_type == 'm') {
                if(!isset($_SESSION['post']['gender'])) {
                    $_SESSION['post']['title'] = "Mrs.";
                    $_SESSION['post']['gender'] = "F";
                }
            } elseif ($new_user_type == 's') {
                $query =    "SELECT groups.GroupID FROM " .
                            "       groups " .
                            "WHERE (groups.GroupTypeID='activestudent' OR groups.GroupTypeID='new') " .
                            "AND   groups.YearIndex=$yearindex ";
                $res = &  $db->query($query);
                if (DB::isError($res))
                    die($res->getDebugInfo()); // Check for errors in query
                while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
                    if(!isset($_SESSION['post']['groups'])) {
                        $_SESSION['post']['groups'] = array();
                    }
                    $_SESSION['post']['groups'][] = $row['GroupID'];
                }
            }
        }
        if(is_null($origpwd)) {
            if(!isset($_SESSION['post']['password'])) {
                include "user/wordlist.php";

                $change_pwd = True;
                $origpwd = "^^^^^^^^^^^^^^^^^^^^^^^^^^^";
                while(strlen($origpwd) > 13) {
                    $origpwd = generate_password(2, $words);
                }
            } else {
                $origpwd = $_SESSION['post']['password'];
            }
        }
    }
    foreach($_SESSION['post'] as $key => $value) {
        if(is_string($value))
            $pval[$key] = "value='" . htmlspecialchars($value, ENT_QUOTES) . "'";
    }

    if($_SESSION['post']['classdepindex'] == "NULL" || is_null($_SESSION['post']['classdepindex'])) {
        $classdepindex = NULL;
    } else {
        $classdepindex = intval($_SESSION['post']['classdepindex']);
    }
    echo "      <form action='$link' method='post'>\n"; // Form method
    echo "         <p align='center'>\n";
    if(!$modify) {
        echo "            <input type='submit' name='action' value='Save'>&nbsp; \n";
    } else {
        echo "            <input type='submit' name='action' value='Update'>&nbsp; \n";
        echo "            <input type='submit' name='action' value='Delete'>&nbsp; \n";
    }
    echo "            <input type='submit' name='action' value='Cancel'>&nbsp; \n";
    echo "         </p>\n";
    echo "         <table class='transparent' align='center'>\n";
    echo "            <tr>\n";
    echo "               <td colspan='1'><b>Username:</b></td>\n";
    echo "               <td colspan='2'>\n";
    if(!$modify) {
        $chcy = "";
        $chcn = "";
        if(!$modify) {
            if(isset($_SESSION['post']['autouname']) && $_SESSION['post']['autouname'] == 'N') {
                $chcn = "checked";
            } else {
                $chcy = "checked";
            }
        }
        echo "                   <input type='radio' name='autouname' value='Y' $chcy>Automatic<br>\n";
        echo "                   <input type='radio' name='autouname' value='N' $chcn><input type='text' name='uname' size=35 {$pval['uname']}>\n";
    } else {
        echo "                   <input type='hidden' name='autouname' value='N'>\n";
        echo "                   <input type='hidden' name='uname' value='$uname'>\n";
        echo "                   $uname\n";
    }
    echo "                   <input type='hidden' name='show_family' value='{$_SESSION['post']['show_family']}'>\n";
    echo "                   <input type='hidden' name='new_user_type' value='{$_SESSION['post']['new_user_type']}'>\n";
    echo "               </td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td colspan='1'><b>Title:</b></td>\n";
    echo "               <td colspan='2'><input type='text' name='title' size=35 {$pval['title']}></td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td colspan='1'><b>First Name:</b></td>\n";
    echo "               <td colspan='2'><input type='text' name='fname' size=35 {$pval['fname']}></td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td colspan='1'><b>Surname:</b></td>\n";
    echo "               <td colspan='2'><input type='text' name='sname' size=35 {$pval['sname']}></td>\n";
    echo "            </tr>\n";
    echo "            <tr><td colspan='3'>&nbsp;</td></tr>\n";
    echo "            <tr>\n";
    echo "            <td><strong>Password:</strong>";
    if(($change_pwd or isset($_SESSION['post']['password'])) and !$modify and !is_null($origpwd)) {
        echo "<input type='hidden' name='password' value='$origpwd' />";
    }
    echo "</td>\n";
    if(!is_null($origpwd)) {
        if($origpwd == "!!") {
            echo "               <td colspan='2'><span name='passwd'><em>User login disabled</em></span></td>\n";
        } elseif($origpwd == "username") {
            echo "               <td colspan='2'><span name='passwd'><em>Same as username</em></span></td>\n";
        } else {
            echo "               <td colspan='2'><span name='passwd'><h2>$origpwd</h2></span></td>\n";
        }
    } else {
        echo "               <td colspan='2'><span name='passwd'><em>Unknown because it was changed</em></span></td>\n";
    }
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td colspan='3'><input type='submit' name='newpass' value='Generate new password'> <input type='submit' name='newpass' value='Set password to username'> <input type='submit' name='newpass' value='Disable user login'></td>\n";
    echo "            </tr>\n";
    echo "            <tr><td colspan='3'>&nbsp;</td></tr>\n";
    $chcm = "";
    $chcf = "";
    if(isset($_SESSION['post']['gender']) && $_SESSION['post']['gender'] == 'F') {
        $chcf = "checked";
    } else {
        $chcm = "checked";
    }
    if(isset($_SESSION['post']['DOB']) and !is_null($_SESSION['post']['DOB'])) {
        $dob = date($dateformat, strtotime($_SESSION['post']['DOB']));
    } else {
        $dob = "";
    }
    echo "            <tr>\n";
    echo "               <td colspan='1'><b>Gender:</b><br>\n";
    echo "                   <input type='radio' name='gender' value='M' $chcm>Male<br>\n";
    echo "                   <input type='radio' name='gender' value='F' $chcf>Female</td>\n";
    echo "               <td colspan='2'><b>Date of Birth:</b><br>\n";
    echo "                   <input type='text' name='DOB' size=35 value='$dob'><br>&nbsp;</td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td><b>Phone number</b></td>\n";
    echo "               <td><b>Comment</b></td>\n";
    echo "               <td>&nbsp;</td>\n";
    echo "            </tr>\n";

    if(isset($_SESSION['post']['phone'])) {
        $phone_matrix = array(
                          array(2, 'Mobile'),
                          array(1, 'Home'),
                          array(3, 'Work'),
                          array(4, 'Other')
                        );
        foreach($_SESSION['post']['phone'] as $key => $phone) {
            $pindex = htmlspecialchars($phone[0]);
            $pnum = htmlspecialchars($phone[1]);
            $ptype = intval($phone[2]);
            $pcomment = htmlspecialchars($phone[3]);
            if(isset($fcode[1]) && ($fcode[1] === "on" || intval($fcode[1]) === 1)) {
                $guardian = "checked";
            } else {
                $guardian = "";
            }
            echo "            <tr>\n";
            echo "               <td><input type='hidden' name='phone[$key][0]' value='$pindex'><input type='text' name='phone[$key][1]' value='$pnum'></td>\n";
            echo "               <td><input type='text' name='phone[$key][3]' value='$pcomment'>&nbsp;\n";
            echo "                  <select name='phone[$key][2]'>\n";
            foreach($phone_matrix as $item) {
                if($ptype == $item[0])
                    $selected = " selected";
                else
                    $selected = "";
                echo "                     <option value='${item[0]}' $selected>${item[1]}</option>\n";
            }
            echo "                  </select>\n";
            echo "               </td>\n";
            echo "               <td><input type='submit' name='phoneaction-$pindex' value='▲' /><input type='submit' name='phoneaction-$pindex' value='▼' /><input type='submit' name='phoneaction-$pindex' value='-' /></td>\n";
            echo "            </tr>\n";
        }

    }
    echo "            <tr>\n";
    echo "               <td>&nbsp;</td>\n";
    echo "               <td>&nbsp;</td>\n";
    echo "               <td>\n";
    echo "                  <input type='submit' name='phoneaction' value='+'>\n";
    $count = 0;
    foreach($_SESSION['post']['phone_remove'] as $remove) {
        $count += 1;
        $remove = intval($remove);
        echo "                  <input type='hidden' name='phone_remove[$count]' value='$remove'>\n";
    }
    echo "               </td>\n";
    echo "            </tr>\n";

    $chcd = "";
    $chc0 = "";
    $chc1 = "";
    if(isset($_SESSION['post']['datetype']) && $_SESSION['post']['datetype'] == '0') {
        $chc0 = "checked";
    } elseif(isset($_SESSION['post']['datetype']) && $_SESSION['post']['datetype'] == '1') {
        $chc1 = "checked";
    } else {
        $chcd = "checked";
    }
    echo "            <tr>\n";
    echo "               <td colspan='1'><b>Date Type:</b><br>\n";
    echo "                   <input type='radio' name='datetype' value='D' $chcd><i>LESSON default</i><br>\n";
    echo "                   <input type='radio' name='datetype' value='0' $chc0>American<br>\n";
    echo "                   <input type='radio' name='datetype' value='1' $chc1>European<br>&nbsp;<br>&nbsp;</td>\n";
    $chcd = "";
    $chcslash = "";
    $chcdash = "";
    $chcperiod = "";
    if(isset($_SESSION['post']['datesep']) && $_SESSION['post']['datesep'] == '/') {
        $chcslash = "checked";
    } elseif(isset($_SESSION['post']['datesep']) && $_SESSION['post']['datesep'] == '-') {
        $chcdash = "checked";
    } elseif(isset($_SESSION['post']['datesep']) && $_SESSION['post']['datesep'] == '.') {
        $chcperiod = "checked";
    } else {
        $chcd = "checked";
    }
    echo "               <td colspan='1'><b>Date Separator:</b><br>\n";
    echo "                   <input type='radio' name='datesep' value='D' $chcd><i>LESSON default</i><br>\n";
    echo "                   <input type='radio' name='datesep' value='/' $chcslash>/ (ex. 1/1/2000)<br>\n";
    echo "                   <input type='radio' name='datesep' value='-' $chcdash>- (ex. 1-1-2000)<br>\n";
    echo "                   <input type='radio' name='datesep' value='.' $chcperiod>. (ex. 1.1.2000)<br>&nbsp;\n";
    echo "               </td>\n";
    echo "               <td colspan='1'><b>Groups:</b><br>\n";
    $query =    "SELECT groups.GroupID, grouptype.GroupName FROM " .
            "       groups, groupmem, grouptype " .
            "WHERE groupmem.Member=CONCAT('@', groups.GroupTypeID) " .
            "AND   groupmem.GroupID='userinfo' " .
            "AND   groups.YearIndex=$yearindex " .
            "AND   grouptype.GroupTypeID = groups.GroupTypeID " .
            "ORDER BY grouptype.GroupName, groups.YearIndex";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        $chc = "";
        if(isset($_SESSION['post']['groups']) && in_array($row['GroupID'], $_SESSION['post']['groups'])) {
            $chc = 'checked';
        }

        echo "                  <label><input type='checkbox' name='groups[]' value='{$row['GroupID']}' $chc>{$row['GroupName']}</label><br>\n";
    }
    echo "               </td>\n";
    echo "            </tr>\n";

    if($show_family) {
        $res = &  $db->query(
                "SELECT FamilyCode FROM family " .
                "ORDER BY FamilyCode");
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query

        if ($res->numRows() > 0) {
            echo "            <tr>\n";
            echo "               <td><b>Family Code</b></td>\n";
            echo "               <td><b>Guardian</b></td>\n";
            echo "               <td>&nbsp;</td>\n";
            echo "            </tr>\n";
            if(isset($_SESSION['post']['fcode'])) {
                foreach($_SESSION['post']['fcode'] as $key => $fcode) {
                    $fcodep = htmlspecialchars($fcode[0]);
                    if(isset($fcode[1]) && ($fcode[1] === "on" || intval($fcode[1]) === 1)) {
                        $guardian = "checked";
                    } else {
                        $guardian = "";
                    }
                    echo "            <tr>\n";
                    echo "               <td><input type='hidden' name='fcode[$key][0]' value='$fcodep'>$fcodep</td>\n";
                    echo "               <td><input type='checkbox' name='fcode[$key][1]' $guardian /></td>\n";
                    echo "               <td><input type='submit' name='action-$fcodep' value='-' /></td>\n";
                    echo "            </tr>\n";
                }
            }
            echo "            <tr>\n";
            echo "               <td>&nbsp;</td>\n";
            echo "               <td>&nbsp;</td>\n";
            echo "               <td><input type='submit' name='action' value='+'></td>\n";
            echo "            </tr>\n";
        }
    }

    $res = &  $db->query(
                    "SELECT Department, DepartmentIndex FROM department " .
                     "ORDER BY DepartmentIndex");
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    echo "            <tr>\n";
    if ($res->numRows() > 0) {
        echo "               <td><b>Department:</b><br>\n";
        echo "                  <select name='department'>\n";
        $chc = "";
        if(!isset($_SESSION['post']['department']) || $_SESSION['post']['department'] == 'NULL') {
            $chc = 'selected';
        }
        echo "                     <option value='NULL'>None</option>\n";
        while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
            $chc = "";
            if(isset($_SESSION['post']['department']) && $_SESSION['post']['department'] == $row['DepartmentIndex']) {
                $chc = 'selected';
            }
            echo "                     <option value='{$row['DepartmentIndex']}' $chc>{$row['Department']}</option>\n";
        }
        echo "                  </select>\n";
        echo "                  <br/>\n";
    }
    echo "               <td colspan='1'><b>Class:</b><br>\n";

    if($modify) {
        $query =    "SELECT DepartmentIndex FROM classlist INNER JOIN classterm USING (ClassTermIndex) " .
                    "                     INNER JOIN class USING (ClassIndex) " .
                    "WHERE  classlist.Username='$uname' " .
                    "AND    class.YearIndex=$yearindex " .
                    "ORDER BY classterm.TermIndex DESC " .
                    "LIMIT 1";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query
        if ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
            $classdepindex = $row['DepartmentIndex'];
        } else {
            $classdepindex = NULL;
        }
    } else {
        $classdepindex = NULL;
    }

    if($yearindex == $currentyear) {
        $query =    "SELECT class.ClassName, classterm.ClassTermIndex, term.TermIndex, term.TermName FROM " .
                    "       class, classterm, currentterm, term " .
                    "WHERE classterm.ClassIndex=class.ClassIndex " .
                    "AND   class.YearIndex=$yearindex " .
                    "AND   term.TermIndex=currentterm.TermIndex " .
                    "AND   classterm.TermIndex=currentterm.TermIndex ";
    } elseif($yearindex < $currentyear) {
        $query =    "SELECT class.ClassName, classterm.ClassTermIndex, term.TermIndex, term.TermName FROM " .
                    "       class INNER JOIN classterm USING (ClassIndex) " .
                    "             LEFT OUTER JOIN classterm AS newct ON " .
                    "                 (classterm.ClassIndex=newct.ClassIndex AND " .
                    "                  ((class.DepartmentIndex != $depindex AND classterm.TermIndex < newct.TermIndex) OR " .
                    "                   (class.DepartmentIndex = $depindex AND " .
                    "                    newct.TermIndex != $termindex AND " .
                    "                    classterm.TermIndex != $termindex))) " .
                    "             INNER JOIN term ON (classterm.TermIndex=term.TermIndex) " .
                    "WHERE class.YearIndex=$yearindex " .
                    "AND   newct.TermIndex IS NULL ";
    } else { /* $yearindex > $currentyear */
        $query =    "SELECT class.ClassName, classterm.ClassTermIndex, term.TermIndex, term.TermName FROM " .
                    "       class INNER JOIN classterm USING (ClassIndex) " .
                    "             LEFT OUTER JOIN classterm AS oldct ON " .
                    "                 (classterm.ClassIndex=oldct.ClassIndex AND " .
                    "                  ((class.DepartmentIndex != $depindex AND classterm.TermIndex > oldct.TermIndex) OR " .
                    "                   (class.DepartmentIndex = $depindex AND " .
                    "                    oldct.TermIndex != $termindex AND " .
                    "                    classterm.TermIndex != $termindex))) " .
                    "             INNER JOIN term ON (classterm.TermIndex=term.TermIndex) " .
                    "WHERE class.YearIndex=$yearindex " .
                    "AND   oldct.TermIndex IS NULL ";
    }
    if(!is_null($classdepindex)) {
        $query .=   "AND   class.DepartmentIndex=$classdepindex ";
    }
    $query .= "ORDER BY class.Grade, class.ClassName";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
    echo "                  <select name='classtermindex'>\n";
    $chc = "";
    echo "                     <option value='NULL'>None</option>\n";
    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        $chc = "";
        if(!isset($_SESSION['post']['classtermindex']) && !is_null($classdepindex)) {
            $query =    "SELECT Username FROM classlist INNER JOIN classterm USING (ClassTermIndex) INNER JOIN class USING (ClassIndex) " .
                        "WHERE classlist.Username='$uname' " .
                        "AND   class.DepartmentIndex=$classdepindex " .
                        "AND   classterm.ClassTermIndex={$row['ClassTermIndex']}";
            $nres = &  $db->query($query);
            if (DB::isError($nres))
                die($nres->getDebugInfo()); // Check for errors in query
            if ( $nres->numRows() > 0) {
                $_SESSION['post']['classtermindex'] = $row['ClassTermIndex'];
                $_SESSION['post']['oldclasstermindex'] = $row['ClassTermIndex'];
            }
        }
        if(isset($_SESSION['post']['classtermindex']) && $_SESSION['post']['classtermindex'] == $row['ClassTermIndex']) {
            $chc = 'selected';
        }
        echo "                     <option value='{$row['ClassTermIndex']}' $chc>{$row['ClassName']} - {$row['TermName']}</option>\n";
    }
    echo "                  </select>\n";
    if(!isset($_SESSION['post']['oldclasstermindex']) || is_null($_SESSION['post']['oldclasstermindex'])) {
        $oldctidx = "NULL";
    } else {
        $oldctidx = $_SESSION['post']['oldclasstermindex'];
    }
    echo "                  <input type='hidden' name='oldclasstermindex' value='$oldctidx' /><br/>\n";
    echo "               </td>\n";
    echo "            </tr>\n";


    echo "            <tr><td colspan='3'>&nbsp;</td></tr>\n";
    echo "            <tr>\n";
    echo "               <td colspan='1'><b>Permissions:</b></td>\n";
    echo "               <td colspan='2'><input type='text' name='perms' size=35 {$pval['perms']}></td>\n";
    echo "            </tr>\n";
    echo "         </table>\n";
    echo "         <p></p>\n";

    echo "         <p align='center'>\n";
    if(!$modify) {
        echo "            <input type='submit' name='action' value='Save'>&nbsp; \n";
    } else {
        echo "            <input type='submit' name='action' value='Update'>&nbsp; \n";
        echo "            <input type='submit' name='action' value='Delete'>&nbsp; \n";
    }
    echo "            <input type='submit' name='action' value='Cancel'>&nbsp; \n";
    echo "         </p>\n";
    echo "      </form>";
    unset($_SESSION['post']);
} else { // User isn't authorized to view or change scores.
    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
?>
