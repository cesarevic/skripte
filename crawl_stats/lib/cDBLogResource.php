<?php
/**
 * cDBLogResource class
 * Copyright (c) 2005-2011 PKM-Inc
 */
class cDBLogResource
{
	/**
	 * Instance of cDb class  
	 *
	 * @var cDb
	 */
	protected $_db;

	public $_ids = array();
	
	public function writeData($aData)
	{
		//Redosled je bitan
		$this->_ids['domain']	= $this->updateDomain($aData);
		$this->_ids['site']		= $this->updateSite($aData);		
		$this->_ids['type']		= $this->updateType($aData);
		$this->_ids['url']		= $this->updateUrl($aData);

		$this->_ids['bot']		= $this->updateBot($aData); 
		$this->_ids['botua']	= $this->updateBotUA($aData);

		$this->updateLog($aData);
	}
	
	public function updateDomain($aData)
	{
		//Build data array
		foreach ($aData as $aLogData) 
		{
			$sValue = $aLogData['domain'];
			$aReturnData[$sValue] = array('id' => NULL);
		}
		
		//Select old
		foreach($aReturnData as $sFirstKey => $aFirstValue) 
		{
			$sValue = $this->_db->realEscapeString($sFirstKey);
			$aSelectValues[] = "'$sValue'";
		}
		
		$sValues = implode(', ', $aSelectValues);	
		
		$sSql = "SELECT id, domain FROM domain WHERE domain IN ($sValues)";
		$aResult = $this->_db->fetchAllFromQuery($sSql);
		
		foreach($aResult as $value)
		{ 
			$sValue = $value['domain'];
			$aReturnData[$sValue]['id'] = $value['id'];
		} 
		
		//Find new
		foreach($aReturnData as $sFirstKey => $aFirstValue)
			if(is_null($aFirstValue['id']))
			{
				$sValue = $this->_db->realEscapeString($sFirstKey);
 				$aValues[] = array('value' => $sValue);
			}

		//Insert New
		$aSelectValues = array();
		if(!empty($aValues))
		{
			foreach($aValues as $value)
			{
				$sValue = "'{$value['value']}'";
				$aInsertValues[] = "($sValue)";
			}
				
			$sValues = implode(', ', $aInsertValues);		

			$sSql = "INSERT INTO domain (domain) VALUES $sValues";
			$this->_db->query($sSql);

			//Select new
			foreach($aValues as $value)
			{ 
				$sValue = "'{$value['value']}'";
				$aSelectValues[] = $sValue;
			}
			
			$sValues = implode(', ', $aSelectValues);		
				
			$sSql = "SELECT id, domain FROM domain WHERE domain IN ($sValues)";
			$aResult = $this->_db->fetchAllFromQuery($sSql);
			
			foreach($aResult as $value)
			{ 
				$sValue = $value['domain'];
				$aReturnData[$sValue]['id'] = $value['id']; 
			}
		}		
		return $aReturnData;				
	}
	
