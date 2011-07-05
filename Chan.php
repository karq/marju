#!/usr/bin/php

<?php

class Chan {

	var $name;
	var $vocfile;
	var $quotefile;
	var $speak;
	public $timerfile;

	function Chan($data) {
		$this->name = $data['name'];
		$this->vocfile = $data['vocfile'];
		$this->quotefile = $data['quotefile'];
		$this->timerfile = $data['timerfile'];
		$this->speak = $data['speak'];
	}

}

?>

