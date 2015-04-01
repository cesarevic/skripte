<?php


$handle = fopen("random_list.txt", "r");
if ($handle) 
{
	while (!feof($handle)) 
	{
		if($sLine = fgets($handle))
		{
			$sFilename = basename($sLine);
			$sMD5 = substr($sFilename,0,strpos($sFilename, "."));
			$aDir = storageDir($sMD5, 16, 16);
			$sL1 = $aDir['L1'];
			$sL2 = $aDir['L2'];
//			isset($aTest[$sDir]) ? $aTest[$sDir]++ : $aTest[$sDir] = 1;

			isset($aTest[$sL1]) ? $aTest[$sL1]['count']++ : $aTest[$sL1]['count'] = 1;
			isset($aTest[$sL1][$sL2]) ? $aTest[$sL1][$sL2]++ : $aTest[$sL1][$sL2] = 1;
			
			
		}
	}
	fclose ($handle);
//	arsort($aTest);
	ksort($aTest);
	
	foreach ($aTest as &$aTest1)
	{
		ksort($aTest1);
		unset($aTest1);
	}
	
	print_r($aTest);
} 


function storageDir($sMD5, $iConfigL1 = 16, $iConfigL2 = 32)
{
//	$iConfigL1 i $iConfigL2 moraju da budu veci od 16
//	do 128k (16,16) do 2M (16,256) do 32M (256,256)
	if($sMD5 !== "")
	{
//		Uzimam prava dva slova
		$sL1 = substr($sMD5,0,2);
		$sL2 = substr($sMD5,2,2);		
		
//		Prevodim ih u dec integer 
		$iL1 = hexdec($sL1);
		$iL2 = hexdec($sL2);


		if ($iL1 < $iConfigL1)
			$sL1Dir = sprintf("%02x", $iL1 ); //sprintf umesto dechex zbog padinga (15->0F)
		else 
		{
//			Uzimamo samo prvo slovo
			$sL1 = substr($sL1,0,1);
			$iL1 = hexdec($sL1);	
			$sL1Dir = sprintf("%02x", $iL1);

//			Menjamo da L2 bude 2. i 3. slovo
			$sL2 = substr($sMD5,1,2);
			$iL2 = hexdec($sL2);
		}
	
		if ($iL2 < $iConfigL2)
			$sL2Dir = sprintf("%02x", $iL2);
		else 
		{
//			Uzimamo samo prvo slovo
			$sL2 = substr($sL2,0,1); 
			$iL2 = hexdec($sL2);	
			$sL2Dir = sprintf("%02x", $iL2);
		}
		
//		return "/{$sL1Dir}/{$sL2Dir}/"; 
		$aDir = array("L1" => $sL1Dir, "L2" => $sL2Dir);
		return $aDir; 
	}
	else 
		return FALSE;			
}





