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

function get_inner_html( $node ) {
    $innerHTML= '';
    $children = $node->childNodes;
    foreach ($children as $child) {
        $innerHTML .= $child->ownerDocument->saveXML( $child );
    }

	// $innerHTML = preg_replace( "{[ \t]+}", ' ', $innerHTML );
	// $innerHTML = str_replace( "\A0", ' ', $innerHTML );
	$innerHTML = preg_replace('/\xA0/u', ' ', $innerHTML);

    return $innerHTML;
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
	// <a href="/?(roombook|topic)\.taf\?(.*)">(<i>)?(.*)(</i>)?</a> (.*)<br/>
	$times[] = trim($column->textContent, " \t\n\r\0\x0B\xC2\xA0");
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

		$pattern = '$<a href="/?(?P<type>roombook|topic)\.taf\?(?P<htmlQuery>.*?)">(<i>)?(?P<id>[\/A-z0-9]+)(</i>)?</a> (?P<name>[A-z0-9 \(\)]+)<br/>$';

		$bookingHtml = get_inner_html($column);
		if (preg_match_all($pattern, $bookingHtml, $matches, PREG_SET_ORDER) == 0) {
			continue;
		}

		$bookingsData = array();
		foreach ($matches as $match) {
			$bookingData = array();

			$bookingData['time'] = $times[$i - 3];

			$bookingData['id'] = $match['id'];
			$bookingData['name'] = $match['name'];
			$htmlQuery = htmlspecialchars_decode($match['htmlQuery']);
			parse_str($htmlQuery, $bookingData['queryString']);

			$bookingData['type'] = $match['type'];

			$bookingsData[] = $bookingData;

		}

    	$room->bookings[$i - 3] = $bookingData;
	}
	
	$rooms[] = $room;
}

// var_dump($rooms);
echo json_encode($rooms);

//foreach ($times as $i => $time) {
//	echo "Rooms free at at $time\n";
//	foreach($rooms as $room) {
//
//		if ($room->bookings[$i] == "") {
//			echo $room->id  . "\t" . $room->name . "\t"  . $room->bookings[$i] . "\n";
//		}
//	}
//}
