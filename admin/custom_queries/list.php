<?php
/**
 * ***************************************************************
 * admin/custom_queries/list.php (c) 2016 Jonathan Dieter
 *
 * Show custom queries
 * ***************************************************************
 */

require '../vendor/autoload.php';

$checkintarray = array(
  "%class%" =>
    array(
      "SELECT ClassIndex AS `Index`, ClassName AS `Name` FROM class WHERE YearIndex=%year% AND DepartmentIndex=%department% ORDER BY class.Grade, class.ClassName",
      "SELECT ClassName AS `Name` FROM class WHERE ClassIndex=%index%"
    ),
  "%subject%" =>
    array(
      "SELECT SubjectIndex AS `Index`, Name AS `Name` FROM subject WHERE YearIndex=%year% AND TermIndex=%term% ORDER BY Name",
      "SELECT Name AS `Name` FROM subject WHERE SubjectIndex=%index%"
    )
  );
$checkstrarray = array(
  "%username%" =>
    array(
      "SELECT Username AS `Index`, CONCATENATE(Username, ' - ', FirstName, ' ', Surname) AS `Name` FROM user ORDER BY Username",
      "SELECT CONCATENATE(Username, ' - ', FirstName, ' ', Surname) AS `Name` FROM user WHERE Username='%index%'"
    )
  );

$title = "Custom Queries";

$queryindex = NULL;
if(isset($_GET['key']) and $_GET['key'] != "NULL")
    $queryindex = intval(dbfuncInt2String($_GET["key"]));

$type = "html";
if(isset($_GET['key2']))
    $type = dbfuncInt2String($_GET["key2"]);
if($type != "csv" and $type != "pdf")
    $type = "html";

if(isset($_POST['query'])) {
    if($_POST['query'] == "NULL") {
        $queryindex = NULL;
        unset($_GET['key']);
    } else {
        $queryindex = intval($_POST['query']);
        $_GET['key'] = dbfuncString2Int($queryindex);
    }
}

$checkarray = array_merge($checkintarray, $checkstrarray);

$fields = array();
$fieldnames = array();

foreach($checkintarray as $key => $value) {
    $fname = str_replace("%", "", $key);
    if(isset($_GET[$fname]))
        $fields[$fname] = intval(dbfuncInt2String($_GET[$fname]));
    if(isset($_POST[$fname])) {
        if($_POST[$fname] == "NULL") {
            unset($fields[$fname]);
            unset($_GET[$fname]);
        } else {
            $fields[$fname] = intval($_POST[$fname]);
            $_GET[$fname] = dbfuncString2Int($fields[$fname]);
        }
    }
}

foreach($checkstrarray as $key => $value) {
    $fname = str_replace("%", "", $key);
    if(isset($_GET[$fname]))
        $fields[$fname] = dbfuncInt2String($_GET[$fname]);
    if(isset($_POST[$fname])) {
        if($_POST[$fname] == "NULL") {
            unset($fields[$fname]);
        } else {
            $fields[$fname] = $_POST[$fname];
        }
    }
}

$linkfields = "&amp;key="      . dbfuncString2Int($queryindex);
foreach($fields as $key => $value) {
    $linkfields .= "&amp;$key="      . dbfuncString2Int($value);

    if(array_key_exists("%$key%", $checkintarray)) {
        if(!is_null($checkintarray["%$key%"][1])) {
            $query = $checkintarray["%$key%"][1];
            $query = str_replace("%index%", $value, $query);
        } else {
            continue;
        }
    } elseif(array_key_exists("%$key%", $checkstrarray)) {
        if(!is_null($checkstrarray["%$key%"][1])) {
            $query = $checkstrarray["%$key%"][1];
            $query = str_replace("%index%", htmlspecialchars($value, ENT_QUOTES), $query);
        } else {
            continue;
        }
    } else {
        continue;
    }
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
    if (!$row = & $res->fetchRow(DB_FETCHMODE_ASSOC))
        continue;

    $fieldnames[$key] = $row['Name'];
}

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

