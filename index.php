<!DOCTYPE html>
<html>
	<head>
		<title>Testing</title>
		
		<link rel='stylesheet' type='text/css' href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,400,700' />
		<link rel="stylesheet" type="text/css" href="style.css" />
		
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
		<script>
			/*** IDEAS TO IMPLEMENT:
			*	- Testing across multiple computers vs. multiple servers
			*	- Double layer security: testing two random computers for computations
			*	- Creating a new worker for each requested calculation so you can do shit simultaenously
			*		- Have an array of workers to access from
			*		- On server, assign each calc a unique ID and send that with each argument to pass
			*	- Split each received task between 2 or more workers (even more threads!)
			***/
			
			var socket, worker;
			var STATE_OFF = 0, STATE_READY = 1, STATE_LISTENING = 2, STATE_CALCULATING = 3;
			function init(){
				// Compatibility check
				if(!window.WebSocket || !window.Worker) {
					alert('Your browser is out of date.');
					return;
				}
				
				// WebSocket utility variables
				var host = 'ws://localhost:8849/scifair/server.php';
				var curState = STATE_OFF;
				var workerData = [];
				
				// Make it so the workers can break up long messages into chunks to send
				// TODO: figure out exactly why max length is 2040?
				function sendChunks(msg, chunkSize){
					var i = 0;
					msg = String(msg);
					do {
						socket.send(msg.substring(i, Math.min(i+chunkSize, msg.length)));
						i += chunkSize
					} while( i < msg.length );
				}
				
				// WebWorker handler functions (for use when created)
				function handleWorkerMessage(event){
					sendChunks(event.data, 2040);
					output("Worker: " + String(event.data).substring(0,10));
					curState = STATE_READY;
				}
				
				function handleWorkerError(event){
					output(['Worker: ERROR Line ', event.lineno, ' in ', event.filename, ': ', event.message].join(''));
				}
				
				// Create a WebSocket
				socket = new WebSocket(host);
				// When the connection opens...
				socket.onopen = function(){
					output('Socket: Connection established!');
				}
				// When we get a message...
				socket.onmessage = function(data){
					var words = data.data.split(" ");
					// Follow command words
					if( words[0] == 'COMMAND' ){
						switch(words[1]){
							// Create the Worker for later use
							case 'create':
								if(worker) break;
								worker = new Worker("worker.js");
								worker.addEventListener('error', handleWorkerError, false);
								worker.addEventListener('message', handleWorkerMessage, false);
								curState = STATE_READY;
								output("Socket: created worker");
								break;
							// Get ready to accept arguments to pass to the worker	
							case 'newcalc':
								curState = STATE_LISTENING;
								output("Socket: listening for new calc");
								break;
							// Send all the info from the server to the worker	
							case 'docalc':
								curState = STATE_CALCULATING;
								worker.postMessage({calc: words[2], data: workerData});
								workerData = [];
								output("Socket: sending data to worker");
								break;
							// Error
							default:
								output("Socket: Command \"" + words[1] + "\" is not valid.");
								break;
						}
					} else {
						switch(curState){
							// Save data from the server to a temporary array
							case STATE_LISTENING:
								workerData.push(parseInt(data.data));
								output("Socket: while listening, received " + data.data);
								break;
							// Error
							default:
								output("Server: " + String(data.data).substring(0,175));
								break;
						}						
					}
				}
				// When the connection closes...
				socket.onclose = function(){
					output("Socket: Connection closing.");
					if(worker) worker.terminate();
				}
			}
			
			// Helper function for page form
			function send( msg ){
				socket.send(msg);
				$('input[type=text]').attr('value', '');
			}
			
			// Easily visible output
			function output( msg ){
				$('#output').append('<div>' + msg + '</div>');
			}
			
			// Wait 'til document is loaded to do anything
			$(document).ready(init);
			
			$(window).unload(function(){
				if(worker) worker.terminate();
			});
		</script>
	</head>
	<body>
		<div id="container">
			<h1>Sample Site</h1>
			<img src="http://www.dummyimage.com/600x50" />
			<form id="input" onsubmit="send(this.message.value); return false;">
				<h2>Input</h2>
				<input type="text" name="message" placeholder="Send a command" /><br />
				<input type="submit" value="Submit" />
			</form>
			<div id="output">
				<h2>Output</h2>
			</div>
		</div>
	</body>
</html>