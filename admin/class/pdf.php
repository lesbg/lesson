<?php
/*
 * pdf.php (c) 2016 Jonathan Dieter
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 *
 */

require '../vendor/autoload.php';

$title = dbfuncInt2String($_GET['keyname']);
$classindex = safe(dbfuncInt2String($_GET['key']));

/* Check whether current user is a counselor */
$res = &  $db->query(
                "SELECT Username FROM counselorlist " .
                 "WHERE Username='$username'");
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
    $is_counselor = true;
} else {
    $is_counselor = false;
}

if ($is_admin or $is_counselor) {
    $showalldeps = true;
} else {
    $admin_page = true;
}
include "core/settermandyear.php";

/* Check whether current user is a hod */
$res = &  $db->query(
                "SELECT Username FROM hod " . "WHERE Username='$username' " .
                 "AND   DepartmentIndex=$depindex");
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
    $is_hod = true;
} else {
    $is_hod = false;
}

/* Check whether current user is principal */
$res = &  $db->query(
                "SELECT Username FROM principal " .
                 "WHERE Username=\"$username\" AND Level=1");
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query

if ($res->numRows() > 0) {
    $is_principal = true;
} else {
    $is_principal = false;
}

if ($is_admin or $is_principal or $is_counselor or $is_hod) {
    $html="";

    $query =    "SELECT user.FirstName, user.Surname, user.Username, newmem.Username AS NewUser, specialmem.Username AS SpecialUser, " .
                 "       class.ClassName, class.ClassIndex ".
                 "       FROM class INNER JOIN classterm ON " .
                 "                  (class.YearIndex=$yearindex " .
                 "                   AND classterm.ClassIndex=class.ClassIndex " .
                 "                   AND classterm.TermIndex=$termindex) " .
                 "            INNER JOIN classlist USING (ClassTermIndex) " .
                 "            INNER JOIN user USING (Username) " .
                 "            LEFT OUTER JOIN (groupgenmem AS newmem INNER JOIN " .
                 "                             groups AS newgroups ON (newgroups.GroupID=newmem.GroupID " .
                 "                                                     AND newgroups.GroupTypeID='new' " .
                 "                                                     AND newgroups.YearIndex=$yearindex)) " .
                 "                             ON (user.Username=newmem.Username) " .
                 "            LEFT OUTER JOIN (groupgenmem AS specialmem INNER JOIN " .
                 "                             groups AS specgroups ON (specgroups.GroupID=specialmem.GroupID " .
                 "                                                     AND specgroups.GroupTypeID='special' " .
                 "                                                     AND specgroups.YearIndex=$yearindex)) " .
                 "                             ON (user.Username=specialmem.Username) ";
    if($classindex != "-1") {
        $query .=   "WHERE class.ClassIndex = $classindex ";
    }
    $query .=   "AND   classterm.TermIndex = $termindex " .
                "GROUP BY user.Username " .
                "ORDER BY class.Grade, class.ClassName, user.FirstName, user.Surname, user.Username";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    $prev_class = -1;
    $first = True;
    $html =  "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />\n";
    $html .= "<html>\n";
    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        if($row['ClassIndex'] != $prev_class) {
            if(!$first) {
                $html .= "      </table>\n";
                $html .= "      <pagebreak />\n";
            } else {
                $first = False;
            }
            $orderNum = 0;
            $html .= "      <table align='center' border='0' width='100%'>\n";
            $html .= "         <tr>\n";
            $html .= "            <td width='100px'><img height='25' width='100' alt='LESSON Logo' src='images/lesson_logo_small.png'></td>\n";
            $html .= "            <td align='center'><h1>{$row['ClassName']}</h1></td>\n";
            $html .= "            <td width='100px'>&nbsp;</td>\n";
            $html .= "         </tr>\n";
            $html .= "      </table>\n";
            $html .= "      <table align='center' border='1' style='border-collapse: collapse;'>\n"; // Table headers
            $html .= "         <tr>\n";
            $html .= "            <th>Order</th>\n";
            $html .= "            <th>Student</th>\n";
            if ($is_admin or $is_principal) {
                $html .= "            <th>New</th>\n";
                $html .= "            <th>Special</th>\n";
            }
            $html .= "         </tr>\n";
            $prev_class = $row['ClassIndex'];
        }
        $orderNum ++;

        $html .= "         <tr>\n";
        $html .= "            <td>$orderNum</td>\n";
        $html .= "            <td>{$row['FirstName']} {$row['Surname']} ({$row['Username']})</td>\n";
        if ($is_admin or $is_principal) {
            if (!is_null($row['NewUser'])) {
                $html .= "            <td align='center'>X</font></td>\n";
            } else {
                $html .= "            <td>&nbsp;</td>\n";
            }
            if (!is_null($row['SpecialUser'])) {
                $html .= "            <td align='center'>X</td>\n";
            } else {
                $html .= "            <td>&nbsp;</td>\n";
            }
        }
        $html .= "         </tr>\n";
    }
    if($prev_class == -1) {
        $html .= "<p align='center'>No students</p>\n";
    } else {
        $html .= "      </table>\n";
    }
    $html .= "</html>\n";

    $mpdf=new mPDF('s');
    $mpdf->SetFooter("{DATE d M Y  h:iA}");
    $mpdf->WriteHTML($html);

    $mpdf->Output();
} else {
    $title = "Generate PDF of classes";
    include "header.php"; // Show header

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    include "footer.php";
}
