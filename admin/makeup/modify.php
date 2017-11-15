<?php
/**
 * ***************************************************************
 * admin/makeup/modify.php (c) 2016-2017 Jonathan Dieter
 *
 * Show fields to fill in for a makeup
 * ***************************************************************
 */

if(isset($_GET['next'])) {
    $backLink = dbfuncInt2String($_GET['next']);
}

$link = "index.php?location=" .
        dbfuncString2Int("admin/makeup/modify_action.php") . "&amp;next=" .
        dbfuncString2Int($backLink);

/* Get variables */
if(isset($_GET['key'])) {
    $title = "Modify Makeup on " . htmlspecialchars(dbfuncInt2String($_GET['keyname']), ENT_QUOTES);
    $makeup_index = intval(dbfuncInt2String($_GET['key']));
    $modify = True;
    $link .= "&amp;key="     . $_GET['key'] .
             "&amp;keyname=" . $_GET['keyname'];
} else {
    $title = "Create new Makeup";
    if(isset($_GET['keyname'])) {
        $new_date = dbfuncInt2String($_GET['keyname']);
    }
    $modify = False;
}

if (!isset($check_all))
    $check_all = False;
if (!isset($uncheck_all))
    $uncheck_all = False;

include "core/settermandyear.php";

include "header.php"; // Show header

if (!$is_admin) {
    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    include "footer.php";

    unset($_SESSION['post']);

    exit(0);
}

if(!isset($_POST)) {
    unset($_SESSION['makeup_assignment']);
}

if(!isset($_SESSION['post'])) {
    $_SESSION['post'] = array();
}

$pval = array();
foreach($_POST as $key => $value) {
    $_SESSION['post'][$key] = $value;
}

if($modify) {
    $query =    "SELECT OpenDate, CloseDate, MakeupDate, MandatoryLower, OptionalLower " .
                "FROM makeup " .
                "WHERE MakeupIndex = '$makeup_index' ";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        if(!isset($_SESSION['post']['open_date']))
            $_SESSION['post']['open_date'] = date($dateformat, strtotime($row['OpenDate']));
        if(!isset($_SESSION['post']['close_date']))
            $_SESSION['post']['close_date'] = date($dateformat, strtotime($row['CloseDate']));
        if(!isset($_SESSION['post']['makeup_date']))
            $_SESSION['post']['makeup_date'] = date($dateformat, strtotime($row['MakeupDate']));
        if(!isset($_SESSION['post']['mandatory_lower']))
            $_SESSION['post']['mandatory_lower'] = $row['MandatoryLower'];
        if(!isset($_SESSION['post']['optional_lower']))
            $_SESSION['post']['optional_lower'] = $row['OptionalLower'];
    }

    if(!isset($_SESSION['makeup_assignment'])) {
        $_SESSION['makeup_assignment'] = array();

        $query = "SELECT AssignmentIndex FROM makeup_assignment WHERE MakeupIndex = '$makeup_index'";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query

        while ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $_SESSION['makeup_assignment'][] = $row['AssignmentIndex'];
        }
    }
} else {
    if(!isset($_SESSION['post']['open_date']))
        $_SESSION['post']['open_date'] = date($dateformat);
}

foreach($_SESSION['post'] as $key => $value) {
    if(is_string($value))
        $pval[$key] = "value='" . htmlspecialchars($value, ENT_QUOTES) . "'";
}

