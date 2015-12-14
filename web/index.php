<?php
// paperplane 1.1
// github.com/tomroyal
// login
	
$f_user = $_POST['u'];
$f_pass = $_POST['p'];
$f_act = $_POST['a'];
include('./includes/ppconfig.php');

session_start(); 

function showloginform(){
	?>	
	<p>Login:
	<form action="index.php" method="post">
	<input type="hidden" name="a" value="1">	
	<p><input type="text" placeholder="email" name="u" class="required">	
	<p><input type="password" name="p" placeholder="password"  class="required">	
	<p><input class="button" type="submit" name="submit" value="Log In">		
	</form>	
	<?
}

if ($f_act == 1){
	// try login

	// escape
	$f_user = pg_escape_string(strtolower($f_user));
	$f_pass = pg_escape_string(strtolower($f_pass));
	
	// hash
	$f_pass = sha1($f_pass.$pwsalt);
	
	$pq1 = 'SELECT * FROM '.$schemaname.'.pp_users WHERE "email" = \''.$f_user.'\' AND "password" = \''.$f_pass.'\''; 
	$rs1 = pg_query($con, $pq1);
	
	
	if ((pg_num_rows($rs1)) == 1){
		// pass	
		$rs1a = pg_fetch_array($rs1);
		$f_user_id = $rs1a['id'];
		
		$_SESSION['pp_user'] = $f_user;
		$_SESSION['pp_user_id'] = $f_user_id;
		header('Location: manage.php');	
		die;
	}
	else {
		// fail
		// html template
		include('./html/top.php');
		echo('<p>Incorrect username or password.');	
		showloginform();
	};	
}
else {
	// html template
	include('./html/top.php');
	if ($pp_allow_reg == 1){
		echo('<p>Welcome. Please log in or <a href="./register.php">register</a>.');	
	}
	else {
		echo('<p>Welcome. Please log in.');
	};
	showloginform();
}
// html template
include('./html/btm.php');

?>