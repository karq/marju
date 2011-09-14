#!/usr/bin/php

<?php

class Bot {

	var $fp;

	var $rawdata;
	var $data;

	var $botnick;
	var $botpassword;
	var $botident;
	var $botrealname;
	var $localhost;
	var $quit_message;

	var $channels;

	var $serveraddress;
	var $serverport;

	private $poker_name;

	##--------------------------------------------
	##CLASS CONSTRUCTOR
	##--------------------------------------------
	function Bot($bot, $chans) {

		$this->botnick = $bot['botnick'];
		$this->botpassword = $bot['botpassword'];
		$this->botident = $bot['botident'];
		$this->botrealname = $bot['botrealname'];
		$this->localhost = $bot['localhost'];

		$this->channels = $chans;

		$this->serveraddress = $bot['serveraddress'];
		$this->serverport = $bot['serverport'];

		$this->poker_name = $bot['poker_name'];

		set_time_limit(0);

		$this->connect();
	}

	##--------------------------------------------
	##CONNECT FUNCTION
	##Connects the bot to the server
	##--------------------------------------------
	function connect(){
		$this->fp = fsockopen($this->serveraddress,$this->serverport, &$err_num, &$err_msg, 30);

		if (!$this->fp){
			echo "There was an error in connecting to ".$this->serveraddress;
			exit;
		} else {
			$this->send("PASS ".$this->botpassword);
			$this->send("NICK ".$this->botnick);
			$this->send("USER ".$this->botident.' '.$this->localhost.' '.$this->serveraddress.' :'.$this->botrealname);

			foreach ($this->channels as $chan) {
				$this->send("JOIN ".$chan->name);
			}

			$this->receive();
		}
	}

	##--------------------------------------------
	##DISCONNECT FUNCTION
	##Disconnects the bot from the server
	##--------------------------------------------
	function disconnect(){
		$this->send("QUIT : Over and out");
		fclose($this->fp);
		exit();
	}

	##--------------------------------------------
	##SEND FUNCTION
	##Sends any data to the server
	##--------------------------------------------
	function send($data){
		fputs($this->fp, $data."\r\n");
	}

	##--------------------------------------------
	##RECEIVE FUNCTION
	##Receives all data from connection
	##--------------------------------------------
	function receive(){
		while (!feof($this->fp)){
			$this->rawdata = fgets($this->fp, 1024);
			$this->rawdata = str_replace("\r", "", str_replace("\n", "", $this->rawdata));
			$this->process_data();
			$this->parse_data();
		}
		$this->disconnect();
	}

    private function isPrivateMessageFromPoker() {
        return $this->data['from'] == $this->poker_name && $this->data['action'] == 'PRIVMSG';
    }

    private function sendTimerNoticeToBot() {
        $message = $this->data['message'];
        $info = explode('/', $message, 5);
        $channelName = $info[0];
        $nick = $info[2];
        $timeout = $info[3];
        $msg = $info[4] ? ' "'.$info[4].'"' : '';
        $fullMessage = $nick.', su '.$timeout.'-minutiline'.$msg.' taimer sai valmis!';
        $this->send('PRIVMSG '.$channelName.' :'.$fullMessage);
    }
	##--------------------------------------------
	##PROCESS DATA FUNCTION
	##Processes the data sent into an array or useful items
	##--------------------------------------------
	function process_data(){
		$params = explode(" ", $this->rawdata);
		$message = str_replace("$params[0]", "", $this->rawdata);
		$message = str_replace("$params[1]", "", $message);
		$channel = explode(":",$message);
		$channel = str_replace(" ","",$channel[0]);
		$message = str_replace("$params[2]", "", $message);
		$from = explode ("!", $params[0]);
		$user = str_replace(":", "", $from[0]);
		$details = explode ("@", $from[1]);

		#stores the data in an array
		$this->data['from'] = str_replace(":", "", $from[0]);
		$this->data['ident'] = $details[0];
		$this->data['host'] = $details[1];
		$this->data['action'] = $params[1];
		$this->data['sent_to'] = $params[2];
		$this->data['message'] = substr($message, 4);
		$this->data['ping'] = $params[0];

		$this->data['channel'] = NULL;

        if ($this->isPrivateMessageFromPoker()) {
            $this->sendTimerNoticeToBot();
        }

		foreach ($this->channels as $chan) {
			if ($chan->name == $channel) {
				$this->data['channel'] = $chan;
			}
		}

		if($this->data['message'][0] == "!"){
			$maction = explode(" ", $this->data['message']);
			$mfullaction = str_replace($maction[0], "", $this->data['message']);

			$this->data['action'] = "TRUE";
			$this->data['message_action'] = $maction[0];
			$this->data['message_target'] = $maction[1];
			$this->data['message_target2'] = $maction[2];
			$this->data['message_action_text'] = str_replace(" ", "%20", substr($mfullaction, 1));
			$this->data['message_action_text_plain'] = str_replace("%20", " ", $this->data['message_action_text']);
			$this->data['message_action_text_plain_with_params'] = substr(str_replace($maction[0], "", str_replace($maction[1], "", $mfullaction)), 2);
		}
	}

