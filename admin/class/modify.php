<?php
/**
 * ***************************************************************
 * admin/class/modify.php (c) 2005-2009, 2016 Jonathan Dieter
 *
 * Add or remove students from class.
 * ***************************************************************
 */

/* Get variables */
if (! isset($nextLink))
    $nextLink = $backLink;

$title = "Modify " . dbfuncInt2String($_GET['keyname']);
$classindex = dbfuncInt2String($_GET['key']);
$link = "index.php?location=" . dbfuncString2Int(
                                                "admin/class/modify_action.php") .
         "&amp;key=" . $_GET['key'] . "&amp;keyname=" . $_GET['keyname'] .
         "&amp;next=" . dbfuncString2Int($nextLink);

include "header.php"; // Show header

/* Check whether user is authorized to change class */
if (dbfuncGetPermission($permissions, $PERM_ADMIN)) {
    echo "      <form action='$link' method='post'>\n"; // Form method

    /* Find current year */
    $res = &  $db->query(
                    "SELECT YearIndex FROM class WHERE ClassIndex = $classindex");
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
    $row = & $res->fetchRow(DB_FETCHMODE_ASSOC);
    $classyearindex = $row['YearIndex'];

    /* Show list of years */
    $res = &  $db->query("SELECT YearIndex, Year FROM year ");
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
    echo "         <p align='center'>Modify year: <select name='year'>\n";
    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        echo "            <option value='{$row['YearIndex']}'";
        if ($row['YearIndex'] == $classyearindex)
            echo " selected";
        echo ">{$row['Year']}\n";
    }
    echo "         </select>&nbsp; &nbsp;<input type='submit' name='action' value='Done' \></p>\n";

    echo "         <table align='center' border='1'>\n"; // Table headers
    echo "            <tr>\n";
    echo "               <th>Students in class</th>\n";
    echo "               <th>Unassigned students</th>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";

    /* Get list of students in class and store in option list */
    echo "               <td>\n";
    echo "                  <select name='removefromclass[]' multiple size=15>\n";
    $res = &  $db->query(
                    "SELECT user.FirstName, user.Surname, user.Username FROM " .
                     "       user, classlist, classterm " .
                     "WHERE classlist.Username = user.Username " .
                     "AND   classlist.ClassTermIndex = classterm.ClassTermIndex " .
                     "AND   classterm.TermIndex = $termindex " .
                     "AND   classterm.ClassIndex = $classindex " .
                     "ORDER BY user.Username");
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        echo "                     <option value='{$row['Username']}'>{$row['Username']} - {$row['FirstName']} " .
             "{$row['Surname']}\n";
    }
    echo "                  </select>\n";
    echo "               </td>\n";

    /* Get list of active students who aren't in any classes and store in option list */
    echo "               <td>\n";
    echo "                  <select name='addtoclass[]' multiple size=15>\n";
    $res = &  $db->query(
                    "SELECT user.FirstName, user.Surname, user.Username FROM " .
                     "       user LEFT OUTER JOIN " .
                     "            (classlist INNER JOIN classterm ON" .
                     "                classlist.ClassTermIndex = classterm.ClassTermIndex AND classterm.TermIndex = $termindex " .
                     "             INNER JOIN class ON" .
                     "                classterm.ClassIndex = class.ClassIndex AND class.YearIndex = $yearindex)" .
                     "            ON user.Username = classlist.Username " .
                     "            INNER JOIN groupgenmem ON (user.Username=groupgenmem.Username) " .
                     "            INNER JOIN groups USING (GroupID) " .
                     "WHERE groups.GroupTypeID='activestudent' " .
                     "AND   groups.YearIndex=$yearindex " .
                     "AND   classlist.ClassTermIndex IS NULL " .
                     "ORDER BY user.Username");
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        echo "                     <option value='{$row['Username']}'>{$row['Username']} - {$row['FirstName']} " .
             "{$row['Surname']}\n";
    }
    echo "                  </select>\n";
    echo "               </td>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <td align='center'><input type='submit' name='action' value='>>' \></td>\n";
    echo "               <td align='center'><input type='submit' name='action' value='<<' \></td>\n";
    echo "            </tr>\n";
    echo "            </tr>\n";
    echo "            <tr>\n";
    echo "               <th>Class teacher</th>\n";
    echo "               <th>Unassigned teachers</th>\n";
    echo "            </tr>\n";
    echo "            <tr class='std'>\n";

    /* Get list of teachers in subject and store in option list */
    echo "               <td>\n";
    echo "                  <select name='removefromteacherlist' style='width: 200px;' size=10>\n";
    $res = &  $db->query(
                    "SELECT user.FirstName, user.Surname, user.Username FROM " .
                     "       user, class " .
                     "WHERE class.ClassTeacherUsername = user.Username " .
                     "AND   class.ClassIndex = $classindex");
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
    if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        echo "                     <option value='{$row['Username']}'>{$row['Username']} - {$row['FirstName']} " .
             "{$row['Surname']}\n";
    }
    echo "                  </select>\n";
    echo "               </td>\n";

    /* Get list of unassigned teachers */
    echo "               <td>\n";
    echo "                  <select name='addtoteacherlist' style='width: 200px;' size=10>\n";

    $query = "SELECT user.FirstName, user.Surname, user.Username FROM " .
             "       user INNER JOIN groupgenmem ON (user.Username=groupgenmem.Username) " .
             "            INNER JOIN groups USING (GroupID) " .
             "            LEFT OUTER JOIN class ON user.Username=class.ClassTeacherUsername AND class.ClassIndex=$classindex " .
             "WHERE user.DepartmentIndex = $depindex " .
             "AND   groups.GroupTypeID='activeteacher' " .
             "AND   groups.YearIndex=$yearindex " .
             "AND   class.ClassTeacherUsername IS NULL " .
             "ORDER BY user.Username";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        echo "                     <option value='{$row['Username']}'>{$row['Username']} - {$row['FirstName']} " .
             "{$row['Surname']}\n";
    }
    echo "                  </select><br>\n";
    echo "               </td>\n";
    echo "            </tr>\n";
    echo "            <tr class='alt'>\n";
    echo "               <td align='center'><input type='submit' name='actiont' value='>>' \></td>\n";
    echo "               <td align='center'><input type='submit' name='actiont' value='<<' \></td>\n";
    echo "            </tr>\n";
    echo "         </table>\n"; // End of table
    echo "         <p></p>\n";
    echo "      </form>\n";
} else { // User isn't authorized to view or change scores.
    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
?>