if (!$is_admin and !$is_principal) {
    include "header.php"; // Show header

    /* Log unauthorized access attempt */
    log_event($LOG_LEVEL_ERROR, "admin/custom_queries/list.php", $LOG_DENIED_ACCESS,
            "Attempted to view list of classes.");

    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
    include "footer.php";
    exit(0);
}

if($type == "html") {
    $link = "index.php?location=" . dbfuncString2Int("admin/custom_queries/list.php") .
             "&amp;key=" . $_GET['key'] .
             "&amp;key2=" . dbfuncString2Int($type);

    include "header.php";
    include "core/titletermyear.php";
    echo "      <form action='$link' method='post'>\n"; // Form method

    $query = "SELECT CustomQueryIndex, QueryName FROM custom_query ORDER BY QueryName";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
    if ($res->numRows() == 0) {
        echo " <p align='center'>No custom queries</p>\n";
        include "footer.php";
        exit(0);
    }

    echo "        <p align='center'>\n";
    echo "          <select name='query' onchange='this.form.submit()'>\n";
    echo "              <option value='NULL'>Select a query...</option>\n";
    while ( $row = & $res->fetchRow(DB_FETCHMODE_ASSOC) ) {
        if($queryindex == $row['CustomQueryIndex']) {
            $selected = " selected";
        } else {
            $selected = "";
        }
        $query_name = safe($row['QueryName']);
        echo "              <option value='{$row['CustomQueryIndex']}' $selected>$query_name</option>\n";
    }
    echo "          </select>\n";
    echo "          <input type='submit' id='query_action' name='action' value='Update query'>&nbsp; \n";
    echo "          <script>\n";
    echo "            document.getElementById('query_action').style.visibility = 'hidden';\n";
    echo "          </script>\n";
    echo "        </p>\n";

    if(!is_null($queryindex)) {
        $query = "SELECT Query FROM custom_query WHERE CustomQueryIndex=$queryindex";
        $res = &  $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query
        if (!$row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            echo "      <p align='center'>Query doesn't exist</p>\n";
            include "footer.php";
            exit(0);
        }
        $ckquery = $row['Query'];

        $have_info = True;
        foreach($checkarray as $key => $value) {
            if (strpos($ckquery, $key) !== false) {
                if(!is_null($value[0])) {
                    $fname = str_replace("%", "", $key);
                    $dname = ucfirst($fname);
                    $query = $value[0];
                    $query = str_replace("%year%", $yearindex, $query);
                    $query = str_replace("%department%", $depindex, $query);
                    $query = str_replace("%term%", $termindex, $query);

                    $res = &  $db->query($query);
                    if (DB::isError($res))
                        die($res->getDebugInfo()); // Check for errors in query
                    if ($res->numRows() == 0) {
                        echo "      <p align='center'>Query requires $fname field, but none are available</p>\n";
                        include "footer.php";
                        exit(0);
                    }
                    echo "      <p align='center'>$dname: \n";
                    echo "        <select name='$fname' onchange='this.form.submit()'>\n";
                    echo "            <option value='NULL'>Please select a $fname...</option>\n";
                    $found = False;
                    while ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
                        $selected = "";
                        if(array_key_exists($fname, $fields) and $fields[$fname] == $row['Index']) {
                            $selected = " selected";
                            $found = True;
                        }
                        echo "        <option value='{$row['Index']}' $selected>{$row['Name']}</option>\n";
                    }
                    if(!$found)
                        $have_info = False;
                    echo "        </select>\n";
                    echo "      </p>\n";
                }
            }
        }
        if(!$have_info) {
            echo "    </form>\n";
            include "footer.php";
            exit(0);
        }
        $pdflink = "index.php?location=" . dbfuncString2Int("admin/custom_queries/list.php") .
                            $linkfields .
                            "&amp;key2="     . dbfuncString2Int('pdf');
        $pdfbutton = dbfuncGetButton($pdflink, "Printable list", "medium", "", "Generate PDF of this query");
        $csvlink = "index.php?location=" . dbfuncString2Int("admin/custom_queries/list.php") .
                            $linkfields .
                            "&amp;key2="     . dbfuncString2Int('csv');
        $csvbutton = dbfuncGetButton($csvlink, "Export to CSV", "medium", "", "Generate CSV of this query");
        echo "        <p align='center'>$pdfbutton$csvbutton</p>\n";
    }
} elseif($type == "csv") {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=students.csv');
}