	##--------------------------------------------
	##PARSE DATA FUNCTION
	##Parses the data that was just sent - i.e. checks for messages and does them
	##--------------------------------------------
	function parse_data(){
		if($this->data['channel'] != null AND $this->data['action'] == 'TRUE'){
			switch($this->data['message_action']){
				case '!h':
					$this->help();
					break;
				case '!quote':
					$this->quote();
					break;
				case '!addquote':
					$this->addquote();
					break;
				case '!calc':
					$this->calc();
					break;
				case '!trans':
					$this->translate();
					break;
				case '!ilm':
					$this->ilm();
					break;
				case '!rand':
					$this->rannainfo();
					break;
				case '!omx':
					$this->omx();
					break;
				case '!quotestat':
					$this->quotestat();
					break;
				case '!timer':
					$this->addtimer();
					break;
				case "!imdb":
					$this->getImdb();
					break;
				case "!fml":
					$this->fml();
					break;  
			}
		}
		if($this->data['ping'] == 'PING'){
			$this->send("PONG");
			return;
		}
		$this->ai();
	}

	##--------------------------------------------
	##AI
	##
	##--------------------------------------------
	function ai() {

		if ($this->data['channel'] == null) return;
		if ($this->data['channel']->speak == false) return;
		if ($this->data['message'][0] == "!") return;

		$match = preg_match('/(\.png|\.jpg|\.gif)^/',$this->data['message'],$asd);
		if ($match AND $this->data['channel']->name == "#starpump.ee" AND $this->data[from] == "Garli") {
			$this->send('PRIVMSG '.$this->data['channel']->name.' :'.'OLD');
		}
		
		$random = rand(0,99);
		$hasBotNick = strpos(strtolower($this->data['message']),strtolower($this->botnick));

		#Kui s�num sisaldab boti nicki, siis 60% t�en�osusega bot �tleb midagi suvalist
		if($hasBotNick !== false) {
			if ($random < 60) {
				$f_contents = file($this->data['channel']->vocfile);
				$line = $f_contents[array_rand($f_contents)];
				$this->send("PRIVMSG ".$this->data['channel']->name." :$line");
			}
			return;
		}

		$random = rand(0,99);

		#10% t�en�osusega �eldud s�num salvestatakse s�navarasse
		if($random < 10) {
			$lisamine = @fopen($this->data['channel']->vocfile, 'a+');
			fputs($lisamine,$this->data['message']."\n");
			fclose($lisamine);
		}

		$random = rand(0,99);
	}

	##--------------------------------------------
	##QUOTE FUNCTION
	##Displays a quote to the user from the database
	##--------------------------------------------
	function quote(){
		$quotefile = $this->data['channel']->quotefile;
		$getquote = @fopen($quotefile,'r');

		$wordToFind = strtolower($this->data['message_action_text_plain']);

		$quotes [] = "";

		if ($wordToFind == "") {
			$wordToFind = ' ';
		}

		if ($getquote) {
			while (!feof($getquote)) {
				$quote = fgets($getquote);
				$quoteLowered = strtolower($quote);
				$pos = strpos($quoteLowered,$wordToFind);
				if($pos === false) {}
				else $quotes [] = $quote;
			}
			fclose($getquote);
		}

		$numberOfQuotes = count($quotes);
		if ($numberOfQuotes != 0) {
			$number = rand(1,$numberOfQuotes - 1);
			$quote = $quotes[$number];
			$this->send("PRIVMSG ".$this->data[channel]->name." :$quote");
		}

	}
	##--------------------------------------------
	##ADDQUOTE FUNCTION
	##Adds quote to file
	##--------------------------------------------
	function addquote(){
		$quotefile = $this->data['channel']->quotefile;
		$addquote = fopen($quotefile,'a+');
		fputs($addquote,$this->data['message_action_text_plain']."\n");
		fclose($addquote);
		$this->send("NOTICE ".$this->data['from'].' : Tsitaat lisatud!');
	}

