<?php
session_start();
require_once 'lib/OAuth/AppConfig.php';
require_once 'lib/OAuth/OAuthClient.php';
require_once 'F1Factory.php';
require_once 'Geocoder.php';
require_once 'DatabaseAdapter.php';
require_once 'ServiceResponse.php';

/********************************************************************************************/
/* FUNCTIONS
/********************************************************************************************/

/**
 * Configures and returns the oAuth client.
 *
 * @return A valid oAuth client
 * @author Mark Adams
 */
function getOAuthClient(){
	// Load tokens from the session.
	$access_token = $_SESSION["oauth_access_token"];
	$token_secret = $_SESSION["oauth_token_secret"];
	
	// Create the new client and initalize the tokens
	$apiConsumer = new OAuthClient(AppConfig::$base_url, AppConfig::$consumer_key, AppConfig::$consumer_secret);
	$apiConsumer->initAccessToken($access_token,
	                          $token_secret);
	
	return $apiConsumer;
}

/**
 * Prepare a simplified person array that is a subset of a F1Person in order to reduce the amount of data
 * transferred to the client.
 *
 * @param F1Person $person The F1Person to use as a source for the simplified person
 * @return void The simplified person array
 * @author Mark Adams
 */
function getSimplePerson(F1Person $person){
	$simplePerson = Array();
	$simplePerson['@id'] = $person->getID();
	$simplePerson['firstName'] = $person->getFirstName();
	$simplePerson['lastName'] = $person->getLastName();
	return $simplePerson;
}

/**
 * Retrieves a list of member addresses with locations and names of members residing at that location,
 *
 * @param string $page The page of results to retrieve
 * @return void A ServiceResponse containing the array of addresses and members as the data.
 * @author Mark Adams
 */
function getMemberAddresses($page){
	// Create a new database connection, geocoder, and F1Factory.
	$db = new DatabaseAdapter();
	$gc = new Geocoder($db);
	$fac = new F1Factory(getOAuthClient());
	
	$resp = null;
	
	try {
		// Retrieve the addresses with the API
		$members = $fac->getActiveMembersWithAddresses(200,$page);

		$addressUserMap = Array();

		foreach ($members as $person){
			// For every member returned, convert them to a simple person
			$simplePerson = getSimplePerson($person);

			// Retrieve the person's addresses
			$addresses = $person->getAddresses();
			if (count($addresses)){
				// If the person has at least one address

				foreach ($addresses as $address){
					// For each address, get the combined address string
					$addressStr = $address->getMapAddressString();

					if (!isset($addressUserMap[$addressStr])){
						// If the address is not already in the array, geocode it
						try{
							// Geocode the address
							$geocodeResult = $gc->getLatLng($addressStr);

							if ($geocodeResult){
								// If the result can be geocoded, add it to the array along with the associated person
								$addressUserMap[$addressStr] = Array(
									'location'=>$geocodeResult,
									'people'=>Array($simplePerson)
								);
							};
						}catch(Exception $ex){
							// The geocoding request had a problem, ignore and move on...		
						}
					}else{
						// If the address is already in the array, add the person to it.
						array_push($addressUserMap[$addressStr]['people'],$simplePerson);
					}
				}
			}

		}
		
		// Prepare the response
		$resp = new PagedServiceResponse(
				"OK",
				Array("addresses"=> $addressUserMap),
				count($addressUserMap),
				(int)$page,
				count($members)
			);
		
	} catch (Exception $ex) {
		$resp = new ServiceResponse("ERROR",$ex->getMessage(),0);
	}
	
	return $resp;
	
}

/**
 * Update a person's name in Fellowship One
 *
 * @param string $id The id of the Person to update
 * @param string $name The updated name of the person
 * @return A ServiceResponse containing the updated SimplePerson as the data.
 * @author Mark Adams
 */
function updatePersonName($id,$name){
	$resp = null;
	
	// Split the name by spaces to split into first and last name
	$nameParts = explode(" ",$name);
	
	// Count the words in the name
	$partsCount = count($nameParts);
	
	$firstName = null;
	$lastName = null;

	if ($partsCount > 1){
		// If the name has several parts, the last word should be the last name. The rest will be the first name.
		$lastName = $nameParts[$partsCount - 1];
		unset($nameParts[$partsCount - 1]);
		$firstName = implode(" ",$nameParts);
		
		try{		
			// Get the preson from Fellowship One.
			$fac = new F1Factory(getOAuthClient());
			$person = $fac->getPersonEdit($id);
			
			// Change the person's name
			$person->setFirstName($firstName);
			$person->setLastName($lastName);
			
			// Save the changes
			$person->save();

			$resp = new ServiceResponse("OK",Array("person" => getSimplePerson($person)),1);
		
		}catch(ServiceException $ex){
			$resp = new ServiceResponse("ERROR",$ex->getMessage(),0);
		}
	}else{
		$resp = new ServiceResponse("ERROR","The name could not be updated. Make sure the name contains at least a first and last name.",0);
	}
	
	return $resp;
}

/********************************************************************************************/
/* MAIN BODY BEGINS
/********************************************************************************************/

$parameters = null;

// Decide whether we are using GET or POST for this request based on the o parameter. GET takes precedence.

if (isset($_GET["o"])){
	$parameters = $_GET;
}elseif (isset($_POST["o"])){
	$parameters = $_POST;
}

$operation = $parameters["o"];
$response = null;

// Based on the operation, execute the appropriate method.
switch ($operation){
	case "updatePersonName":
		$resp = null;
		if (isset($parameters["id"]) && (is_numeric($parameters["id"]))){
			// If the ID exists and is numeric
			if (isset($parameters["name"])){
				// If the name exists, update the person's name
				$resp = updatePersonName($parameters["id"],$parameters["name"]);
			}else{
				// If the name is missing, return an error.
				$resp = new ServiceResponse("ERROR","Missing parameters: name",0);
			}
		}else{
			// If the ID is missing or invalid, return an error.
			$resp = new ServiceResponse("ERROR","Missing or invalid parameter: id", 0);
		}
		// Encode the response
		$response = json_encode($resp);
		break;
	case "getAddresses":
		$resp = null;
		
		// Default page is 1
		$page = 1;
		
		if (isset($parameters["page"]) && (is_numeric($parameters["page"]))){
				// If the page parameter is valid, use it
				$page = $parameters["page"];
		}else{
				// Otherwise return an error.
				$resp = new ServiceResponse("ERROR","Invalid value for parameter: page",0);
		}
		
		// Retrieve and encode the response
		$response = json_encode(getMemberAddresses($page));
		break;
	default:
		// Return an error if the operation is invalid
		$response = json_encode(new ServiceResponse("ERROR","The specified operation is invalid.",0));
}

// Send the appropriate headers to prevent caching and set type to JSON.
header("Cache-Control: no-cache, must-revalidate");
header('Content-type: application/json');

// Return the result as JSON
echo $response;

?>