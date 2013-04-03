#!/usr/bin/php
<?php

require_once('./vendor/autoload.php');

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;


function format_date(\DateTime $date) {
	return strtoupper($date->format('d-M-Y'));
}

$date_format = '';
$base_url = 'http://stusyswww.flinders.edu.au/roombook.taf';

$parameters = array(
	'_function' => 'all',
	'bldg' => 'IST',
	'weekday' => format_date(new DateTime())
);


$request_url = $base_url . '?' . http_build_query($parameters);

// var_dump($request_url);

$client = new Client();

$crawler = $client->request('GET', $request_url);

$rows = $crawler->filter('tr');

$headerRow = $rows->eq(0);

// var_dump(get_class_methods($headerRow));

$columns = $headerRow->children();

$times = array();

foreach ($columns as $column) {
	$times[] = trim($column->textContent);
}
array_shift($times);


// 1-indexed to skip header row
$bookings = array();
for ($i = 1; $i < count($rows); $i++ ){

	$columns = $rows->eq($i)->children();

	$roomId = trim($columns->eq(0)->text());

	$bookings[$roomId] = array();
	foreach ($columns as $column) {
		$bookings[$roomId][] = trim($column->textContent);
	}

	for ($j = 0; $j < 3; $j++) {
		array_shift($bookings[$roomId]);
	}
}

var_dump($times);

var_dump($bookings);