	public function updateSite($aData)
	{
		//Build return data
		foreach ($aData as $aLogData) 
		{
			$sValue = $aLogData['site'];
			$sRefValue = $aLogData['domain'];
			$iRefId = $this->_ids['domain'][$sRefValue]['id'];
			$aRefIds[$iRefId] = NULL;
			
			$aReturnData[$sValue][$iRefId] = array('id' => NULL, 'domain' => $sRefValue, 'domain_id' => $iRefId);
		}

		//Select old
		foreach($aReturnData as $sFirstKey => $aFirstValue) 
		{
			$sValue = $this->_db->realEscapeString($sFirstKey);
			$sValue = "'$sValue'";
			$aSelectValues[] = $sValue;
		}
							
		$sValues = implode(', ', $aSelectValues);		
		$sRefIds = implode(', ', array_keys($aRefIds));
						
		$sSql = "SELECT id, domain_id, site FROM site WHERE site IN ($sValues) AND domain_id IN ($sRefIds)";
		$aResult = $this->_db->fetchAllFromQuery($sSql);

		foreach($aResult as $value)
		{ 
			$iId = $value['id'];
			$sValue = $value['site'];
			$iRefId = $value['domain_id'];

			if(array_key_exists($iRefId, $aReturnData[$sValue]))
				$aReturnData[$sValue][$iRefId]['id'] = $iId; 
		} 
		
		//Find new
		foreach($aReturnData as $sFirstKey => $aFirstValue) 
			foreach($aFirstValue as $sSecondKey => $aSecondValue)
				if(is_null($aSecondValue['id']))
				{
					$sValue = $this->_db->realEscapeString($sFirstKey);
					$iRefId = $aSecondValue['domain_id'];	
					$aValues[] = array('ref_id' => $iRefId, 'value' => $sValue);
				}

		$aSelectValues = array();
		if(!empty($aValues))
		{
			//Insert new
			foreach($aValues as $value)
			{ 
				$iRefId = $value['ref_id'];
				$sValue = "'{$value['value']}'";
				
				$aInsertValues[] = "($iRefId, $sValue)";
			}
				
			$sValues = implode(', ', $aInsertValues);		
			
			$sSql = "INSERT INTO site (domain_id, site) VALUES $sValues";
			$this->_db->query($sSql);
		
			//Select new
			foreach($aValues as $value)
			{ 
				$sValue = "'{$value['value']}'";
				$aSelectValues[] = $sValue;

				$iRefId = $value['ref_id'];
				$aSelectRefIds[$iRefId] = NULL;
			}
			
			$sValues = implode(', ', $aSelectValues);			
			$sRefIds = implode(', ', array_keys($aSelectRefIds));	
			
			$sSql = "SELECT id, domain_id, site FROM site WHERE site IN ($sValues) AND domain_id IN ($sRefIds)";
			$aResult = $this->_db->fetchAllFromQuery($sSql);
				
			foreach($aResult as $value)
			{ 
				$iId = $value['id'];
				$iRefId = $value['domain_id'];
				$sValue = $value['site'];

				if(array_key_exists($iRefId, $aReturnData[$sValue]))
					$aReturnData[$sValue][$iRefId]['id'] = $iId; 
			}		
		}		
		
		//Build return array
		foreach($aReturnData as $sFirstKey => $aFirstValue) 
			foreach($aFirstValue as $iFirstRefId => $aSecondValue)
			{
				$iId = $aSecondValue['id'];
				$iRefId = $aSecondValue['domain_id'];
				$sRefValue =  $aSecondValue['domain'];

				$aReturn[$sRefValue][$sFirstKey] = array('id' => $iId, 'domain_id' => $iRefId);
			}		
		
		return $aReturn;			
	}

	public function updateType($aData)
	{
		//Build data array
		foreach ($aData as $aLogData) 
		{
			$sValue = $aLogData['type'];
			$aReturnData[$sValue] = array('id' => NULL);
		}
		//Select old
		foreach($aReturnData as $sFirstKey => $aFirstValue) 
			{
				$sValue = $this->_db->realEscapeString($sFirstKey);
				$aSelectValues[] = "'$sValue'";
			}
		
		$sValues = implode(', ', $aSelectValues);	
		
		$sSql = "SELECT id, page_type FROM page_type WHERE page_type IN ($sValues)";
		$aResult = $this->_db->fetchAllFromQuery($sSql);
		
		foreach($aResult as $value)
		{ 
			$sValue = $value['page_type'];
			$aReturnData[$sValue]['id'] = $value['id'];
		} 
		
		//Find new
		$aValues = array();	
		foreach($aReturnData as $sFirstKey => $aFirstValue)
			if(is_null($aFirstValue['id']))
			{
				$sValue = $this->_db->realEscapeString($sFirstKey);
 				$aValues[] = array('value' => $sValue);
			}

		//Insert New
		$aSelectValues = array();
		if(!empty($aValues))
		{
			foreach($aValues as $value)
			{
				$sValue = "'{$value['value']}'";
				$aInsertValues[] = "($sValue)";
			}
				
			$sValues = implode(', ', $aInsertValues);		

			$sSql = "INSERT INTO page_type (page_type) VALUES $sValues";
			$this->_db->query($sSql);

			//Select new
			foreach($aValues as $value)
			{ 
				$sValue = "'{$value['value']}'";
				$aSelectValues[] = $sValue;
			}
			
			$sValues = implode(', ', $aSelectValues);		
				
			$sSql = "SELECT id, page_type FROM page_type WHERE page_type IN ($sValues)";
			$aResult = $this->_db->fetchAllFromQuery($sSql);
			
			foreach($aResult as $value)
			{ 
				$sValue = $value['page_type'];
				$aReturnData[$sValue]['id'] = $value['id']; 
			}
		}		
		return $aReturnData;				
	}
	
