<?php
/**
 * A simple PHP library for doing RESTful HTTP stuff. Does *not* require the curl extension.
 * @link https://github.com/fictivekin/resty.php
 */
class Resty
{

	/**
	 * The version of this lib
	 */
	const VERSION = '0.3.2';

	const DEFAULT_TIMEOUT = 240;

	/**
	 * @var bool enables debugging output
	 */
	protected $debug = false;

	/**
	 * @var bool whether or not to auto-parse the response body as JSON or XML
	 */
	protected $parse_body = true;

	/**
	 * @var string
	 * @see Resty::getUserAgent()
	 */
	protected $user_agent = null;

	/**
	 * @var string
	 */
	protected $base_url;

	/**
	 * Stores the last request hash
	 * @var array
	 */
	protected $last_request;

	/**
	 * Stores the last response hash
	 * @var array
	 */
	protected $last_response;

	/**
	 * stores anon func callbacks (because you can't store them as obj props
	 * @var array
	 */
	protected $callbacks = array();

	/**
	 * username for basic auth
	 * @var string
	 */
	protected $username;

	/**
	 * password for basic auth
	 * @var string
	 */
	protected $password;

	/**
	 * content-types that will trigger JSON parsing of body
	 * @var array
	 */
	public static $JSON_TYPES = array(
		'application/json',
		'text/json',
		'text/x-json',
	);

	/**
	 * content-types that will trigger XML parsing
	 * @var array
	 */
	public static $XML_TYPES = array(
		'application/xml',
		'text/xml',
		'application/rss+xml',
		'application/xhtml+xml',
		'application/atom+xml',
		'application/xslt+xml',
		'application/mathml+xml',
	);


	/**
	 * Passed opts can include
	 * $opts['onRequestLog'] - an anonymous function that takes the Resty::last_request property as arg
	 * $opts['onResponseLog'] - an anonymous function that takes the Resty::last_response property as arg
	 *
	 * @see Resty::last_request
	 * @see Resty::last_response
	 * @see Resty::sendRequest()
	 * @param array $opts OPTIONAL array of options
	 */
	function __construct($opts=null) {
		if (!empty($opts['onRequestLog']) && ($opts['onRequestLog'] instanceof Closure)) {
			$this->callbacks['onRequestLog'] = $opts['onRequestLog'];
		}
		if (!empty($opts['onResponseLog']) && ($opts['onResponseLog'] instanceof Closure)) {
			$this->callbacks['onResponseLog'] = $opts['onResponseLog'];
		}
	}


	/**
	 * retrieve the last request we sent
	 *
	 * valid keys are ['url', 'method', 'querydata', 'headers', 'options', 'opts']
	 *
	 * @param string $key just retrieve a given field from the hash
	 * @return mixed
	 */
	public function getLastRequest($key=null) {
		if (!isset($key)) {
			return $this->last_request;
		}

		return $this->last_request[$key];

	}

	/**
	 * retrieve the last response we got
	 *
	 * valid keys are ['meta', 'status', 'headers', 'body']
	 *
	 * @param string $key just retrieve a given field from the hash
	 * @return mixed
	 */
	public function getLastResponse($key=null) {
		if (!isset($key)) {
			return $this->last_response;
		}

		return $this->last_response[$key];

	}

	/**
	 * make a GET request
	 *
	 * @param string the URL. This will be appended to the base_url, if any set
	 * @param array $querydata hash of key/val pairs
	 * @param array $headers hash of key/val pairs
	 * @param array $options hash of key/val pairs ('timeout')
	 * @return array the response hash
	 * @see Resty::sendRequest()
	 */
	public function get($url, $querydata=null, $headers=null, $options=null) {
		return $this->sendRequest($url, 'GET', $querydata, $headers, $options);
	}

