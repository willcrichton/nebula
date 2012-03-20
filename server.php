<?php  /*  >php -q server.php  */

require_once("websocket.class.php");

class ServerControl extends WebSocket {
	
	var $timer;
	var $timer2;
	var $calcQueue = array();
	var $answerMatch = array();
	
	function process($user, $msg){
		$words = explode(" ", $msg);
		switch($words[0]):
			case "find_primes":
				$max = intval($words[1]);
				$i = 1;
				$this->timer = microtime(true);
				$output = exec("echo $max | primes.exe");
				$endtime = round(microtime(true) - $this->timer,5);
				$user->send("C++ calc primes after $endtime seconds");
				
				$this->timer = microtime(true);
				$queueIndex = uniqid();
				$this->calcQueue[$queueIndex] = array( 'users' => array(), 'answer' => array(), 'asker' => $user->id );
				foreach($this->users as $u){
					if(!$u->hasWorker){
						$u->send("CMD create");
						$u->hasWorker = true;
					}
					
					$u->send("CMD newcalc");
					$u->send($i == 1 ? 3 : floor($max / count($this->users) * ($i - 1)) + 1);
					$u->send(floor($max / count($this->users) * $i));
					$u->send("CMD docalc primes");
					
					$this->calcQueue[$queueIndex]['users'][] = $u->id;
					$this->answerMatch[$u->id] = $queueIndex;
					
					$i++;
				}
				
				/*$this->timer2 = microtime(true);
				$primes = array();
				$p = 3;
				$user->send("PHP doing prime calc of max " . $words[1]);
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
				$user->send("PHP calc primes after " . (microtime(true) - $this->timer2) . " seconds");*/
				break;
				
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
					
					for($i = 0; $i < count($calc['answer']); $i++) 
						$answer .= $this->calcQueue[$calcID]['answer'][$i] . ($i == count($calc['answer'])-1 ? " " : ", ");
						
					foreach( $this->users as $u )
						if($u->id == $calc['asker']){ $u->send("Your answer (" . round(microtime(true)-$this->timer,5) . "): $answer"); break; }
						
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
					foreach($this->users as $u)
						$u->send("Chunk of length " . strlen($msg) . " received after " . round(microtime(true) - $this->timer,5) . " seconds from user " . $user->id . ($user->id == $u->id ? " (you) " : "") );
				endif;
				break;
		endswitch;
	}
	
}

$master = new ServerControl("localhost",8800);

?>
