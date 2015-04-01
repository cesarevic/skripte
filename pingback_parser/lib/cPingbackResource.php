<?php
class cPingbackResource
{
	/**
	 * Instance of cDb object
	 * 
	 * @var cDb
	 */
	protected $_db;
	
	protected $_kws = array();
	protected $_domains = array();
	protected $_pingbackUrl = array();
	protected $_urls = array();
	
	public function insertData($aUrls)
	{
		
//		$aUrls = include('mock.php');
		// Bitan je redosled 
		$this->_kws 		= $this->_insertKeywords($aUrls);
		$this->_domains 	= $this->_insertDomains($aUrls);
		$this->_pingbackUrl = $this->_insertPingbackUrls($aUrls);
		$this->_urls 		= $this->_insertUrls($aUrls);

		$this->_insertXmlData($aUrls);
		$this->_insertKwUrl($aUrls);
		
		unset($this->_kws, $this->_domains, $this->_pingbackUrl, $this->_urls);

//		echo "Memory: " . memory_get_usage(TRUE);
//		echo "Memory Peak: " . memory_get_peak_usage(TRUE);
		
//		echo "test";
		
	}
	
	protected function _insertKwUrl($aData)
	{
		$aKwUrl = array();
		$sSqlValues = "";
		
		foreach ($aData as $sDomainKey => $aDomainValue) 
			foreach ($aDomainValue['urls'] as $sUrlKey => $aUrlValue )
				foreach ($aUrlValue['kw'] as $sKw => $val)
				{
					$sArrayKey = "{$this->_kws[$sKw]['id']}-{$this->_urls[$sUrlKey]['id']}";
					$aKwUrl[$sArrayKey]['keyword_id'] = $this->_kws[$sKw]['id'];
					$aKwUrl[$sArrayKey]['url_id'] = $this->_urls[$sUrlKey]['id'];
				}
		if (!empty($aKwUrl))
		{
			foreach ($aKwUrl as $key => $value) 
				$sSqlValues = $sSqlValues . "({$value['keyword_id']}, {$value['url_id']}),";
	
			$sSqlValues = substr($sSqlValues, 0 , -1);
			$sSql = "INSERT IGNORE INTO keyword_url(keyword_id, url_id) VALUES $sSqlValues";
			$this->_db->query($sSql);
		}
	}
	
	protected function _insertXmlData($aData)
	{
		foreach ($aData as $sDomainKey => $aDomainValue) 
			foreach ($aDomainValue['urls'] as $sUrlKey => $aUrlValue )
			{
				if(empty($this->_urls[$sUrlKey]['id']))
				{
					var_dump($sUrlKey, $aUrlValue, $this->_urls[$sUrlKey]);
					die;
				}
				
				$sSql= "INSERT IGNORE INTO xml_data(url_id, xml) VALUES ({$this->_urls[$sUrlKey]['id']}, '{$this->_db->realEscapeString($aUrlValue['xml'])}')";
				$this->_db->query($sSql);
			}
	}
	
	protected function _insertUrls($aData)
	{
		$aUrls = array();
		$aDomainIds = array();
		$sSqlValues = "";
		
		foreach ($aData as $sDomainKey => $aDomainValue) 
			foreach ($aDomainValue['urls'] as $sUrlKey => $aUrlValue )
			{
				$aUrls[$sUrlKey]['domain_id'] = $this->_domains[$sDomainKey]['id'];
				$aUrls[$sUrlKey]['pingback_url_id'] = $this->_pingbackUrl[ $aUrlValue['pbLink'] ]['id'];
			}

		if(!empty($aUrls))
		{
			foreach ($aUrls as $key => $value)
			{ 
				$sSqlUrl = $this->_db->realEscapeString($key);
				$sSqlValues = $sSqlValues . "('$sSqlUrl', UNHEX(MD5('$sSqlUrl')), {$value['domain_id']}, {$value['pingback_url_id']}),";
				$aDomainIds[ $value['domain_id'] ] = NULL;				
			}
			
			$sSqlValues = substr($sSqlValues, 0 , -1);
			$sSql = "INSERT IGNORE INTO url(url, md5, domain_id, pingback_url_id) VALUES $sSqlValues";
			$this->_db->query($sSql);
			
			$aDomainIds = array_keys($aDomainIds);
			$sDomainIds = implode(', ', $aDomainIds);
			//@todo Jeftinije je da zahvatim sve pingback_url-ove sa odredjenim domain_id -om pa da ih
			// posle u php-u razvrstam ?? (jedan domen u 99% slucajeva ima jedan pingbackurl) Proveriti !!!
			$sSql = "SELECT id, domain_id, url FROM url WHERE domain_id IN ($sDomainIds)";
	
			$aResult = $this->_db->fetchAllFromQuery($sSql);
			
			//Ovo je ostavljeno ovako namerno, zbog ovoga sam nasao gresku sa case insensitive url-ovima
			foreach ($aResult as $aSqlUrls) 
				$aUrls[ $aSqlUrls['url'] ]['id'] = $aSqlUrls['id'];
		}			
		return $aUrls;
			
	}
	
