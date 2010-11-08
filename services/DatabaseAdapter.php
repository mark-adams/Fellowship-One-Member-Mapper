<?php
/**
 * This class provides database access services to the application.
 * @author Mark Adams
 **/
class DatabaseAdapter{
	private $con = null;
	
	/**
	 * Creates a new instance of the DatabaseAdapter.
	 *
	 * @author Mark Adams
	 */
	public function __construct(){
		$this->con = new mysqli("SERVERNAME", "USER", "PASSWORD", "DATABASE");

		if ($this->con->connect_error) { 
		   throw new Exception(sprintf("Can't connect to MySQL Server. Errorcode: %s\n", mysqli_connect_error())); 
		}
	}
	
	/**
	 * Closes the database connection
	 *
	 * @return void
	 * @author Mark Adams
	 */
	public function __destruct(){
		$this->con->close();
	}


	/**
	 * Checks the cache for a particular address.
	 *
	 * @param string $address The address to check.
	 * @return Returns the coordinates if hit, otherwise returns null.
	 * @author Mark Adams
	 */
	public function checkGeocodeCache($address){
		
		$result = null;
		
		$sql = "SELECT lat,lng FROM GeocodeCache WHERE hash = SHA1(?) LIMIT 1";
		$stmt = $this->con->prepare($sql);
		
		$stmt->bind_param('s',$address);
		$stmt->execute();
		$stmt->bind_result($lat,$lng);
		
		if ($stmt->fetch()){
			// If a result is returned
			$result = Array('lat'=>$lat,'lng'=>$lng);
		}else{
			// If a result is not found
			return null;
		}
		
		$stmt->close();
		
		return $result;

	}
	
	/**
	 * Adds an address and corresponding coordinates to the cache.
	 *
	 * @param string $address The address
	 * @param string $lat The latitude
	 * @param string $lng The longitude
	 * @return Returns 1 on success, 0 if failed.
	 * @author Mark Adams
	 */
	public function addGeocodeCache($address,$lat,$lng){
		$sql = "INSERT GeocodeCache (hash,lat,lng) VALUES (SHA1(?),?,?)";
		$stmt = $this->con->prepare($sql);
		$stmt->bind_param('sss',$address,$lat,$lng);
		$stmt->execute();
		return $stmt->affected_rows;
	}
	
}

?>