echo "      <form action='$link' method='post'>\n"; // Form method
if(isset($error_list)) {
    foreach($error_list as $error) {
        echo "         <p align='center' class='error'>$error</p>\n";
    }
}
echo "         <table class='transparent' align='center'>\n";
echo "            <tr>\n";
echo "               <td colspan='1'><b>Registration open date:</b></td>\n";
echo "               <td colspan='2'><input type='text' name='open_date' size=35 {$pval['open_date']} /></td>\n";
echo "            </tr>\n";
echo "            <tr>\n";
echo "               <td colspan='1'><b>Registration closed date:</b></td>\n";
echo "               <td colspan='2'><input type='text' name='close_date' size=35 {$pval['close_date']} /></td>\n";
echo "            </tr>\n";
echo "            <tr>\n";
echo "               <td colspan='1'><b>Makeup date:</b></td>\n";
echo "               <td colspan='2'><input type='text' name='makeup_date' size=35 {$pval['makeup_date']} /></td>\n";
echo "            </tr>\n";
echo "            <tr>\n";
echo "               <td colspan='1'><b>Makeup is mandatory for any average lower than:</b></td>\n";
echo "               <td colspan='2'><input type='text' name='mandatory_lower' size=35 {$pval['mandatory_lower']} /></td>\n";
echo "            </tr>\n";
echo "            <tr>\n";
echo "               <td colspan='1'><b>Makeup is optional for any average lower than:</b></td>\n";
echo "               <td colspan='2'><input type='text' name='optional_lower' size=35 {$pval['optional_lower']} /></td>\n";
echo "            </tr>\n";
echo "            <tr>\n";

if(!isset($_SESSION['post']['category_index']) or $_SESSION['post']['category_index'] == -1) {
    // Get default category index
    $query =    "SELECT CategoryIndex FROM category INNER JOIN categorylist USING (CategoryIndex) " .
                "                                   INNER JOIN assignment USING (CategoryListIndex) " .
                "                                   INNER JOIN makeup_assignment USING (AssignmentIndex) " .
                "                                   INNER JOIN makeup USING (MakeupIndex) " .
                "WHERE makeup.Username='$username' " .
                "ORDER BY makeup.MakeupDate DESC " .
                "LIMIT 1";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    if($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $category_index = $row['CategoryIndex'];
    } else {
        $category_index = -1;
    }
} else {
    $category_index = $_SESSION['post']['category_index'];
}

$query =    "SELECT CategoryIndex, CategoryName FROM category INNER JOIN categorylist USING (CategoryIndex) " .
            "                                   INNER JOIN assignment USING (CategoryListIndex) " .
            "                                   INNER JOIN subject ON subject.SubjectIndex=assignment.SubjectIndex " .
            "WHERE subject.YearIndex = $yearindex " .
            "AND   subject.TermIndex = $termindex " .
            "GROUP BY category.CategoryIndex " .
            "ORDER BY category.CategoryName";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

