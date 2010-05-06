<?php

/* This example was tested on PHP 5.3.2 */

require "Curl/Multi.php";

$curl_multi = new Curl_Multi();

$ch1 = curl_init("http://www.example.com/");
$ch2 = curl_init("http://www.example.com/");

$callback = function($curl_info, $curl_data, $callback_data) 
{
	echo "CALLBACK DATA: $callback_data\n";
	echo "CURL_INFO:\n";
	print_r($curl_info);
	echo $curl_data;
};


$curl_multi->addHandle($ch1, $callback, "Handle 1");
$curl_multi->addHandle($ch2, $callback, "Handle 2");

/*
   optionally you can process other stuff while waiting for HTTP requests to come back...

   while ($not_done_processing_other_stuff)
   {
   		$not_done_processing_other_stuff = process_more_async_stuff();
		$curl_multi->poll();
	}
*/
$curl_multi->finish();

?>
