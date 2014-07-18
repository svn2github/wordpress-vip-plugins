<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/FacebookWPHttpClient.php';

Facebook\FacebookRequest::setHttpClientHandler( new FacebookWPHttpClient() );