<?php
// paperplane 1.1
// github.com/tomroyal
// manage acc - list apps, offer upload, logout

session_start(); 

include('./includes/ppconfig.php');
require('autoload.php'); // does dropbox
include('./includes/CFP/CFPropertyList.php'); // doesn't work via composer, odd..

$stage = $_REQUEST['s'];
$share_app_id = $_REQUEST['i'];


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
		// unzip
		
		// with thanks to https://github.com/wbroek/IPA-Distribution
		
		if (!is_dir($userid)) {
	    		if (!mkdir($userid)) die('Failed to create folder '.$fileid.'... Is the current folder writeable?');
	    }
		
		$getdatasuccess = 0;
		
		if (is_dir($userid)) {
		
		$zip = zip_open($ipafilepath);
		if ($zip) {
		  while ($zip_entry = zip_read($zip)) {
		    $fileinfo = pathinfo(zip_entry_name($zip_entry));	
		    if ($fileinfo['basename']=="Info.plist") {
		    	$fp = fopen($fileinfo['basename'], "w");
		    	if (zip_entry_open($zip, $zip_entry, "r")) {
			      $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
			      fwrite($fp,"$buf");
			      zip_entry_close($zip_entry);	
			      $getdatasuccess = 1;     
			      fclose($fp);
			    }
		    }
		  }
		  zip_close($zip);
		};
		
		};
		
		if ($getdatasuccess == 1){
			
			// get metadata from plist
			$plist = new CFPropertyList('Info.plist');
			$plistArray = $plist->toArray();	
			$theapp_id = $plistArray['CFBundleIdentifier'];
			$theapp_name = $plistArray['CFBundleDisplayName'];
			$theapp_ver = $plistArray['CFBundleShortVersionString'];
			
			// cleanup temp plist
			if (file_exists("Info.plist")) {
				@unlink("Info.plist");
			};	
			
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
				$q3='UPDATE pp_apps SET appdbpath = "'.$dbxfileurl.'" WHERE id = "'.$last_inserted_id.'"';
				if($link->query($q3) === false) {
				  // todo handle error
				} else {
				  // success
				  echo('<p class="alert">Success - '.$theapp_name.' version '.$theapp_ver.' uploaded.');
				};		
			}
			else {
				// TODO - remove db entry for failed
			}
		}
		else {
			echo('<p>Error - file could not be processed.');	
		};		
	} 	// end upload
	else if ($stage == 2){
		/*
		// make share link
		$q5 = 'SELECT * FROM pp_apps WHERE dlhash = "'.$share_app_id.'"';
		$r5=$link->query($q5); 
		if ($r5->num_rows == 1){
			$r5->data_seek(0);
			while($row5 = $r5->fetch_assoc()){
			    $theapp_dbid = $row5['id'];
			    $theapp_name = $row5['appname'];
			    $theapp_ver = $row5['appversion'];
			};
			// make share key
			$share_key = pg_escape_string(md5($theapp_ver.$pwsalt.time()));
			$q6='INSERT INTO pp_shares (ownerid, appid, sharekey, limuses) VALUES ("'.$userid.'","'.$theapp_dbid.'","'.$share_key.'",1)';
			if($link->query($q6) === false) {
				  // todo handle error
				} else {
				  // success
				  echo('<p class="alert">Success - single-use share link created. Send this to the tester:');
				  echo('<p><a href="https://'.$_SERVER['SERVER_NAME'].'/install.php?k='.$share_key.'">https://'.$_SERVER['SERVER_NAME'].'/install.php?k='.$share_key.'</a>');
			};	
		}
		else {
			// app removed before share was activated	
		};
		*/
	};


	// list user's apps - grouped
	echo('<h4>Your Apps</h4>');

	$pq1 = 'SELECT DISTINCT "appid" FROM '.$schemaname.'.pp_apps WHERE "ownerid" = \''.$userid.'\''; 
	$rs1 = pg_query($con, $pq1);

	while($row = pg_fetch_assoc($rs1)){	
		
		$thisappid = $row['appid'];	
		$pq2 = 'SELECT * FROM '.$schemaname.'.pp_apps WHERE "ownerid" = \''.$userid.'\' AND "appid" = \''.$thisappid.'\''; 
		$rs2 = pg_query($con, $pq2);		
		$firstver = 1;
		while ($row2 = pg_fetch_assoc($rs2)){	
			if ($firstver == 1){
				echo('<h5>'.$row2['appname'].'</h5>');
				$firstver = 0;
			};
			echo('<p>').'Version '.$row2['appversion'];	
			$linkurl = "itms-services://?action=download-manifest&url=https://".$_SERVER['SERVER_NAME'].'/plists/'.$row2['dlhash'].'/info.plist';
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
					<p>Select an IPA file to upload:
					<input type="hidden" name="s" value="1">	
					<p><input type="file" name="file" id="file" class="required">
					<p><input class="button upbtn" type="submit" name="submit" value="Upload">
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