<?php
require_once 'CouchRequest.class.php';
require_once 'CouchResponse.class.php';

/**
 * CouchSimple
 *
 * @author Alexander Thiemann
 */
class CouchSimple
{
    private $db;
    private $protocol;
    private $host;
    private $port;
    private $username;
    private $password;

    private static $okStatus = array(412);

    public function __construct($protocol = "http", $host, $port = 5984, $db, $username = null, $password = null, $autoCreate=false)
    {
        $this->protocol = $protocol;
        $this->host = $host;
        $this->port = $port;
        $this->db = $db;
        $this->username = $username;
        $this->password = $password;

        if ($autoCreate) {
            $this->talkToDB("", CouchRequest::COUCH_PUT);
        }
    }

    private function talkToDB($url, $method = CouchRequest::COUCH_GET, $data = null)
    {
        $fullURL = $this->protocol . "://".$this->host.":".$this->port."/".$this->db.$url;

        $request = new CouchRequest($fullURL, $method, $data, $this->username, $this->password);
        $resp = $request->send();

        if ($resp->getStatusCode() >= 400 && !in_array($resp->getStatusCode(), self::$okStatus)) {
            throw new Exception("CouchDB-HTTP Error: ".$resp->getBody(), $resp->getStatusCode());
        }

        $response = $resp->getBody();

        if ('application/json' == $resp->getContentType()) {
            $response = json_decode($response);
        }

        return $response;
    }

    public function storeDocWithId($doc)
    {
        return $this->talkToDB("/".$doc->_id, CouchRequest::COUCH_PUT, json_encode($doc));
    }

    public function storeDoc($doc)
    {
        return $this->talkToDB("", CouchRequest::COUCH_POST, json_encode($doc));
    }

    public function deleteDoc($docId, $rev)
    {
        return $this->talkToDB("/".$docId."?rev=".$rev, CouchRequest::COUCH_DELETE);
    }

    public function getView($designDoc, $viewName)
    {
        return $this->talkToDB("/_design/".$designDoc."/_view/".$viewName, CouchRequest::COUCH_GET);
    }

    public function getAll()
    {
        return $this->talkToDB('/_all_docs');
    }

    public function getDocById($id)
    {
        return $this->talkToDB('/'.$id);
    }
}