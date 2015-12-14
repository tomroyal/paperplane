<?php
// generic postgres connector

// also set global debug

$apt_debug_mode = 1;

$schemaname = "pp";
$con = pg_connect(getenv('DATABASE_URL')); // pg conn is $con, not $link
?>