if($type == "pdf") {
    $html =  "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />\n";
    $html .= "<html>\n";
    $html .= "   <body style='font-size: 70%'>\n";
} else {
    $html = "";
}

if(is_null($queryindex)) {
    if($type == "html") {
        echo "      </form>\n";
        include "footer.php";
    } elseif($type == "pdf") {
        $html .= "      <p align='center'>No query selected</p>\n";
        $mpdf=new mPDF('s');
        $mpdf->SetFooter("{DATE d M Y  h:iA}");
        $mpdf->WriteHTML($html);
        $mpdf->Output();
    }
    exit(0);
}

$query = "SELECT Year FROM year WHERE YearIndex=$yearindex";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    $yearname = $row['Year'];
} else {
    $yearname = "Unknown year";
}

$query = "SELECT TermName FROM term WHERE TermIndex=$termindex";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    $termname = $row['TermName'];
} else {
    $termname = "Unknown term";
}

$query = "SELECT Department FROM department WHERE DepartmentIndex=$depindex";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    $depname = $row['Department'];
} else {
    $depname = "Unknown department";
}

$query = "SELECT Query, QueryName, QueryPrintTitle FROM custom_query WHERE CustomQueryIndex=$queryindex";
$res = &  $db->query($query);
if (DB::isError($res))
    die($res->getDebugInfo()); // Check for errors in query
if (!$row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    if($type == "html") {
        echo "      <p align='center'>Query doesn't exist</p>\n";
        include "footer.php";
    } elseif($type == "pdf") {
        $html .= "      <p align='center'>Query doesn't exist</p>\n";
        $mpdf=new mPDF('s');
        $mpdf->SetFooter("{DATE d M Y  h:iA}");
        $mpdf->WriteHTML($html);

        $mpdf->Output();
    } else {
        echo "Query doesn't exist";
    }
    exit(0);
}

$query = $row['Query'];
$queryname = htmlspecialchars($row['QueryName'], ENT_QUOTES);
if(is_null($row['QueryPrintTitle'])) {
    $querytitle= $queryname;
} else {
    $querytitle = htmlspecialchars($row['QueryPrintTitle'], ENT_QUOTES);
}

$query = str_replace("%year%", $yearindex, $query);
$querytitle = str_replace("%year%", $yearname, $querytitle);
$query = str_replace("%department%", $depindex, $query);
$querytitle = str_replace("%department%", $depname, $querytitle);
$query = str_replace("%term%", $termindex, $query);
$querytitle = str_replace("%term%", $termname, $querytitle);

foreach($checkintarray AS $key => $value) {
    $fname = str_replace("%", "", $key);
    if(array_key_exists($fname, $fields)) {
        $query = str_replace($key, $fields[$fname], $query);
        $querytitle = str_replace($key, $fieldnames[$fname], $querytitle);
    }
}
foreach($checkstrarray AS $key => $value) {
    $fname = str_replace("%", "", $key);
    if(array_key_exists($fname, $fields)) {
        $query = str_replace($key, htmlspecialchars($fields[$fname], ENT_QUOTES), $query);
        $querytitle = str_replace($key, htmlspecialchars($fieldnames[$fname], ENT_QUOTES), $querytitle);
    }
}