	##--------------------------------------------
	##QUOTESTAT
	##
	##--------------------------------------------

	function quotestat(){
		$wordToFind = strtolower($this->data['message_action_text_plain']);
		$numberOfQuotes = 0;

		$quotefile = $this->data['channel']->quotefile;
		$countquote = @fopen($quotefile,'r');
		if ($wordToFind == "") $wordToFind = ' ';
		if ($countquote) {
			while (!feof($countquote)) {
				$buffer = fgets($countquote);
				$bufferlow = strtolower($buffer);
				$pos = strpos($bufferlow,$wordToFind);
				if($pos === false) {}
				else $numberOfQuotes++;
			}
			fclose($countquote);
		}

		if ($wordToFind === ' ') {
			$this->send("PRIVMSG ".$this->data['channel']->name." :Kokku on $numberOfQuotes tsitaati.");
		}
		else $this->send("PRIVMSG ".$this->data['channel']->name." :S�na \"$wordToFind\" kohta on $numberOfQuotes tsitaati.");
	}

	##--------------------------------------------
	##TRANSLATE FUNCTION
	##Translates using google translate
	##--------------------------------------------

		function translate() {
			$text = urlencode(substr($this->data['message_action_text_plain'],6));
			$destLang = urlencode($this->data['message_target2']);
			$srcLang = urlencode($this->data['message_target']);

			$trans = @file_get_contents("http://ajax.googleapis.com/ajax/services/language/translate?v=1.0&q={$text}&langpair={$srcLang}|{$destLang}");
			$json = json_decode($trans,true);

			if( $json['responseStatus'] != '200' ) return;
			else {
				$translated = $json['responseData']['translatedText'];
				$this->send("PRIVMSG ".$this->data[channel]->name." :$translated".chr(10));
			}
	}

	##--------------------------------------------
	##ILM
	##
	##--------------------------------------------
	function ilm(){
		$url = "http://www.emhi.ee/index.php?ide=21&v_kaart=0";
		$f = "<B>";
		$t = "&deg;";
		$linn = ucfirst($this->data['message_target']);
		#############################
		if ($linn == "Tar" || $linn == "Tart" || $linn == "Tartu") {
			$this->tartuilm();
			return;
		}
		#############################
		if($linn == '') {
			$this->send("NOTICE ".$this->data['from']." :Olemasolevad linnad: Heltermaa, J�geva, J�hvi, Kihnu, Kunda, Kuusiku, L��ne-Nigula, Narva-J�esuu, Pakri, P�rnu, Ristna, Rohuk�la, Roomassaare, Ruhnu, S�rve, Tallinn, Tartu, Tiirikoja, T�ri, Valga, Viljandi, Vilsandi, Virtsu, V�ru, V�ike-Maarja");
			return;
		}
		preg_match('/<td height="30">'.$linn.'(.*?)<\/td>'."\n\t\t\t".'<td align="center">(.*?)<\/td>'."\n\t\t\t".'<td align="center">(.*?)<\/td>/', file_get_contents($url), $info);
		preg_match('/<td height="30">'.$linn.'(.*?)<\/td>'."\n\t\t\t".'<td align="center">(.*?)<\/td>'."\n\t\t\t".'<td align="center">/',$info[0],$esimene);
		$info = str_replace($esimene[0],"",$info);
		preg_match('/<\/td>/',$info[0],$teine);
		$info = str_replace($teine[0],"",$info[0]);
		$this->send("PRIVMSG ".$this->data['channel']->name." :".$info);
	}

	##--------------------------------------------
	##TARTU ILM
	##
	##--------------------------------------------
	function tartuilm(){
		$ilm = @file_get_contents("http://meteo.physic.ut.ee/json/");
		$json = json_decode($ilm,true);
		$temp = $json['data'][0]['value'];
		$temp = floatval($temp);
		$temp = round($temp,1);
		$this->send("PRIVMSG ".$this->data['channel']->name." :$temp".chr(10));
	}

