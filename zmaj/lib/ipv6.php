#!/usr/bin/php
<?php

include ('../../framework-0.3.6/Framework.php');

error_reporting(E_ALL);

Framework::enableErrorToStandardError();
Framework::disableErrorToStandardOutput();

	$aPrefix = array('2001:470:e3bf');
	$sDevice = 'eth0';
	$iIpLimit = 500;
	
	foreach ($aPrefix as $sPrefix) 
	{
		$aSubnet = array();
	
		while (count($aSubnet) < $iIpLimit)
		{
			// 	$hRand = dechex(mt_rand(1,65535));
		
			$hRand = sprintf("%04x", mt_rand(1,65535));
			$sSubnet = $sPrefix . ':' . $hRand;
		
			$aSubnet[$sSubnet] = $sSubnet;
		}
		
		
		foreach ($aSubnet as &$sSubnet)
			for ($i = 0; $i < 4; $i++)
				$sSubnet = $sSubnet . ':' . sprintf("%04x", mt_rand(1,65535));
		
		unset($sSubnet);
		
		$aIps[$sPrefix] = array_values($aSubnet);
	}
	
	$sIpList = '';
	$sRouteAdd = '';
	$sIpAdd = "#!/bin/bash" . "\n";
// 	$sIpAdd .= "ip -6 route del default " . "\n";
	
	$sIpDel = $sIpAdd; 
	
	foreach ($aIps as $sPrefix => $aSubnet) 
		foreach ($aSubnet as $sIp) 
		{
			$sIpList .= "'$sIp'," . "\n";
			$sIpAdd .=	"ip -6 addr add $sIp/48 dev $sDevice" . "\n";
			$sIpDel .= "ip -6 addr del $sIp/48 dev $sDevice" . "\n";
			
			$aConfigIps[$sPrefix][$sIp] = 12345;
		}

	$sConfig = cArrayHelper::arrayToPhpString($aConfigIps);
	$sConfig = str_replace(' => 12345', '', $sConfig);
		
	$sIpAdd .= "ip -6 route add default via $sPrefix::1" . "\n";
	
/* 	
	foreach ($aIp as $sIp) 
	{
		$sIpList .= "'$sIp'," . "\n";
		$sIpAdd .=	"ip -6 addr add $sIp/48 dev $sDevice" . "\n";
		$sIpDel .= "ip -6 addr del $sIp/48 dev $sDevice" . "\n";
	}
 */	
	
	file_put_contents('ip_config.txt', $sConfig);
// 	file_put_contents('ip_list.txt', $sIpList);
	file_put_contents('ip_add.sh', $sIpAdd);
	chmod('ip_add.sh', 0744);
	
	file_put_contents('ip_del.sh', $sIpDel);
	chmod('ip_del.sh', 0744);
	
	
	
	
	
	
	













