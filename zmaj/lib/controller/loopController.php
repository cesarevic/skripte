<?php
/**
 * Cli Application controller
 * Copyright (c) 2005-2012 ViewSource
 */
class loopController extends cLoopControllerAbstract
{
    /**
     * 
     * @var cQueryModel
     */
    protected $_queryModel;
    
    /**
     * 
     * @var cUrlModel
     */
    protected $_urlModel;
    
    /**
     * 
     * @var cCurlModel
     */
    protected  $_curlModel;
    
    /**
     * 
     * @var cCurlCache
     */
    protected $_curlCache;
        
    /**
     * 
     * @var cParserModel
     */
    protected $_parserModel;
    
    
    
    //Ovo bi trebalo da bude keywords a unutar ovog niza neki pod niz sa query-jima
    //Po jednom keyword-u moze biti vise query-ja tip pod navodnicima, sa modifikatorom itd.
    protected $_queries = array();

    protected $_urls = array();
    
    protected $_queriesDone = array();
    
    protected $_queue = array();
    
    protected $_resultsCurl = array();
    
    public $requeueLimit = 3;
    
    //Ovo bi trebalo da se zove site limit posto je queue sajtovi * servisi
    public $queueLimit = 100;
    
    public $cacheLimit = 89;
    
    
	/**
	 * Action Run
	 *
	 * @param array $aData
	 */
	public function actionRun($aData)
	{

/* 		$sFile = "/home/galileo/zmaj-0.2/kw.txt";
		$this->_queryModel->importKeywords($sFile);
		
		die();
 */
// 		$this->_parserModel->dummyParse();

		
		//sa trenutnim sistemom cu prepuniti queue ako sites model vrati vise podataka
		//Manje vise nebitno mada moze da se doda parametar na sites model pa da vraca zadati broj sajtova
		//Ovo opet ne znaci mnogo posto je queue lista izbildovanih url-ova ka servisima. 		

		if (count($this->_queue) < $this->queueLimit) 
			$this->buildQueue();		
		
// 		$this->_curlCache->deleteAll();

		
// 		$this->dummyCurl();
		
/* 				
		$this->addCurl();

	    $this->_curlModel->run();
	    
	    $this->getCurlResult();

		$smem = memory_get_usage(true);
	    
 */

	    while ($this->_curlCache->is_Available() && !empty($this->_resultsCurl))
	    	$this->_curlCache->add(array_pop($this->_resultsCurl));
	    
	    $this->_curlCache->insert();

	    $this->getCurlFromCache();
	    
	    	  
	    
	    
	    
	    
/* 	    
	    if (empty($this->_queue)) 
	    	$this->buildQueue();
		else 
			$this->addCurl();
 */

	    while ($this->_parserModel->is_Available() && !empty($this->_resultsCurl))
	    	$this->_parserModel->add(array_pop($this->_resultsCurl));
	    
	    $this->_parserModel->run();
	    
		$this->getParserResult();					    

// 		echo "\n\n\n\n\n\n" . memory_get_usage(true);
// 		die();
		
	    $this->writeData();

	    if (empty($this->_queue))
	    	$this->buildQueue();
	    
	    if (empty($this->_queue))
	    	throw new cDispatchSleepException('Sleep nema rezultata');
	    
		// Stop script test
// 		throw new cDispatchStopException('Stop dispatch test!');
	}

	public function getCurlFromCache()
	{
		//glup nacin ali moram da osiguran preuzimanje podatka iz cache-a, ako se okine getdata
		//i ne sacuva u results nizu podatak je izgubljen (za sada u sistemu tupavog curlcache) 

		$aResult = $this->_curlCache->getData(); 
		while ($aResult) 
		{
			$this->_resultsCurl[] = $aResult;

			if(count($this->_resultsCurl) < $this->cacheLimit)
				$aResult = $this->_curlCache->getData();
			else 
				break;
		}
	}
	
	
	public function dummyCurl()
	{
		$aServiceUrls = array_keys($this->_urls);
		
		for ($i = 0; $i < 10; $i++) 
		{
			$oCurl = new cCurl();
			$oCurlResult = $oCurl->doCurl("http://test.galileo.local.viewsource.biz/test.php?service=google&hash=123");
			$oCurlResult->compress();
			
			$sServiceUrl = next($aServiceUrls);
			
			//Ovo je url koji je setovan kurlu, ne efektivni url (ako je bilo redirect-a)
	// 		$sServiceUrl 	= $oCurl->getUrl();
			$sService 		= $this->_urls[$sServiceUrl]['service'];
			$sQuery 		= $this->_urls[$sServiceUrl]['query']['query'];
			
			$this->_resultsCurl[] = array('result' => $oCurlResult, 'service' => $sService, 'query' => $sQuery);
		}
	}
	
