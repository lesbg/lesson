<?php
	/*****************************************************************
	 * header.php  (c) 2004, 2005 Jonathan Dieter
	 *
	 * Sets title of page and prints header text.  This can be changed
	 * to whatever is appropriate for your school.
	 *****************************************************************/
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<?php
	if(isset($extraMeta)) echo $extraMeta;           // Print any extra metatags.
?>
		<title><?=$title?></title>
<?php
	if(!isset($noJS) || !$noJS) {
		?><script language="JavaScript" type="text/javascript" src="js/main.js"></script><?php
		if(isset($extra_js)) {
			?><script language="JavaScript" type="text/javascript" src="js/<?=$extra_js?>"></script><?php
		}
	}

	/* Link in CSS stylesheets */
?>
		<link rel="StyleSheet" href="css/standard.css" title="LES" type="text/css" media="screen">
<?php
	if(isset($use_extra_css) && $use_extra_css == true) {
		?>
		<link rel="StyleSheet" href="css/standard.css" title="Regular" type="text/css" media="screen">
		<link rel="StyleSheet" href="css/standard-hidden.css" title="Hidden" type="text/css" media="screen">
		<link rel="StyleSheet" href="css/standard-unmarked.css" title="Unmarked" type="text/css" media="screen">
		<?php
	}
?>
		<link rel="StyleSheet" href="css/print.css" title="Printable colors" type="text/css" media="print">
		<link rel="Alternate StyleSheet" href="css/basic.css" title="Basic" type="text/css" media="screen">
	</head>
<?php
	if(isset($bodyClass)) {
		$alt = " class=\"$bodyClass\"";
	} else {
		$alt = "";
	}
?>
	<body<?=$alt?>>
<?php
	if(!isset($noHeaderLinks) || !$noHeaderLinks) {
		if(!isset($homebutton)) {
			$homelink = "index.php?location=" . dbfuncString2Int("user/main.php");
			$homebutton = dbfuncGetButton($homelink, "Home", "small", "home");
		}
		?>
		<table class="transparent" width="100%">
			<tr>
				<?php
					$useragent = $_SERVER['HTTP_USER_AGENT'];
					if (preg_match('|MSIE ([0-6].[0-9]{1,2})|',$useragent,$matched)) {
						// Can't handle transparent png's, so we'll give them transparent gif's
						?><td width="200px" class="logo"><img height="50" width="200" alt="LESSON Logo" src="images/lesson_logo_small.gif"></td><?php
					} else {
						// Can't handle transparent png's, so we'll give them transparent gif's
						?><td width="200px" class="logo"><img height="50" width="200" alt="LESSON Logo" src="images/lesson_logo_small.png"></td><?php
					}
				?>
				<td class="title">
					<?=$title?>
		<?php
		if(isset($subtitle)) {
			?><span class="subtitle"><br><?=$subtitle?></span><?php
		}
		?>
				</td>
				<td width="200px" class="home"><?=$homebutton?></td>
			</tr>
		</table>
		<?php
	}

	$here       = "index.php?{$_SERVER['QUERY_STRING']}";
?>