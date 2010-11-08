<?php
require_once 'F1Person.php';

/**
 * This class serves to generate data access objects from JSON results returned from the Fellowship One API.
 * @author Mark Adams
 **/
class F1Factory{
	
	protected $apiClient;
	
	/**
	 * Instantiates a new F1Factory based on the specified API client.
	 *
	 * @param string $client A valid API client
	 * @author Mark Adams
	 */
	public function __construct($client){
		$this->apiClient = $client;
	}

	/**
	 * Returns a new Person from the Fellowship One API.
	 *
	 * @return A new person template object
	 * @author Mark Adams
	 */
	public function getPersonNew(){
		$result = new _PersonNew($this->apiClient);
		return $result;
	}
	
	/**
	 * Returns an existing Person from the Fellowship One API for editing.
	 *
	 * @param string $id The ID of the person
	 * @return The person object to edit
	 * @author Mark Adams
	 */
	public function getPersonEdit($id){
		$result = new _PersonEdit($this->apiClient,$id);
		return $result;
	}
	
	/**
	 * Returns a page of active people including address information
	 *
	 * @param string $maxRecords The maximum records per page
	 * @param string $page The page to retrieve
	 * @return void An array of editable People from the Fellowship One API
	 * @author Mark Adams
	 */
	public function getActiveMembersWithAddresses($maxRecords = 100,$page = 1){
		
		//P repare the parameters
		$parameters = Array(
			'status' => 1,
			'include' => 'addresses',
			'includeInactive' => 'false',
			'includeDeceased' => 'false',
			'recordsPerPage' => $maxRecords,
			'page' => $page);
		
		$parameterString = $this->convertArrToQueryString($parameters);
		
		// Perform the request
		$postContentType = array("Accept: application/json", "Content-type: application/json");
		$requestUrl = $this->apiClient->getBaseUrl() . AppConfig::$f1_people_search;
		$searchJSON = $this->apiClient->doRequest($requestUrl . $parameterString, $postContentType);
	
		if ($searchJSON == null){
			// If something goes wrong talking to the API, throw an error.
			$info = $this->apiClient->getLoggingInfo();
			throw new ServiceException("API Error: The search request could not be completed.",$info['GET_INFO']['http_code'],$info);
		}else{
			$search_results_raw = json_decode($searchJSON,true);
			$search_results = $search_results_raw["results"]["person"];
			$people = Array();
			
			// Convert the results into an array of _PersonData
			if (count($search_results)){
				foreach ($search_results as $result){
					$person = new _PersonData($this->apiClient,$result);
					array_push($people,$person);
				}
			}

			return $people;
		}
	}

	/**
	 * Converts an array into a query string
	 *
	 * @param string $arr An array
	 * @return The array as a query string
	 * @author Mark Adams
	 */
	private function convertArrToQueryString($arr){
		$result = "?";
		$first = true;
		foreach ($arr as $key => $value){
			if (!$first){
					$result .= "&" . urlencode($key) . "=" . urlencode($value);
				}else{
					$first = false;
					$result .= urlencode($key) . "=" . urlencode($value);
			}
		}

		return $result;

	}
	
	
}

/**
 * Represents a person generated directly from JSON data
 *
 * @author Mark Adams
 */
class _PersonData extends F1Person{
	public function __construct($client,$data){
		parent::__construct($client);
		$this->data = Array('person' => $data);
		$this->modified = false;
		$this->new = false;
	}
}

/**
 * Represents a new person template retrieved from the Fellowship One API.
 *
 * @package default
 * @author Mark Adams
 */
class _PersonNew extends F1Person{
	
		public function __construct($client){
			parent::__construct($client);
			$this->data = $this->getNew();
			$this->modified = true;
			$this->new = true;
		}
		
		private function getNew(){
			$postContentType = array("Accept: application/json", "Content-type: application/json");
			$requestUrl = $this->apiClient->getBaseUrl() . AppConfig::$f1_people_new;
			$personTemplate = $this->apiClient->doRequest($requestUrl, $postContentType);
			if ($personTemplate == null){
				$info = $this->apiClient->getLoggingInfo();
				throw new ServiceException("API Error: The new person structure could not be loaded",$info['GET_INFO']['http_code'],$info);
			}else{
				return json_decode($personTemplate, true);
			}
			
		}
}

/**
 * Represents an existing editable person retrieved from the Fellowship One API.
 *
 * @package default
 * @author Mark Adams
 */
