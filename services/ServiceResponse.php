<?php
/**
 * This class represents a result returned by the application for a particular request.
 * @author Mark Adams
 **/
class ServiceResponse{
	public function __construct($status = null, $data = null, $dataCount = null){
		$this->status = $status;
		$this->data = $data;
		$this->dataCount = $dataCount;
	}
	
	/* The status of the request (either OK or ERROR usually) */
	public $status;
	
	/* The number of succesful data items loaded in data */
	public $dataCount;
	
	/* The returned data; or an error message if status = ERROR */
    public $data;
}

/**
 * ServiceResponse
 *
 * This class represents a result returned by the application for a particular request for which the results are paged.
 * @author Mark Adams
 **/
class PagedServiceResponse extends ServiceResponse{
	public function __construct($status = null, $data = null, $dataCount = null, $page = null, $apiCount = null){
		parent::__construct($status,$data,$dataCount);
		$this->page = $page;
		$this->apiCount = $apiCount;
	}
	
	/* The page of the returned data */
	public $page;
	
	/* The total number of items returned by the API request before application filtering is performed. This is
	 * used primarily to determine if more pages might exist. */
	public $apiCount;

}
?>