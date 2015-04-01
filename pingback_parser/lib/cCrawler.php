<?php
class cCrawler
{
	/**
	 * Instance of cDb object
	 * 
	 * @var cDb
	 */
	protected $_db;
	
	/**
	 * Instance of cCurl object
	 * 
	 * @var cCurl
	 */
	protected $_curl;
	
	
	
	
	protected function _getDomain($sDomain)
	{
		$iUCount = 0; 
		$iPUCount = 0; 

		$sSql = "SELECT id, active, parse_error_count FROM domain WHERE domain = '" . 
			$this->_db->realEscapeString($sDomain) . "'";
		$aResult = $this->_db->fetchAllFromQuery($sSql);
		
		if(empty($aResult))
		{
			// Domain doesn't exist, insert
			$sSql = "INSERT INTO domain SET domain = '" . $this->_db->realEscapeString($sDomain) . "'";
			$this->_db->query($sSql);
			$sDomainId = $this->_db->getLastInsertId();
		}
		elseif($aResult[0]['active'] == '0')
		{
			// Domain is disabled
			throw new Exception("Domain '$sDomain' is disabled!");
		}
		elseif((int)$aResult[0]['parse_error_count'] >= 10)
		{
			// Domain has too many parse errors
			throw new Exception("Domain '$sDomain' has too many parse errors!");
		}
		else 
		{
			// Domain exists
			$sDomainId = $aResult[0]['id'];
			
			// Get pingback_url count, and url count
			$iPUCount = (int)$this->_db->fetchOneFromQuery("SELECT COUNT(id) FROM pingback_url WHERE domain_id = $sDomainId");
			if($iPUCount == 1)
				$iUCount = (int)$this->_db->fetchOneFromQuery("SELECT COUNT(id) FROM url WHERE domain_id = $sDomainId");
		}	
		
		return array(
			'name' => $sDomain,
			'id' => $sDomainId,
			'urlCount' => $iUCount,
			'pingbackUrlCount' => $iPUCount,
		);
	}
	
	
	
	
	
	protected function _insertUrl($sDomainId, $sUrl, $aUrlData)
	{
		// Insert url
		$sSql = "INSERT INTO url SET domain_id = $sDomainId, url = '" . $this->_db->realEscapeString($sUrl) . 
			"', pingback_url_id = $sPingbackUrlId";
		$this->_db->query($sSql);
		$sUrlId = $this->_db->getLastInsertId();

		
		// Da li moze da bude duplikata ??!?!?!
		// Insert xml
		$sSql = "INSERT INTO xml_data SET url_id = $sUrlId, xml = '" . 
			$this->_db->realEscapeString($aUrlData['xml']) . "'";
		$this->_db->query($sSql);
		
		// Insert keywords
		foreach($aUrlData['kw'] as $sKw)
		{
			// Da li moze da bude duplikata ??!?!?!
			$sSql = "INSERT INTO keyword SET keyword = $sUrlId, xml = '" . $this->_db->realEscapeString($sKw) . "'";
			$this->_db->query($sSql);
			$sKwId = $this->_db->getLastInsertId();

			// Da li moze da bude duplikata ??!?!?!
			$this->_db->query("INSERT INTO keyword_url SET keyword_id = $sKwId, url_id = $sUrlId");
		}
	}
	
	
	
