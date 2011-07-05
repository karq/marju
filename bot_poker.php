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

	var $serveraddress;
	var $serverport;

	private $poked_bot_nickname;
	private $timers;

	##--------------------------------------------
	##CLASS CONSTRUCTOR
	##--------------------------------------------
	function Bot($bot) {
		$this->botnick = $bot['botnick'];
		$this->botpassword = $bot['botpassword'];
		$this->botident = $bot['botident'];
		$this->botrealname = $bot['botrealname'];
		$this->localhost = $bot['localhost'];
		$this->serveraddress = $bot['serveraddress'];
		$this->serverport = $bot['serverport'];
		$this->poked_bot_nickname = $bot['poked_bot_nickname'];
		$this->timers = $bot['timers'];

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
			$this->connectTime = time();
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

	private function getRunOutTimers() {
	    $runOutTimers = array();
        foreach($this->timers as $timerChatRoomAndFilepath) {
		    $timerFile = @fopen($timerChatRoomAndFilepath[1], 'r');
		    if ($timerFile) {
		        $deletableLines = array();
		        $i = 1;
			    while (!feof($timerFile)) {
				    $timerInfoLine = fgets($timerFile);
				    $timerInfo = explode('/', $timerInfoLine, 5);
				    if ($timerInfo[0] <= time() && isset($timerInfo[1])) {
				        $runOutTimers[] = array($timerChatRoomAndFilepath[0], $timerInfoLine);
				        $deletableLines[] = $i;
				    }
				    $i++;
			    }
			    fclose($timerFile);
			    if ($deletableLines) {
			        $arr = file($timerChatRoomAndFilepath[1]);
			        foreach ($deletableLines as $lineNo) {
			            unset($arr[$lineNo-1]);
			        }
			        if ($fp = fopen($timerChatRoomAndFilepath[1], 'w+')) {
                        foreach($arr as $line) { fwrite($fp,$line); }
                        fclose($fp);
                    }
                }
		    }
        }
        return $runOutTimers;
	}

	function checkTimersAndPoke() {
        $timers = $this->getRunOutTimers();
        if ($timers) {
            foreach ($timers as $timer) {
                $roomName = $timer[0];
                $encoded = $roomName.'/'.$timer[1];
                $this->send('PRIVMSG '.$this->poked_bot_nickname.' :'.$encoded);
            }
			//echo 'KELL ON: '.date("H:i:s", time());
        }
	}

	function receive() {
	    stream_set_blocking($this->fp, 0);
		while (!feof($this->fp)) {
            while ($chunk = fgets($this->fp, 1024)) {
                $chunkWithFixedLinebreak = str_replace("\r", "", str_replace("\n", "", $chunk));
                $params = explode(" ", $chunkWithFixedLinebreak);
                if ($params[0] == 'PING'){
			        $this->send("PONG");
		        }
            }
            $this->checkTimersAndPoke();
            sleep(10);
		}
		$this->disconnect();
	}
}
?>
