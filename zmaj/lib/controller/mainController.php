<?php
/**
 * Cli Application controller
 * Copyright (c) 2005-2012 ViewSource
 */
class mainController extends cControllerAbstract
{
	/**
	 * Action Run
	 *
	 * @param array $aData
	 */
	public function actionRun($aData)
	{

	    /**
            Treba mi import iz fajla, import preko stdin-a i redirekcija
            truncate za bazu gde pita dal' sam siguran. 
	     */
	    
	    
	    $oDb = $this->getDbResource();

	    $oApp = $this->getApplication();
	    
	    $oRoutesCollection = $oApp->getRouter()->getRoutes();
	    
	    $sFile = "url.txt";
	    
	    if(!file_exists($sFile))
	        throw new cException("File '$sFile' not found", 0);

	    //Ovo bi trebalo da bude u sites modelu, njemu treba samo da se prosledi putanja do fajla
	    
	    $aUrls = file($sFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	    $iCount = count($aUrls);

        foreach ($aUrls as &$sUrl) 
            if (strpos($sUrl, 'http://') === FALSE) 
                $sUrl = "http://" . $sUrl;

        $oDb->importSitemapUrls($aUrls);
        
        
	    
	    
	    
	}

	public function primeri()
	{

	    // 		$db_nesto=$this->getDbResource();
	    
	    // 		$this->getResponse()->write("Unesi nesto [Yes|No|All|Quit]");
	    
	    
	    
	    //var_dump($this->getResponse());
	    
	    
	    
	    //var_dump($aData);
	    
	    //print "Kontroler!!!";
	    
	    
	    
	    //$this->setSranje(new sranje());
	    
	    
	    
	    //$this->getSranje()->drkaj();
	    
	    
	    //$this->setKurcina();
	    
	    
	    //$this->setKurcina('sdsd');
	    
	    
	    
	    //var_dump($this->db);
	    
	    // E sad jbg, ne moze se radi ona varijanta ->mika laza pera i tako to.... mada bih ja to direkt u view
	    // znaci this->responese, ili view ili sta vec....
	    
	    // E sad ovde dolazi do sranja... kontroler direktno cacka p oview lejeru... razmisli....
	    
	    //$this->view->mika = 'nesto';
	    
	    
	    
	    
	    
	    
	    // @todo ovo se mora nabudzi neki fajl sa primerima, posto bez toga ovo nece niko zivi znati da koristi!!!
	    
	    
	    
	    //$oLogger = $this->getApp()->getLogger();
	    //$oLogger->writeMessage(new cLogMessage('File does not exist: /home/marko/public_html/formaideale-cz-10/design/images/arrow-down.gif, referer: http://formaideale-cz-10.marko.local.viewsource.biz/newsletter/', cLogMessage::error));
	    //$oLogger->writeMessage(new cLogMessage('Constant DIRECTORY_SEPARATOR already defined in /home/marko/public_html/formaideale-cz-10/config/config.inc.php on line 20, referer: http://formaideale-cz-10.marko.local.viewsource.biz/article/forma-ideale/specijalna-ponuda.html', cLogMessage::warning));
	    //$oLogger->writeMessage(new cLogMessage('Sisajte mi djoku!', cLogMessage::info));
	    //$oLogger->writeMessage(new cLogMessage('Sisajte mi djoku!', cLogMessage::debug));
	    
	    //print "ovde kao radim nesto!\n";
	    //var_dump($aData);
	    
	    //$data = $this->_dbResource->getData(1);
	    //var_dump($data);
	    
	    /*
	     // Povadi logove iz memorije
	    
	    $a = ($oLogger->getLogger('memory')->getWriter('all')->getLogs());
	    foreach($a as $b)
	        var_dump($b);
	    */
	    
	    
	    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	    // INPUT TEST
	    
	    //$this->getApp()->getOutput()->write("Unesi nesto [Yes|No|All|Quit]");
	    
	    //$sAns = $this->getApp()->getInput()->read(null);
	    
	    //$sAns = $this->getApp()->getInput()->read('Unesi nesto', array('Yes', 'No', 'Abort'));
	    // 		$sAns = $this->getApp()->getInput()->read('Unesi nesto', array('Yes'));
	    
	    // 		var_dump($sAns);
	    
	    
	    
	    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	    // OUTPUT TEST
	    
	    // 		$this->getApp()->getOutput()
	    // 			->write('marko')
	    // 			->red()->bold()->bGreen()
	    // 			->write('mika')
	    // 			->reset()->nl();
	    
	    	  
	}
}