	/**
	 * make a POST request
	 *
	 * @param string the URL. This will be appended to the base_url, if any set
	 * @param array $querydata hash of key/val pairs
	 * @param array $headers hash of key/val pairs
	 * @param array $options hash of key/val pairs ('timeout')
	 * @return array the response hash
	 * @see Resty::sendRequest()
	 */
	public function post($url, $querydata=null, $headers=null, $options=null) {
		return $this->sendRequest($url, 'POST', $querydata, $headers, $options);
	}

	/**
	 * make a PUT request
	 *
	 * @param string the URL. This will be appended to the base_url, if any set
	 * @param array $querydata hash of key/val pairs
	 * @param array $headers hash of key/val pairs
	 * @param array $options hash of key/val pairs ('timeout')
	 * @return array the response hash
	 * @see Resty::sendRequest()
	 */
	public function put($url, $querydata=null, $headers=null, $options=null) {
		return $this->sendRequest($url, 'PUT', $querydata, $headers, $options);
	}

	/**
	 * make a DELETE request
	 *
	 * @param string the URL. This will be appended to the base_url, if any set
	 * @param array $querydata hash of key/val pairs
	 * @param array $headers hash of key/val pairs
	 * @param array $options hash of key/val pairs ('timeout')
	 * @return array the response hash
	 * @see Resty::sendRequest()
	 */
	public function delete($url, $querydata=null, $headers=null, $options=null) {
		return $this->sendRequest($url, 'DELETE', $querydata, $headers, $options);
	}

	/**
	 * @param string $url
	 * @param array  $files
	 * @param array  $params
	 * @param array  $headers
	 * @param array  $options
	 *
	 * The $files array should be a set of key/val pairs, with the key being
	 * the field name, and the val the file path. ex:
	 * $files['avatar'] = '/path/to/file.jpg';
	 * $files['background'] = '/path/to/file2.jpg';
	 *
	 */
	public function postFiles($url, $files, $params=null, $headers=null, $options=null) {

		$datastr = "";
		$boundary = "---------------------".substr(md5(rand(0,32000)), 0, 10);

		// build params
		if (isset($params)) {
			foreach($params as $key => $val) {
				$datastr .= "--$boundary\n";
				$datastr .= "Content-Disposition: form-data; name=\"".$key."\"\n\n".$val."\n";
			}
		}
		$datastr .= "--$boundary\n";

		// build files
		foreach($files as $key => $file)
		{
			$filename = pathinfo($file, PATHINFO_BASENAME);
			$content_type = $this->getMimeType($file);
			$fileContents = file_get_contents($file);

			$datastr .= "Content-Disposition: form-data; name=\"{$key}\"; filename=\"{$filename}\"\n";
			$datastr .= "Content-Type: {$content_type}\n";
			$datastr .= "Content-Transfer-Encoding: binary\n\n";
			$datastr .= $fileContents."\n";
			$datastr .= "--$boundary\n";
		}

		if (!isset($headers)) {
			$headers = array();
		}
		$headers['Content-Type'] = 'multipart/form-data; boundary='.$boundary;

		return $this->post($url, $datastr, $headers, $options);
	}


	/**
	 * @param string $url
	 * @param array  $binary_data
	 * @param array  $params
	 * @param array  $headers
	 * @param array  $options
	 *
	 * The $binary_data array should be a set of key/val pairs, with the key being
	 * the field name, and the val the binary data. ex:
	 * $files['avatar'] = <BINARY>;
	 * $files['background'] = <BINARY>;
	 *
	 * with that data, a multipart POST body is created, identical to a file
	 * upload, just without reading the data from a file
	 *
	 */
	public function postBinary($url, $binary_data, $params=null, $headers=null, $options=null) {

		$datastr = "";
		$boundary = "---------------------".substr(md5(rand(0,32000)), 0, 10);

		// build params
		if (isset($params)) {
			foreach($params as $key => $val) {
				$datastr .= "--$boundary\n";
				$datastr .= "Content-Disposition: form-data; name=\"".$key."\"\n\n".$val."\n";
			}
		}
		$datastr .= "--$boundary\n";

		// build files
		foreach($binary_data as $key => $bdata)
		{
			$filename = 'bdata';
			$content_type = 'application/octet-stream';

			$datastr .= "Content-Disposition: form-data; name=\"{$key}\"; filename=\"{$filename}\"\n";
			$datastr .= "Content-Type: {$content_type}\n";
			$datastr .= "Content-Transfer-Encoding: binary\n\n";
			$datastr .= $bdata."\n";
			$datastr .= "--$boundary\n";
		}

		if (!isset($headers)) {
			$headers = array();
		}
		$headers['Content-Type'] = 'multipart/form-data; boundary='.$boundary;

		return $this->post($url, $datastr, $headers, $options);
	}

