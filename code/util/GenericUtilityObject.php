<?php
class GenericUtilityObject {
	
	function __construct() {
	
	}
	
	function buildTriple($subject, $predicate, $object) {
		$triple = array ();
		
		if (! is_array ( $object )) {
			$newObject ['type'] = (is_numeric ( $object )) ? 'uri' : 'literal';
			$newObject ['value'] = $object;
			
			$object = $newObject;
		} else {
			$keys = array_keys ( $object );
			
			if (isset ( $keys [2] ) && (! isset ( $object [$keys [2]] ) || empty ( $object [$keys [2]] )))
				unset ( $object [$keys [2]] );
			
			if (isset ( $keys [3] ) && (! isset ( $object [$keys [3]] ) || empty ( $object [$keys [3]] )))
				unset ( $object [$keys [3]] );
		}
		
		$triple ['subject'] = $subject;
		$triple ['predicate'] = $predicate;
		$triple ['object'] = $object;
		
		return $triple;
	}
	
	/**
	 * calcDate
	 *
	 * @access protected
	 * @param  string $interval
	 * @return date
	 */
	function calcDate($interval) {
		date_default_timezone_set ( PG_DEFAULT_TIMEZONE );
		
		$calc = strtotime ( "+ " . $interval . " day" );
		
		$return = date ( PG_DEFAULT_DATETIME_FORMAT, $calc );
		
		return $return;
	}
	
	/**
	 * comparingDates
	 *
	 * @access protected
	 * @param  string $date1, string $date2
	 * @return float
	 */
	function comparingDates($date1, $date2) {
		return round ( (strtotime ( $date2 ) - strtotime ( $date1 )) / 60 / 60 / 24 );
	}
	
	function getINValueFromArray($valuesArray) {
		if (is_array ( $valuesArray ))
			$result = implode ( ",", $valuesArray );
		else
			$result = $valuesArray;
		
		return $result;
	}
	
	function getListOffsetAndPgSize($page, $pageSize, $total) {
		$offsets = array ('offset' => 0, 'pageSize' => 0, 'pages' => 0 );
		
		$pages = ceil ( $total / $pageSize );
		
		if (($page > $pages) || ($total == 0))
			return $offsets;
		
		$offsets ['pageSize'] = $pageSize;
		
		if (($page < 1) or ($pageSize < 1)) {
			$page = 1;
			$pageSize = 1;
			$offsets ['pageSize'] = $total;
		} else
			$offsets ['offset'] = ($page - 1) * $pageSize;
		
		if ($page > $pages)
			$offsets ['offset'] = ($pages - 1) * $pageSize;
		
		$offsets ['pages'] = $pages;
		
		return $offsets;
	}
	
	function getListPage($list, $page, $pageSize) {
		$listPage = array ();
		
		if (isset ( $list ) && is_array ( $list ) && ! empty ( $list )) {
			$chunks = array_chunk ( $list, $pageSize, TRUE );
			
			if ($page > 0)
				$page --;
			
			if ($page > sizeof ( $chunks ))
				$page = 0;
			
			$listPage = $chunks [$page];
		}
		
		return $listPage;
	}
	
	/**
	 * getLowercaseStream
	 *
	 * @access protected
	 * @param  string $tream
	 * @return string
	 */
	function getLowercaseStream($stream) {
		$endoding = mb_detect_encoding ( $stream );
		
		$stream = mb_strtolower ( $stream, $endoding );
		
		return $stream;
	}
	
	function getSerializeDBObject($object) {
		/*		 
		$search = array ('&', '"', '\'', '<', '>', "\t", "\r", "\n", "\0" );
		$replace = array ('&amp;', '&quot;', '&#039;', '&lt;', '&gt;', '&nbsp;&nbsp;', '&#13;', '&#10;', "~~NULL_BYTE~~" ); 
		 * */
		$search = array ("\0" );
		$replace = array (PG_NULL_CHAR_STR_REPLACE );
		
		$stream = '';
		if (isset ( $object ))
			$stream = str_replace ( $search, $replace, serialize ( $object ) );
		
		return $stream;
	}
	
	function getSortedArray($unsortedArray, $asc = TRUE) {
		$iterator = new ArrayIterator ( $unsortedArray );
		
		$iterator->natsort ();
		
		$sortedArray = $iterator->getArrayCopy ();
		
		if (! $asc)
			$sortedArray = array_reverse ( $sortedArray );
		
		return $sortedArray;
	}
	
	function getUnserializeDBObject($stream) {
		/*
		$search = array ('&amp;', '&quot;', '&#039;', '&lt;', '&gt;', '&nbsp;&nbsp;', '&#13;', "~~NULL_BYTE~~" );
		$replace = array ('&', '"', '\'', '<', '>', "\t", "\r", "\0" );
		 * */
		
		$search = array (PG_NULL_CHAR_STR_REPLACE );
		$replace = array ("\0" );
		
		$object = null;
		if (isset ( $stream ) && (! empty ( $stream )))
			$object = unserialize ( str_replace ( $search, $replace, $stream ) );
		
		return $object;
	}
	
	/**
	 * objectToArray
	 *
	 * @access protected
	 * @param  object $obj
	 * @return array
	 */
	function objectToArray($obj) {
		if (is_object ( $obj ))
			$obj = get_object_vars ( $obj );
		else
			return $obj;
		
		foreach ( $obj as $key => $value )
			$obj [$key] = $this->objectToArray ( $value );
		
		return $obj;
	}
	
	function __destruct() {
	
	}
}

?>