	public function updateUrl($aData)
	{
		//Build data array	
		foreach($aData as $aLogData) 
		{
			$sDomain	= $aLogData['domain'];
			$sType 		= $aLogData['type'];
			$sSite 		= $aLogData['site'];
			$sUrl 		= $aLogData['url'];

			$sUrlMD5	= strtoupper(md5($sUrl));
			
			$iSiteId	= $this->_ids['site'][$sDomain][$sSite]['id'];
			$iTypeId	= $this->_ids['type'][$sType]['id'];
			
//			if(strlen($sUrl) > 128)
//				$sUrl = substr($sUrl, 0, 128);
			
			$aReturnData[$sUrlMD5][$iSiteId] = array(			
												'id' 		=> NULL, 
												'site_id' 	=> $iSiteId,
												'site'		=> $sSite, 
												'page_type' => $sType, 
												'ptype_id' 	=> $iTypeId,
												'domain'	=> $sDomain,
												'url'		=> $sUrl
											);
			$aRefIds[$iSiteId] = NULL;
		}

		//Select old
		foreach($aReturnData as $sFirstKey => $aFirstValue) 
		{
//			$sValue = $this->_db->realEscapeString($sFirstKey);
			$aSelectValues[] = "UNHEX('$sFirstKey')";
		}
							
		$sValues = implode(', ', $aSelectValues);		
		$sRefIds = implode(', ', array_keys($aRefIds));
		
		$sSql = "SELECT id, site_id, url, HEX(md5) AS md5 FROM url WHERE md5 IN ($sValues) AND site_id IN ($sRefIds)";
		$aResult = $this->_db->fetchAllFromQuery($sSql);

		foreach($aResult as $value)
		{ 
			$iId = $value['id'];
			$iRefId = $value['site_id'];
			$sValue = $value['md5'];
			
			if(array_key_exists($iRefId, $aReturnData[$sValue]))
				$aReturnData[$sValue][$iRefId]['id'] = $iId; 
		}		
		
		//Find new
		foreach($aReturnData as $sFirstKey => $aFirstValue) 
			foreach($aFirstValue as $iFirstRefId => $aSecondValue)
				if(is_null($aSecondValue['id']))
				{
					$sValue = $this->_db->realEscapeString($aSecondValue['url']);
					$sValue1 = $sFirstKey;

					$iRefId = $aSecondValue['site_id'];
					$iRefId1 = $aSecondValue['ptype_id'];
					$aValues[] = array('ref_id' => $iRefId, 'ref_id1'=> $iRefId1, 'value' => $sValue, 'value1' => "UNHEX('$sValue1')");
				}

		//Insert new				
		$aSelectValues = array();
		if(!empty($aValues))
		{
			foreach($aValues as $value)
			{ 
				$iRefId = $value['ref_id'];
				$iRefId1 = $value['ref_id1'];
				$sValue = "'{$value['value']}'";
				$sValue1 = $value['value1'];
				
				$aInsertValues[] = "($iRefId, $iRefId1, $sValue, $sValue1)";
			}
				
			$sValues = implode(', ', $aInsertValues);		

			$sSql = "INSERT INTO url (site_id, ptype_id, url, md5) VALUES $sValues";
			$this->_db->query($sSql);
		
			//Select new
			foreach($aValues as $value)
			{ 
				$sValue = $value['value1'];
				$iRefId = $value['ref_id'];
				
				$aSelectValues[] = $sValue;
				$aSelectRefIds[$iRefId] = NULL;
			}
			
			$sValues = implode(', ', $aSelectValues);			
			$sRefIds = implode(', ', array_keys($aSelectRefIds));	
			
			$sSql = "SELECT id, site_id, url, HEX(md5) AS md5 FROM url WHERE md5 IN ($sValues) AND site_id IN ($sRefIds)";
			
			$aResult = $this->_db->fetchAllFromQuery($sSql);
				
			foreach($aResult as $value)
			{ 
				$iId = $value['id'];
				$iRefId = $value['site_id'];
				$sValue = $value['md5'];

				if(!isset($aReturnData[$sValue]))
				{
					echo "test";
				}
				
				
				
				if(array_key_exists($iRefId, $aReturnData[$sValue]))
					$aReturnData[$sValue][$iRefId]['id'] = $iId; 
			}		
		}		
		
		//Build return array
		foreach($aReturnData as $sFirstKey => $aFirstValue) 
			foreach($aFirstValue as $iFirstRefId => $aSecondValue)
			{
				$iId = $aSecondValue['id'];
				if(!isset($aSecondValue['site_id']))
				{
					echo "test";
				}
				$iRefId = $aSecondValue['site_id'];
				$iRefId1 = $aSecondValue['ptype_id'];

				$sRefValue = $aSecondValue['site'];
				$sRefValue1 = $aSecondValue['domain'];
				$Value = $aSecondValue['url'];
				
				$aReturn[$sRefValue1][$sRefValue][$sFirstKey] = array(
																	'id' => $iId, 
																	'site_id' => $iRefId, 
																	'ptype_id' => $iRefId1, 
																	'url'=> $Value
																);
			}		

		return $aReturn;			
	}

