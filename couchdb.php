<?php

class couchedio{
		
}


class CouchDBRequest {

    static $VALID_HTTP_METHODS = array('DELETE', 'GET', 'POST', 'PUT');

    private $method = 'GET';
    private $url = '';
    private $data = NULL;
    private $sock = NULL;
    private $username;
    private $password;

    function __construct($host, $port = 5984, $url, $method = 'GET', $data = NULL, $username = null, $password = null) {
        $method = strtoupper($method);
        $this->host = $host;
        $this->port = $port;
        $this->url = $url;
        $this->method = $method;
        $this->data = $data;
        $this->username = $username;
        $this->password = $password;

        if(!in_array($this->method, self::$VALID_HTTP_METHODS)) {
            throw new CouchDBException('Invalid HTTP method: '.$this->method);
        }
    }

    function getRequest() {
        $req = "{$this->method} {$this->url} HTTP/1.0\r\nHost: {$this->host}\r\n";

        if($this->username || $this->password)
            $req .= 'Authorization: Basic '.base64_encode($this->username.':'.$this->password)."\r\n";

        if($this->data) {
            $req .= 'Content-Length: '.strlen($this->data)."\r\n";
            $req .= 'Content-Type: application/json'."\r\n\r\n";
            $req .= $this->data."\r\n";
        } else {
            $req .= "\r\n";
        }

        return $req;
    }

    private function connect() {
        $this->sock = @fsockopen($this->host, $this->port, $err_num, $err_string);
        if(!$this->sock) {
            throw new CouchDBException('Could not open connection to '.$this->host.':'.$this->port.' ('.$err_string.')');
        }
    }

    private function disconnect() {
        fclose($this->sock);
        $this->sock = NULL;
    }

    private function execute() {
        fwrite($this->sock, $this->getRequest());
        $response = '';
        while(!feof($this->sock)) {
            $response .= fgets($this->sock);
        }
        $this->response = new CouchDBResponse($response);
        return $this->response;
    }

    function send() {
        $this->connect();
        $this->execute();
        $this->disconnect();
        return $this->response;
    }

    function getResponse() {
        return $this->response;
    }
}

class CouchDBResponse {

    private $raw_response = '';
    private $headers = '';
    private $body = '';

    function __construct($response = '') {
        $this->raw_response = $response;
        list($this->headers, $this->body) = explode("\r\n\r\n", $response);
    }

    function getRawResponse() {
        return $this->raw_response;
    }

    function getHeaders() {
        return $this->headers;
    }

    function getBody($decode_json = false) {
        return $decode_json ? CouchDB::decode_json($this->body) : $this->body;
    }
}


class CouchDB {

    private $username;
    private $password;

    function __construct($db, $host = 'localhost', $port = 5984, $username = null, $password = null) {
        $this->db = $db;
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }

    static function decode_json($str) {
        return json_decode($str);
    }

    static function encode_json($str) {
        return json_encode($str);
    }

    function send($url, $method = 'GET', $data = NULL) {
        $url = '/'.$this->db.(substr($url, 0, 1) == '/' ? $url : '/'.$url);
        $request = new CouchDBRequest($this->host, $this->port, $url, $method, $data, $this->username, $this->password);
        return $request->send();
    }

    function get_all_docs() {
        return $this->send('/_all_docs');
    }

    function get_item($id) {
        return $this->send('/'.$id);
    }
		
		function get($id,$decode = true) {
				if($decode){
					$response = $this->send('/'.$id);
					$data = $response->getBody(true);
					return $data;
				}else{
					return $this->send('/'.$id);
				}
    }
		
		function put($id,$data){
			if(is_array($data) || is_object($data)){
				$data = $this->encode_json($data);
			}
			$rez = $this->send('/'.$id,'PUT',$data);
			return $rez->getBody(true);
		}
		
		function update($id,$data,$rev = false){
			if($rev){
				foreach($data as $k=>$v){
					$rev->$k = $v;
				}
				return $this->put($id,$rev);
			}else{
				$old = $this->get($id);
				foreach($data as $k=>$v){
					$old->$k = $v;
				}
				return $this->put($id,$old);
			}
		}
		
		function view($a,$b){
			$rez = $this->send('_design/'.$a.'/_view/'.$b,'GET');
			return $rez->getBody(true);
		}
		//http://localhost:5984/emails/_design/emails_groups/_view/email_and_group?key=%22a%22
		
}