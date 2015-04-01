<?php
/**
 * Cli Application Resource
 * Copyright (c) 2005-2013 PKM Inc
 */
class cCurlModel extends cModelAbstract
{
	/**
	 * Curl instance
	 *
	 * @var cCurlMulti
	 */
	protected $_curlMulti;
	
	/**
	 * 
	 * @var cShuffler
	 */
	protected $_curlShuffler;

	public $queueLimit = 100;
	
	public $curlReuselimit = 5;
	
	protected $_queue = array();
	
	protected $_results = array();
	
	protected $_callback = NULL;
	
	protected $_callbackMethod = NULL;
	
	protected $_interrupt = FALSE;
	
	protected $_curlList = array();
	
	protected $_step = 5;
	
	protected $_curlLimit = 10;
	
	
	public function getData()
	{
		if (empty($this->_results))
			return FALSE;
		else
			return array_pop($this->_results);
	}
	
	public function add($aItem)
	{
		if ($this->is_Available())
			$this->_queue[] = $aItem;
		
/* 		else 
			$this->addCurl();  		//Ideja je bila kada se napuni queue da se okine dodavanje
		
		//Ovo mi pravi zajebanciju u bulk modu kada je queue u curl modelu manji od queue u kontroleru			
	
 */	
	}
	
	public function is_Available()
	{
		if (count($this->_queue) >= $this->queueLimit)
			return FALSE;
		 
		return TRUE;
	}
	
	public function enableInterrupt()
	{
		$this->_interrupt = TRUE;
	}
	
	public function disableInterrupt()
	{
		$this->_interrupt = FALSE;
	}
	
	public function run()
	{

/* 		
// 		$sIp = $this->_curlShuffler->getIp("google");

		echo "obican \n";
		for ($i = 0; $i < 5; $i++) 
		{
			$sIp = $this->_curlShuffler->getIp();
			echo "$sIp \n";
		}
		
		echo "google \n";
		for ($i = 0; $i < 6; $i++) 
		{
			for ($j = 0; $j < 3; $j++) 
			{
				$sIp = $this->_curlShuffler->getIp("google");
				echo "$sIp \n";
			}
			echo "Sledeci subnet \n";
			$sSubnet = $this->_curlShuffler->nextFilter("google");
			echo "$sSubnet \n";
		}
		
		echo "msn \n";

		for ($i = 0; $i < 5; $i++)
		{
			$sIp = $this->_curlShuffler->getIp("msn");
			echo "$sIp \n";
		}
		
		
		$sIp = $this->_curlShuffler->getIp();
 		$sIp = $this->_curlShuffler->getIp("google");		

 		$sIp = $this->_curlShuffler->getIp("msn");
		
 */ 		

		
		
		do 
		{
// 			$this->addCurl();
			$this->addCurlG();
			$this->_curlMulti->doCurl();
			$this->getCurlData();	

// 			$this->addCurl();

			if(count($this->_results) > 0 && !is_null($this->_callback))
				$this->_callCallback();
		}
		while (count($this->_queue) > 0 && !$this->_interrupt);
		
		$this->disableInterrupt();
		
		//Ovde bi trebalo implementirati neku logiku unsetovanja curl-ova iz liste
		//dodatno negde treba da stoji i logika koja limitira broj curl reuse-ova
		
	}
	

	
	
	

/* 
 *    
 *  Callback sistem: Callback prozivi se propagiraju do kontrolera.
 *  Callback sa curl-a se prosledjuje na resurs, resurs callback-uje kontroler.
 *    
 *  Pri svakom callback-u, nebitno da li je callback-ovan kontroler ili resurs 
 *  trebalo bi uzeti podatke iz podredjenog objekta (resurs -> curl, kontroler -> resurs). 
 *  
 *  Kada je kontroler callback-ovan tu se bira vrsta logike rada. Kontroler moze samo da uzme
 *  podatke iz resursa i da tu zavrsi sa callbackom. Alternativno moze da proveri neki drugi resurs
 *  ili vreme izvrsavanja itd. Ovo je deo cooperativnog multitaskinga. Takodje moze da se setuje
 *  i interupt na resurs koji ce da propagira taj interupt na curl. U ovom slucaju se zavrsavaju
 *  run sekcije u resursu i curl-u i tu se zavrsava taj ciklus obrade.
 *  
 *  Sitem moze da se koristi i na tradicionalan nacin. Ako se callback-ovi ne setuju na resursu i curlu.
 *  Ovo je takodje i metod da se pravilno zatvore svi resursi kada se primi zahtev za gasenjem skripte.
 *  Kontroler ce iskljuciti callback na resursu, resurs na curl-u (ova funkcionalnost ne postoji, treba
 *  dokodirati neki shutdown metod) i kada se obrada zavrsi nastavlja se uobicajen proces.
 *  
 *  
 *  
 *   
 *  
 *  
 *  
 */		

