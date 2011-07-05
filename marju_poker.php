#!/usr/bin/php
<?php
	require_once('bot_poker.php');
	$bot = array();
	$bot['botnick'] = "marjupoker";
	$bot['botpassword'] = "";
	$bot['botident'] = "marjupoker";
	$bot['botrealname'] = "Marju Poker";
	$bot['localhost'] = "localhost";
	$bot['serveraddress'] = "zone.ircworld.org";
	$bot['serverport'] = "6667";
	$bot['poked_bot_nickname'] = 'marju';
	$bot['timers'] = array(
	    array('#it08', './it08/timers.txt'),
	    array('#it10', './it10/timers.txt'),
		array('#star', './starpump.ee/timers.txt'),
	    array('#filmiveeb', './filmiveeb/timers.txt'),
		array('#starpump.ee', './starpump.ee/timers.txt'),
		array('&', './and/timers.txt')
	);

	$mybot = new Bot($bot);
?>
