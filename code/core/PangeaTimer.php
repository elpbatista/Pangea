<?php
class PangeaTimer {
	
	private $classname = "PangeaTimer";
	private $start = 0;
	private $stop = 0;
	private $elapsed = 0;
	
	# Constructor 
	function PangeaTimer($start = true) {
		if ($start)
			$this->start ();
	}
	
	# Start counting time 
	function start() {
		$this->start = $this->_gettime ();
	}
	
	# Stop counting time 
	function stop() {
		$this->stop = $this->_gettime ();
		$this->elapsed = $this->_compute ();
	}
	
	# Get Elapsed Time 
	function _getelapsed() {
		if (! $this->elapsed)
			$this->stop ();
		
		return round ( $this->elapsed, 4 );
	}
	
	# Get Elapsed Time 
	function reset() {
		$this->start = 0;
		$this->stop = 0;
		$this->elapsed = 0;
	}
	
	#### PRIVATE METHODS #### 
	

	# Get Current Time 
	private function _gettime() {
		$mtime = microtime (TRUE);
		/*
		$mtime = explode ( " ", $mtime );
		return $mtime [1] + $mtime [0]; 
		 * */
		
		return $mtime;
	}
	
	# Compute elapsed time 
	private function _compute() {
		return $this->stop - $this->start;
	}
}