	public function getCurlData()
	{
		$oCurl = $this->_curlMulti->getCurl();
			
		while ($oCurl instanceof cCurl)
		{
			$rHandle =  $oCurl->getHandle(FALSE);
			$iHttpCode = curl_getinfo($rHandle, CURLINFO_HTTP_CODE);
			
			//Ovo ide kao patch, zbog kesiranja ne upisuje sve u log fajl dok se na neki nacin ne uradi
			//flush 
			$sCurlLog = $oCurl->getLog();
			
			if ($iHttpCode == 503) 
			{
				//Ovo bi trebalo da ide po novom sistemu shutdown-a, i trebalo bi sacuvati vracenu stranu u neki fajl.
				//Cuvanje strane verovatno treba da odradi curlResult. 
				echo "Kurcinaaaaaaaaaaaaaaaaaa";
				echo "Curl log file:" . $oCurl->getLogFilePath();
				
				//u produkciji log fajl se brise pri gasenju curl-a tako da treba 
				//setovati cuvanje ako se desi greska 
				$oCurl->keepLogs = TRUE;
				
				//Mozda ovde okinuti jedan unset za curlMulti, mozda to pobije sve 
				//transfere a nastaviti sa obradom ? 
				
				die();	
			}
			
			if ($oCurl->flag == 0) 
			{
				$this->_results[] = $oCurl;
				
				//Ovo je problem u novoj logici 
// 				$this->_curlMulti->enableInterrupt();
			}
			else
			{
				echo " Nesto se sjebalo";
				die();
			} 
			
			$oCurl = $this->_curlMulti->getCurl();
		}
	}
	
	public function callback()
	{
		$this->getCurlData();
// 		$this->addCurl();
		
		if (count($this->_results) > $this->_step) 
			$this->_curlMulti->enableInterrupt();
	}
	