	public function crawlData($aData)
	{
		$sDomain = $aData['domain'];
		
		// Get domain data; If domain has error, exception will be thrown
		$aDomain = $this->_getDomain($aData['domain']);
		
		
		var_dump($aDomain);
		
		die;
		
		// Step 2: Add Urls
		$sPingbackUrlId = null;
		
		
		while (!empty($aData['urls']))
		{
			// Grab url & data
			$sUrl = key($aData['urls']);
			$aUrlData = $aData['urls'][$sUrl];
			unset($aData['urls'][$sUrl]);
			
			print  "\n[Url:$sUrl] [PingbackUrl:{$aDomain['pingbackUrlCount']}] [Url:{$aDomain['urlCount']}] ";

			// Aj da vidimo da li treba da idemo na curl?!?!
			if($iPUCount == 1 && $iUCount >= 3)
			{
				if($sPingbackUrlId === null)
					$sPingbackUrlId = $this->_db->fetchOneFromQuery("SELECT id FROM pingback_url WHERE domain_id = $sDomainId");
			}
			else 
			{
				// Ocemo curl
				try
				{
					$oResult = $this->_curl->doCurl($sUrl);
					if($oResult->getFirstHttpStatus() !== 200)
						throw new Exception("Curl http status " . $oResult->getFirstHttpStatus());
					
					// I sad redimo da ima i da je url ovaj
					// @todo naravno da nije
					$sPinbackUrl = "http://www.IdeCikaPostar.com/sadCeDaTeKara";
					
					$sSql = "INSERT INTO pingback_url SET domain_id = $sDomainId, url = '$sPinbackUrl'";
					$sPingbackUrlId = $this->_db->getLastInsertId();
				}
				catch (Exception $e)
				{
					error_log("[Error: " . $e->getMessage() . "] ", E_USER_NOTICE);
					
					// Nesto nije uspelo, aj oppet.... samo da se nesto ne izjebe ?!?!?!
					// !!Digni count za sjeban url (mada ovde moze da se zavrsi i zobg sjebanog sql-a a to je problem!!)
				}
				
				// Ovde sad digni countere, ako je isti kao stari (kako???) ili ako je novi (?kako)
				
				
			}

			
			// Ovde sad upisujemo url, kqyword i ostala sranja....
			die;
			
			
			$this->_insertUrl($sDomainId, $sUrl, $aUrlData);
			
			
			
			
			
			// !!!!!!!!!!!!!!!!!!!!!!!!!!
			// A gde mi je provera da li taj url ovde vec postoji???? Moze da postoji???!?!?
			
			
			// E sa sta ako je slucaj prethodni, onda ne dodajem pingback url posto ga imam

			// Ondak ce da idemo na id...
			
			
			
			// proces upisa je:
			
			// prvo dodaj pinback_url
			// dodaj url u bazu, xml data 
			// dodaj kw, ako ne postoji i uvezi tabele keyword, keyword_url
			
			
			
			
			// Ovde upisujemo
			
		/*
			Ok, ajmo sad po urlovima i da vidmo sta se desava... ali kurcina.....
			isti problem odozgo moemo da imamo i ovde..... potreba je logicka rekonstrukcija procesa....
			posto i ovde moze da se desi (interno logivki) ista stvar a to je da jedan domen (sad ovaj) odem na 5
			urlova
			
			E sad ovo novi sto dodju... kako njih resiti, tj upoediti sa prethodnima da vidmo da li su isti tb-urlovi
		*/
			
			die;
		}
		
		
		
		
		
		
		
		
		die;
	}
	
	
/*
	Kako ovo govno sad u opste treba da radi:
		- Imam jedan url, imam kws, imam pod urlove...
		- treba da ispitam bazu za ovaj domen dal se radi ili ne.. kako ovo?
			- ako ga imam, onda razmatram da li ima dovoljno ok rezultata, ako ima onda ove urlove samo zavalim unutra
				bez curlovanja....
			- domen nema aktiv/neaktiv, dodati
			- domen dipsejblovan, odjebi...
			
		- po domenu moze da ide vise pinback urlova..
			 
	 	- ako uzimam da radim... sta u opste radim?
	 	- kad uradim treba da flegujem nesto, nekako... a sta ? i kako ?
	 
*/
	
	
	
	
	
	
	
	public function __construct(cDb $oDb, cCurl $oCurl)
	{
		$this->_db = $oDb;
		$this->_curl = $oCurl;
	}
		
}
