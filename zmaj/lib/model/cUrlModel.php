<?php
/**
 * Cli Application Resource
 * Copyright (c) 2005-2013 PKM Inc
 */
class cUrlModel extends cModelAbstract
{
    
    public $service = array();
    
    public function getData()
    {
        
    }
    
    //Ovo treba da bude getData
    public function getUrls($aKeywords, $sService = NULL)
    {

        if (!is_array($aKeywords))
            $aKeywords = array($aKeywords);
        
        $aServiceUrls = (is_null($sService) ? $this->service : $this->service[$sService]);
        
        foreach ($aKeywords as $sKeyword) 
            foreach ($aServiceUrls as $sSE => $sServiceUrl) 
        	{
//              	$aResult[$aUrl][$sSE]['url'] = $sServiceUrl . urlencode("related:" . $aUrl)  . "&num=100";
//              	$aResult[$aUrl][$sSE]['url'] = $sServiceUrl . urlencode($aUrl)  . "&num=100";
//         		$aResult[$aUrl][$sSE]['url'] = $sServiceUrl . urlencode("php str" . rand(10, 200))  . "&num=100";

        		$sQuery = str_replace(" ", "+", $sKeyword);
        		
        		
//         		$aResult[$sKeyword][$sSE]['url'] = $sServiceUrl . $sQuery . "&ie=utf-8&oe=utf-8&aq=t&rls=org.mozilla:en-US:official&client=firefox-a";
        		$aResult[$sKeyword][$sSE]['url'] = $sServiceUrl . $sQuery . "&btnG=Google+Search&gbv=1";
        	}
        
        return $aResult;
    }
    
    
    
    public function getServices()
    {
        //return kljucevi od $this->urls;
    }
    
}