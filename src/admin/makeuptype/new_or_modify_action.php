<?php
/**
 * ***************************************************************
 * admin/makeuptype/new_or_modify_action.php (c) 2005, 2017 Jonathan Dieter
 *
 * Change makeup type information
 * ***************************************************************
 */

/* Get variables */
$nextLink = dbfuncInt2String($_GET['next']); // Link to next page

/* Check which button was pressed */
if ($_POST["action"] == "Save" or $_POST["action"] == "Update") {
    /* Check for input errors */
    $format_error = False;
    $errorlist = "";
    if (!isset($_POST['title']) or is_null($_POST['title']) or $_POST['title'] == "") {
        $errorlist .= "<p class='error' align='center'>You must specifiy a makeup type name!</p>\n";
        $format_error = True;
    }
    if (!is_numeric($_POST['origmax'])) {
        $errorlist .= "<p class='error' align='center'>The maximum original score must be a number!</p>\n";
        $format_error = True;
    }
    if (!is_numeric($_POST['targetmax'])) {
        $errorlist .= "<p class='error' align='center'>The target score must be a number!</p>\n";
        $format_error = True;
    }

    if ($format_error) {
        include "admin/makeuptype/modify.php";
        exit(0);
    }

    $_POST['title'] = "'" . $db->escapeSimple($_POST['title']) . "'";
    if (! isset($_POST['descr']) or is_null($_POST['descr']) or
         $_POST['descr'] == "") {
        $_POST['descr'] = "NULL";
    } else {
        $_POST['descr'] = "'" . $db->escapeSimple($_POST['descr']) . "'";
    }
    $_POST['origmax'] = floatval($_POST['origmax']);
    $_POST['targetmax'] = floatval($_POST['targetmax']);

    $errorlist = ""; // Clear error list. This list will now only contain database errors.

    $title = "LESSON - Saving changes..."; // common info and go to the appropriate page.
    $noHeaderLinks = true;
    $noJS = true;

    include "header.php"; // Print header

    echo "      <p align='center'>Saving changes...";

    if ($_POST["action"] == "Save") { // Create new subject if "Save" was pressed
        include "admin/makeuptype/new_action.php";
    } else {
        include "admin/makeuptype/modify_action.php"; // Change subject if "Update" was pressed
    }

    if ($error) { // If we ran into any errors, print failed, otherwise print done
        echo "failed!</p>\n";
    } else {
        echo "done.</p>\n";
    }
    echo "      <p align='center'><a href='$nextLink'>Continue</a></p>\n"; // Link to next page
    include "footer.php";
} elseif ($_POST["action"] == 'Delete') { // If delete was pressed, confirm deletion
    include "admin/makeuptype/delete_confirm.php";
} else {
    redirect($nextLink);
}
