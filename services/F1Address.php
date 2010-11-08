<?php
require_once('ServiceException.php');

/**
 * This class represents an address stored in Fellowship One.
 * @author Mark Adams
 **/
class F1Address
{
	protected $data = null;
	protected $apiClient = null;
	protected $new = null;
	protected $modified = null;
	
	/**
	 * Instantiates a new F1Address
	 *
	 * @param string $client Valid API client
	 * @param string $data Data used to create F1Address
	 * @author Mark Adams
	 */
	public function __construct($client, $data = null){
		if ($data != null){
			$this->data = $data;
		}
		$this->apiClient = $client;
	}

	/* GETTERS */	
	
	/**
	 * Gets the ID of the address
	 *
	 * @return The ID of the address
	 * @author Mark Adams
	 */
	public function getID(){
		return $this->getValue('@id');
	}
	
	/**
	 * Returns a combined address string for geocoding
	 *
	 * @return A combined address string
	 * @author Mark Adams
	 */
	public function getMapAddressString(){
		return $this->getValue('address1') . ', ' . $this->getValue('city') . ', ' . $this->getValue('stProvince') . ', ' . $this->getvalue('postalCode');
		
	}

	/* PRIVATE FUNCTIONS */
	private function getValue($name){
		return $this->data[$name];
	}

	private function setValue($name,$value){
		$this->data[$name] = $value;
		$this->modified = true;
	}
}

?>