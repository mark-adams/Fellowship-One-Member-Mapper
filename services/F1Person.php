<?php
require_once('ServiceException.php');
require_once('F1Address.php');

/**
 * This abstract class represents a person stored within Fellowship One.
 * @author Mark Adams
 **/
abstract class F1Person
{
	protected $data = null;
	protected $apiClient = null;
	protected $new = null;
	protected $modified = null;
	
	/**
	 *  Performs base class initialization of the F1Person object
	 *
	 * @param string $client 
	 * @author Mark Adams
	 */
	public function __construct($client){
		$this->apiClient = $client;
	}

	/* GETTERS */
	
	/**
	 * Retrieves the ID of the specified Person
	 *
	 * @return The ID of the specified person
	 * @author Mark Adams
	 */	
	public function getID(){
		return $this->getValue('@id');
	}
	
	/**
	 * Retrieves the first name of the specified person
	 *
	 * @return The first name of the specified person
	 * @author Mark Adams
	 */
	public function getFirstName(){
		return $this->getValue('firstName');
	}
	
	/**
	 * Retrieves the last name of the specified person
	 *
	 * @return The last name of the specified person
	 * @author Mark Adams
	 */
	public function getLastName(){
		return $this->getValue('lastName');
	}
	
	/**
	 * Retrieves the addresses of the specified person
	 *
	 * @return An array containing the addresses of the person as F1Address objects.
	 * @author Mark Adams
	 */
	public function getAddresses(){
		$addresses = $this->getValue('addresses');
		$result = null;
		if ($addresses != null){
			$result = Array();
			foreach ($addresses as $address){
				$addrObj = new F1Address($apiClient,$address[0]);
				array_push($result,$addrObj);
			}
		}
	
		return $result;
	}
	
	/* SETTERS */
	
	/**
	 * Sets the first name of the person
	 *
	 * @param string $value The value to be set
	 * @author Mark Adams
	 */
	public function setFirstName($value){
		$this->setValue('firstName',$value);
	}
	
	/**
	 * Sets the last name of the person
	 *
	 * @param string $value The value to be set
	 * @author Mark Adams
	 */
	public function setLastName($value){
		$this->setValue('lastName',$value);
	}
	
	/* PRIVATE FUNCTIONS */
	private function getValue($name){
		return $this->data['person'][$name];
	}

	private function setValue($name,$value){
		$this->data['person'][$name] = $value;
		$this->modified = true;
	}	

	/**
	 * Saves any changes to the created or edited user into Fellowship One.
	 *
	 * @return void
	 * @author Mark Adams
	 */
	public function save(){
		// If the Person is new or has been modified, save the changes
		if ($this->new){
			// If the user is new, create the user:
			
			$postContentType = array("Accept: application/json", "Content-type: application/json");
			$requestUrl = $this->apiClient->getBaseUrl() . AppConfig::$f1_people_create;
			$personJSON = $this->apiClient->postRequest($requestUrl, json_encode($this->data),$postContentType);
			
			if ($personJSON == null){
				// If a problem occurs, throw an exception
				$info = $this->apiClient->getLoggingInfo();
				throw new ServiceException("API Error: The person could not be created",$info['GET_INFO']['http_code'],$info);
			}else{
				// If everything works, replace current data with the new data from Fellowship One
				$this->data = json_decode($personJSON,true);
				
				// Mark the user as no longer new and no longer modified
				$this->new = false;
				$this->modified = false;
			}	
		}elseif($this->modified){
			// If the user already exists and is being modified, save the changes:
			
			$id = $this->getID();
			$postContentType = array("Accept: application/json", "Content-type: application/json");
			$requestUrl = $this->apiClient->getBaseUrl() . str_replace("{id}",$id,AppConfig::$f1_people_update);
			$personJSON = $this->apiClient->postRequest($requestUrl, json_encode($this->data),$postContentType,200);
			if ($personJSON == null){
				// If a problem occurs, throw an exception
				$info = $this->apiClient->getLoggingInfo();
				throw new ServiceException("API Error: The person could not be updated",$info['GET_INFO']['http_code'],$info);
			}else{
				// If everything works, replace current data with new data from Fellowship One.
				$this->data = json_decode($personJSON,true);
				
				// Change modified to false
				$this->modified = false;
			}
			
		}
	}
}

/* Unimplemented Classes */
/*
class F1HouseholdMemberType{
	
	protected $data = null;
	
	public function __construct($data){
		unset($data['@array']);
		$this->data = $data;

	}
	
	public function getID(){
		return $this->data['@id'];
	}
	
	public function getName(){
		return $this->data['name'];
	}
	
	public function getData(){
		return $this->data;
	}

}
*/
?>