if($type == "pdf") {
    $html .= "      <table align='center' border='0' width='100%'>\n";
    $html .= "         <tr>\n";
    $html .= "            <td width='100px'><img height='25' width='100' alt='LESSON Logo' src='images/lesson_logo_small.png'></td>\n";
    $html .= "            <td align='center'><h2>$querytitle</h2></td>\n";
    $html .= "            <td width='100px'>&nbsp;</td>\n";
    $html .= "         </tr>\n";
    $html .= "      </table>\n";
    $html .= "      <p>&nbsp;</p>\n";
}

preg_match_all("/'(?:\\\\.|[^\\\\'])*'|[^;]+/", $query, $queries);
foreach($queries[0] as $query) {
    $res = & $db->query($query);
    if (DB::isError($res)) {
        $error = htmlspecialchars($res->getDebugInfo(), ENT_QUOTES);
        if($type == "html" or $type == "pdf") {
            $html .= "      <p><strong>Error running query</strong>:<br>$error</p>\n";
            if($type == "html") {
                echo $html;
                include "footer.php";
            } else {
                $mpdf=new mPDF('s');
                $mpdf->SetFooter("{DATE d M Y  h:iA}");
                $mpdf->WriteHTML($html);
                $mpdf->Output();
            }
        } else {
            echo "Error running query:\n$error";
        }
        exit(0);
    }
}

if ($res->numRows() == 0) {
    if($type == "html" or $type == "pdf") {
        $html .= " <p align='center'>No results</p>\n";
        if($type == "html") {
            echo $html;
            include "footer.php";
        } else {
            $mpdf=new mPDF('s');
            $mpdf->SetFooter("{DATE d M Y  h:iA}");
            $mpdf->WriteHTML($html);
            $mpdf->Output();
        }
    }
    exit(0);
}

if($type == "html") {
    $rowcount = $res->numRows();
    $html .= "      <p align='center'><em>$rowcount results</em></p>\n";
}

$first = True;
$alt_count = 0;
while ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
    if($type == "pdf" and $alt_count > 50) {
        $alt_count = 0;
        $html .= "      </table>\n";
        $html .= "      <p>&nbsp;</p>\n";
    }
    $alt_count += 1;
    if ($alt_count % 2 == 0) {
        $alt = " class='alt'";
    } else {
        $alt = " class='std'";
    }

    if($alt_count == 1) {
        if($type == "html" or $type == "pdf") {
            if($type == "html") {
                $html .= "      <table align='center' border='1'>\n";
            } else {
                $html .= "      <table align='center' border='1' style='page-break-inside: avoid; border-collapse: collapse;'>\n";
            }
            $html .= "         <tr>\n";
            foreach($row as $key => $value) {
                $key = htmlspecialchars($key, ENT_QUOTES);
                $html .= "           <th>$key</th>\n";
            }
            $html .= "         </tr>\n";
        } else {
            foreach($row as $key => $value) {
                $html .= "\"$key\",";
            }
            $html .= "\n";
        }
    }

    if($type == "html" or $type == "pdf") {
        if($type == "html") {
            $altval = $alt;
        } else {
            $altval = "";
        }
        $html .= "         <tr$alt>\n";
        foreach($row as $value) {
            $value = htmlspecialchars($value, ENT_QUOTES);
            $html .= "           <td>$value</td>\n";
        }
        $html .= "         </tr>\n";
    } else {
        foreach($row as $value) {
            $html .= "\"$value\",";
        }
        $html .= "\n";
    }
}

if($type == "html" or $type == "pdf") {
    $html .= "      </table>\n";
}

if($type == "html") {
    echo $html;
    echo "      </form>\n";
    include "footer.php";
} elseif($type == "csv") {
    echo $html;
} elseif($type == "pdf") {
    $mpdf=new mPDF('s');
    $mpdf->SetFooter("{DATE d M Y  h:iA} - Page {PAGENO}");
    $mpdf->WriteHTML($html);
    $mpdf->Output();
}

log_event($LOG_LEVEL_EVERYTHING, "admin/custom_queries/list.php", $LOG_ADMIN,
        "Viewed custom query.");
