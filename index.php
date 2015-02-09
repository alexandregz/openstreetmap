<?php
/**
 * Ready to use openstreetmap
 * Helper functions, includes and css/js code to leaflet js library
 */

// GeoIP2
require 'vendor/autoload.php';
use GeoIp2\Database\Reader;

// you can config this
define('REALPATH', '/Applications/MAMP/htdocs/estraviz/');


/**
 * function to return address and coordinates from IP address
 *
 * @ToDo important, REUSE Reader() (see below)
 *
 * @param string $ip
 * @return array $values Array with values: latitude, longitude and address
 */
function getAddressIP($ip)
{
	// This creates the Reader object, which should be reused across
	// lookups.
	$pathGeoipBD = REALPATH.'/geoip/GeoLite2-City.mmdb';
	$reader = new Reader($pathGeoipBD);


	$record = $reader->city($ip);
	$lat = $record->location->latitude;
	$long = $record->location->longitude;


	$cp = (isset($record->postal->code) ? $record->postal->code : null);
	$isoCode = (isset($record->country->isoCode) ? $record->country->isoCode : null);
	$city = checkLanguagesGeoIP($record->city->names);
	$country = checkLanguagesGeoIP($record->country->names);
	$continent = checkLanguagesGeoIP($record->continent->names);


	// "special" values, it can appears but without pt-BR traduction
	$subdivisions = null;
	if(isset($record->subdivisions))
	{
		// they going from bigger to little and we put infor from little to bigger
		foreach(array_reverse($record->subdivisions) as $sub)
		{
			if(checkLanguagesGeoIP($sub->names)) {
				$subdivisions.= checkLanguagesGeoIP($sub->names)."<br />";
			}
		}
	}


	// format some values
	if($isoCode) $country.= " ($isoCode)";

	// prepare to output
	$ip_show = preg_replace('/\d/', 'x', $ip);

	$address = $ip_show."<br />";
	if($cp) 			$address.= "$cp<br />";
	if($city) 			$address.= "$city<br />";
	if($subdivisions) 	$address.= $subdivisions;		// this one becomes with <br />
	if($country) 		$address.= "$country<br />";
	if($continent) 		$address.= "$continent<br />";


	return array($lat, $long, $address);
}



/**
 * To check languages from 'names' values because some places/countries/... haven't all langs.
 *
 * @param array $names Values from GeoIP db to check
 * @param array $preferedLangs Prefered langs, with order (first prefered)
 * @return string $value Prefered value
 *
 *         ["names"]=>
 *         array(8) {
 *           ["de"]=>
 *            string(8) "Galicien"
 *            ["en"]=>
 *            string(7) "Galicia"
 *            ["es"]=>
 *            string(7) "Galicia"
 *            ["fr"]=>
 *            string(6) "Galice"
 *            ["ja"]=>
 *            string(15) "ガリシア州"
 *            ["pt-BR"]=>
 *            string(8) "A Galiza"
 *            ["ru"]=>
 *            string(14) "Галиция"
 *            ["zh-CN"]=>
 *            string(12) "加利西亚"
 */
function checkLanguagesGeoIP($names, $preferedLangs = array('pt-BR', 'gl', 'gl-ES', 'en', 'fr', 'es'))
{
	if(!$names) return;

	foreach($preferedLangs as $l)
	{
		if(isset($names[$l])) return $names[$l];
	}

	return null;
}


?>
<DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.css" />
        <script src="http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.js"></script>
    </head>
    <body>
        <div id="map" style="width: 1200px; height: 800px;"></div>

        <script>
        //create a map in the "map" div, set the view to a given place and zoom
        var map = L.map('map').setView([51.505, -0.09], 1);

        // add an OpenStreetMap tile layer
        L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);



        <?php 
        // test
        $ips = array('8.8.8.8', '8.8.4.4');    // commented to protect the innocent

        foreach($ips as $ip)
        {
            list($lat, $long, $address) = getAddressIP($ip);
            ?>
                L.marker([<?php echo $lat;?>, <?php echo $long;?>]).addTo(map)
                .bindPopup('<?php echo $address;?>')
                .openPopup();
            <?php 
        }

        ?>
        </script>
    </body>
</html>
