<?php
	/*****************************************************************
	 * user/new_message.php  (c) 2006 Jonathan Dieter
	 *
	 * Write a new message
	 *****************************************************************/

	 /* Get variables */
	$toIndex  = dbfuncInt2String($_GET['key']);
	$to       = dbfuncInt2String($_GET['keyname']);
	$toType   = dbfuncInt2String($_GET['key2']);
	$link             = "index.php?location=" . dbfuncString2Int("user/send_message.php") .
						"&amp;key=" .           $_GET['key'] .
						"&amp;keyname=" .       $_GET['keyname'] .
						"&amp;keyname2=" .      $_GET['key2'] .
						"&amp;next=" .          $_GET['next'];
	$title    = "Send message";

	include "header.php"
?>
      <form action='<?=$link?>' method='post' name='assignment'>
         <table class='transparent' align='center'>
            <tr>
               <td>To:</td>
               <td><?=$to?></td>
            </tr>
            <tr>
               <td>Subject:</td>
               <td><input type='text' name='subject' tabindex='1' size='50'></td>
            </tr>
            <tr>
               <td>Message:</td>
               <td><textarea rows='10' cols='50' name='message' tabindex='2'></textarea></td>
            </tr>
         </table>
         <p align='center'>
            <input type='submit' name='action' value='Save'>&nbsp;
            <input type='submit' name='action' value='Cancel'>&nbsp;
         </p>

         <p></p>

<?php
	include "footer.php";
?>