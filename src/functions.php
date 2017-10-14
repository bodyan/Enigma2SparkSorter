<?php


function readBouquets ($source) {
	//$source - is a folder where enigma2 bouquets contained
	$list_files = scandir($source);
	if (!in_array('bouquets.tv', $list_files)) {
		exit("Помилка відкриття - bouquets.tv!");
	}
	$bouquets_tv = fopen($source.'bouquets.tv', 'r') or exit("Unable to open bouquets.tv!");
	$bouquets_tv_arr = array();
	while(!feof($bouquets_tv)) {
		// опрацьовуємо кожен рядок
	  	$row = fgets($bouquets_tv);
		$reg_getbouqets = '/^#SERVICE\s[\d:]+FROM BOUQUET\s"([\w]+.[\w]+.tv)".+/';
		preg_match($reg_getbouqets, $row, $bouquets);
		if (isset($bouquets[1])) {
			$bouquets_tv_arr[] = $bouquets[1];
		}
	}
	fclose($bouquets_tv);
	return $bouquets_tv_arr;
}

function readServicesFromBouquets($bouqets, $source)
{
	if (!is_array($bouqets)) {
		exit("Помилка відкриття - bouqets!");
	}

	$list_services = array();
	foreach ($bouqets as $bouqet) {
		$services = file($source.'/'.$bouqet);
	    foreach ($services as $service) {
	        if (!preg_match("/^#SERVICE (.*)$/", $service, $m)) {
	            continue;
	        }
	        $service = $m[1];
	        $data = explode(":", $service);
	        $typeOfService = $data[1];
	        if($typeOfService == 64 || $typeOfService == 134) {
	        	continue;
	        }
	        $list_services[] = $service;
	    }
	}

	return array_unique($list_services);
}


function readFavFromBouquets($bouqets, $source)
{
	if (!is_array($bouqets)) {
		exit("Помилка відкриття - bouqets!");
	}

	$bouquetsServices = array();
	foreach ($bouqets as $bouqet) {
		$services = file($source.'/'.$bouqet);
	    foreach ($services as $service) {
	        if (preg_match("/^#NAME (.*)/", $service, $name)) {
	            $bouquetName = $name[1];
	        }
	        if (!preg_match("/^#SERVICE (.*)$/", $service, $m)) {
	            continue;
	        }
	        $service = $m[1];
	        $data = explode(":", $service);
	        $typeOfService = $data[1];
	        if($typeOfService == 64 || $typeOfService == 134) {
	        	continue;
	        }
	        $bouquetsServices[$bouquetName][] = $service;
	    }
	}

	return $bouquetsServices;
}


function readSpark($source)
{
	$sat_xml=simplexml_load_file($source.'sat.xml') or die("Error: Cannot create object - sat.xml");
	$tv_xml=simplexml_load_file($source.'tv_prog.xml') or die("Error: Cannot create object - tv_prog.xml");

	$sat_array = json_decode(json_encode($sat_xml), true);
	$tv_array = json_decode(json_encode($tv_xml), true);

	$spark_array = array();
	if (!isset($tv_array['prog'])) {
		die("Error: Cannot create object - tv_prog.xml");
	}

	foreach ($tv_array['prog'] as $key => $value) {
		$spark_array[$key]['sat_key'] = $value['@attributes']['sat_key'];
		$spark_array[$key]['tp_key'] = $value['@attributes']['tp_key'];
		$spark_array[$key]['service_key'] = $value['@attributes']['service_key'];
		$spark_array[$key]['tuner_type_idex'] = $value['@attributes']['tuner_type_idex'];
		$spark_array[$key]['sat_type'] = $value['@attributes']['sat_type'];
		$spark_array[$key]['service_id'] = $value['@attributes']['service_id'];
		$spark_array[$key]['pmt_pid'] = $value['@attributes']['pmt_pid'];
		$spark_array[$key]['video_pid'] = $value['@attributes']['video_pid'];
		$spark_array[$key]['audio_pid'] = $value['@attributes']['audio_pid'];
		$spark_array[$key]['pcr_pid'] = $value['@attributes']['pcr_pid'];
		$spark_array[$key]['block'] = $value['@attributes']['block'];
		$spark_array[$key]['bskip'] = $value['@attributes']['bskip'];
		$spark_array[$key]['audio_lang'] = $value['@attributes']['audio_lang'];
		$spark_array[$key]['audio_type'] = $value['@attributes']['audio_type'];
		$spark_array[$key]['video_type'] = $value['@attributes']['video_type'];
		$spark_array[$key]['provider'] = $value['@attributes']['provider'];
		$spark_array[$key]['encrypt'] = $value['@attributes']['encrypt'];
		$spark_array[$key]['hd'] = $value['@attributes']['hd'];
		$spark_array[$key]['name'] = $value['@attributes']['name'];
		foreach ($sat_array['sat'] as $key1 => $value1) {
			if ($value['@attributes']['sat_key'] == $value1['@attributes']['sat_key']) {
				$spark_array[$key]['longitude'] = $value1['@attributes']['longitude'];
				foreach ($value1['transponder'] as $key2 => $value2) {
					if ($value['@attributes']['tp_key'] == $value2['@attributes']['tp_key']) {
						$spark_array[$key]['frequency'] = $value2['@attributes']['frequency'];
						$spark_array[$key]['symbol_rate'] = $value2['@attributes']['symbol_rate'];
						$spark_array[$key]['polarization'] = $value2['@attributes']['polarization'];
						$spark_array[$key]['fec'] = $value2['@attributes']['fec'];
						$spark_array[$key]['modulation_mode'] = $value2['@attributes']['modulation_mode'];
					}
				}
			}
		}
	}

	return $spark_array;

}

