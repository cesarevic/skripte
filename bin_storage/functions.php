<?php



function build_storage($sDestDir = ".", $iPerm = 0777, $iL1 = 16, $iL2 = 16)
{
	for ($i = 0; $i < $iL1; $i++) 
	{
		for ($j = 0; $j < $iL2; $j++) 
		{
//			$sL1Dir = dechex($i);	
			$sL1Dir = sprintf("%02x", $i);
			
//			$sL2Dir = dechex($j);	
			$sL2Dir = sprintf("%02x", $j);
			
			$sPath = "$sDestDir/{$sL1Dir}/{$sL2Dir}";
//			echo $sPath . "\n"; 
			mkdir($sPath, $iPerm, true);
//			Patch: effective permission depends on umask, 
//			only the level 2 dir (/00/00) will be set to supplied permission
			chmod($sPath, $iPerm);
		}
	}
	
	
	
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
		
		return "/{$sL1Dir}/{$sL2Dir}/"; 
//		$aDir = array("L1" => $sL1Dir, "L2" => $sL2Dir);
//		return $aDir; 
	}
	else 
		return FALSE;			
}
