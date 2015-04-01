<?php

function createRandomPassword() 
{
	$chars = "abcdefghijkmnopqrstuvwxyz023456789";
	$i = 0;
	$pass = '';
	while ($i <= 7) 
	{
		$num = mt_rand() % 33;
		$tmp = substr($chars, $num, 1);
		$pass = $pass . $tmp;
		$i ++;
	}
	return $pass;
}
//1048576
for ($i = 0; $i < 262144; $i++) 
{
	$sMD5 = "/1/2/" . md5(createRandomPassword()) . ".jpg\n";
//	var_dump($sMD5);
	file_put_contents('random_list.txt', $sMD5, FILE_APPEND);
}












//file_put_contents('./keywords.txt', $sKeyword, FILE_APPEND);