function _fgets($source)
{
        $s = fgets($source);
        $s = str_replace(chr(194).chr(134), '', $s);
        $s = str_replace(chr(194).chr(135), '', $s);
        return $s;
}

function convertFec($data)
{
	$fec = array(
        0=>'Auto',
        1=>'1/2',
        2=>'2/3',
        3=>'3/4',
        4=>'5/6',
        5=>'7/8',
        6=>'8/9',
        7=>'3/5',
        8=>'4/5',
        9=>'9/10'
    );
    return $fec[$data];
}

function convertPol($data)
{
	$fec = array(
        0=>'H',
        1=>'V',
        2=>'H',
        3=>'V'
    );
    return $fec[$data];
}

function enigma2Transponders ($source) {
	$lamedb = $source.'lamedb';
	if (!file_exists($lamedb)) {
      die("File $lamedb does not exists");
	}

	$tp = array();
	$file = fopen($lamedb,"r");
	if($file) {

		// find begin of service definition
		while (false !== ($s = trim(fgets($file)))) {
            if ($s == "transponders") {
                break;
            }

        }

        while(!feof($file)) {
        	$namespace = trim(fgets($file));
	        if ($namespace == "end") {
	            break;
	        }

        	$transponder = trim(fgets($file));
        	$delimiter = trim(fgets($file));
        	$nm = explode(':', $namespace);
        	$transp = explode(':', $transponder);
	        $tp[] = ['namespace' => $nm[0],
	        		 'id_tp' => $nm[1],
	        		 'id_network' => $nm[2],
	        		 'frequency' => substr($transp[0], 2,-3),
	        		 'symbol_rate' => substr($transp[1],0,-3),
	        		 'polarization' => convertPol($transp[2]),
	        		 'fec' => convertFec($transp[3]),
	        		 'longitude' => $transp[4],
	        		];
	    }
        fclose($file);
		}
	return $tp;
}



function readServicesFromLameDb($folder)
{
		$source = fopen($folder.'lamedb',"r");
		$lamedb_channels = array();
		// find begin of service definition
        while (false !== ($s = trim(_fgets($source)))) {
            if ($s == "services") {
                break;
            }
        }
        while (false !== ($l1 = trim(_fgets($source)))) {
            if ($l1 == "end") {
                break;
            }
            $data = explode(':', $l1);
			$channel = '1:0:'.dechex($data[4]).':'.strtoupper(ltrim($data[0], '0')).':'.strtoupper(ltrim($data[2], '0')).
			':'. strtoupper(ltrim($data[3], '0')).':'.strtoupper(ltrim($data[1], '0'));
            $serviceName = trim(_fgets($source));
            //echo $serviceName.": ".mb_detect_encoding($serviceName)."<br/>";
            $l3 = trim(_fgets($source));

	      	$lamedb_channels[] = array($l1,$serviceName,$l3,$channel);
        }
        fclose($source);
    return $lamedb_channels;
}

function sortService($lamedb, $channels)
{
	if(!is_array($lamedb) || !is_array($channels)) {
		die("Wrong arrays in sort service function!");
	}
	$result = array();
	foreach ($channels as $channel) {
		foreach ($lamedb as $value) {

			if (mb_strstr($channel, $value[3])) {
				$tmp = explode(":", $value[0]);
				$result[] = ['sid' => $tmp[0],
						   'namespace' => $tmp[1],
						   'id_tp' => $tmp[2],
						   'id_network' => $tmp[3],
						   'channel' => $value[1],
						   'provider' => substr($value[2], 2),
                           'link' => $value[3]
						];
			}
		}
	}
	return $result;
}