	protected function addCurlG()
	{
		
		$iLimit = $this->_curlLimit;

		if (count($this->_queue) < $iLimit)
			$iLimit = count($this->_queue);
		
		if ($iLimit >= count($this->_curlList))
			$iLimit -= count($this->_curlList); 
		
		if($iLimit > 0)
			$this->buildNewCurls($iLimit);
		
		foreach ($this->_curlList as $oCurl) 
		{
			$aUrl = array_pop($this->_queue);
			$sUrl = key($aUrl);
			$sService = $aUrl[$sUrl];
				
			$oCurl->setUrl($sUrl);
				
			$this->_curlMulti->addCurl($oCurl);
		}	
			
		//Ovo je nepotrebno ovde 
// 		$this->_curlMulti->doCurl();
	}
	
	
	protected function buildNewCurls($iLimit = 0)
	{
		if ($iLimit == 0)
			return FALSE;
		
		for ($i = 0; $i < $iLimit; $i++) 
		{
			$oCurl = new cCurl();
			$oCurl->service = 'google';
			$oCurl->flag = 1;
			
			$sUrl = "https://www.google.com";
				
			$sIp = $this->_curlShuffler->getIp("google");
			$sUA = $this->_curlShuffler->getUserAgent();
			
			$oCurl
			->setUserAgent($sUA)
			->setIp($sIp)
			->setCookie()
			->setUrl($sUrl);
			
			$oCurl->setVerbose();
				
			$oCurl->setLogFile();
			$oCurl->keepLogs = TRUE;
				
			$oCurl->setTimeout(120);
				
			$this->_curlMulti->addCurl($oCurl, TRUE);
		}
		
		$this->_curlMulti->disableCallback();
		$this->_curlMulti->disableInterrupt();
		
		$this->_curlMulti->doCurl();
		
		//If multicurl callback is disabled this will fetch $oCurl from multi
		// 		$this->getCurlData();
		
		
		//Teska budzevina, ceo blok ispod sluzi da se dobiju cisti kurlovi
		//koji su prosli celu proceduru pre samog SE rezultata 
		
		$oCurl = $this->_curlMulti->getCurl();
		while ($oCurl instanceof cCurl)
		{
			$this->checkHttp($oCurl);

			$oCurl->flag = 2;
			$sUrl = "https://www.google.com/preferences?hl=en";
			$oCurl->setUrl($sUrl);
			$this->_curlMulti->addCurl($oCurl, TRUE);
			
			$oCurl = $this->_curlMulti->getCurl();
		}

		$this->_curlMulti->doCurl();
		
		
		$oCurl = $this->_curlMulti->getCurl();
		while ($oCurl instanceof cCurl)
		{
			$this->checkHttp($oCurl);
			$sResult = curl_multi_getcontent($oCurl->getHandle(FALSE));

			$sSig = "";
			if (preg_match('/<input.*name="sig".*>/i', $sResult, $regs))
				if (preg_match('/value="([^"]*)"/', $regs[0], $regs))
					$sSig = $regs[1];
			
			if(empty($sSig))
			{
				echo "Greska, nema sig varijable \n";
				continue;
			}
				
			$oCurl->flag = 3;
				
			$sUrl = "https://www.google.com/setprefs?sig=$sSig&submit2=Save+Preferences&hl=en&lang=en&lr=lang_en&uulo=0&muul=4_20&luul=&safeui=images&suggon=2&num=100&tts=0&newwindow=1&q=&prev=";
			$oCurl->setUrl($sUrl);
			$this->_curlMulti->addCurl($oCurl, TRUE);
				
			$oCurl = $this->_curlMulti->getCurl();
		}
		
		$this->_curlMulti->doCurl();
		

		$oCurl = $this->_curlMulti->getCurl();
		while ($oCurl instanceof cCurl)
		{
			$oCurl->flag = 0;
			$this->_curlList[] = $oCurl;
			
			$oCurl = $this->_curlMulti->getCurl();
		}
	}

	//djubre funkcija, cisto da mi se ne ponavlja kod ovo je strpano ovde
	protected function checkHttp(cCurl $oCurl)
	{
		$rHandle =  $oCurl->getHandle(FALSE);
		$iHttpCode = curl_getinfo($rHandle, CURLINFO_HTTP_CODE);
		
		//Ovo ide kao patch, zbog kesiranja ne upisuje sve u log fajl dok se na neki nacin ne uradi
		//flush
		$sCurlLog = $oCurl->getLog();
		
		if ($iHttpCode == 503)
		{
			//Ovo bi trebalo da ide po novom sistemu shutdown-a, i trebalo bi sacuvati vracenu stranu u neki fajl.
			//Cuvanje strane verovatno treba da odradi curlResult.
			echo "Kurcinaaaaaaaaaaaaaaaaaa";
			echo "Curl log file:" . $oCurl->getLogFilePath();
		
			//u produkciji log fajl se brise pri gasenju curl-a tako da treba
			//setovati cuvanje ako se desi greska
			$oCurl->keepLogs = TRUE;
		
			//Mozda ovde okinuti jedan unset za curlMulti, mozda to pobije sve
			//transfere a nastaviti sa obradom ?
		
			die();
		}
		
	}
	
	
	
