<?php
/**
 * Curl shuffler 
 * Copyright (c) 2005-2013 PKM Inc
 */
class cShuffler extends cComponent
{
    /**
     * 
     * @var array
     */
    public $ips = array();
    
    /**
     * 
     * @var array
     */
    public $userAgents = array();
    
    /**
     * 
     * @var array
     */
    protected $_firstShuffle = array();
    
    
    //Manje vise nepotrebno posto se moze koristiti key() od trenutne pozicije u nizu ips[$servis]
    public $_ipFilter = array();

    public function getIp($sService = 'ip')
    {
    	if (key_exists($sService, $this->ips) && !key_exists($sService, $this->_firstShuffle))
    	{
			//Check if there is a sub array in this service (Service ips with filters)
    		$sKey = key($this->ips[$sService]);

    		if (is_array($this->ips[$sService][$sKey]))
    		{
    			$aServiceFilter = array_keys($this->ips[$sService]);
    			$iFilterCount = count($aServiceFilter);

    			//Create an entry for each filter in the _firstShuffle array
    			$this->_firstShuffle[$sService] = array_combine($aServiceFilter, $aServiceFilter);
    			
    			foreach ($aServiceFilter as $sFilter)
    			{ 
    				if(empty($this->ips[$sService][$sFilter]))
    					throw new cException('Shuffling is enabled, but array $sFilter is empty!', 0, $this);

    				shuffle($this->ips[$sService][$sFilter]);
    			}
    			
    			$this->_ipFilter[$sService] = current($this->_firstShuffle[$sService]);
    		}
    		else   //Service ips without filters
    		{
    			shuffle($this->ips[$sService]);
    			$this->_firstShuffle[$sService] = $sService; 
    		}
    	}
		elseif (!key_exists($sService, $this->ips))
		{
			$this->ips[$sService] = $this->ips['ip'];
			shuffle($this->ips[$sService]);

    		$this->_firstShuffle[$sService] = $sService; 
		}
		
		
		if (key_exists($sService, $this->_ipFilter)) 
		{
			$sFilter = $this->_ipFilter[$sService];
			$sIp = current($this->ips[$sService][$sFilter]);
			
			if(next($this->ips[$sService][$sFilter]) === FALSE)
				shuffle($this->ips[$sService][$sFilter]);
		}
		else 
		{
			$sIp = current($this->ips[$sService]);
			if(next($this->ips[$sService]) === FALSE)
				shuffle($this->ips[$sService]); // This will also reset the pointer to the first element off the array
		}		
    	

		return $sIp;
    	
    }
    
    public function nextFilter($sService)
    {
    	if (key_exists($sService, $this->_ipFilter)) 
    	{
    		if(next($this->_firstShuffle[$sService]) === FALSE)
    			reset($this->_firstShuffle[$sService]);
    		
    		$this->_ipFilter[$sService] = current($this->_firstShuffle[$sService]);
    		
    		return $this->_ipFilter[$sService];
    	}
    	else 
    		return FALSE;
    }
    
    
    /**
     * Returns UserAgent
     *
     * @return string|null
     */
    public function getUserAgent()
    {
        if(!is_array($this->userAgents))
            throw new cException(get_class($this) . '::$userAgents must be an array!', 0, $this);
        if(empty($this->userAgents))
            throw new cException('UserAgents shuffling is enabled, but there are no UserAgents!', 0, $this);

        $sUserAgent = current($this->userAgents);
        if(next($this->userAgents) === false)
            shuffle($this->userAgents); // This will also reset pointer to the first element off the array

        return $sUserAgent;
    }
    
    
}