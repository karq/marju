#!/usr/local/bin/php

<?php

	include("Bot.php");
	include("Chan.php");

	#bot
	$bot[botnick] = "devMarju";
	$bot[botpassword] = "klaara";
	$bot[botident] = "devMarju";
	$bot[botrealname] = "devMarju";
	$bot[localhost] = "localhost";
	$bot[serveraddress] = "zone.ircworld.org";
	$bot[serverport] = "6667";

	$bot['poker_name'] = "marjupoker";

	#testkanal
	$sp[vocfile] = "./vocabulary.txt";
	$sp[quotefile] = "./quotes.txt";
	$sp['timerfile'] = "./timers.txt";
	$sp[name] = "#marjukanal";
	$sp[speak] = true;

	#kanalid
	$chans [] = new Chan($sp);

	#alustame
	$mybot = new Bot($bot,$chans);

?>

