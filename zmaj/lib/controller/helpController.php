<?php
/**
 * Cli Application controller
 * Copyright (c) 2005-2012 ViewSource
 */
class helpController extends cControllerAbstract
{
	const tab  = 4;
	const cmd  = 35;
	const desc = 70;

	public function actionShowHelp($aData)
	{
		$sTab = str_repeat(' ', self::tab);
		$oApp = $this->getApplication();

		print $oApp->name . ', version: ' . $oApp->version . ' powered by ' . Framework::getName() . ' ' .
			Framework::getVersion() . "\n";
		if(! (isset($aData['opt']['version']) && $aData['opt']['version'] === true))
		{
			print $oApp->copyright . "\n" . $oApp->description . "\n";
			print "Usage: \n{$sTab}{$_SERVER['SCRIPT_NAME']} <command> [options] -- [arguments]\n";
			$oRoutesCollection = $oApp->getRouter()->getRoutes();
			if($oRoutesCollection->count() !== 0)
			{
				print "Available commands:\n";
				foreach($oRoutesCollection as $oRoute)
				{
					printf("%-" . self::cmd . "s", $sTab . $oRoute->getRouteMatch());
					print implode(("\n$sTab" . str_repeat(' ', self::cmd - 1)),
						str_split($oRoute->getDescription(), self::desc)) . "\n";
					$aOptions = $oRoute->getOptions();
					if(!empty($aOptions))
					{
						print "$sTab  options:\n";
						foreach($aOptions as $aOption)
						{
							printf("%-" . self::cmd . "s", "{$sTab}{$sTab}--{$aOption['name']}" .
								(empty($aOption['short']) ? '' : ", -{$aOption['short']}") . ' ' .
								($aOption['type'] == cCliRoute::flag ? '' : '[val]'));
							print implode(("\n$sTab" . str_repeat(' ', self::cmd - 1)),
								str_split($aOption['description'], self::desc)) . "\n";
						}
					}

					$aArgs = $oRoute->getAguments();
					if(!empty($aArgs))
					{
						print "$sTab  arguments:\n";
						foreach($aArgs as $sArgName => $aArg)
						{
							printf("%-" . self::cmd . "s", "{$sTab}{$sTab}$sArgName" .	($aArg['required'] ? '[Req]' : ''));
							print implode(("\n$sTab" . str_repeat(' ', self::cmd - 1)),
								str_split($aArg['description'], self::desc)) . "\n";
						}
					}
				}
			}
		}
	}

	
	/**
	 * Ovo se nigde ne proziva
	 */
	
	public function actionShowVersion()
	{
		$sTab = str_repeat(' ', self::tab);
		$oApp = $this->getApp();
		print $oApp->name . ', version: ' . $oApp->version . "\n";
	}
}