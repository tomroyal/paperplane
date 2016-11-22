<?php
// paperplane 1.1
// github.com/tomroyal
// manage acc - list apps, offer upload, logout

session_start(); 

include('./includes/ppconfig.php');
require('autoload.php'); // does dropbox

$stage = $_REQUEST['s'];
$share_app_id = $_REQUEST['i'];
$share_email =  $_REQUEST['e'];

$add_id = $_REQUEST['appn'];
$add_ver = $_REQUEST['appv'];
$add_name = $_REQUEST['appx'];
$add_appid = $_REQUEST['appy'];


use \Dropbox as dbx;
$dbxClient = new dbx\Client($dbxtoken, $dbxname);

// html template
include('./html/top.php');


if (isset($_SESSION['pp_user'])) {  
    // logged in
	
	$userid = $_SESSION['pp_user_id'];
	
	// handle upload
	if ($stage == 1){
		// do upload
		$fname = $_FILES["file"]["name"];	
		$tname = $_FILES["file"]["tmp_name"];	
		$ipafilepath = realpath($_FILES["file"]["tmp_name"]);

		if (!is_dir($userid)) {
	    		if (!mkdir($userid)) die('Failed to create folder '.$fileid.'... Is the current folder writeable?');
	    }
		
		// get metadata for app
		$gotmetadata = 0;
		
		if (($add_id != "") && ($add_ver != "")){
			// new version of existing app
			$add_id = pg_escape_string($add_id);
			$pq10 = 'SELECT * FROM '.$schemaname.'.pp_apps WHERE "id" = \''.$add_id.'\''; 
			$rs10 = pg_query($con, $pq10);
			
			while($row10 = pg_fetch_assoc($rs10)){
			    $theapp_id = $row10['appid'];
			    $theapp_name = $row10['appname'];
			    $theapp_ver = pg_escape_string($add_ver);
			    $gotmetadata = 1;
			};	
		}
		else {
			// new app
			if (($add_name != "") && ($add_appid != "") && ($add_ver != "")){
				$theapp_name = pg_escape_string($add_name);
				$theapp_id = pg_escape_string($add_appid);
				$theapp_ver = pg_escape_string($add_ver);
				$gotmetadata = 1;
			}
			else {
				// missing required data
			};
		}
		
		if ($gotmetadata == 1){
		
			// put in database. use index to unique dropbox name, below
			$theapp_id = pg_escape_string($theapp_id);
			$theapp_name = pg_escape_string($theapp_name);
			$theapp_ver = pg_escape_string($theapp_ver);
			$theapp_hash = pg_escape_string(md5($theapp_id.$pwsalt.time()));
			$q2='INSERT INTO pp_apps (ownerid, appid, appname, appversion, dlhash) VALUES ("'.$userid.'","'.$theapp_id.'","'.$theapp_name.'","'.$theapp_ver.'","'.$theapp_hash.'")';
			
			$pq2 = 'INSERT INTO '.$schemaname.'.pp_apps ("ownerid", "appid", "appname", "appversion", "dlhash") VALUES (\''.$userid.'\', \''.$theapp_id.'\', \''.$theapp_name.'\', \''.$theapp_ver.'\', \''.$theapp_hash.'\') RETURNING "id"'; 
			$rs2 = pg_query($con, $pq2);
			
			// get insert id
			$temp = pg_fetch_row($rs2); 
			$last_inserted_id = $temp['0'];	
	
			// push file to dropbox
			$dbxfileurl = '/'.$userid.'/'.$last_inserted_id.'-'.$fname;
			
			$fup = fopen($ipafilepath, "rb");
			$result = $dbxClient->uploadFile($dbxfileurl, dbx\WriteMode::add(), $fup);
			fclose($fup);
			
			if ($result['mime_type'] == 'application/octet-stream'){		
				// add dbx path to db
				$dbxfileurl = pg_escape_string($dbxfileurl);
				$pq3 = 'UPDATE '.$schemaname.'.pp_apps SET "appdbpath" = \''.$dbxfileurl.'\' WHERE  "id" = \''.$last_inserted_id.'\''; 
				$rs3 = pg_query($con, $pq3);	
				
				echo('<p class="alert">Success - '.$theapp_name.' version '.$theapp_ver.' uploaded.');
						
			}
			else {
				// TODO - remove db entry for failed
			}
		}
		else {
			// error
			echo('<p class="alert">Error - missing information required to add a version');	
		};
			
	
	} 	// end upload
	else if (($stage == 2) || ($stage == 3)){
		// make share link
		$pq5 = 'SELECT * FROM '.$schemaname.'.pp_apps WHERE "dlhash" = \''.$share_app_id.'\''; 
		$rs5 = pg_query($con, $pq5);
		
		if (pg_num_rows($rs5) == 1){
			while($row5 = pg_fetch_assoc($rs5)){
			    $theapp_dbid = $row5['id'];
			    $theapp_name = $row5['appname'];
			    $theapp_ver = $row5['appversion'];
			};
			// make share key
			$share_key = pg_escape_string(md5($theapp_ver.$pwsalt.time()));			
			$pq6 = 'INSERT INTO '.$schemaname.'.pp_shares ("ownerid", "appid", "sharekey", "limuses") VALUES (\''.$userid.'\',\''.$theapp_dbid.'\',\''.$share_key.'\',\'1\')'; 
			$rs6 = pg_query($con, $pq6);
		
			if ($stage == 2){
				echo('<p class="alert">Success - single-use share link created. Send this to the tester:');
				echo('<p><a href="https://pplane.herokuapp.com/install.php?k='.$share_key.'">https://pplane.herokuapp.com/install.php?k='.$share_key.'</a>');
			}
			else if ($stage == 3){
				echo('<p>Generated share key: '.$share_key);
				echo('<p>TODO email to '.$share_email);	
			};
	
		}
		else {
			// app removed before share was activated	
		};
	};


	// list user's apps - grouped
	echo('<h4>Your Apps</h4>');

	$pq1 = 'SELECT DISTINCT "appid" FROM '.$schemaname.'.pp_apps WHERE "ownerid" = \''.$userid.'\''; 
	$rs1 = pg_query($con, $pq1);

	while($row = pg_fetch_assoc($rs1)){	
		
		$thisappid = $row['appid'];	
		$pq2 = 'SELECT * FROM '.$schemaname.'.pp_apps WHERE "ownerid" = \''.$userid.'\' AND "appid" = \''.$thisappid.'\' ORDER BY "id" DESC'; 
		$rs2 = pg_query($con, $pq2);		
		$firstver = 1;
		while ($row2 = pg_fetch_assoc($rs2)){	
			if ($firstver == 1){
				echo('<h5>'.$row2['appname'].'</h5>');
				$firstver = 0;
			};
			echo('<p>').'Version '.$row2['appversion'];	
			// $linkurl = "itms-services://?action=download-manifest&url=https://".$_SERVER['SERVER_NAME'].'/plists/'.$row2['dlhash'].'/info.plist';
			$linkurl = 'itms-services://?action=download-manifest&url=https://pplane.herokuapp.com/plists/'.$row2['dlhash'].'/info.plist';
			
			
			$sharelinkurl = './manage.php?s=2&i='.$row2['dlhash'];
			echo('<p><a href="'.$linkurl.'"><span class="button">Install</span></a>  <a href="'.$sharelinkurl.'"><span class="button">Share</span></p></a>');
		};
	};

	// show upload form
	?>
	</div>
	<!-- sidebar -->
	<div class="three columns u-pull-right">
	<h4>Upload</h4>
	<form id="upform" action="manage.php" method="post" enctype="multipart/form-data">
					<input type="hidden" name="s" value="1">	
					
					<label for="appn">App Name</label>
					<select name="appn" id="appn">
					<?	
					$pq1 = 'SELECT DISTINCT "appname", "id" FROM '.$schemaname.'.pp_apps WHERE "ownerid" = \''.$userid.'\''; 
					$rs1 = pg_query($con, $pq1);
					if (pg_num_rows($rs1) == 0){
						echo('<option value="none">No apps available</option>');
					}
					else {
						echo('<option value="none">Choose an app</option>');
						while ($group_row = pg_fetch_array($rs1)){
							echo('<option value="'.$group_row['id'].'">'.$group_row['appname'].'</option>');	
						};
					};
					?>	
					</select>
					
					<label for="appx">Or New Name</label>
					<input type="text" name="appx" id="appx">
					
					<label for="appy">New AppID</label>
					<input type="text" name="appy" id="appy">
					
					<label for="appv">Version Number</label>
					<input type="text" name="appv" id="appv">
					
					<label class="custom-file-upload">
					    <input type="file" name="file" id="file" class="required"/>Choose IPA File
					</label>
					
					<br><input class="button upbtn" type="submit" name="submit" value="Upload">
	</form>
	<?
	// show logout
	?>
	<h4>Log Out</h4>
	<p>Click <a href="./logout.php">here</a> to log out.</p>
	
	<?	
   
} else {
	// not logged in
    echo('<p>You are not logged in. Please click <a href="index.php">here</a>.');
   
};
// html template
include('./html/btm.php');

?>