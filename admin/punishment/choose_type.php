<?php
/**
 * ***************************************************************
 * admin/punishment/choose_type.php (c) 2006-2016 Jonathan Dieter
 *
 * Choose punishment type
 * ***************************************************************
 */

/* Get variables */
$title = "Choose punishment type";

$query = "SELECT user.FirstName, user.Surname, user.Username FROM " .
         "       user INNER JOIN groupgenmem ON (user.Username=groupgenmem.Username) " .
         "            INNER JOIN groups USING (GroupID) " .
         "WHERE user.Username='$username' " .
         "AND   groups.GroupTypeID='activeteacher' " .
         "AND   groups.YearIndex=$yearindex " .
         "ORDER BY user.Username";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($res->numRows() > 0) {
    $is_teacher = true;
} else {
    $is_teacher = false;
}

$query = "SELECT Permissions FROM disciplineperms WHERE Username=\"$username\"";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    $perm = $row['Permissions'];
} else {
    $perm = $DEFAULT_PUN_PERM;
}

include "header.php"; // Show header

if (dbfuncGetPermission($permissions, $PERM_ADMIN) or
     ($perm >= $PUN_PERM_ALL and $is_teacher)) {
    echo "      <form action=\"$link\" method=\"post\" name=\"pundate\">\n"; // Form method
    echo "         <table border=\"0\" class=\"transparent\" align=\"center\">\n";
    echo "            <tr>\n";
    echo "               <td>\n";
    echo "                  Which punishment would you like to work with?<br>\n";
    $query = "SELECT disciplinetype.DisciplineType, disciplinetype.DisciplineTypeIndex " .
             "       FROM disciplinetype, disciplineweight " .
             "WHERE  disciplinetype.DisciplineTypeIndex = disciplineweight.DisciplineTypeIndex " .
             "AND    disciplineweight.YearIndex = $yearindex " .
             "AND    disciplineweight.TermIndex = $termindex " .
             "AND    disciplineweight.DisciplineWeight IS NOT NULL " .
             "AND    disciplinetype.PermLevel <= $perm " .
             "ORDER BY disciplinetype.DisciplineTypeIndex";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
    $count = 0;
    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        $count += 1;
        if ($count == 1) {
            $default = "checked";
        } else {
            $default = "";
        }
        echo "                  <label for=\"type{$row['DisciplineTypeIndex']}\">\n";
        $val = $row['DisciplineTypeIndex'];
        echo "                  <input type=\"radio\" name=\"type\" value=\"$val\" id=\"type{$row['DisciplineTypeIndex']}\" $default>\n";
        echo "                     {$row['DisciplineType']}\n";
        echo "                  </label><br>\n";
    }
    echo "               </td>\n";
    echo "            </tr>\n";

    echo "         </table>\n";
    echo "         <p align=\"center\">\n";
    echo "            <input type=\"submit\" name=\"action\" value=\"Continue\">&nbsp; \n";
    echo "         </p>\n";
    echo "      </form>\n";
} else { // User isn't authorized to create a punishment
    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "admin/punishment/choose_type.php",
            $LOG_DENIED_ACCESS, "Tried to set punishment date.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
}

include "footer.php";
?>