	protected function _insertPingbackUrls($aUrls)
	{
		$aPingbackUrls = array();
		$aReturnPingbackUrls = array();
		$sSqlValues = "";
		$aDomainIds = array();

		foreach ($aUrls as $key => $value)
			foreach ($value['urls'] as $aUrl)
				$aPingbackUrls[$aUrl['pbLink']]['domain_id'] = $this->_domains[$key]['id'];
		

		if (!empty($aPingbackUrls))
		{
			foreach ($aPingbackUrls as $key => $value)
			{ 
				$sSqlUrl = $this->_db->realEscapeString($key);
				$sSqlValues = $sSqlValues . "('$sSqlUrl', UNHEX(MD5('$sSqlUrl')), {$value['domain_id']}),";
				$aDomainIds[ $value['domain_id'] ] = NULL;
			}

			if ($key == '')
			{
				echo "\n\n\n Prazan url !!!!!!!!!!!!!!!!!!!!! \n\n\n";
				var_dump($aPingbackUrls);
				die();
			}
							
			$sSqlValues = substr($sSqlValues, 0 , -1);
	
			$sSql = "INSERT IGNORE INTO pingback_url(url, md5, domain_id) VALUES $sSqlValues";
			$this->_db->query($sSql);

			$aDomainIds = array_keys($aDomainIds);
			$sDomainIds = implode(', ', $aDomainIds);

			//@todo Jeftinije je da zahvatim sve pingback_url-ove sa odredjenim domain_id -om pa da ih
			// posle u php-u razvrstam ?? (jedan domen u 99% slucajeva ima jedan pingbackurl) Proveriti !!!
			$sSql = "SELECT id, domain_id, url FROM pingback_url WHERE domain_id IN ($sDomainIds)";
	
			$aResult = $this->_db->fetchAllFromQuery($sSql);
			
			foreach ($aResult as $aSqlPingback) 
				$aReturnPingbackUrls[ $aSqlPingback['url'] ]['id'] = $aSqlPingback['id']; 
		}
		return $aReturnPingbackUrls;
	}
	
	protected function _insertDomains($aUrls)
	{
		$aDomains = array();
		$sSqlValues = "";
		
		foreach ($aUrls as $aDomain) 
			$sSqlValues = $sSqlValues . "('" . $this->_db->realEscapeString($aDomain['domain']) . 
				"', {$aDomain['active']}, {$aDomain['parseErrorCount']}),";							

		$sSqlValues = substr($sSqlValues, 0 , -1);
		
		$sSql = "
		INSERT INTO
			domain(domain, active, parse_error_count)
		VALUES
			$sSqlValues
		ON DUPLICATE KEY UPDATE
			active = VALUES(active), parse_error_count = VALUES(parse_error_count)";
					
		$this->_db->query($sSql);			
		
		$aSqlDomains = array_keys($aUrls);
		$this->_db->realEscapeArray($aSqlDomains);
		
		$sDomains = "'" . implode('\', \'', $aSqlDomains) . "'";
		$sSql = "SELECT id, domain FROM domain WHERE domain IN ($sDomains)";
		$aResult = $this->_db->fetchAllFromQuery($sSql);

		foreach ($aResult as $key => $value) 
			$aDomains[ $value['domain'] ]['id'] = $value['id'];
		
		return $aDomains;
	
	}
	
