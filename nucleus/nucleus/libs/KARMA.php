<?

/**
  * Nucleus: PHP/MySQL Weblog CMS (http://nucleuscms.org/) 
  * Copyright (C) 2002 The Nucleus Group
  *
  * This program is free software; you can redistribute it and/or
  * modify it under the terms of the GNU General Public License
  * as published by the Free Software Foundation; either version 2
  * of the License, or (at your option) any later version.
  * (see nucleus/documentation/index.html#license for more info)
  *
  * Class representing the karma votes for a certain item
  */
class KARMA {
	
	function KARMA($itemid, $initpos = 0, $initneg = 0, $initread = 0) {	
		// itemid
		$this->itemid = $itemid;

		// have we read the karma info yet?
		$this->inforead = $initread;	
		
		// number of positive and negative votes
		$this->karmapos = $initpos;
		$this->karmaneg = $initneg;
	}
	
	function getNbPosVotes() {
		if (!$this->inforead) $this->readFromDatabase();
		return $this->karmapos;
	}
	function getNbNegVotes() {
		if (!$this->inforead) $this->readFromDatabase();
		return $this->karmaneg;
	}
	function getNbOfVotes() {
		if (!$this->inforead) $this->readFromDatabase();
		return ($this->karmapos + $this->karmaneg);
	}
	function getTotalScore() {
		if (!$this->inforead) $this->readFromDatabase();
		return ($this->karmapos - $this->karmaneg);
	}
	
	function setNbPosVotes($val) {
		$this->karmapos = $val;
	}
	function setNbNegVotes($val) {
		$this->karmaneg = $val;
	}


	// adds a positive vote
	function votePositive() {
		$newKarma = $this->getNbPosVotes() + 1;
		$this->setNbPosVotes($newKarma);
		$this->writeToDatabase();
		$this->saveIP();
	}
	
	// adds a negative vote
	function voteNegative() {
		$newKarma = $this->getNbNegVotes() + 1;	
		$this->setNbNegVotes($newKarma);		
		$this->writeToDatabase();		
		$this->saveIP();
	}



	// these methods shouldn't be called directly
	function readFromDatabase() {
		$query = 'SELECT ikarmapos, ikarmaneg FROM nucleus_item WHERE inumber=' . $this->itemid;
		$res = sql_query($query);
		$obj = mysql_fetch_object($res);
		
		$this->karmapos = $obj->ikarmapos;
		$this->karmaneg = $obj->ikarmaneg;		
		$this->inforead = 1;
	}
		
	
	function writeToDatabase() {
		$query = 'UPDATE nucleus_item SET ikarmapos=' . $this->karmapos . ', ikarmaneg='.$this->karmaneg.' WHERE inumber=' . $this->itemid;
		sql_query($query);
	}
	
	// checks if a vote is still allowed for an IP
	function isVoteAllowed($ip) {
		$query = "SELECT * FROM nucleus_karma WHERE itemid=$this->itemid and ip='$ip'";
		$res = sql_query($query);
		return (mysql_num_rows($res) == 0);
	}
	
	// save IP in database so no multiple votes are possible
	function saveIP() {
		$query = 'INSERT INTO nucleus_karma (itemid, ip) VALUES ('.$this->itemid.",'".serverVar('REMOTE_ADDR')."')";
		sql_query($query);
	}
}

?>