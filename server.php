<?php  /*  >php -q server.php  */

require_once("websocket.class.php");

class ServerControl extends WebSocket {
	
	var $timer;
	var $timer2;
	
	function process($user, $msg){
		$words = explode(" ", $msg);
		switch($words[0]):
			case "find_primes":
				foreach($this->users as $u){
					if(!$u->hasWorker){
						$u->send("COMMAND create");
						$u->hasWorker = true;
					}
					
					$this->timer = microtime(true);
					$u->send("COMMAND newcalc");
					$u->send($words[1]);
					$u->send("COMMAND docalc primes");
				}
				
				$this->timer2 = microtime(true);
				$primes = array();
				$p = 3;
				while( count($primes) < intval($words[1]) ){
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
				$user->send("PHP calc primes after " . (microtime(true) - $this->timer2) . " seconds");
				break;
				
			case "sum":
				foreach($this->users as $u){
					if(!$u->hasWorker){
						$u->send("COMMAND create");
						$u->hasWorker = true;
					}
					
					$this->timer = microtime(true);
					$u->send("COMMAND newcalc");
					for($i = 1; $i < count($words); $i++)
						$u->send($words[$i]);
					$u->send("COMMAND docalc sum");
				}
				break;
				
			default:
				$user->send("length " . strlen($msg) . " after " . (microtime(true) - $this->timer) . " seconds, saying: \"$msg\"" );
				break;
		endswitch;
	}
	
	function onConnect($user){}
	
	function onDisconnect($user){}

}

$master = new ServerControl("localhost",8866);

?>