	/**
	 * Ovaj metod treba da se proziva svaki put kada se desi neki 
	 * callback. Ovde bi trebalo da ide dopunjavanje queue-a, provera
	 * modela itd. 
	 * 
	 *   
	 */
	protected function scheduler()
	{
		if (count($this->_queue) < $this->queueLimit)
			$this->buildQueue();
		
		//Mozda ovde staviti dopunjavanje svih modela ? 
		
	}
	
	public function writeData() 
	{
		// Sto se pita ping model ????
		
		while ($this->_queryModel->is_Available() && !empty($this->_queriesDone))
			$this->_queryModel->add(array_pop($this->_queryDone));

		$this->_queryModel->update();
	}
	
	
	//Debilan naziv
	protected function buildQueue()
	{
		$iQC = count($this->_queue); 
		$iQL = $this->queueLimit;
		
		for ($i = $iQC; ($i < $iQL) && ($sQuery = $this->_queryModel->getData()) !== FALSE; $i++) 
		{
			if (!key_exists($sQuery, $this->_queries))
			{
				$this->_queries[$sQuery]['query'] = $sQuery;
				$this->_queries[$sQuery]['results'] = array('count' => 0, 'requeue_count' => 0, 'service' => array());
				$aQueries[$sQuery] = $sQuery;
			}
		}
		
		if (!empty($aQueries))
		{
			$aUrls = $this->_urlModel->getUrls(array_keys($aQueries));
			unset($aQueries);
				
			foreach ($aUrls as $sQuery => $aServices)
			{
				$aQuery = &$this->_queries[$sQuery];
					
				$aQuery['urls'] = $aServices;
					
				foreach ($aServices as $sService => $aServiceUrl)
				{
					$aQuery['urls'][$sService]['requeue'] = 0;
					$aQuery['results']['service'][$sService] = array('data' => NULL, 'status' => NULL);
						
					$sServiceUrl = $aServiceUrl['url'];
						
					//_urls niz sluzi da bih lakse nasao sajt kada mi se vrati url sa obrade
					$this->_urls[$sServiceUrl]['service'] = $sService;
					$this->_urls[$sServiceUrl]['query'] = &$aQuery;
						
					//Sta raditi kada je ovo drugo okidanje ?
					$this->_queue[] = array($sServiceUrl => $sService);
				}
			}
		}
		
	}
	
	
	protected function requeue($sServiceUrl)
	{
		$aSite = &$this->_urls[$sServiceUrl]['site']; 
		$sService = $this->_urls[$sServiceUrl]['service'];
		
		$iRequeueCount	= &$aSite['curlUrls'][$sService]['requeue'];
		$iTotalRequeueCount = &$aSite['results']['requeue_count'];
		
		$iRequeueCount++;
		$iTotalRequeueCount++;

		
		$this->updateSite($sServiceUrl);
		
/* 		if ($iRequeueCount > $this->requeueLimit)
		{
			//Ovo treba da ide na logger
			echo "Requeue limit reached for bla bla\n";
			$this->updateSite($sServiceUrl);
		}
		else
			$this->_queue[] = array($sServiceUrl => $sService);
 */		
		
	}
	