	##--------------------------------------------
	##RANNAINFO
	##
	##--------------------------------------------
	function rannainfo() {
		$url = "http://www.g4s.ee/beaches2.php";
		$dom = new DOMDocument('1.0'); 
		$dom->load($url);
		$rannad = $dom->getElementsByTagName('marker');
		
		$otsing = $this->data['message_target'];
		$otsi = strtolower($otsing);
		foreach ($rannad as $elem) {
			$rand = strtolower($elem->getAttribute('town'));
			if (preg_match("/^$otsi(.*)$/", $rand)) {
				$watertemp = $elem->getAttribute('watertemp');
				$airtemp = $elem->getAttribute('airtemp');
				$pop = $elem->getAttribute('pop');
				$town  = $elem->getAttribute('town');
				$time  = $elem->getAttribute('time');
				$this->send("PRIVMSG ".$this->data['channel']->name." :$town kell $time - Vesi: $watertemp �hk: $airtemp Inimesi: $pop");
				return;
			}
		}
	}
	
	##--------------------------------------------
	##OMX
	##
	##--------------------------------------------
	function omx(){
		$url = "http://www.nasdaqomxbaltic.com/market/?pg=mainlist&lang=et";
		$stock = strtoupper($this->data[message_target]);

		if($stock == '') return;

		preg_match('/'.$stock.'[1A][LRT]<\/a><\/td> \n\t\t\t\t\t\t\t\t<td>[TLNRIGV]{3} <\/td> \n\t\t\t\t<td>[EURLTV]{3}<\/td> \n\t\t\t\t<td>(.*?)<\/td> \n\t\t\t\t\t\t\t\t<td>(.*?)<\/td> \n\t\t\t\t<td class="[negpos]{0,3}">(.*?)<\/td>/', file_get_contents($url), $info);
		if($info == null) {
			$this->send("PRIVMSG ".$this->data['channel']->name." :Ei leidnud seda aktsiat");
			return;
		}
		$viimane = $info[1];
		$muutus = $info[3];
		$numberolemas = '/[0-9]/';
		$muutuspositiivne = '/[+]/';
		$varv = '4';
		if (preg_match($muutuspositiivne,$muutus)) $varv = '3';
		if (!(preg_match($numberolemas,$muutus))) {
			$muutus = " 0%";
			$varv = '9';
		}
		$this->send("PRIVMSG ".$this->data['channel']->name." :".$viimane.''.$varv.$muutus);
	}
	##--------------------------------------------
	##CALC FUNCTION
	##
	##--------------------------------------------
	function calc(){
		$query = $this->data['message_action_text_plain'];
		if (!empty($query)){
			$url = "http://www.google.co.uk/search?q=".urlencode($query);
			$f = array("?", "<font size=-2> </font>", " &#215; 10", "<sup>", "</sup>");
			$t = array("", "", "e", "^", "");
			preg_match('/<h2 class=r style="font-size:138%"><b>(.*?)<\/b><\/h2>/', file_get_contents($url), $matches);
			if (!$matches['1']){
				$this->send("PRIVMSG ".$this->data['channel']->name." :Mingi jama on lol.");
				return;
			} else {
				$this->send("PRIVMSG ".$this->data['channel']->name." :".str_replace($f, $t, $matches['1']));
				return;
			}
		} else return;
	}



	// Adds timer current time + given time. Saves in unix time.
	function addtimer(){
		$timerfile = $this->data['channel']->timerfile;
		$addtime = fopen($timerfile,'a+');
		//TODO: parse time (and msg) from plaintext
		$from = $this->data['from'];
		$msg = $this->data['message_action_text_plain'];
		$msgArray = explode(' ', $msg, 2);
		$timeInMinutesUnchecked = $msgArray[0];
		if (!$timeInMinutesUnchecked) {
		    $this->send("PRIVMSG ".$this->data['channel']->name." :Timeri argument puudus v�i oli 0!");
            return false;
        }
		if (!is_numeric($timeInMinutesUnchecked)) {
		    $this->send("PRIVMSG ".$this->data['channel']->name." :Timeri esimene argument peab olema arv!");
            return false;
        }

		$time_minutes = intval($timeInMinutesUnchecked);

		if ($time_minutes < 1) {
		    $this->send("PRIVMSG ".$this->data['channel']->name." :Timeri minutiseier peab olema v�hemalt 1!");
            return false;
        }

		$message = '';
		if (isset($msgArray[1])) {
		    $message = $msgArray[1];
        }
		//ready time in unix time
		$ready_time = time() + $time_minutes * 60;

        //format timestamp/nickname/timer
		fputs($addtime, $ready_time.'/'.$from.'/'.$time_minutes.'/'.$message."\n");
		fclose($addtime);
		$this->send("NOTICE ".$this->data['from'].' : Timer lisatud!');
	}

