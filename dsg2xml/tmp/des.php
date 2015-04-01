<?php
function structData(array $aData)
{
	$aReturnData = array();
	
	// Unserialize all articles
	foreach($aData as $aResult)
	{
		$sKeyword = $aResult['keyword'];
		$aSeResult = array();
		foreach(array(1,2,3) as $iSe)
		{
			$aSeUnserializedData = unserialize($aResult['se' . $iSe]);
			if(is_array($aSeUnserializedData))
			{
				$aSeResult = array_merge($aSeResult, $aSeUnserializedData);
				foreach ($aSeResult as $key => &$aSeData)
					foreach ($aSeData as &$aSeDataElement)
						if(!empty($aSeDataElement))
							$aSeDataElement = cleanString($aSeDataElement);
			}
		}
		$sArticleCode = str_replace(" ", "-", $sKeyword);
		$aReturnData[$sArticleCode] = array(
			'se_results' => $aSeResult,
			'title' => $sKeyword, 
			'description' => buildDescription($aSeResult),
			'keywords' => buildKws($sKeyword)
		);
	}
		
	return $aReturnData;
}


function buildDescription(array $sSeResults)
{
	$aStat = array();
	
	// Calculate results
	foreach($sSeResults as $key => $aSeResult)
	{
		$sDescription = $aSeResult['description'];
		$aStat[$key] = array(
			'nondef' => 0,
			'upperascii' => 0,
		);
		
		$aStat[$key]['comma'] = substr_count($sDescription, ",");
		$aStat[$key]['dot'] = substr_count($sDescription, ".");
		$aStat[$key]['sentence'] = substr_count($sDescription, ". ");
		
		$iDescLen = strlen($sDescription);
		for($i = 0; $i<$iDescLen; $i++)
		{
			if(!preg_match('/[a-zA-Z0-9 ]/', $sDescription[$i]))
				$aStat[$key]['nondef']++;
			if(preg_match('/[A-Z]/', $sDescription[$i]))
				$aStat[$key]['upperascii']++;
		}
	}

	// Try to find first minimal request	
	$aMatch = 0;
	foreach($sSeResults as $key => $aSeResult)
	{
		if($aStat[$key]['sentence'] >= 1 && substr($aSeResult['description'], -1) == ".")
			$aMatch = $key;
	}

	// Try to find better match
	foreach($sSeResults as $key => $aSeResult)
	{
		$calc = 0;
		if(!($aStat[$key]['sentence'] >= 1 && substr($aSeResult['description'], -1) == "." )) // con from minimal match
			break;
		
		$aStat[$key]['comma'] <=  $aStat[$aMatch]['comma']            ? $calc++ : $calc--;
		$aStat[$key]['dot'] <=  $aStat[$aMatch]['dot']                ? $calc++ : $calc--;
		$aStat[$key]['sentence'] >=  $aStat[$aMatch]['sentence']      ? $calc++ : $calc--;    
		$aStat[$key]['nondef'] <=  $aStat[$aMatch]['nondef']          ? $calc++ : $calc--;
		$aStat[$key]['upperascii'] <=  $aStat[$aMatch]['upperascii']  ? $calc++ : $calc--;
		if($calc > 0)
			$aMatch = $key;
	}
	
	return $sSeResults[$aMatch]['description'];
}