	//Ovaj metod se proziva samo u 2 slucaja, kada je sajt gotov, ili kada je prekoracen requeue limit
	public function updateSite($sServiceUrl)
	{
		$sService = $this->_urls[$sServiceUrl]['service'];
		$aSite = $this->_urls[$sServiceUrl]['site'];
		$iStatus = $aSite['results']['service'][$sService]['status']; 
		$iRequeueCount	= $aSite['curlUrls'][$sService]['requeue'];
		$iTotalRequeueCount = $aSite['results']['requeue_count'];
		
		$iResultsCount = $aSite['results']['count'];
		$iServiceCount = count($aSite['results']['service']);
		
		$fPercentComplete = $iResultsCount / $iServiceCount;
		
/* 		
		Agoritam:
		Ako je status = 0 url je odradjen i brise se, u slucaju da su svi odradjeni uspesno, taj sajt se
		kopira u gotove sajtove i brise se iz niza _sites.
		
		Prvi elseif: Ako je status razlicit od 0 (curl error, prvi requeue, parser omanuo itd) proveravam
		koliko je procentualno servisa odradjeno. U slucaju da je vise od 50% i da ovo nije prvi requeue 
		zahtev brisem ovaj url iz _url niza i proveravam da li ovaj sajt ima jos neki aktivan zahtev u _url nizu.
		Ovo moram da uradim posto ne znam koliko curl instanci trenutno radi. Ovo se okida vise puta, tako da nije
		bas optimalno ali mora ovako. Ako nema vise aktivnih zahteva sajt se prebacuje u done niz.
		
		Posledji elsif je za slucaj kada ide standardi requeue 
		
		If na kraju ne bi trebalo nikada da se okine, ako se to desi znaci da su svi servisi 
		omanuli maksimalni broj puta. To moze da se desi ako se sjebao kurl nacisto.
		
 */		
		if ($iStatus == 0)
		{
			unset($this->_urls[$sServiceUrl]);

			
			if ($iResultsCount == $iServiceCount) 
			{
			    $sSiteUrl = $aSite['url'];
				$this->_queriesDone[$sSiteUrl] = $aSite;
				unset($this->_queries[$sSiteUrl]);
				
				//Log: Site  $sSiteUrl complete
			}
		}
		elseif ($fPercentComplete >= 0.5 && $iRequeueCount > 0)
		{
			$iRefCount = 0;
			unset($this->_urls[$sServiceUrl]);

			foreach ($aSite['curlUrls'] as $aService) 
			{
			    $sUrl = $aService['url'];
			    if (key_exists($sUrl, $this->_urls)) 
			    	$iRefCount++;
			}
			
			if ($iRefCount == 0) 
			{
				$sSiteUrl = $aSite['url'];
				$this->_queriesDone[$sSiteUrl] = $aSite;
				unset($this->_queries[$sSiteUrl]);
			}
			
		}
		elseif ($iRequeueCount < $this->requeueLimit)
		{
			$this->_queue[] = array($sServiceUrl => $sService);			
		}

		
		if ($iTotalRequeueCount > $this->requeueLimit * $iServiceCount)
		{
			//log + exception
			echo "nesto se gadno sjebalo, baci exception?";
			//Ako se ne prekida izvrsavanje  obrisati sajt iz liste.
		}
	}
	
	
	protected function getParserResult()
	{
		$aResult = $this->_parserModel->getData();
		
		while ($aResult !== FALSE) 
		{
			$oCurlResult	= $aResult['result'];
			$sServiceUrl	= $oCurlResult->getUrl();
			$sService 		= $aResult['service'];
			$sData 			= $aResult['string'];
			$iStatus 		= $aResult['status'];

			$aSite			= &$this->_urls[$sServiceUrl]['site'];
			$aService		= &$aSite['results']['service'][$sService];
			
			$aService['data'] = $sData;
			$aService['status'] = $iStatus;
			
//			$aSite['results']['service'][$sService] = array('data' => $sData, 'status' => $iStatus);
			
			//Ako je status 0 sve je uspesno odradjeno, brise se ovaj url iz _url niza

			//ako je status 1 parsiranje nije uspelo, u slucaju ping-a nije nesto bitno dokle god
			//je ping uspesan na barem 3 druga servisa, u nekom drugom slucaju requeue, ako je 
			//broj ponovnih pokusaja preveliki trebalo bi skloniti sajt iz liste i poslati ga na updejt
			//sa nekakvim statusom ?
			
			//Ako je status 2 banovan poslati na requeue 
			//requeue treba da bude neki metod sa logikom provere, promeni i u getpingresult 		
			
			
			if ($iStatus == 0)
			{
				$aSite['results']['count']++;
				$this->updateSite($sServiceUrl);
			} 
			else 
				$this->requeue($sServiceUrl);
			
			//Ovde bi trebalo da ide i neki proziv na logger gde ce da posalje poruku od servisa
			
			$aResult = $this->_parserModel->getData();
		}
	}	
	
