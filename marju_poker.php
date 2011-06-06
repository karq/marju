#!/usr/local/bin/php
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
	    array('#marjukanal', './timers.txt')
	);

	$mybot = new Bot($bot);