	protected function addCurl()
	{
		while ($this->_curlMulti->isAddCurlAvailable() && !empty($this->_queue) && !$this->_interrupt)
		{
			$aUrl = array_pop($this->_queue);
			$sUrl = key($aUrl);
			$sService = $aUrl[$sUrl];

			//Ovde treba da ide neka logika tipa ako je google u pitanju 
// 			prebacujem se na bulk mod rada
			
			$oCurl = $this->getNewCurl($sService);			
			$oCurl->setUrl($sUrl);
			
// 			$oCurl->setUrl('http://www.youtube.com');
			
			
			$oCurl->setVerbose();
			
			
// 			$oCurl->setOpt(CURLOPT_FOLLOWLOCATION, FALSE);
// 			$oCurl->setOpt(CURLOPT_FORBID_REUSE, TRUE);
			
// 			$oCurl->setOpt(CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
			
			$this->_curlMulti->addCurl($oCurl);
			
			//Ovo je nepotrebno ???
			
			if(count($this->_results) > $this->_step && !is_null($this->_callback))
				$this->_callCallback();
		}

		$this->_curlMulti->curlPerform();
	}

	
	protected function getNewCurl($sService = NULL)
	{
		$oCurl = $this->getCurlFromQueue($sService);
		
		for ($i = 0; $i < $this->curlReuselimit && !$oCurl; $i++) 
		{
			$oCurl = new cCurl();
			$oCurl->service = $sService;
			$oCurl->flag = 1;
				
			//Ovo bi trebalo da se dobije iz nekog resursa, use case vrtimo google tld-ove
			$sUrl = "https://www." . $sService . ".com";
			
			$sIp = $this->_curlShuffler->getIp($sService);
			$sUA = $this->_curlShuffler->getUserAgent();
				
			$oCurl
			->setUserAgent($sUA)
			->setIp($sIp)
			->setCookie()
			->setUrl($sUrl);
				
			$oCurl->setVerbose();
			
			
// 			$sLogFile = tempnam(sys_get_temp_dir(), md5($oCurl->getHandleHash()));
			
			$oCurl->setLogFile();
			
			$oCurl->setTimeout(120);
			
			$this->_curlMulti->addCurl($oCurl, TRUE);
			$this->_curlMulti->doCurl();

			//If multicurl callback is disabled this will fetch $oCurl from multi
			$this->getCurlData();
			$oCurl = $this->getCurlFromQueue($sService);
				
			
			//Tezak zajeb: Ako je novo kreirani curl odjeban od strane googl-a 
			//i obrisan iz kolekcije ovaj while loop se pretvara u mrtvu petlju.
			
			
			while (!$oCurl) 
			{
				$this->_curlMulti->doCurl();
				$this->getCurlData();
				$oCurl = $this->getCurlFromQueue($sService);

				if (!$oCurl) 
				{
					usleep(100);
				}
			}
		}
		
		if (!$oCurl)
		{
			throw new cException("Nema Curla - Nesto se sjebalo", 0, $this);
		}
		else 
			return $oCurl;
	}
	