	public function updateBot($aData)
	{
		//Build data array
		foreach ($aData as $aLogData) 
		{
			$sValue = $aLogData['bot'];
			$aReturnData[$sValue] = array('id' => NULL);
		}

		//Glupa fora da bi google imao id 1
		ksort($aReturnData);
		
		//Select old
		foreach($aReturnData as $sFirstKey => $aFirstValue) 
		{
			$sValue = $this->_db->realEscapeString($sFirstKey);
			$aSelectValues[] = "'$sValue'";
		}
		
		$sValues = implode(', ', $aSelectValues);	
		
		$sSql = "SELECT id, bot FROM bot WHERE bot IN ($sValues)";
		$aResult = $this->_db->fetchAllFromQuery($sSql);
		
		foreach($aResult as $value)
		{ 
			$sValue = $value['bot'];
			$aReturnData[$sValue]['id'] = $value['id'];
		} 
		
		//Find new
		foreach($aReturnData as $sFirstKey => $aFirstValue)
			if(is_null($aFirstValue['id']))
			{
				$sValue = $this->_db->realEscapeString($sFirstKey);
 				$aValues[] = array('value' => $sValue);
			}

		//Insert New
		$aSelectValues = array();
		if(!empty($aValues))
		{
			foreach($aValues as $value)
			{
				$sValue = "'{$value['value']}'";
				$aInsertValues[] = "($sValue)";
			}
				
			$sValues = implode(', ', $aInsertValues);		

			$sSql = "INSERT INTO bot (bot) VALUES $sValues";
			$this->_db->query($sSql);

			//Select new
			foreach($aValues as $value)
			{ 
				$sValue = "'{$value['value']}'";
				$aSelectValues[] = $sValue;
			}
			
			$sValues = implode(', ', $aSelectValues);		
				
			$sSql = "SELECT id, bot FROM bot WHERE bot IN ($sValues)";
			$aResult = $this->_db->fetchAllFromQuery($sSql);
			
			foreach($aResult as $value)
			{ 
				$sValue = $value['bot'];
				$aReturnData[$sValue]['id'] = $value['id']; 
			}
		}		
		return $aReturnData;				
	}
	
