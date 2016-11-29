<?php
/**
 * ***************************************************************
 * core/filefunc.php (c) 2016 Jonathan Dieter
 *
 * Functions for file management
 * ***************************************************************
 */

$filefunc_file_list = array();

function uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),

        // 16 bits for "time_mid"
        mt_rand(0, 0xffff),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand(0, 0x0fff) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand(0, 0x3fff) | 0x8000,

        // 48 bits for "node"
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function join_paths() {
    $args = func_get_args();
    $paths = array();
    foreach ($args as $arg) {
        $paths = array_merge($paths, (array)$arg);
    }

    $paths = array_map(create_function('$p', 'return trim($p, "/");'), $paths);
    $paths = array_filter($paths);
    $path = join('/', $paths);
    if(substr($args[0], 0, 1) == '/')
        $path = '/' . $path;
    return $path;
}

function run_or_die($command) {
    $retval = 0;
    $output = array();
    exec($command, $output, $retval);
    if($retval != 0) {
        print "$comand<br>\n";
        foreach($output as $line) {
            print "$line<br>\n";
        }
        die("Unable to resize image");
    }
    return $output;
}

function delete_photo($uname, $yearindex) {
    global $db;

    $query =    "DELETE image FROM photo, image " .
                "WHERE photo.Username='$uname' " .
                "AND photo.YearIndex=$yearindex " .
                "AND (image.ImageIndex=photo.SmallImageIndex " .
                "     OR image.ImageIndex=photo.LargeImageIndex)";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
    $query =    "DELETE FROM photo " .
                "WHERE photo.Username='$uname' " .
                "AND photo.YearIndex=$yearindex ";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
}

function upload_photo($uploadfile, $username, $yearindex) {
    global $db;

    // Get rid of old photo
    delete_photo($username, $yearindex);

    // Upload full size image
    $image_data = getimagesize($uploadfile);
    $image_id = get_id_from_file($uploadfile);
    $query =    "INSERT INTO image (FileIndex, Width, Height) " .
                "           VALUES ('$image_id', {$image_data[0]}, {$image_data[1]})";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    $full_size_id = NULL;
    $query = "SELECT ImageIndex FROM image WHERE FileIndex='$image_id'";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
    if (!$row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $error = "Unable to find uploaded photo";
    } else {
        $full_size_id = $row['ImageIndex'];
    }

    $small_file = $uploadfile . "-small.jpg";
    run_or_die("convert $uploadfile -resize 100x100 $small_file 2>&1");
    run_or_die("convert $small_file \( -size 100x100 xc:none -fill white -draw \"circle 50,50 50,1\" \) -compose copy_opacity -composite -quality 95 $small_file.png 2>&1");
    run_or_die("pngquant $small_file.png --quality 0-85 --speed 1");
    $image_data = getimagesize("$small_file-fs8.png");
    $small_image_id = get_id_from_file("$small_file-fs8.png");
    unlink("$small_file-fs8.png");
    unlink("$small_file.png");
    unlink("$small_file");
    $query =    "INSERT INTO image (FileIndex, Width, Height) " .
                "           VALUES ('$small_image_id', {$image_data[0]}, {$image_data[1]})";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    $small_size_id = NULL;
    $query = "SELECT ImageIndex FROM image WHERE FileIndex='$small_image_id'";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
    if (!$row = & $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        $error = "Unable to find uploaded photo";
    } else {
        $small_size_id = $row['ImageIndex'];
    }

    $query =    "INSERT INTO photo (Username, YearIndex, LargeImageIndex, SmallImageIndex) " .
                "           VALUES ('$username', $yearindex, $full_size_id, $small_size_id)";
    $res = &  $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query
}

function get_id_from_upload($uploadfile, $static=TRUE) {
    if(!isset($uploadfile['name'])) {
        print_r($uploadfile);
        die("No filename provided in upload");
    }
    $path = $uploadfile['name'];
    $ext = NULL;
    if(strrpos($path, '/') === FALSE) {
        $fname = $path;
    } else {
        $fname = substr($path, strrpos($path, '/')+1);
    }
    if(strrpos($fname, '.') !== FALSE) {
        $ext = substr($path, strrpos($path, '.')+1);
    }
    $handle = fopen($uploadfile['tmp_name'], 'r') or die('Unable to open uploaded file');
    $data = fread($handle, filesize($uploadfile['tmp_name']));
    fclose($handle);
    return get_id_from_data($data, $static, $ext);
}

function get_id_from_file($path, $static=TRUE) {
    $ext = NULL;
    if(strrpos($path, '/') === FALSE) {
        $fname = $path;
    } else {
        $fname = substr($path, strrpos($path, '/')+1);
    }
    if(strrpos($fname, '.') !== FALSE) {
        $ext = substr($path, strrpos($path, '.')+1);
    }
    $handle = fopen($path, 'r') or die('Unable to open uploaded file');
    $data = fread($handle, filesize($path));
    fclose($handle);
    return get_id_from_data($data, $static, $ext);
}