	protected function getCurlFromQueue($sService = NULL)
	{
		if (!empty($this->_curlList))
			foreach ($this->_curlList as $key => $oCurl)
				if ($oCurl->service === $sService)
				{
					unset($this->_curlList[$key]);
					break;
				}
		
		if (isset($oCurl)) 
		{
			return $oCurl;
		}
		else
		{ 
			return FALSE;
		}
	}
	
	
	protected function addToQueue(cCurl $oCurl)
	{

		
		if ($oCurl->flag == 1)
		{
			$oCurl->flag = 0;
			$this->_curlList[] = $oCurl;
			return TRUE;
		}

		//Ovde bi trebala da stoji neka logika za slucaj vracanja curl-a iz 
		//kontrolera ali od toga se odustalo (cookie jar itd logika).
		
		
		
		
		
		
		
		
		
		
/* 		
		
		$rCurlHandle 	= $oCurl->getHandle(false);
		$sResult 		= curl_multi_getcontent($rCurlHandle);
		$oCurlResult 	= new cCurlResult($oCurl->getOpt(), $rCurlHandle, $sResult);
		$sStatus 		= $oCurlResult->getFirstHttpStatus();
		
		$sStatus2 		= $oCurlResult->getHttpStatus();

		$sBody 			= $oCurlResult->getBody();
		
		$sEffectiveUrl	= $oCurl->getEffectiveUrl();
		$sUrl			= $oCurl->getUrl();
		$sIp 			= $oCurl->getIp();		
		
		$sService		= $oCurl->service;
		
		$sLog = $oCurl->getLog();
		
		
		//Pogresno (redirect na google.de
		$sServiceUrl = "http://www." . $oCurl->service . ".de";
 */		
// 		$oCurlResult bi trebalo da se prosledjuje na parser
		
		/*
		 	Ako je flag 1, effective url = servis url i status code (dal' ovo uposte treba) = 200
		 	onda je sve ok. Proveriti kako da se uradi hendling za redirect na nacionalne  domene.

		 	Ako flag nije 1 ?
		 	Ovo je za slucaj da je curl vracen iz kontrolera
		 	Ovde treba da ide provera koliko puta je okinut, ako je zadovoljen uslov vraca se na curl listu 
		 	ako ne brise se. 
		 
		 */

		//Ceo ovaj blok treba da se prepravi da lici na nesto
		
		
		//Ova provera treba da se radi kroz parser
/* 
		$sYoutubePatch = strpos($sEffectiveUrl, "supported_browsers");
		
		if ($sYoutubePatch) 
		{
			echo "Unsuported browser";
		}
 		elseif ($sUrl !== $sEffectiveUrl || $sStatus != 200) 
		{

			echo "ban na prvom curlu !!!!!!!!!!!!!!!!!1";
			die();
			
			
			unset($oCurl);
			//Neka poruka da je ovaj curl banovan i brisanje tog ip-a iz shufflera

// 			Ovo ne treba da se radi ovde, get new curl se vec vrti 5 puta dok ne dobije odogovarajuci curl
// 			$this->getNewCurl($sService);
			return FALSE;
		}
		
 				
		if ($oCurl->flag == 1) 
		{
			$oCurl->flag = 0;
			$this->_curlList[] = $oCurl;
			return TRUE;
		}

 		
		if ($oCurl->_counterIp >= $this->curlReuselimit)
		{
			unset($oCurl);
			$this->getNewCurl($sService);
			return FALSE;
		}
	
		
		
		
	
	
 */

	}
	
	/**
	 * Configures the callback functionality.
	 *
	 * @param mixed $callback
	 * @param string $sMethod
	 * @return boolean
	 */
	public function setCallBack($callback, $sMethod = "callback")
	{
		//if $callback is an object
		if (is_callable(array($callback, $sMethod)))
		{
			$this->_callback = $callback;
			$this->_callbackMethod = $sMethod;
		}
		//if $callback is a function
		elseif (is_callable($callback))
			$this->_callback = $callback;
		else
			return FALSE;
	
		return TRUE;
	}
	
	/**
	 * Disables the callback functionality
	 */
	public function disableCallback()
	{
		$this->_callback = NULL;
		$this->_callbackMethod = NULL;
	}
	
	/**
	 * Executes the callback
	 *
	 * @return boolean
	 */
	protected function _callCallback()
	{
		if (is_callable(array($this->_callback, $this->_callbackMethod)))
			call_user_func(array($this->_callback, $this->_callbackMethod));
	
		elseif (is_callable($this->_callback))
			call_user_func($this->_callback);
		else
			return FALSE;
	
		return TRUE;
	}
	
	/**
	 * Class constructor
	 *
	 * @param cCurlMulti $oCurlMulti
	 */
	public function __construct(cCurlMulti $oCurlMulti, cShuffler $oCurlShuffler)
	{
		$this->_curlMulti = $oCurlMulti;

		//Ovo bi trebalo da ide u konfig od curlMulti? 
		$this->_curlMulti->setLimits($this->_curlLimit);
		
		$this->_curlMulti->setCallBack($this, "callback");
		
		$this->_curlShuffler = $oCurlShuffler;
	}
}