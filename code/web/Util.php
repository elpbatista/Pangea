<?php
class Util{
	
	private static $lowers = array();
	
	/**
     * Caches the result of extension_loaded() calls.
     *
     * @param string $ext  The extension name.
     *
     * @return boolean  Is the extension loaded?
     * 
     */
	static function extensionExists($ext){
		
		static $cache = array();
		  
		if (!isset($cache[$ext]))
               $cache[$ext] = extension_loaded($ext);
          
		return $cache[$ext];
	}
	
	/**
     * Makes a string lowercase.
     *
     * @param string  $string   The string to be converted.
     * @param boolean $locale   If true the string will be converted based on a
     *                          given charset, locale independent else.
     * @param string  $charset  If $locale is true, the charset to use when
     *                          converting. If not provided the current charset.
     *
     * @return string  The string with lowercase characters
     */
    static function lower($string, $locale = false, $charset = null)
    {
        if ($locale) {
            /* The existence of mb_strtolower() depends on the platform. */
            if ($this->extensionExists('mbstring') &&
                function_exists('mb_strtolower')) {
                if (is_null($charset)) {
                    $charset = 'iso-8859-1';
                }
                $ret = @mb_strtolower($string, $charset);
                if (!empty($ret)) {
                    return $ret;
                }
            }
            return strtolower($string);
        }
        
        if (!isset($this->lowers[$string])) {
            $language = setlocale(LC_CTYPE, 0);
            setlocale(LC_CTYPE, 'en_US');
            $lowers[$string] = strtolower($string);
            setlocale(LC_CTYPE, $language);
        }

        return $lowers[$string];
    }
    
    
	function splitMime( $mime ) {
		if( strpos( $mime, '/' ) !== false ) {
			return explode( '/', $mime, 2 );
		} else {
			return array( $mime, 'unknown' );
		}
	}

	static function makeGallery($img){
		$result = '';
		//$result .= '<ol class="gallery">';
		foreach ($img as $image){
			$result .= '<li class="thumb">';
			//$result .= '<div class="entity">';
			//$result .= '<div class="thumb">';
			$result .= "<img src=\"".$image['url']."\" alt=\"".$image['name']."\" />";
			//$result .= "</div>";
			$result .= '<a href="#">'.$image['name'].'</a>';
			$result .= "<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit.</p>";
			//$result .= "</div>";
			$result .= "</li>";
		}	
			
		//$result .= "</ol>"; 
		return $result;	
	}
	
}

function backgroundPost($url) {
    $parts = parse_url ( $url );
    
    $fp = fsockopen ( $parts ['host'], isset ( $parts ['port'] ) ? $parts ['port'] : 80, $errno, $errstr, 30 );
    
    if (! $fp) {
        return false;
    } else {
        $out = "POST " . $parts ['path'] . " HTTP/1.1\r\n";
        $out .= "Host: " . $parts ['host'] . "\r\n";
        $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $out .= "Content-Length: " . strlen ( $parts ['query'] ) . "\r\n";
        $out .= "Connection: Close\r\n\r\n";
        if (isset ( $parts ['query'] ))
            $out .= $parts ['query'];
        
        fwrite ( $fp, $out );
        fclose ( $fp );
        
        return true;
    }
}

//$r = new HTTPRequest('http://www.example.com'); 
//echo $r->DownloadToString(); 

class HTTPRequest 
{ 
    var $_fp;        // HTTP socket 
    var $_url;        // full URL 
    var $_host;        // HTTP host 
    var $_protocol;    // protocol (HTTP/HTTPS) 
    var $_uri;        // request URI 
    var $_port;        // port 
    
    // scan url 
    function _scan_url() 
    { 
        $req = $this->_url; 
        
        $pos = strpos($req, '://'); 
        $this->_protocol = strtolower(substr($req, 0, $pos)); 
        
        $req = substr($req, $pos+3); 
        $pos = strpos($req, '/'); 
        if($pos === false) 
            $pos = strlen($req); 
        $host = substr($req, 0, $pos); 
        
        if(strpos($host, ':') !== false) 
        { 
            list($this->_host, $this->_port) = explode(':', $host); 
        } 
        else 
        { 
            $this->_host = $host; 
            $this->_port = ($this->_protocol == 'https') ? 443 : 80; 
        } 
        
        $this->_uri = substr($req, $pos); 
        if($this->_uri == '') 
            $this->_uri = '/'; 
    } 
    
    // constructor 
    function HTTPRequest($url) 
    { 
        $this->_url = $url; 
        $this->_scan_url(); 
    } 
    
    // download URL to string 
    function DownloadToString() 
    { 
        $crlf = "\r\n";
        $response =""; //added by PB
        
        // generate request 
        $req = 'GET ' . $this->_uri . ' HTTP/1.0' . $crlf 
            .    'Host: ' . $this->_host . $crlf 
            .    $crlf; 
        
        // fetch 
        $this->_fp = fsockopen(($this->_protocol == 'https' ? 'ssl://' : '') . $this->_host, $this->_port); 
        fwrite($this->_fp, $req); 
        while(is_resource($this->_fp) && $this->_fp && !feof($this->_fp)) 
            $response .= fread($this->_fp, 1024); 
        fclose($this->_fp); 
        
        // split header and body 
        $pos = strpos($response, $crlf . $crlf); 
        if($pos === false) 
            return($response); 
        $header = substr($response, 0, $pos); 
        $body = substr($response, $pos + 2 * strlen($crlf)); 
        
        // parse headers 
        $headers = array(); 
        $lines = explode($crlf, $header); 
        foreach($lines as $line) 
            if(($pos = strpos($line, ':')) !== false) 
                $headers[strtolower(trim(substr($line, 0, $pos)))] = trim(substr($line, $pos+1)); 
        
        // redirection? 
        if(isset($headers['location'])) 
        { 
            $http = new HTTPRequest($headers['location']); 
            return($http->DownloadToString($http)); 
        } 
        else 
        { 
            return($body); 
        } 
    } 
} 
/*
if(json_decode($xnpl) == NULL) {
	echo $xnpl." not valid json!";
}
else {
	$exemplars = json_decode($xnpl, true);
}
*/