function get_id_from_data($data, $static=TRUE, $ext=NULL) {
    global $db;
    global $REPLICA_COUNT;

    if(!is_null($ext))
        $ext = "'" . safe($ext) . "'";
    else
        $ext = "NULL";
    $data = base64_encode(gzcompress($data));
    $uuid = uuid();

    $query =    "INSERT INTO filebuffer (FileIndex, Data, Extension, Replicas, Static) " .
                                "VALUES ('$uuid', '$data', $ext, $REPLICA_COUNT, $static)";

    $res = & $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    return $uuid;
}

function get_path_from_id($id) {
    global $db;
    global $REPLICA_COUNT;
    global $REPLICA_ID;
    global $DYNAMIC_FILES_LOCATION;
    global $STATIC_FILES_LOCATION;
    global $STATIC_FILES_WEBPATH;

    $id = safe($id);

    $query = "SELECT Path, Static FROM filelist WHERE FileIndex='$id'";
    $res = & $db->query($query);
    if (DB::isError($res))
        die($res->getDebugInfo()); // Check for errors in query

    if($res->numRows() == 0)
        $has_path = FALSE;
    else
        $has_path = TRUE;

    if($has_path)
        $row =& $res->fetchRow(DB_FETCHMODE_ASSOC);
    else
        $row = NULL;

    if(($has_path and
         (($row['Static'] == 1 and !file_exists(join_paths($STATIC_FILES_LOCATION, $row['Path']))) or
          ($row['Static'] == 0 and !file_exists(join_paths($DYNAMIC_FILES_LOCATION, $row['Path']))))) or
       !$has_path) {
        $query =    "SELECT filebuffer.FileIndex, Extension, Data, Replicas, Static, ReplicaID FROM " .
                    "       filebuffer LEFT OUTER JOIN filebuffercounter ON " .
                    "         (filebuffer.FileIndex=filebuffercounter.FileIndex AND " .
                    "          filebuffercounter.ReplicaID=$REPLICA_ID) " .
                    "WHERE filebuffer.FileIndex='$id'";
        $nres = & $db->query($query);
        if (DB::isError($nres))
            die($nres->getDebugInfo()); // Check for errors in query

        $nrow =& $nres->fetchRow(DB_FETCHMODE_ASSOC);
        if(is_null($nrow['FileIndex'])) {
            $path = "not_found.jpg";
            $static = 1;
        } else {
            if(!is_null($nrow['Extension']))
                $ext = ".{$nrow['Extension']}";
            else
                $ext = "";
            if($has_path)
                $path = $row['Path'];
            else
                $path = substr($id, 0, 2) . '/' . substr($id, 2, 2) . '/' . $id . $ext;
            $static = $nrow['Static'];


            if($static)
                $outfile = join_paths($STATIC_FILES_LOCATION, $path);
            else
                $outfile = join_paths($DYNAMIC_FILES_LOCATION, $path);

            $outpath = substr($outfile, 0, strrpos($outfile, '/'));
            //echo "$outfile  $outpath";
            if(!file_exists($outpath))
                mkdir($outpath, 0700, TRUE);
            $handle = fopen($outfile, 'w') or die('Unable to open file for writing.');
            $data = gzuncompress(base64_decode($nrow['Data']));
            fwrite($handle, $data);
            fclose($handle);

            if(!$has_path) {
                $query =    "REPLACE INTO filelist (FileIndex, Path, Static) " .
                            "                       VALUES ('$id', '$path', $static)";
                $nres = & $db->query($query);
                if (DB::isError($nres))
                    die($nres->getDebugInfo());
            }
            $query =    "REPLACE INTO filebuffercounter (FileBufferCounterIndex, FileIndex, ReplicaID) " .
                        "                           VALUES ('$id-$REPLICA_ID', '$id', $REPLICA_ID)";
            $nres = & $db->query($query);
            if (DB::isError($nres))
                die($nres->getDebugInfo());
            $query =    "SELECT FileBufferCounterIndex FROM filebuffercounter WHERE FileIndex='$id'";
            $nres = & $db->query($query);
            if (DB::isError($nres))
                die($nres->getDebugInfo());
            if($nres->numRows() >= $REPLICA_COUNT) {
                $query =    "DELETE FROM filebuffer WHERE FileIndex='$id'";
                $nres = & $db->query($query);
                if (DB::isError($nres))
                    die($nres->getDebugInfo());
                $query =    "DELETE FROM filebuffercounter WHERE FileIndex='$id'";
                $nres = & $db->query($query);
                if (DB::isError($nres))
                    die($nres->getDebugInfo());
            }
        }
    } else {
        $path = $row['Path'];
        $static = $row['Static'];
    }

    if($static) {
        return htmlspecialchars(join_paths($STATIC_FILES_WEBPATH, $path), ENT_QUOTES);
    } else {
        return "index.php?location=" . dbfuncString2Int("core/get_file.php") .
                         "&amp;key=" . dbfuncString2Int($id);
    }
}