	protected function getCurlResult()
	{
		//oCurl treba da bude oResult instance cModelResult ??
		$oCurl = $this->_curlModel->getData();

		//Ovo treba da se generalizuje tipa while ! FALSE
		while ($oCurl instanceof cCurl)
		{
			$rCurlHandle 	= $oCurl->getHandle(false);
			$sResult 		= curl_multi_getcontent($rCurlHandle);
			$aInfo 			= curl_getinfo($rCurlHandle);
			
			$oCurlResult 	= new cCurlResult($oCurl->getOpt(), $aInfo, $sResult);
			$oCurlResult->compress();
			
			$sLog = $oCurl->getLog();
			
			$sFirstStatus 	= $oCurlResult->getFirstHttpStatus();	

			//Ovo je pogresno u logici gde se ide na google.com pa ma redirektuje na nacionalni domen. 
			if ($sFirstStatus != 200) 
			{
				echo "Kurcina \n\n\n\n\n\n\n";
			}
			
			$sStatus 		= $oCurlResult->getHttpStatus(FALSE);

			//Ovo je url koji je setovan kurlu, ne efektivni url (ako je bilo redirect-a)
			$sServiceUrl 	= $oCurl->getUrl();
			$sService 		= $this->_urls[$sServiceUrl]['service'];
			$sQuery 		= $this->_urls[$sServiceUrl]['query']['query'];
			
// 			$aSite			= &$this->_urls[$sServiceUrl]['site'];
			
			if ($sStatus == 'success')
			{ 
				$this->_resultsCurl[] = array('result' => $oCurlResult, 'service' => $sService, 'query' => $sQuery);
// 				$this->_resultsCurl[] = array('curl'=> $oCurl, 'service' => "test");
			}
			else 
			{
				//Ovo bi trebalo da ide na looger 
				echo "Curl error: " . trim(curl_error($rCurlHandle)) . " Curl errno: " . curl_errno($rCurlHandle) . "\n";

// 				$this->requeue($sServiceUrl);
			}

			
			//!!!!!!!!!!!! Ovde treba da ide upis na curl cache
			
			//Ovo ne bi trebalo da stoji ovde ali zbog optimizacije memorije u ovom nakaradnom sistemu ...  

// 			if (!empty($this->_resultsCurl))
// 				$this->_parserModel->add(array_pop($this->_resultsCurl));
			
			$oCurl = $this->_curlModel->getData();
		}
	}
	
	public function preAction()
	{
	    $this->_queryModel 		= Registry::getObject("oQueryModel");
	    $this->_urlModel 		= Registry::getObject("oUrlModel");
	    $this->_parserModel 	= Registry::getObject("oParserModel");
	    $this->_curlModel 		= Registry::getObject("oCurlModel");
	    $this->_curlCache		= Registry::getObject("oCurlCache");
	    
	    $this->_curlModel->setCallBack($this, "curlCallback");
// 	    $this->_parserModel->setCallBack($this, "parserCallback");
	}
	
	public function postAction()
	{
	}
	
	public function curlCallback()
	{
		$this->getCurlResult();
// 		$this->addPing();
		
		//Dodati property 
		if (count($this->_resultsCurl) > 30) 
			$this->_curlModel->enableInterrupt();
	}
	
	public function parserCallback()
	{
		$this->_parserModel->enableInterrupt();
	}
	
	
	//Ovo je debilan naziv, zvuci kao da se dodaju curl objekti a ne url na curl resurs
	protected function addCurl()
	{
		while ($this->_curlModel->is_Available() && !empty($this->_queue))
			$this->_curlModel->add(array_pop($this->_queue));
	}
	
	
	
	public function header()
	{
	    // Print Loop header
	    $this->getResponse()->write(
	            'Loop #' . $this->getApplication()->getDispatchCount() . ' ' .
	            '[Start time:' . date("H:i:s", $this->getApplication()->getRunningTime()) . '] ' .
	            '[Working time:' . $this->getApplication()->getRunningTime(true) . '] ' .
	            '[Time left:' .	cDateTimeHelper::formatSeconds(round($this->maxExecutionTime - $this->getApplication()->getRunningTime(), 2)) . '] ' .
	            '[Memory:' . number_format(memory_get_usage(true), 0, ',', ',') . 'b]' . "\n"
	    );
	}
	
	
	
	
	
	
	
/* 	
    public function __construct()
    {
        $oApp = Registry::getObject("oApplication");
        parent::__construct($oApp);
        $this->_curlMulti = Registry::getObject("oCurlMulti");
    }
 */

}