	/**
	 * @see Resty::postFiles()
	 *
	 * Stole this from the Amazon S3 class:
	 *
	 * Copyright (c) 2008, Donovan Schönknecht.  All rights reserved.
	 *
	 * Redistribution and use in source and binary forms, with or without
	 * modification, are permitted provided that the following conditions are met:
	 *
	 * - Redistributions of source code must retain the above copyright notice,
	 *   this list of conditions and the following disclaimer.
	 * - Redistributions in binary form must reproduce the above copyright
	 *   notice, this list of conditions and the following disclaimer in the
	 *   documentation and/or other materials provided with the distribution.
	 *
	 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
	 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
	 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
	 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
	 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
	 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	 * POSSIBILITY OF SUCH DAMAGE.
	 *
	 * Amazon S3 is a trademark of Amazon.com, Inc. or its affiliates.
	 *
	 */
	protected function getMimeType($filepath) {

		if (extension_loaded('fileinfo') && isset($_ENV['MAGIC']) &&
			($finfo = finfo_open(FILEINFO_MIME, $_ENV['MAGIC'])) !== false) {

			if (($type = finfo_file($finfo, $filepath)) !== false) {
				// Remove the charset and grab the last content-type
				$type = explode(' ', str_replace('; charset=', ';charset=', $type));
				$type = array_pop($type);
				$type = explode(';', $type);
				$type = trim(array_shift($type));
			}
			finfo_close($finfo);

		// If anyone is still using mime_content_type()
		} elseif (function_exists('mime_content_type')) {
			$type = trim(mime_content_type($filepath));
		}

		if ($type !== false && strlen($type) > 0) { return $type; }

		// Otherwise do it the old fashioned way
		static $exts = array(
			'jpg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png',
			'tif' => 'image/tiff', 'tiff' => 'image/tiff', 'ico' => 'image/x-icon',
			'swf' => 'application/x-shockwave-flash', 'pdf' => 'application/pdf',
			'zip' => 'application/zip', 'gz' => 'application/x-gzip',
			'tar' => 'application/x-tar', 'bz' => 'application/x-bzip',
			'bz2' => 'application/x-bzip2', 'txt' => 'text/plain',
			'asc' => 'text/plain', 'htm' => 'text/html', 'html' => 'text/html',
			'css' => 'text/css', 'js' => 'text/javascript',
			'xml' => 'text/xml', 'xsl' => 'application/xsl+xml',
			'ogg' => 'application/ogg', 'mp3' => 'audio/mpeg', 'wav' => 'audio/x-wav',
			'avi' => 'video/x-msvideo', 'mpg' => 'video/mpeg', 'mpeg' => 'video/mpeg',
			'mov' => 'video/quicktime', 'flv' => 'video/x-flv', 'php' => 'text/x-php'
		);
		$ext = strtolower(pathInfo($filepath, PATHINFO_EXTENSION));

		return isset($exts[$ext]) ? $exts[$ext] : 'application/octet-stream';
	}

	/**
	 * bc wrapper
	 */
	public function enableDebugging($state=false) {
		$this->debug($state);
	}

	/**
	 * enable or disable debugging. default is false
	 * @param bool $state default FALSE
	 */
	public function debug($state=false) {
		$state = (bool)$state;
		$this->debug = $state;
	}

