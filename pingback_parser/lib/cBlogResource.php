<?php
class cBlogResource
{
	/**
	 * Script lock time 
	 * 
	 * @var integer
	 */
	public $lockTime = 900 ;
	
	/**
	 * Broj rezultata koliko ce resurs da vraca
	 * 
	 * @var integer
	 */
	public $resultReturnCount = 10;
	
	/**
	 * Instance of cDOMDocument object
	 *
	 * @var cDOMDocument
	 */
	protected $_dom;
	
	/**
	 * Instance of cDb object
	 * 
	 * @var cDb
	 */
	protected $_db;
	
	/**
	 * Idovi urlova koji su preuzeti na obradu
	 * 
	 * @var array
	 */
	protected $_queryIds = array();
	
	
	/**
	 * 
	 * 
	 * @return array
	 */
	public function getData()
	{
		$iCounter = 1;
		do
		{
			$aResult = $this->_getResults($this->resultReturnCount);
			if($aResult === false)
				$aResult = array();
			else
				break;
			$iCounter++;
		}
		while($iCounter <= 50);
		
		return $aResult;
	}

	/**
	 * Javlja resursu da jos uvek radi, updejtuju se lock timestampovi 
	 * 
	 */
	public function ping()
	{
		if(empty($this->_queryIds))
			throw new Exception('Unable to ping, no data!');
		
		$sQueryIds = implode(', ', $this->_queryIds);
		$iTimeStamp = time();
		
		$sSql = "UPDATE se_query SET pingback_timestamp = $iTimeStamp WHERE id IN ($sQueryIds)";
		$this->_db->query($sSql);
	}
	
	/**
	 * Fleguje uzete rezultate da se obradjeni
	 * 
	 */
	public function updateData()
	{
		if(empty($this->_queryIds))
			throw new Exception('Unable to update, no data!');
		
		$sQueryIds = implode(', ', $this->_queryIds);
		$iTimeStamp = time();

		$sSql = "UPDATE se_query SET xml_parsed = 1, pingback_timestamp = $iTimeStamp WHERE id IN ($sQueryIds)";
		$this->_db->query($sSql);
		
		$this->_queryIds = array();
	}
	
	/**
	 * 
	 * 
	 * @param unknown_type $iCount
	 * @return array
	 */
	protected function _getResults($iCount = 1)
	{
		$aQueryIds = array();
		$aBlogData = array();
		$iPid = getmypid();
		$iTimeStamp = time();
		$this->_queryIds = array();
		$bEmptyResult = TRUE;
		
		$sQuery = "UPDATE se_query SET pingback_pid = $iPid, pingback_timestamp = $iTimeStamp WHERE" .
			" pingback_timestamp < " . ($iTimeStamp - $this->lockTime) .   
			" AND xml_parsed = 0 LIMIT $iCount";
		$this->_db->query($sQuery);

		$sQuery = "
			SELECT
			  se_query_id, keyword, xml
			FROM
			  se_xml_data JOIN se_query ON (se_query_id = se_query.id)
			WHERE
			  xml_parsed = 0 AND pingback_timestamp = $iTimeStamp AND pingback_pid = $iPid";		
		
		$aResults = $this->_db->fetchAllFromQuery($sQuery);

		foreach ($aResults as $aResult)
		{
			$bEmptyResult = FALSE;
			$bEmptyXml = TRUE;
			$aQueryIds[ $aResult['se_query_id'] ] = NULL;
				
			$sXmlData = @gzuncompress($aResult['xml']);
			if($sXmlData === FALSE)
			{
				error_log("Unable to Gzuncompress xml data for kw '{$aResult['keyword']}'"); //@todo da bacim exception??? ili hendlovati ovde!
				// Za ovo treba reque
				continue;
			}

			//Zakrpa da vidim dal' ce da opet jede memoriju ili ne
			unset($this->_dom);
			$this->_dom = new cDOMDocument();

			$oXml = $this->_dom;
			$oXml->enableFormatOutput();
//			$oXml->formatOutput = TRUE;
			$oXmlRoot = $oXml->string2Xml($sXmlData);
			unset($sXmlData);
			
			foreach ($oXmlRoot->childNodes as $oEntry) 
			{
				$sLink = $oEntry->getElementsByTagName('link')->item(0)->nodeValue;

				//Zakrpa za specijalni tip linka : (/url?q=http://boardreader.com/thread/14th_Annual_International_Congress_on_An_2dgvXby0.html&amp;sa=U&amp;ei=WZLqTOiKEoL68AaCw7jXDQ&amp;ved=0CBgQFjAEOGQ&amp;usg=AFQjCNG75WyxGbEuv7Jd6-AIRL8wWD_VlA)
				if(strpos($sLink, "/url?q=http") == 0 && strpos($sLink, "&amp;sa=U") !== FALSE)
				{
					$iStart = 7;
					$iEnd = strpos($sLink, "&amp;sa=U");
					$sLink = substr($sLink, $iStart, $iEnd - $iStart);
				}				
				//Trebaju nam samo http i https linkovi
				if(strpos($sLink, "http") !== 0)
					continue;
					
				if(strlen($sLink) > 254)
				{
					error_log("Url > 254 char: $sLink for kw '{$aResult['keyword']}'");
					continue;
				}
				
				$sDomain = @parse_url($sLink, PHP_URL_HOST);
				if($sDomain === FALSE)
				{
					error_log("Unable to parse domain from link $sLink for kw '{$aResult['keyword']}'");
					continue;
				}
			
				// Ubacujemo keyword u niz
				$aBlogData[$sDomain][$sLink]['kw'][$aResult['keyword']] = NULL;
				
				// Ubacujem kompresovani xml entry u niz
				if(!isset($aBlogData[$sDomain][$sLink]['xml']))
					$aBlogData[$sDomain][$sLink]['xml'] = gzcompress($oXml->xml2string($oEntry));

				$bEmptyXml = FALSE;					
			}
			unset($oXmlRoot);

			if($bEmptyXml)
			{
				//@todo Ovde bi verovatno trebalo okinuti i brisanje iz se_query tabele, sta ce mi podatak koji nije ceo ? 
				//Isto prebaciti i u se_parser ? 
				$sSql = "UPDATE se_query SET xml_parsed = 1, pingback_timestamp = $iTimeStamp WHERE id IN ({$aResult['se_query_id']})";
				$this->_db->query($sSql);
			}

		}
		$this->_queryIds = array_keys($aQueryIds);

		//Zakrpa kad ima rezultata u bazi ali su svi prazni ili losi
		if($bEmptyResult === FALSE && empty($aBlogData))
			$aBlogData = FALSE;
		return $aBlogData;
	}
	
	/**
	 * Public class constructor
	 * 
	 * @param cDb $oDb
	 */
	public function __construct(cDb $oDb)
	{
		$this->_db = $oDb;
		$this->_dom = new cDOMDocument();
	}
		
}