	public function updateBotUA($aData)
	{
		//Build return data
		foreach ($aData as $aLogData) 
		{
			$sValue = $aLogData['botua'];
			$sRefValue = $aLogData['bot'];
			$iRefId = $this->_ids['bot'][$sRefValue]['id'];
			$aRefIds[$iRefId] = NULL;
			
			$aReturnData[$sValue][$iRefId] = array('id' => NULL, 'bot' => $sRefValue, 'bot_id' => $iRefId);
		}

		//Glupa fora da bi google imao id 1
		ksort($aReturnData);

		//Select old
		foreach($aReturnData as $sFirstKey => $aFirstValue) 
		{
			$sValue = $this->_db->realEscapeString($sFirstKey);
			$sValue = "'$sValue'";
			$aSelectValues[] = $sValue;
		}
							
		$sValues = implode(', ', $aSelectValues);		
		$sRefIds = implode(', ', array_keys($aRefIds));
						
		$sSql = "SELECT id, bot_id, bot_ua FROM bot_ua WHERE bot_ua IN ($sValues) AND bot_id IN ($sRefIds)";
		$aResult = $this->_db->fetchAllFromQuery($sSql);

		foreach($aResult as $value)
		{ 
			$iId = $value['id'];
			$sValue = $value['bot_ua'];
			$iRefId = $value['bot_id'];

			if(array_key_exists($iRefId, $aReturnData[$sValue]))
				$aReturnData[$sValue][$iRefId]['id'] = $iId; 
		} 
		
		//Find new
		foreach($aReturnData as $sFirstKey => $aFirstValue) 
			foreach($aFirstValue as $sSecondKey => $aSecondValue)
				if(is_null($aSecondValue['id']))
				{
					$sValue = $this->_db->realEscapeString($sFirstKey);
					$iRefId = $aSecondValue['bot_id'];	
					$aValues[] = array('ref_id' => $iRefId, 'value' => $sValue);
				}

		$aSelectValues = array();
		if(!empty($aValues))
		{
			//Insert new
			foreach($aValues as $value)
			{ 
				$iRefId = $value['ref_id'];
				$sValue = "'{$value['value']}'";
				
				$aInsertValues[] = "($iRefId, $sValue)";
			}
				
			$sValues = implode(', ', $aInsertValues);		
			
			$sSql = "INSERT INTO bot_ua (bot_id, bot_ua) VALUES $sValues";
			$this->_db->query($sSql);
		
			//Select new
			foreach($aValues as $value)
			{ 
				$sValue = "'{$value['value']}'";
				$aSelectValues[] = $sValue;

				$iRefId = $value['ref_id'];
				$aSelectRefIds[$iRefId] = NULL;
			}
			
			$sValues = implode(', ', $aSelectValues);			
			$sRefIds = implode(', ', array_keys($aSelectRefIds));	
			
			$sSql = "SELECT id, bot_id, bot_ua FROM bot_ua WHERE bot_ua IN ($sValues) AND bot_id IN ($sRefIds)";
			$aResult = $this->_db->fetchAllFromQuery($sSql);
				
			foreach($aResult as $value)
			{ 
				$iId = $value['id'];
				$iRefId = $value['bot_id'];
				$sValue = $value['bot_ua'];

				if(array_key_exists($iRefId, $aReturnData[$sValue]))
					$aReturnData[$sValue][$iRefId]['id'] = $iId; 
			}		
		}		
		
		//Build return array
		foreach($aReturnData as $sFirstKey => $aFirstValue) 
			foreach($aFirstValue as $iFirstRefId => $aSecondValue)
			{
				$iId = $aSecondValue['id'];
				$iRefId = $aSecondValue['bot_id'];
				$sRefValue = $aSecondValue['bot'];

				$aReturn[$sRefValue][$sFirstKey] = array('id' => $iId, 'bot_id' => $iRefId);
			}		
		
		return $aReturn;			
	}
	
