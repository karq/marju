#!/usr/bin/php

<?php

	include("Bot.php");
	include("Chan.php");
	error_reporting (E_ALL ^ E_NOTICE);
	
	#bot
	$bot['botnick'] = "marju";
	$bot['botpassword'] = "";
	$bot['botident'] = "marjubot";
	$bot['botrealname'] = "Marju";
	$bot['localhost'] = "localhost";
	$bot['serveraddress'] = "zone.ircworld.org";
	$bot['serverport'] = "6667";

	$bot['poker_name'] = "marjupoker";

	#starpump.ee
	$sp['vocfile'] = "./starpump.ee/sonavara.txt";
	$sp['quotefile'] = "./starpump.ee/kvoodid.txt";
	$sp['timerfile'] = "./starpump.ee/timers.txt";
	$sp['name'] = "#starpump.ee";
	$sp['speak'] = true;

	#star
	$star['vocfile'] = "./starpump.ee/sonavara.txt";
	$star['quotefile'] = "./starpump.ee/kvoodid.txt";
	$star['name'] = "#star";
	$star['speak'] = false;
	$star['timerfile'] = "./starpump.ee/timers.txt";

	#it08
	$it['vocfile'] = "./it08/sonavara.txt";
	$it['quotefile'] = "./it08/kvoodid.txt";
	$it['timerfile'] = "./it08/timers.txt";
	$it['name'] = "#it08";
	$it['speak'] = false;

	#it10
	$it10['vocfile'] = "./it10/sonavara.txt";
	$it10['quotefile'] = "./it10/kvoodid.txt";
	$it10['timerfile'] = "./it10/timers.txt";
	$it10['name'] = "#it10";
	$it10['speak'] = true;

	#filmiveeb
	$film['vocfile'] = "./filmiveeb/sonavara.txt";
	$film['quotefile'] = "./filmiveeb/kvoodid.txt";
	$film['timerfile'] = "./filmiveeb/timers.txt";
	$film['name'] = "#filmiveeb";
	$film['speak'] = true;

	#&
	$and['vocfile'] = "./and/sonavara.txt";
	$and['quotefile'] = "./and/kvoodid.txt";
	$and['timerfile'] = "./and/timers.txt";
	$and['name'] = "&";
	$and['speak'] = false;

	#kanalid
	$chans [] = new Chan($sp);
	$chans [] = new Chan($film);
	$chans [] = new Chan($star);
	$chans [] = new Chan($it);
	$chans [] = new Chan($it10);
	$chans [] = new Chan($and);

	#alustame
	$mybot = new Bot($bot,$chans);

?>

