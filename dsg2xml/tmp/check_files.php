<?php
/**
 * Dsg data to xml 
 * 
 * Copyright (c) 2005-2009 ViewSource
 */

include ("../../framework/Framework.php");

// Create database object and configure it (add di if config is required)
$oDb = new cDb();

$oDb->username = "root";
$oDb->host = "localhost";
$oDb->charset = "latin1";

$aDatabases = $oDb->fetchAllFromQuery("show databases like 'sitegen_%'");

$iCounter = 0;
foreach($aDatabases as $iNo => $dbName)
{
	$iCounter++;
	print "#$iCounter $dbName\n";
	include("data/{$dbName}.php");
}

print "End\n";