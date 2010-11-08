<?php
require_once 'DatabaseAdapter.php';

/**
 * This class interacts with a DatabaseAdapter-based cache and Google Web Service APIs in order to geocode the latitude
 * and longitude for a specific address and then return the result (after caching the result in the database).
 *
 * @author Mark Adams
 **/
class Geocoder{
	
	// The base URL for Google Maps geocoding requests
	private $base_url = 'http://maps.googleapis.com/maps/api/geocode/json?address={address}&sensor=false';
	
	// The curl connection used for making requests
	private $connection = null;
	
	// The database resource to use when checking the cache.
	private $db = null;
	
	/**
	 * Instantiates a new Geocoder which uses the specified database cache.
	 *
	 * @param DatabaseAdapter $db The database to use for caching of geocode requests.
	 * @author Mark Adams
	 */	
	public function __construct(DatabaseAdapter $db){
		$this->init_curl();
		$this->db = $db;
	}
	
	/**
	 * Fetches the coordinates for a given address
	 *
	 * @param string $address The address to retrieve coordinates for
	 * @return An array of lat / lng if the coordinates are found; otherwise null.
	 * @author Mark Adams
	 */
	public function getLatLng($address){
		$cacheResult = $this->db->checkGeocodeCache($address);

		if (!$cacheResult){
			$loc = $this->getLocation($address);
			if ($loc){
				$this->db->addGeocodeCache($address,$loc['lat'],$loc['lng']);
			}else{
				$this->db->addGeocodeCache($address,0,0);
			}
			$cacheResult = $loc;
		}else{
			if (($cacheResult['lat'] == "0.0000000") && ($cacheResult['lng'] == "0.0000000")){
				$cacheResult = null;
			}
		}
		
		return $cacheResult;
	}
	
	/**
	 * Initializes CURL parameters to process HTTP requests
	 *
	 * @return void
	 * @author Mark Adams
	 */
	private function init_curl() {
        // Create a connection
        $this->connection = curl_init();	
		
        curl_setopt( $this->connection, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $this->connection, CURLOPT_HEADER, false );
        curl_setopt( $this->connection, CURLINFO_HEADER_OUT, false);
    }

	/**
	 * Returns the coordinates of a specified address from the Google Maps Geocoding API.
	 *
	 * @param string $address The address to geocode
	 * @return void The coordinates as an array.
	 * @author Mark Adams
	 */
	private function getLocation($address){
		
		$returnVal = null;
		$enc_address = urlencode(str_replace(',','',$address));
		$request_url = str_replace("{address}",$enc_address,$this->base_url);

		curl_setopt( $this->connection, CURLOPT_URL, $request_url );
		
		$response = curl_exec($this->connection);
		$info = curl_getinfo($this->connection);
		
		if ($info['http_code'] == 200){
			// If the request is returned succesfully
			$result = json_decode($response);
			$status = $result->status;

			if ($status === "OK"){
				// Check to make sure the request status is OK
				$type = $result->results[0]->geometry->location_type;
				if (($type == 'ROOFTOP') || ($type == 'RANGE_INTERPOLATED') || ($type == 'APPROXIMATE')){
					// If the request is precise enough (mostly just not GEOGRAPHIC_CENTER), use it.
					$returnVal = (array) $result->results[0]->geometry->location;
				}else{
					// Otherwise return null
					$returnVal = null;
				}
					
			}else{
				// Throw an exception if something went wrong.
				throw new Exception("Geocode was not completed succesfully.");
			}
		}else{
			// Throw an exception if something went wrong.
			throw new Exception("Geocode request was not able to complete.");
		}

		return $returnVal;
	}

}
?>