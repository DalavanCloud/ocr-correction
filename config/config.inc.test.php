<?php

//local app for testing
defined("HTTP_HOST") || define("HTTP_HOST", "http://www.ocr-correction.local/");

// CouchDB database name
defined("DB_NAME") || define("DB_NAME", "ocr");

// CouchDB protocol
defined("DB_PROTOCOL") || define("DB_PROTOCOL", "http");

// CouchDB host
defined("DB_HOST") || define("DB_HOST", "localhost");

// CouchDB port
defined("DB_PORT") || define("DB_PORT", 5984);

// CouchDB login name
defined("DB_USER") || define("DB_USER", null);

// CouchDB password
defined("DB_PASS") || define("DB_PASS", null);

//OAuth Public Key
defined("OAUTH_KEY") || define("OAUTH_KEY", "");

date_default_timezone_set('UTC');

?>