	public function updateLog($aData)
	{
		//Build data array	
		foreach($aData as $aLogData) 
		{
			$sDomain	= $aLogData['domain'];
			$sSite 		= $aLogData['site'];
			$sUrl 		= $aLogData['url'];
			$sBot		= $aLogData['bot'];
			$sBotUA		= $aLogData['botua'];
			$sUrlMD5	= strtoupper(md5($sUrl));
			
//			if(strlen($sUrl) > 128)
//				$sUrl = substr($sUrl, 0, 128);
						
			$iUrlId = $this->_ids['url'][$sDomain][$sSite][$sUrlMD5]['id'];
			$iBotUAId = $this->_ids['botua'][$sBot][$sBotUA]['id'];
			
			$iTimestamp = $aLogData['timestamp'];
			
			$aReturnData[$iTimestamp][$iUrlId][$iBotUAId] = array(			
																'id' 		=> NULL, 
																'url_id' 	=> $iUrlId,
																'botua_id'	=> $iBotUAId,
															); 
			
			$aRefIds[0][$iUrlId] = NULL;
			$aRefIds[1][$iBotUAId] = NULL;
		}

		//Select old
		foreach($aReturnData as $sFirstKey => $aFirstValue) 
		{
			$aSelectValues[] = $sFirstKey;
		}
							
		$sValues  = implode(', ', $aSelectValues);		
		$sRefIds  = implode(', ', array_keys($aRefIds[0]));
		$sRefIds1 = implode(', ', array_keys($aRefIds[1]));
		//Ovaj debilizam +0 je ovde zbog mysql bug-a, query optimizer se zaglupi u 'statistics' stanju kada ima previse id-ova u IN()
		//Jos jedan dokaz kakvo je djubre mysql.
		//http://bugs.mysql.com/bug.php?id=20932
		$sSql = "SELECT id, url_id, botua_id, timestamp FROM log WHERE url_id+0 IN ($sRefIds) AND timestamp+0 IN ($sValues) AND botua_id+0 IN ($sRefIds1)";
		$aResult = $this->_db->fetchAllFromQuery($sSql);

		foreach($aResult as $value)
		{ 
			$iId = $value['id'];
			$iRefId = $value['url_id'];
			$iRefId1 = $value['botua_id'];
			$sValue = $value['timestamp'];
			
			$aReturnData[$sValue][$iRefId][$iRefId1]['id'] = $iId; 
		}		
		
		//Find new
		foreach($aReturnData as $sFirstKey => $aFirstValue) 
			foreach($aFirstValue as $iFirstRefId => $aSecondValue)
				foreach($aSecondValue as $iSecondRefId => $aThirdValue)
					if(is_null($aThirdValue['id']))
					{
						$sValue = $sFirstKey;
						$iRefId = $aThirdValue['url_id'];
						$iRefId1 = $aThirdValue['botua_id'];
						$aValues[] = array('ref_id' => $iRefId, 'ref_id1'=> $iRefId1, 'value' => $sValue);
					}

		//Insert new				
		$aSelectValues = array();
		if(!empty($aValues))
		{
			foreach($aValues as $value)
			{ 
				$iRefId = $value['ref_id'];
				$iRefId1 = $value['ref_id1'];
				$sValue = $value['value'];
				
				$aInsertValues[] = "($iRefId, $iRefId1, $sValue)";
			}
				
			$sValues = implode(', ', $aInsertValues);		

			$sSql = "INSERT INTO log (url_id, botua_id, timestamp) VALUES $sValues";
			$this->_db->query($sSql);
		}
/*		
			//Select new
			foreach($aValues as $value)
			{ 
				$sValue = $value['value'];
				$iRefId = $value['ref_id'];
				$iRefId1 = $value['ref_id1'];
				
				$aSelectValues[] = $sValue;
				$aSelectRefIds[0][$iRefId] = NULL;
				$aSelectRefIds[1][$iRefId1] = NULL;
			}
			
			$sValues = implode(', ', $aSelectValues);			
			$sRefIds = implode(', ', array_keys($aSelectRefIds[0]));	
			$sRefIds1 = implode(', ', array_keys($aSelectRefIds[1]));
			
			$sSql = "SELECT id, url_id, botua_id, timestamp FROM log WHERE timestamp IN ($sValues) AND url_id IN ($sRefIds) AND botua_id IN ($sRefIds1)";
			$aResult = $this->_db->fetchAllFromQuery($sSql);
				
			foreach($aResult as $value)
			{ 
				$iId = $value['id'];
				$iRefId = $value['url_id'];
				$iRefId1 = $value['botua_id'];
				$sValue = $value['timestamp'];
				
				$aReturnData[$sValue][$iRefId][$iRefId1]['id'] = $iId; 
			}		
		}		
		
		//Build return array
		foreach($aReturnData as $sFirstKey => $aFirstValue) 
			foreach($aFirstValue as $iFirstRefId => $aSecondValue)
				foreach($aSecondValue as $iSecondRefId => $aThirdValue)
				{
					$iRefId = $aThirdValue['url_id'];
					$iRefId1 = $aThirdValue['botua_id'];
					$iId = $aThirdValue['id'];

					$sRefValue = $aSecondValue['site'];
					$sRefValue1 = $aSecondValue['domain'];
					$aReturn[$sRefValue1][$sRefValue][$sFirstKey] = array('id' => $iId, 'site_id' => $iRefId, 'ptype_id' => $iRefId1);
				}		

		return $aReturn;			
*/	
	}
		
	public function __construct(cDb $oDb)
	{
		$this->_db = $oDb;
	}
}