	/**
	 * enable or disable automatic parsing of body. default is true
	 * @param bool $state default TRUE
	 */
	public function parseBody($state=true) {
		$state = (bool)$state;
		$this->parse_body = $state;
	}

	/**
	 * Sets the base URL for all subsequent requests
	 * @param string $base_url
	 */
	public function setBaseURL($base_url) {
		$this->base_url = $base_url;
	}

	/**
	 * retrieves the current Resty::$base_url
	 * @return string
	 */
	public function getBaseURL() {
		return $this->base_url;
	}

	/**
	 * Sets the user-agent
	 * @param string $user_agent
	 */
	public function setUserAgent($user_agent) {
		$this->user_agent = $user_agent;
	}

	/**
	 * Gets the current user agent. if Resty::$user_agent is not set, uses a default
	 * @return string
	 */
	public function getUserAgent() {
		if (empty($this->user_agent)) {
			$this->user_agent = 'Resty ' . static::VERSION;
		}
		return $this->user_agent;
	}

	/**
	 * Sets credentials for http basic auth
	 * @param string $username
	 * @param string $password
	 */
	public function setCredentials($username, $password) {
		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * removes current credentials
	 */
	public function clearCredentials() {
		$this->username = null;
		$this->password = null;
	}

	/**
	 * logging helper
	 *
	 * @param mixed $msg
	 */
	protected function log($msg) {

		if (!$this->debug) { return; }

		$line = date(\DateTime::RFC822) . " :: ";

		if (is_string($msg)) {
			$line .= "{$msg}\n";
		} else {
			ob_start();
			var_dump($msg);
			$line = ob_get_clean();
			$line .= "\n";
		}

		if (PHP_SAPI === 'cli') {
			echo $line;
		} else {
			echo "<pre>$line</pre>\n";
		}
	}


	/**
	 * takes a set of key/val pairs and builds an array of raw header strings
	 *
	 * @param string $headers
	 * @return void
	 * @author Ed Finkler
	 */
	protected function buildHeadersArray($headers) {
		$str_headers = array();
		foreach ($headers as $key => $value) {
			$str_headers[] = "{$key}: {$value}";
		}
		return $str_headers;
	}

	/**
	 * Extracts the headers of a response from the stream's meta data
	 * @param array $meta
	 * @return array
	 */
	protected function metaToHeaders($meta) {
		$headers = array();
		foreach ($meta['wrapper_data'] as $value) {
			if (strpos($value, 'HTTP') !== 0) {
				preg_match("|^([^:]+):\s?(.+)$|", $value, $matches);
				$headers[trim($matches[1])] = trim($matches[2], " \t\n\r\0\x0B\"");
			}
		}
		return $headers;
	}

	/**
	 * extracts the status code from the stream meta data
	 * @param array $meta
	 * @return integer
	 */
	protected function getStatusCode($meta) {
		$matches = array();
		preg_match("|\s(\d\d\d)\s|", $meta['wrapper_data'][0], $matches);
		$status = (int)trim($matches[1]);
		return $status;
	}

	/**
	 * Sends the HTTP request and retrieves/parses the response
	 *
	 * @param string $url
	 * @param string $method
	 * @param array $querydata OPTIONAL
	 * @param array $headers OPTIONAL
	 * @param array $options OPTIONAL
	 * @return array
	 * @author Ed Finkler
	 */
	public function sendRequest($url, $method='GET', $querydata=null, $headers=null, $options=null) {
		$resp = array();

		if ($this->base_url) {
			$url = $this->base_url.$url;
		}

		// we need to supply a default content-type
		if (!isset($headers['Content-Type'])) {
			$headers['Content-Type'] = 'application/x-www-form-urlencoded';
		}

		// by default, pass the header "Connection: close"
		if (!isset($headers['Connection'])) {
			$headers['Connection'] = 'close';
		}

		// if we have a username and password, use it
		if (isset($this->username) && isset($this->password) && !isset($headers['Authorization'])) {
			$this->log("{$this->username}:{$this->password}");
			$headers['Authorization'] = 'Basic '.base64_encode("{$this->username}:{$this->password}");
		}

		// default timeout
		$timeout = isset($options['timeout']) ? $options['timeout'] : static::DEFAULT_TIMEOUT;

		$content = null;

		// if querydata is a string, just pass it as-is
		if (isset($querydata) && is_string($querydata)) {

			$content = $querydata;

		// else if it's an array, make an http query
		} elseif (isset($querydata) && is_array($querydata)) {

			$content = http_build_query($querydata);

		}

		// create an array of header strings from the hash
		$headerarr = isset($headers) ? $this->buildHeadersArray($headers) : array();

		// GET and DELETE should use the URL to pass data
		$urlcontent = ('GET' === $method || 'DELETE' === $method);

		// if this is a GET or DELETE and we have some $content, append to URL
		if ($urlcontent && isset($content)) {
			$url .= '?'.$content;
		}


		$opts = array(
			'http'=>array(
				'timeout'=>$timeout,
				'method'=>$method,
				'content'=> (!$urlcontent) ? $content : null,
				'user_agent'=>$this->getUserAgent(),
				'header'=>$headerarr,
				'ignore_errors'=>1
			)
		);

		$this->log('URL =================');
		$this->log($url);

		$this->log('METHOD =================');
		$this->log($method);

		$this->log('QUERYDATA =================');
		$this->log($querydata);

		$this->log('HEADERS =================');
		$this->log($headers);

		$this->log('OPTIONS =================');
		$this->log($options);

		$this->log('OPTS =================');
		$this->log($opts);

		$this->last_request = compact('url', 'method', 'querydata', 'headers', 'options', 'opts');
		// call custom req log callback
		if (!empty($this->callbacks['onRequestLog'])) {
			$this->callbacks['onRequestLog']($this->last_request);
		}

		$context = stream_context_create($opts);

		$this->log("Sending…");

		if ($this->debug) { $start_time = microtime(true); }

		$this->log("Opening stream…");
		$stream = fopen($url, 'r', false, $context);

		$this->log("Getting metadata…");
		$resp['meta'] = stream_get_meta_data($stream);

		$this->log("Getting response…");

		$resp['body'] = stream_get_contents($stream);

		$this->log("Closing stream…");
		fclose($stream);

		if ($this->debug) {
			$stop_time = microtime(true);
			$req_time = $stop_time - $start_time;
			$this->log(sprintf("Request time for \"%s %s\": %f", $method, $url, $req_time));
		}


		$resp['status'] = $this->getStatusCode($resp['meta']);
		$resp['headers'] = $this->metaToHeaders($resp['meta']);
		$this->log($resp);

		$this->log("Processing response body…");
		$resp = $this->processResponseBody($resp);
		$this->log($resp['body']);

		$this->last_response = $resp;

		// call custom resp log callback
		if (!empty($this->callbacks['onResponseLog'])) {
			$this->callbacks['onResponseLog']($this->last_response);
		}

		return $resp;
	}


	/**
	 * If we get back something that claims to be XML or JSON, parse it as such and assign to $resp['body']
	 *
	 * @param string $resp
	 * @return string|object
	 * @see Resty::$JSON_TYPES
	 * @see Resty::$XML_TYPES
	 */
	protected function processResponseBody($resp) {

		if ($this->parse_body === true) {
			$content_type = preg_split('/[;\s]+/', $resp['headers']['Content-Type']);
			$content_type = $content_type[0];

			if (in_array($content_type, static::$JSON_TYPES)) {

				$this->log("Response body is JSON");
				$resp['body'] = json_decode($resp['body']);
				return $resp;

			} elseif (in_array($content_type, static::$XML_TYPES)) {

				$this->log("Response body is XML");
				$resp['body'] = new \SimpleXMLElement($resp['body']);
				return $resp;

			}

		}

		$this->log("Response body not parsed");

		return $resp;

	}

}

