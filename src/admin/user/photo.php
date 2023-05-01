<?php
/**
 * ***************************************************************
 * admin/user/photo.php (c) 2016 Jonathan Dieter
 *
 * Show photo for user
 * ***************************************************************
 */

if(isset($_GET['next'])) {
    $backLink = dbfuncInt2String($_GET['next']);
}

$link = "index.php?location=" .
        dbfuncString2Int("admin/user/photo.php") .
        "&amp;key="     . $_GET['key'] .
        "&amp;keyname=" . $_GET['keyname'] .
        "&amp;next=" . dbfuncString2Int($backLink);

$name = htmlspecialchars(dbfuncInt2String($_GET['keyname']), ENT_QUOTES);
$title = "Upload photo for $name";
$uname = safe(dbfuncInt2String($_GET['key']));

include "core/settermandyear.php";

include "header.php"; // Show header

if ($is_admin) {
    $error = NULL;
    if(isset($_POST['action'])) {
        if($_POST['action'] == "Done") {
            redirect($backLink);
        } elseif($_POST['action'] == "Delete") {
            echo "      <form action='$link' method='post'>\n";
            echo "         <p align='center'>Are you sure you want to delete $name's picture?</p>\n";
            echo "         <p align='center'><input type='submit' name='action' value='Yes, delete photo'><input type='submit' value='No, do not delete photo'></p>\n";
            echo "      </form>\n";
            exit(0);
        } elseif($_POST['action'] == "Yes, delete photo") {
            delete_photo($uname, $yearindex);
        } elseif($_POST['action'] == "Upload") {
            if(!isset($_FILES['photo'])) {
                $error = "No photo uploaded!";
            } else {
                $photo_file_type = safe($_FILES['photo']['type']);
                if ($photo_file_type != "image/png" and $photo_file_type != "image/jpeg") {
                    $error = "Uploaded file is not a JPEG or PNG image.";
                } else {
                    $path = $_FILES['photo']['name'];
                    $ext = NULL;
                    if(strrpos($path, '/') === FALSE) {
                        $fname = $path;
                    } else {
                        $fname = substr($path, strrpos($path, '/')+1);
                    }
                    if(strrpos($fname, '.') !== FALSE) {
                        $ext = substr($path, strrpos($path, '.')+1);
                    }
                    rename($_FILES['photo']['tmp_name'], $_FILES['photo']['tmp_name'] . ".$ext");
                    delete_photo($uname, $yearindex);
                    upload_photo($_FILES['photo']['tmp_name'] . ".$ext", $uname, $yearindex);
                }
            }
        }
    }
    $query =    "SELECT image.FileIndex AS LargeIndex, image.Height AS LargeHeight, image.Width AS LargeWidth, " .
                "       simg.FileIndex AS SmallIndex, simg.Height AS SmallHeight, simg.Width AS SmallWidth, " .
                "       photo.YearIndex, year.Year " .
                "FROM photo INNER JOIN image ON (photo.LargeImageIndex=image.ImageIndex) " .
                "           INNER JOIN image AS simg ON (photo.SmallImageIndex=simg.ImageIndex) " .
                "           INNER JOIN year USING (YearIndex) " .
                "WHERE Username='$uname' " .
                "AND YearIndex<=$yearindex " .
                "ORDER BY YearIndex";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    echo "      <form enctype='multipart/form-data' action='$link' method='post'>\n";

    $row = array();
    $disabled = "";
    $last_yi = NULL;
    if($res->numRows() > 0) {
        echo "      <p align='center'>\n";
        while ($row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
            $lphoto_link = get_path_from_id($row['LargeIndex']);
            $lheight = $row['LargeHeight'];
            $lwidth = $row['LargeWidth'];
            $sphoto_link = get_path_from_id($row['SmallIndex']);
            $sheight = $row['SmallHeight'];
            $swidth = $row['SmallWidth'];

            echo "         <span style='display: inline-block;'><a href='$lphoto_link'>${row['Year']}<br><img src='$sphoto_link' width='$swidth' height='$sheight' alt='Picture of $uname in ${row['Year']}' /></a></span>\n";
            $last_yi = $row['YearIndex'];
        }
        echo "      </p>\n";
    }
    if(is_null($last_yi) or $last_yi != $yearindex) {
        $lphoto_link = "images/nobody.png";
        $lwidth = 56;
        $lheight = 80;
        $disabled = " $disabled";
    }

    $show_width = intval(($lwidth / $lheight) * 30);
    echo "         <p align='center'>\n";
    echo "            <img src='$lphoto_link' height=30% width=$show_width% alt='Picture of $uname'/>\n";
    echo "         </p>\n";
    if(!is_null($error)) {
        echo "      <p align='center' class='error'>$error</p>\n";
    }
    echo "         <p align='center'>Choose the file you want to upload: <input name='photo' accept='image/*' type='file'></p>\n";
    echo "         <p align='center'><input type='hidden' name='MAX_FILE_SIZE' value='102400000'><input type='submit' name='action' value='Upload'><input type='submit' name='action' value='Delete' $disabled></p>\n";
    echo "         <p align='center'><input type='submit' name='action' value='Done'></p>\n";
    echo "      </form>\n";
} else { // User isn't authorized to view or change scores.
    echo "      <p>You do not have permission to access this page</p>\n";
    echo "      <p><a href='$backLink'>Click here to go back</a></p>\n";
}

include "footer.php";
?>
