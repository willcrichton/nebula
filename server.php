<?php
/*************************************
* server.php - a WebSocket controller
* Created by Will Crichton, 2012
* Handles incoming messages on the WebSocket 
* Distributes calculations to users
*************************************/

require_once("websocket.class.php");

class ServerControl extends WebSocket {
	
	var $timer;
	var $timer2;
	var $timer3;
	var $calcQueue = array();	// array of all the pending calculations
	var $answerMatch = array();	// array which matches received answers to calculations
	var $serverDebug = false;	// turn on for more messages
	
	// method called whenever a message is received via the websocket
	function process($user, $msg){
		$words = explode(" ", $msg);
		if($this->serverDebug) $user->send("Received '" . utf8_encode($msg));
		switch($words[0]):
			case "find_primes":
				// Try finding primes via C++ (primes.exe)
				$max = intval($words[1]);
				$this->timer = microtime(true);
				exec("echo $max | primes.exe"); // run the program ($user->send won't be called til exec is finished)
				$user->send("C++: " . round(microtime(true) - $this->timer,5));*/
				
				// Now try and find via PHP (super slow)
				$this->timer2 = microtime(true);
				$primes = array();
				$p = 3;
				while( $p <= intval($words[1]) ){
					$isPrime = false;
					for( $i = 3; $i < sqrt($p); $i += 2 ){
						if( $p%$i == 0 ){
							$isPrime = true;
							break;
						}
					}
					if(!$isPrime) $primes[] = $p;
					$p += 2;
				}
				$user->send("PHP: " . round(microtime(true) - $this->timer2,5));
		
				// Lastly, do what we want: broadcast the calculation to the users
				$this->timer3 = microtime(true);
				$queueIndex = uniqid();	// generate a unique ID so we can match received calculations to their correct slot in the calcQueue
				$this->calcQueue[$queueIndex] = array( 'users' => array(), 'answer' => array(), 'asker' => $user->id );
				$i = 1;
				foreach($this->users as $u){
					// Tell the user to make a new worker if they don't already have one
					if(!$u->hasWorker){
						$u->send("CMD create");
						$u->hasWorker = true;
					}
					
					// Tell the user to start listening for arguments to the calculation
					$u->send("CMD newcalc");
					// Send the user the bottom and top bounds to check for prime numbers based on # of clients connected
					$u->send($i == 1 ? 3 : floor($max / count($this->users) * ($i - 1)) + 1);
					$u->send(floor($max / count($this->users) * $i));
					$u->send("CMD docalc primes");
					
					// Add the user to the list of users currently calculating primes
					$this->calcQueue[$queueIndex]['users'][] = $u->id;
					// This let's us know quickly which calcQueue element this user is currently operating on
					$this->answerMatch[$u->id] = $queueIndex;
					
					$i++;
				}
				
				break;
				
			// Just a test case I worked on, nothing big
			case "sum":
				foreach($this->users as $u){
					if(!$u->hasWorker){
						$u->send("CMD create");
						$u->hasWorker = true;
					}
					
					$this->timer = microtime(true);
					$u->send("CMD newcalc");
					for($i = 1; $i < count($words); $i++)
						$u->send($words[$i]);
					$u->send("CMD docalc sum");
				}
				break;
				
			case "end_calc":				
				
				// Find the array of calculation info via the uniq ID contained in answerMatch
				$calcID = $this->answerMatch[$user->id];
				$calc = $this->calcQueue[$calcID];	
				
				// Remove the user from answerMatch so further messages aren't thought as additional calculations
				unset($this->answerMatch[$user->id]);		
	
				// Send the user the full answer if we've gotten all the info
				if( count($calc['users']) == count($calc['answer']) ):				
					$answer = "";
				
					// Build up the answer from all info received from different clients
					for($i = 0; $i < count($calc['answer']); $i++) 
						$answer .= $this->calcQueue[$calcID]['answer'][$i] . ($i == count($calc['answer'])-1 ? " " : ", ");
						
					// Find the original asker and send them the answer
					foreach( $this->users as $u )
						if($u->id == $calc['asker']){ 
							$u->send("JS: ". round(microtime(true)-$this->timer3,5));
							break; 
						}
						
					// Destroy the calculation
					unset($this->calcQueue[$calcID]);
				endif;
								
				break;
				
			default:
				if( isset($this->answerMatch[$user->id]) ):
					// Find the array of calculation info via the uniq ID contained in answerMatch
					$calcID = $this->answerMatch[$user->id];
					$calc = $this->calcQueue[$calcID];
			
					// Find which index in the answer array to put the user's message
					$userIndex = 0;
					foreach($calc['users'] as $k => $v)
						if( $v == $user->id ){ $userIndex = $k; break; }
											
					// Add the user's message to the answer array
					$this->calcQueue[$calcID]['answer'][$userIndex] = isset($this->calcQueue[$calcID]['answer'][$userIndex]) ? $this->calcQueue[$calcID]['answer'][$userIndex] . $msg : $msg;
					
				else: 
					// Default to notifying what the chunk received was
					foreach($this->users as $u)
						$u->send("Chunk of length " . strlen($msg) . " received after " . round(microtime(true) - $this->timer3,5) . " seconds from user " . $user->id . ($user->id == $u->id ? " (you) " : "") );
				endif;
				break;
		endswitch;
	}
	
}

new ServerControl("localhost",8749); ?>