   	##--------------------------------------------
	##IMDB
	##
	##--------------------------------------------
	   function getImdb(){
	$title = $this->data['message_action_text_plain'];
	$this->imdb($title);
	
	}

	function imdb($text){
		$address = "http://www.imdbapi.com/";
		$text = str_replace(" ","+",$text);
		$file = @file_get_contents($address . "?t=" . $text);
		$json = json_decode($file,true);
		if($json["Response"] == "True"){
			$title = $json["Title"];
			$id = $json["ID"];
			$year = $json["Year"];
			$info = $title . " " . $year . " " . " http://www.imdb.com/title/" . $id . "/";
			$this->send("PRIVMSG ".$this->data['channel']->name." :".$info);
		}
		else{
			$this->send("PRIVMSG ".$this->data['channel']->name." :IMDB:Sellist filmi ei leitud!");
		}
	}
	
	##--------------------------------------------
	##FML - Retrieve a random post from fmylife.com
	## and send to channel
	##--------------------------------------------
	
	//Get random post from http://www.fmylife.com/ and send to channel.
	function fml(){
		$key = "4e66020d86695";
		$language = "en";
		$address = "http://api.fmylife.com/view/random/?key=" . $key . "&language=" . $language;
		echo $address;
		$file = @file_get_contents($address);
		$text = getFMLText($file);
		$this->send("PRIVMSG ".$this->data['channel']->name." :". $text);
	}
	
	//Get random post text from fml XML
	function getFMLText($file){
				$doc = new DOMDocument(); 
	            $doc->loadXML($file);
	            $inputs = $doc->getElementsByTagName("item");
	            $item = $inputs->item(0)->getElementsByTagName("text");
	            $txt = $item->item(0)->nodeValue;
				
				return $txt;                
	}


	##--------------------------------------------
	##HELP
	##
	##--------------------------------------------
	function help() {
		$this->send("NOTICE ".$this->data['from'].' :!quote [otsis�na] - v�ljastab suvalise tsitaadi, mis sisaldab otsingus�na');
		$this->send("NOTICE ".$this->data['from'].' :!addquote [tsitaat] - lisab tsitaadi');
		$this->send("NOTICE ".$this->data['from'].' :!quotestat [otsis�na] - v�ljastab otsis�na sisaldavate tsitaatide koguarvu');
		$this->send("NOTICE ".$this->data['from'].' :!trans [from] [to] [lause] - t�lgib lause �hest keelest teise');
		$this->send("NOTICE ".$this->data['from'].' :!calc [tehe] - kalkulaator');
		$this->send("NOTICE ".$this->data['from'].' :!ilm [asukoht] - v�ljastab asukoha temperatuuri. Parameetrita k�sk annab asukohaloendi');
		$this->send("NOTICE ".$this->data['from'].' :!rand [asukoht] - v�ljastab rannainfot');
		$this->send("NOTICE ".$this->data['from'].' :!omx [aktsia l�hinimi] - v�ljastab OMX aktsia hetkehinna ja p�evase t�usuprotsendi');
		$this->send("NOTICE ".$this->data['from'].' :!timer [pikkus minutites] <[kirjeldus]> - v�ljastab antud minuti p�rast sulle meeldetuletusteate');
		$this->send("NOTICE ".$this->data['from'].' :!imdb [Filmi nimi] - Tagastab filmi nime, aasta  ja IMDB lingi');
		$this->send("NOTICE ".$this->data['from'].' :!fml - Suvaline postitus fmylife.com lehelt');
		return;
	}
}

?>

