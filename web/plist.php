<?php
// paperplane 1.1
// github.com/tomroyal
// dynamically serve plist manifest from db	
// needs mod rewrite rule: RewriteRule ^plists/(.*)/(.*)$ plist.php?f=$1 [NC,L]

include('./includes/ppconfig.php');
require('autoload.php'); // does dropbox

use \Dropbox as dbx;
	
	$userid = $_SESSION['pp_user_id'];
	$fkey = $_GET['f'];

	// $fkey is app id dlhash
	$dl_allowed = 0; // success marker
	
	// is dlhash correct
	$fkey = pg_escape_string($fkey);
	$pq1 = 'SELECT * FROM '.$schemaname.'.pp_apps WHERE "dlhash" = \''.$fkey.'\''; 
	$rs1 = pg_query($con, $pq1);

	if (pg_num_rows($rs1) == 1){
		// found app - owned by this user
		$dl_allowed = 1;
		// get metadata
		while($row = pg_fetch_assoc($rs1)){
		    $theapp_id = $row['appid'];
		    $theapp_name = $row['appname'];
		    $theapp_ver = $row['appversion'];
		    $theappdbpath = $row['appdbpath'];
		};
	}
	else {
		// install from share key?
		$pq1 = 'SELECT * FROM '.$schemaname.'.pp_shares WHERE "sharekey" = \''.$fkey.'\''; 
		$rs1 = pg_query($con, $pq1);
		
		if (pg_num_rows($rs1) == 1){
			// real key at least
			while($row = pg_fetch_assoc($rs1)){
			$shared_app_id = $row['appid'];
			$shared_app_owner = $row['ownerid'];
			$shared_app_uses = $row['limuses'];	
			if ($shared_app_uses > 0){
				// still usable
				$pq2 = 'SELECT * FROM '.$schemaname.'.pp_apps WHERE "id" = \''.$shared_app_id.'\''; 
				$rs2 = pg_query($con, $pq2);
				while($row3 = pg_fetch_assoc($rs2)){
					// get app details
					$theapp_id = $row3['appid'];
					$theapp_name = $row3['appname'];
					$theapp_ver = $row3['appversion'];
					$theappdbpath = $row3['appdbpath'];
					// unlock
					$dl_allowed = 1;
					// decrement allowed uses
					$pq4 = 'UPDATE '.$schemaname.'.pp_shares SET "limuses" = \''.($shared_app_uses-1).'\' WHERE "sharekey" = \''.$fkey.'\''; 
					$rs4 = pg_query($con, $pq1);
					// done
				};	
			};
		};	
			
		};
	};
	
	if ($dl_allowed == 1){
		
		// get url from dropbox
		$dbxClient = new dbx\Client($dbxtoken, $dbxname);
		$url = $dbxClient->createTemporaryDirectLink($theappdbpath);
		$theapp_url = $url[0];
		
		// send out plist
		header('Content-type: application/xml');
		// make xml
		$thexml = '<?xml version="1.0" encoding="UTF-8"?>
		<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
		<plist version="1.0">
		<dict>
			<key>items</key>
			<array>
				<dict>
					<key>assets</key>
					<array>
						<dict>
							<key>kind</key>
							<string>software-package</string>
							<key>url</key>
							<string>'.$theapp_url.'</string>
						</dict>
					</array>
					<key>metadata</key>
					<dict>
						<key>bundle-identifier</key>
						<string>'.$theapp_id.'</string>
						<key>bundle-version</key>
						<string>'.$theapp_ver.'</string>
						<key>kind</key>
						<string>software</string>
						<key>title</key>
						<string>'.$theapp_name.'</string>
					</dict>
				</dict>
			</array>
		</dict>
		</plist>';	
	echo($thexml);	
	};
?>