function createNewSparkChannelList($source, $folder) {
    if(!is_array($source)) {
        die("Wrong array in createNewSparkChannelList function!");
    }
    $line = '<?xml version="1.0"?>';
    $line .= '<programs progdb_version="1.0.0">';
    foreach ($source as $ch) {
        $line .= '<prog sat_key="'.$ch['sat_key'].'" tp_key="'.$ch['tp_key'].'" service_key="'.$ch['service_key'].'" tuner_type_idex="0" sat_type="'.$ch['sat_type'].'" service_id="'.$ch['service_id'].'" pmt_pid="'.$ch['pmt_pid'].'" video_pid="'.$ch['video_pid'].'" audio_pid="'.$ch['audio_pid'].'" pcr_pid="'.$ch['pcr_pid'].'" block="'.$ch['block'].'" bskip="'.$ch['bskip'].'" audio_lang="'.$ch['audio_lang'].'" audio_type="'.$ch['audio_type'].'" video_type="'.$ch['video_type'].'" provider="'.$ch['provider'].'" encrypt="'.$ch['encrypt'].'" hd="'.$ch['hd'].'" name="'.$ch['name'].'" />';
    }
    $line .= '</programs>

<!--
prog useable flags
1.audio_type, video_type
    MPEG1_VIDEO
    MPEG2_VIDEO
    MPEG4_VIDEO
    MPEG1 LAYER I
    MPEG1 LAYER II
    MP4_AUDIO
    PES_TTX
    PES_SUBTITLE
    PCR
    AC3
    H264
    MPEG4 PART II
    VC1
    AAC
    HEAAC
    WMA
    DDPLUS
    DTS
    MMV
    MMA
    AVS
    MP1A_AD
    MP2A_AD
    AC3_AD
    HEAAC
    LPCM
    MP1A_AUX
    MP2A_AUX
    MP4A_AUX
    AC3_AUX
    AAC_AUX
    HEAAC_AUX
    WMA_AUX
    DDPLUS_AUX
    DTS_AUX
    LPCM_AUX
2.hd
    1 - Is hd
    0 - Not hd

 -->';
   //return $line;
    $newfile = $folder.'tv_prog.xml_new';
    $oldfile = $folder.'tv_prog.xml';

    $newSparklist = fopen($newfile, "w") or die("Unable to open file!");
    fwrite($newSparklist, $line);
    fclose($newSparklist);

    rename($oldfile, $oldfile.'_bak');
    rename($newfile, $oldfile);

}

function createNewSparkFavList($spark_array, $favChannels, $folder) {
    if(!is_array($spark_array)) {
        die("Wrong array in createNewSparkFavList function!");
    }
    $line_tv = '<?xml version="1.0"?>';
    $line_tv .= '<favourites progdb_version="1.0.0">';
        foreach ($favChannels as $name => $channels) {
            if (count($channels) == 0) {
                $line_tv .= '<fav name="'.$name.'" block="0" />';
            } else {
            $line_tv .= '<fav name="'.$name.'" block="0">';
            foreach ($channels as $channel) {
                foreach ($spark_array as $key => $spark) {
                    if (mb_strstr($channel, $spark['link'])) {
                        $line_tv .= '<prog service_key="'.$spark['service_key'].'" />';
                    }
                }
            }
            $line_tv .= '</fav>';
            }
        }
        //must be 32 favorites
        for ($i=count($favChannels); $i <= 32; $i++) {
            $line_tv .= '<fav name="FAV'.$i.'" block="0" />';
        }
    $line_tv .= '</favourites>';

    $line_radio = '<?xml version="1.0"?>';
    $line_radio .= '<favourites progdb_version="1.0.0">';
        foreach ($favChannels as $name => $channels) {
            $line_radio .= '<fav name="'.$name.'" block="0" />';
        }
        //must be 32 favorites
        for ($i=count($favChannels); $i <= 32; $i++) {
            $line_radio .= '<fav name="FAV'.$i.'" block="0" />';
        }
    $line_radio .= '</favourites>';
   //return $line;
    $newfile = $folder.'tv_fav.xml_new';
    $oldfile = $folder.'tv_fav.xml';

    $newSparkTvlist = fopen($newfile, "w") or die("Unable to open file!");
    fwrite($newSparkTvlist, $line_tv);
    fclose($newSparkTvlist);

    rename($oldfile, $oldfile.'_bak');
    rename($newfile, $oldfile);

    $newfile = $folder.'radio_fav.xml_new';
    $oldfile = $folder.'radio_fav.xml';

    $newSparkRadioList = fopen($newfile, "w") or die("Unable to open file!");
    fwrite($newSparkRadioList, $line_radio);
    fclose($newSparkRadioList);

    rename($oldfile, $oldfile.'_bak');
    rename($newfile, $oldfile);
}



 ?>