echo "               <td><b>Filter assignments by category:</b></td>\n";
echo "               <td>\n";
echo "                  <select name='category_index'>\n";
echo "                     <option value='-1'>Select a category</option>\n";
while($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    if($row['CategoryIndex'] == $category_index) {
        $selected = "selected";
    } else {
        $selected = "";
    }
    echo "                     <option value='{$row['CategoryIndex']}' $selected>{$row['CategoryName']}</option>\n";
}
echo "                  </select>\n";
echo "                  <input type='submit' name='action' value='Update filters'>&nbsp; \n";
echo "                  <input type='submit' name='action' value='Select all'>&nbsp; \n";
echo "                  <input type='submit' name='action' value='Deselect all'>&nbsp; \n";
echo "               </td>\n";
echo "            </tr>\n";
if($category_index > 0) {
    echo "            <tr>\n";
    echo "               <td colspan='3'>\n";
    echo "                  <table align='center' border='1'>\n";
    echo "                     <tr>\n";
    echo "                        <th>&nbsp;</th>\n";
    echo "                        <th>Assignment</th>\n";
    echo "                        <th>Subject</th>\n";
    echo "                        <th>Teacher</th>\n";
    echo "                        <th>Date</th>\n";
    echo "                        <th>Avg</th>\n";
    echo "                        <th><a title='Student count'>S</a></th>\n";
    echo "                        <th><a title='Mandatory makeups'>M</a></th>\n";
    echo "                        <th><a title='Optional makeups'>O</a></th>\n";
    echo "                     </tr>\n";

    $mandatory_lower = floatval($_SESSION['post']['mandatory_lower']);
    $optional_lower = floatval($_SESSION['post']['optional_lower']);

    $query =    "SELECT assignment.Title, assignment.AssignmentIndex, " .
                "       assignment.Date, ROUND(assignment.Average) AS Average, " .
                "       subject.Name AS SubjectName, " .
                "       GROUP_CONCAT(DISTINCT CONCAT(user.Title, ' ', " .
                "                           user.FirstName, ' ', " .
                "                           user.Surname, " .
                "                           ' (', user.Username, ')') SEPARATOR '<br>') AS Teacher, " .
                "       COUNT(DISTINCT subjectstudent.Username) AS StudentCount, " .
                "       COUNT(DISTINCT CASE WHEN mark.Percentage >= 0 " .
                "                            AND mark.Percentage < $mandatory_lower THEN mark.Username END) " .
                "             AS Mandatory, " .
                "       COUNT(DISTINCT CASE WHEN mark.Percentage >= $mandatory_lower " .
                "                            AND mark.Percentage  < $optional_lower THEN mark.Username END) " .
                "             AS Optional " .
                "FROM category INNER JOIN categorylist USING (CategoryIndex) " .
                "              INNER JOIN assignment USING (CategoryListIndex) " .
                "              INNER JOIN subject ON subject.SubjectIndex=assignment.SubjectIndex " .
                "              INNER JOIN subjectteacher ON subject.SubjectIndex=subjectteacher.SubjectIndex " .
                "              INNER JOIN subjectstudent ON subject.SubjectIndex=subjectstudent.SubjectIndex " .
                "              INNER JOIN user ON subjectteacher.Username = user.Username " .
                "              LEFT OUTER JOIN mark ON " .
                "                (mark.AssignmentIndex = assignment.AssignmentIndex AND " .
                "                 mark.Username=subjectstudent.Username) " .
                "WHERE subject.YearIndex = $yearindex " .
                "AND   subject.TermIndex = $termindex " .
                "AND   category.CategoryIndex = $category_index " .
                "GROUP BY assignment.AssignmentIndex " .
                "ORDER BY assignment.Date DESC, SubjectName, Title";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    $alt_count = 0;
    while($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $alt_count += 1;
        if ($alt_count % 2 == 0) {
            $alt = " class='alt'";
        } else {
            $alt = " class='std'";
        }

        $assignmentdate = date($dateformat, strtotime($row['Date']));
        if(isset($_SESSION['makeup_assignment']) and in_array($row['AssignmentIndex'], $_SESSION['makeup_assignment'])) {
            $checked = "checked";
        } else {
            $checked = "";
        }

        if($check_all)
            $checked = "checked";

        if($uncheck_all)
            $checked = "";


        $aidx = $row['AssignmentIndex'];

        if($row['Average'] < 0)
            $row['Average'] = 'N/A';

        echo "                     <tr$alt>\n";
        echo "                        <td><input type='hidden' name='hid_$aidx' value='1'/><input type='checkbox' id='cbox_$aidx' name='cbox_$aidx' $checked /></td>\n";
        echo "                        <td><label for='cbox_$aidx'>{$row['Title']}</label></td>\n";
        echo "                        <td><label for='cbox_$aidx'>{$row['SubjectName']}</label></td>\n";
        echo "                        <td><label for='cbox_$aidx'>{$row['Teacher']}</label></td>\n";
        echo "                        <td><label for='cbox_$aidx'>$assignmentdate</label></td>";
        echo "                        <td><label for='cbox_$aidx'>{$row['Average']}</label></td>\n";
        echo "                        <td><label for='cbox_$aidx'>{$row['StudentCount']}</label></td>\n";
        echo "                        <td><label for='cbox_$aidx'>{$row['Mandatory']}</label></td>\n";
        echo "                        <td><label for='cbox_$aidx'>{$row['Optional']}</label></td>\n";
        echo "                     </tr>\n";
    }
    echo "                  </table>\n";
    echo "               </td>\n";
    echo "            <tr>\n";
}
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

include "footer.php";