class _PersonEdit extends F1Person{
	
		public function __construct($client, $id){
			parent::__construct($client);
			$this->data = $this->getEdit($id);
			$this->modified = true;
			$this->new = false;
		}
		
		private function getEdit($id){
			$postContentType = array("Accept: application/json", "Content-type: application/json");
			$requestUrl = $this->apiClient->getBaseUrl() . str_replace("{id}",$id,AppConfig::$f1_people_edit);
			$personJSON = $this->apiClient->doRequest($requestUrl, $postContentType);
			if ($personJSON == null){
				$info = $this->apiClient->getLoggingInfo();
				throw new ServiceException("API Error: The new person structure could not be loaded",$info['GET_INFO']['http_code'],$info);
			}else{
				return json_decode($personJSON, true);
			}
			
		}
}

/*	Unimplemented Methods
	
	require_once 'F1Household.php';
	require_once 'F1Status.php';
	
	public function getHouseholdEdit($id){
		$result = new _HouseholdEdit($this->apiClient,$id);
		return $result;

	}

	public function getHouseholdNew(){
		$result = new _HouseholdNew($this->apiClient);
		return $result;
	}


public function getStatuses(){
	$postContentType = array("Accept: application/json", "Content-type: application/json");
	$requestUrl = $this->apiClient->getBaseUrl() . AppConfig::$f1_statuses_list;
	$statusJSON = $this->apiClient->doRequest($requestUrl, $postContentType);
	if ($statusJSON == null){
		$info = $this->apiClient->getLoggingInfo();
		throw new ServiceException("API Error: The new person structure could not be loaded",$info['GET_INFO']['http_code'],$info);
	}else{
		$statuses_raw = json_decode($statusJSON,true);
		$statuses = Array();
		
		$base = $statuses_raw['statuses']['status'];

		foreach ($base as $statusData){
			array_push($statuses,new F1Status($statusData));
		}
			
		return $statuses;
	}
}

public function getHouseholdMemberTypes(){
	$postContentType = array("Accept: application/json", "Content-type: application/json");
	$requestUrl = $this->apiClient->getBaseUrl() . AppConfig::$f1_householdMemberTypes_list;
	$hmtJSON = $this->apiClient->doRequest($requestUrl, $postContentType);
	if ($hmtJSON == null){
		$info = $this->apiClient->getLoggingInfo();
		throw new ServiceException("API Error: The new person structure could not be loaded",$info['GET_INFO']['http_code'],$info);
	}else{
		$hmt_raw = json_decode($hmtJSON,true);
		$hmts = Array();
		
		$base = $hmt_raw['householdMemberTypes']['householdMemberType'];

		foreach ($base as $hmtData){
			array_push($hmts,new F1HouseholdMemberType($hmtData));
		}
			
		return $hmts;
	}
}


class _HouseholdEdit extends F1Household{
	public function __construct($client,$id){
		parent::__construct($client);
		$this->data = $this->getEdit($id);
		$this->modified = false;
		$this->new = false;
	}
	
	protected function getEdit($id){
		$postContentType = array("Accept: application/json", "Content-type: application/json");
		$requestUrl = $this->apiClient->getBaseUrl() . str_replace("{id}",$id,AppConfig::$f1_household_edit);
		$householdJSON = $this->apiClient->doRequest($requestUrl, $postContentType);
		if ($householdJSON == null){
			$info = $this->apiClient->getLoggingInfo();
			throw new ServiceException("API Error: The household could not be retrieved",$info['GET_INFO']['http_code'],$info);
		}else{
			return json_decode($householdJSON,true);	
		}
		
	}
}

class _HouseholdNew extends F1Household{
	
		public function __construct($client){
			parent::__construct($client);
			$this->data = $this->getNew();
			$this->modified = true;
			$this->new = true;
		}
		
		private function getNew(){
			$postContentType = array("Accept: application/json", "Content-type: application/json");
			$requestUrl = $this->apiClient->getBaseUrl() . AppConfig::$f1_household_new;
			$householdTemplate = $this->apiClient->doRequest($requestUrl, $postContentType);
			if ($householdTemplate == null){
				$info = $this->apiClient->getLoggingInfo();
				throw new ServiceException("API Error: The new household structure could not be loaded",$info['GET_INFO']['http_code'],$info);
			}else{
				return json_decode($householdTemplate, true);
			}
			
		}
}		
*/
?>