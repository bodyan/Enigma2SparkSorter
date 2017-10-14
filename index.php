<?php

require_once 'src/functions.php';

$sparkFolder = __DIR__.'/spark/';
$enigma2Folder = __DIR__.'/enigma2/';

//let's start from reading bouquets of enigma2
$listOfBouquets = readBouquets($enigma2Folder);

//get channel's from these bouquet's
$bouquetsChannels = readServicesFromBouquets($listOfBouquets, $enigma2Folder);

//reading each bouquets with name services
$favChannels = readFavFromBouquets($listOfBouquets, $enigma2Folder);

//reading sat & channel and creating single array for next compiling with enigma2 channels
$sparkData = readSpark($sparkFolder);

//reading transponders from lamedb
$e2transponders = enigma2Transponders($enigma2Folder);

//reading services from lamedb
$lamedbServices = readServicesFromLameDb($enigma2Folder);

//sort lamedb according bouquets list
$sortE2services = sortService($lamedbServices, $bouquetsChannels);


foreach ($sortE2services as $key => $service) {
	foreach ($e2transponders as $tp) {
		if ($service['namespace'] == $tp['namespace']
			&& $service['id_tp'] == $tp['id_tp']
			&& $service['id_network'] == $tp['id_network']) {
			$sortE2services[$key]['frequency'] = $tp['frequency'];
			$sortE2services[$key]['symbol_rate'] = $tp['symbol_rate'];
			$sortE2services[$key]['polarization'] = $tp['polarization'];
			$sortE2services[$key]['fec'] = $tp['fec'];
			$sortE2services[$key]['longitude'] = $tp['longitude'];
		}
	}
}

$spark_array = array();
$i = 1;
foreach ($sortE2services as $key1 => $value1) {
	foreach ($sparkData as $key2 => $value2) {
		if ($value1['longitude'] == $value2['longitude']
			&& $value1['frequency'] == $value2['frequency']
			&& $value1['symbol_rate'] == $value2['symbol_rate']
			&& $value1['polarization'] == $value2['polarization']
			&& hexdec($value1['sid']) == $value2['service_id']
		) {
			$spark_array[] = ['sat_key' => $value2['sat_key'],
							'tp_key' => $value2['tp_key'],
							'service_key' => $i,
							'tuner_type_idex' => $value2['tuner_type_idex'],
							'sat_type' => $value2['sat_type'],
							'service_id' => $value2['service_id'],
							'pmt_pid' => $value2['pmt_pid'],
							'video_pid' => $value2['video_pid'],
							'audio_pid' => $value2['audio_pid'],
							'pcr_pid' => $value2['pcr_pid'],
							'block' => $value2['block'],
							'bskip' => $value2['bskip'],
							'audio_lang' => $value2['audio_lang'],
							'audio_type' => $value2['audio_type'],
							'video_type' => $value2['video_type'],
							'provider' => $value2['provider'],
							'encrypt' => $value2['encrypt'],
							'hd' => $value2['hd'],
							'name' => $value2['name'],
							'link' => $value1['link']
						];
			$i++;
		}
	}
}

//create new file with sorted channel list
createNewSparkChannelList($spark_array, $sparkFolder);

//create fav list of spark according enigma2 bouquets
createNewSparkFavList($spark_array, $favChannels, $sparkFolder);

echo 'OK';

 ?>
