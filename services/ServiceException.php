<?php
/**
 * This exception is used whenever an error occurs accessing the Fellowship One API.
 * @author Mark Adams
 **/
class ServiceException extends Exception{
	protected $loggingInfo = null;
	
	/**
	 * Instantiates a new ServiceException
	 *
	 * @param string $message The message to store in the exception
	 * @param string $code The error code
	 * @param string $loggingInfo The logging info for the request
	 * @author Mark Adams
	 */
	public function __construct($message,$code,$loggingInfo){
		parent::__construct($message,$code);
		$this->loggingInfo = $loggingInfo;
	}
	
	/**
	 * Returns the logging info for the request
	 *
	 * @return The logging info for the request
	 * @author Mark Adams
	 */
	public function getLoggingInfo(){
		return $this->loggingInfo;
	}
}
?>