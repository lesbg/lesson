<?php
/**
 * ***************************************************************
 * admin/category/new_or_modify_action.php (c) 2005 Jonathan Dieter
 *
 * Change category type information
 * ***************************************************************
 */

/* Get variables */
$key = dbfuncInt2String($_GET['key']); // Key
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page

/* Check which button was pressed */
if ($_POST["action"] == "Save" or $_POST["action"] == "Update") { // If update or save were pressed, print
                                                                  // common info and go to the right page.
    $values = array();
    if (isset($_POST['value'])) {
        $tok = strtok($_POST['value'], ",");
        while ( $tok ) {
            if ($tok != "") {
                $values[intval($tok)] = intval($tok);
            }
            $tok = strtok(",");
        }
    }

    /* Check for input errors */
    $format_error = False;
    $errorlist = "";
    if (! isset($_POST['name']) or is_null($_POST['name']) or
         $_POST['name'] == "") { // Make sure name has been entered
        $errorlist .= "<p class='error' align='center'>You must specifiy a category type name!</p>\n";
        $format_error = True;
    }
    /* Get subject information */
    if (! $format_error) {
        $_POST['name'] = safe($_POST['name']);
        $query = "SELECT CategoryIndex FROM category " .
                 "WHERE CategoryName = '{$_POST['name']}'";
        if ($_POST["action"] == "Update")
            $query .= " AND CategoryIndex != $key";
        $res = & $db->query($query);
        if (DB::isError($res))
            die($res->getDebugInfo()); // Check for errors in query
        if ($res->numRows() > 0) {
            $errorlist .= "<p class='error' align='center'>This name has already been chosen.  Please choose another.</p>\n";
            $format_error = True;
        }
    }

    if (! $format_error) {

        $errorlist = ""; // Clear error list. This list will now only contain database errors.

        $title = "LESSON - Saving changes...";
        $noHeaderLinks = true;
        $noJS = true;

        include "header.php";

        echo "      <p align='center'>Saving changes...";

        if ($_POST["action"] == "Save") { // Create new subject if "Save" was pressed
            include "admin/category/new_action.php";
        } else {
            include "admin/category/modify_action.php"; // Change subject if "Update" was pressed
        }

        if ($error) { // If we ran into any errors, print failed, otherwise print done
            echo "failed!</p>\n";
        } else {
            echo "done.</p>\n";
        }
        echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n"; // Link to next page
        include "footer.php";
    } else {
        if ($_POST["action"] == "Save") {
            include "admin/category/new.php";
        } else {
            include "admin/category/modify.php";
        }
    }
} elseif ($_POST['action'] == "Cancel") {
    $extraMeta = "      <meta http-equiv=\"REFRESH\" content=\"0;url=$nextLink\">\n";
    $noJS = true;
    $noHeaderLinks = true;
    $title = "LESSON - Cancelling...";

    include "header.php";

    echo "      <p align=\"center\">Cancelling and redirecting you to <a href=\"$nextLink\">$nextLink</a>." .
         "</p>\n";

    include "footer.php";
} else {
    $values = array();
    if (isset($_POST['value'])) {
        $tok = strtok($_POST['value'], ",");
        while ( $tok ) {
            if ($tok != "") {
                $values[intval($tok)] = intval($tok);
            }
            $tok = strtok(",");
        }
    }
    if ($_POST['action'] == ">") {
        foreach ( $_POST['removesubjecttype'] as $remSubject ) {
            $remSubject = intval($remSubject);
            if (isset($values[$remSubject]))
                unset($values[$remSubject]);
        }
    } elseif ($_POST['action'] == "<") {
        foreach ( $_POST['addsubjecttype'] as $addSubject ) {
            $addSubject = intval($addSubject);
            $values[$addSubject] = $addSubject;
        }
    }

    if ($_POST['type'] == "new") {
        include "admin/category/new.php";
    } else {
        include "admin/category/modify.php";
    }
}
?>
