<?php
// 3.0.7 Do not change port number for Apache variable ${MYVAR}
// 3.1.7 Do not change port number in vhosts if switch apache version
require 'config.inc.php';
require 'wampserver.lib.php';

//Replace Used Port by New port ($_SERVER['argv'][1])
$portToUse = intval(trim($_SERVER['argv'][1]));

$ChangeVhosts = (empty($_SERVER['argv'][2])) ? true : false;

//Check validity
$goodPort = true;
if($portToUse < 80 || ($portToUse > 81 && $portToUse < 1025) || $portToUse > 65535)
	$goodPort = false;

if($goodPort) {
	//Change port into httpd.conf
	$httpdFileContents = @file_get_contents($c_apacheConfFile ) or die ("httpd.conf file not found");
	$findTxtRegex = array(
	'/^(Listen 0.0.0.0:).*$/m',
	'/^(Listen \[::0\]:).*$/m',
	'/^(ServerName localhost:).*$/m',
	);
	$httpdFileContents = preg_replace($findTxtRegex,'${1}'.$portToUse, $httpdFileContents,1);

	$fphttpd = fopen($c_apacheConfFile ,"w");
	fwrite($fphttpd,$httpdFileContents);
	fclose($fphttpd);

	$virtualHost = check_virtualhost(true);

	//Change port into httpd-vhosts.conf
	if($virtualHost['include_vhosts'] && $virtualHost['vhosts_exist'] && $ChangeVhosts) {
		$c_vhostConfFile = $virtualHost['vhosts_file'];
		$myVhostsContents = file_get_contents($c_vhostConfFile) or die ("httpd-vhosts.conf file not found");
		$findTxtRegex = $replaceTxtRegex = array();
		$findTxtRegex[] = '/^([ \t]*<VirtualHost[ \t]+.+:)[^\$].*$/m';
		$replaceTxtRegex[] = '${1}'.$portToUse.'>';
		if(version_compare($wampConf['apacheVersion'], '2.4.0', '<')) {
			//Second element only for Apache 2.2
			$findTxtRegex[] = '/^([ \t]*NameVirtualHost).*$/m';
			$replaceTxtRegex[] = '${1} *:'.$portToUse;
		}

		$myVhostsContents = preg_replace($findTxtRegex,$replaceTxtRegex, $myVhostsContents);

		$fphttpdVhosts = fopen($c_vhostConfFile ,"w");
		fwrite($fphttpdVhosts,$myVhostsContents);
		fclose($fphttpdVhosts);
	}

	$apacheConf['apachePortUsed'] = $portToUse;
	if($portToUse == $c_DefaultPort)
		$apacheConf['apacheUseOtherPort'] = "off";
	else
		$apacheConf['apacheUseOtherPort'] = "on";
	wampIniSet($configurationFile, $apacheConf);
}
else {
	echo "The port number you give: ".$portToUse."\n\n";
	echo "is not valid or is not allowed.\n";
	echo "\nPress ENTER to continue...";
  trim(fgets(STDIN));
}

?>
