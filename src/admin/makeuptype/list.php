<?php
/**
 * ***************************************************************
 * admin/makeuptype/list.php (c) 2004, 2005, 2017 Jonathan Dieter
 *
 * List all makeup types
 * ***************************************************************
 */
$title = "Subject Types List";

include "header.php"; // Show header

/* Check whether user is authorized to list makeup types */
if (!$is_admin) {
    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href=\"$backLink\">Click here to go back</a></p>\n";
    include "footer.php";
    exit(0);
}

/* Get makeup type list */
$query =    "SELECT MakeupTypeIndex, MakeupType, Description, OriginalMax, TargetMax FROM makeup_type " .
            "ORDER BY MakeupType";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo());

$newlink =  "index.php?location=" .
            dbfuncString2Int("admin/makeuptype/modify.php");
$newbutton = dbfuncGetButton($newlink, "New makeup type", "medium", "",
                             "Create new makeup type");
echo "      <p align=\"center\">$newbutton</p>\n";

/* If there are no makeup types, display message and finish */
if ($res->numRows() == 0) {
    echo "      <p>There are no makeup types.</p>\n";
    include "footer.php";
    exit(0);
}

echo "      <table align=\"center\" border=\"1\">\n"; // Table headers
echo "         <tr>\n";
echo "            <th>&nbsp;</th>\n";
echo "            <th>Makeup type</th>\n";
echo "            <th>Description</th>\n";
echo "            <th><a title='Maximum original score that will get full benefit of makeup'>Original Max Score</a></th>\n";
echo "            <th><a title='Target score that maximum original score that will receive if they get 100% on the makeup'>Target Max Score</a></th>\n";
echo "         </tr>\n";

/* Show makeup types */
$alt_count = 0;
while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
    $alt_count += 1;
    if ($alt_count % 2 == 0) {
        $alt = " class=\"alt\"";
    } else {
        $alt = " class=\"std\"";
    }
    $editlink = "index.php?location=" .
                 dbfuncString2Int("admin/makeuptype/modify.php") .
                 "&amp;key=" .
                 dbfuncString2Int($row['MakeupTypeIndex']) .
                 "&amp;keyname=" . dbfuncString2Int($row['MakeupType']); // Get link to subject

    echo "         <tr$alt>\n";

    $editbutton = dbfuncGetButton($editlink, "E", "small", "edit",
                                "Edit makeup type");
    echo "            <td>$editbutton</td>\n";
    echo "            <td>{$row['MakeupType']}</td>\n";
    echo "            <td>{$row['Description']}</td>\n";
    echo "            <td>{$row['OriginalMax']}</td>\n";
    echo "            <td>{$row['TargetMax']}</td>\n";
    echo "         </tr>\n";
}
echo "      </table>\n";

include "footer.php";
