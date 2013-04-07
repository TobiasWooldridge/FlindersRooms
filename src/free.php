#!/usr/bin/php
<?php

require_once('./vendor/autoload.php');

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;


class Room {
	public $id;
	public $name;
	public $bookings = array();
}

function format_stusys_date(\DateTime $date) {
	return strtoupper($date->format('d-M-Y'));
}


$date_format = '';
$base_url = 'http://stusyswww.flinders.edu.au/roombook.taf';

$parameters = array(
	'_function' => 'all',
	'bldg' => 'IST',
	'weekday' => format_stusys_date(new DateTime())
);


$request_url = $base_url . '?' . http_build_query($parameters);

// var_dump($request_url);

$client = new Client();

$crawler = $client->request('GET', $request_url);

$rows = $crawler->filter('tr');

$headerRow = $rows->eq(0);
$rows = $rows->eq(0)->siblings();

// var_dump(get_class_methods($headerRow));

$columns = $headerRow->children();

$times = array();

foreach ($columns as $column) {
	$times[] = trim($column->textContent);
}
array_shift($times);

// 1-indexed to skip header row
$rooms = array();
foreach ($rows as $i => $row) {
	$columns = $rows->eq($i)->children();

	$room = new Room();

	$room->id = trim($columns->eq(0)->text());
	$room->name = trim($columns->eq(1)->text());
	foreach ($columns as $i => $column) {
		// Skip the first three columns
		if ($i < 3) {
			continue;
		}

    	$room->bookings[$i - 3] = trim($column->textContent, " \t\n\r\0\x0B\xC2\xA0");
	}
	
	$rooms[] = $room;
}

// var_dump($times);
// var_dump($rooms);

foreach ($times as $i => $time) {
	echo "Rooms free at at $time\n";
	foreach($rooms as $room) {

		if ($room->bookings[$i] == "") {
			echo $room->id  . "\t" . $room->name . "\t"  . $room->bookings[$i] . "\n";
		}
	}
}