	protected function _insertKeywords($aUrls)
	{
		$aKw = array();

		foreach ($aUrls as $key => $value) 
			foreach ($value['urls'] as $aUrl) 
				$aKw = array_merge($aKw, $aUrl['kw']);

		if(!empty($aKw))
		{
			$aSqlKw = array_keys($aKw);
			$this->_db->realEscapeArray($aSqlKw);				

			$sKw = "('" . implode('\'),(\'', $aSqlKw) . "')";
	
			$sSql = "INSERT IGNORE INTO keyword(keyword) VALUES $sKw";
			$this->_db->query($sSql);
			
			$sKw = "'" . implode('\', \'', $aSqlKw) . "'";
			$sSql = "SELECT id, keyword FROM keyword WHERE keyword IN ($sKw)";
			
			$aResult = $this->_db->fetchAllFromQuery($sSql);
	
			foreach ($aResult as $key => $value) 
				$aKw[ $value['keyword'] ]['id'] = $value['id'];
		}
		return $aKw;
	}

	public function getPingbackUrls($iDomainId)
 	{
		$sSql = "SELECT url FROM pingback_url WHERE domain_id = $iDomainId";
		$aResult = $this->_db->fetchAllFromQuery($sSql);

		return $aResult;
 	} 

 	public function getUrls($iDomainId)
 	{
		$aUrlData = array();
		$sSql = "SELECT
				  url.url AS url, 
				  pingback_url.url AS pingback_url
				FROM
				  url 
				JOIN pingback_url ON (url.pingback_url_id = pingback_url.id) AND
				(url.domain_id = pingback_url.domain_id)
				WHERE
				  url.domain_id = $iDomainId";
		
		$aResult = $this->_db->fetchAllFromQuery($sSql);

		foreach ($aResult as $aUrl) 
			$aUrlData[ $aUrl['url'] ] = $aUrl['pingback_url']; 
		
		return $aUrlData;
 	} 
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	
 	public function xxxxgetUrls($iDomainId, $aUrls)
 	{
		$aUrlData = array();
 		$aSqlUrls = array_keys($aUrls);
		$this->_db->realEscapeArray($aSqlUrls);
		
		$sUrls = "'" . implode('\', \'', $aSqlUrls) . "'";

		$sSql = "SELECT
				  url.url AS url, 
				  pingback_url.url AS pingback_url
				FROM
				  url JOIN pingback_url ON (url.pingback_url_id = pingback_url.id)
				WHERE
				  url.url IN ($sUrls) AND url.domain_id = $iDomainId";
		
		$aResult = $this->_db->fetchAllFromQuery($sSql);

		foreach ($aResult as $aUrl) 
			$aUrlData[ $aUrl['url'] ]['pingback_url'] = $aUrl['pingback_url']; 
		
		return $aUrlData;
 	} 
	
	public function getDomain($aDomains)
	{
		$aReturn = array();
		$this->_db->realEscapeArray($aDomains);
		$sDomains = "'" . implode('\', \'',$aDomains) . "'";
		$sSql = "SELECT id, domain, active, parse_error_count FROM domain WHERE domain IN ($sDomains)";
		$aResult = $this->_db->fetchAllFromQuery($sSql);
		
		foreach ($aResult as $aDomain) 
		{
			$iUCount = 0; 
			$iPUCount = 0;
			
			$iPUCount = (int)$this->_db->fetchOneFromQuery("SELECT COUNT(id) FROM pingback_url WHERE domain_id = {$aDomain['id']}");
			if($iPUCount == 1)
				$iUCount = (int)$this->_db->fetchOneFromQuery("SELECT COUNT(id) FROM url WHERE domain_id = {$aDomain['id']}");
			
			$aReturn[ $aDomain['domain'] ] = array(
				'id' => $aDomain['id'],
				'domain' => $aDomain['domain'],
				'active' => $aDomain['active'],
				'urlCount' => $iUCount,
				'pingbackUrlCount' => $iPUCount,
				'parseErrorCount' => $aDomain['parse_error_count'],
			); 
		}
		
		return $aReturn;		
	}
	
	public function __construct(cDb $oDb)
	{
		$this->_db = $oDb;
	}
}
