<?php
// paperplane 1.1
// github.com/tomroyal
// displays install button to recipients of shared link

$share_key = $_GET['k'];
$stage = $_GET['s'];

include('./includes/ppconfig.php');

// html template
include('./html/top.php');

$share_key = pg_escape_string($share_key);
$pq1 = 'SELECT * FROM '.$schemaname.'.pp_shares WHERE "sharekey" = \''.$share_key.'\''; 
$rs1 = pg_query($con, $pq1);
	if (pg_num_rows($rs1) == 1){
		// valid key
		while($row = pg_fetch_assoc($rs1)){
			$shared_app_id = $row['appid'];
			$shared_app_owner = $row['ownerid'];
			$shared_app_uses = $row['limuses'];	
		};
		
		if ($shared_app_uses > 0){
			// still has valid uses
			// get app details
			$pq2 = 'SELECT * FROM '.$schemaname.'.pp_users WHERE "id" = \''.$shared_app_owner.'\''; 
			$rs2 = pg_query($con, $pq2);	
			
			while($row2 = pg_fetch_assoc($rs2)){
				$sender_name = $row2['fullname'];
			};
			$pq2 = 'SELECT * FROM '.$schemaname.'.pp_apps WHERE "id" = \''.$shared_app_id.'\''; 
			$rs2 = pg_query($con, $pq2);	
			while($row3 = pg_fetch_array($rs2)){
				$app_name = $row3['appname'];
				$app_ver = $row3['appversion'];
				$app_dbh = $row3['dlhash'];
			};

			if ($stage == 1){
				// has clicked through - offer install button	
				echo('<p>Tap the button below to install now.');
				
				$linkurl = 'itms-services://?action=download-manifest&url=https://pplane.herokuapp.com/plists/'.$share_key.'/info.plist';
				echo('<p><a href="'.$linkurl.'"><span class="button">Install Now</span></a></p>');
				
			}
			else {
				// has just arrived
				echo('<h4>Welcome</h4><p>You have been invited to install '.$app_name.' version '.$app_ver.' by '.$sender_name.'.');
				echo('<p>If you are viewing this page on your iOS device, and are ready to install now, tap Next.');
				$next_btn_url = 'install.php?k='.$share_key.'&s=1';
				echo('<p><a href="'.$next_btn_url.'"><span class="button">Next</span></a>');
			
			};	
		}
		else {
			echo('<p>Sorry, the link you have followed has expired.');
		};	
	} // valid key
	else {
		echo('<p>Sorry, the link you have followed is invalid.');
	};


// html template
include('./html/btm.php');

?>