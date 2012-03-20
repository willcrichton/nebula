/*** IDEAS TO IMPLEMENT:
*	- Testing across multiple computers vs. multiple servers
*	- Double layer security: testing two random computers for computations
*	- Creating a new worker for each requested calculation so you can do shit simultaenously
*		- Have an array of workers to access from
*		- On server, assign each calc a unique ID and send that with each argument to pass
*	- Split each received task between 2 or more workers (even more threads!)
***/

var STATE_OFF = 0, STATE_READY = 1, STATE_LISTENING = 2, STATE_CALCULATING = 3;

Nebula = function(host, workerPath){	
	// Compatibility check
	if(!window.WebSocket || !window.Worker) {
		alert('Your browser is out of date.');
		return;
	}
	
	// WebSocket utility variables
	this.socket = undefined, this.worker = undefined;
	this.chunkSize = 2040;
	var self = this;
	var curState = STATE_OFF;
	var workerData = [];
	
	// Create a WebSocket
	this.socket = new WebSocket(host);
	// When the connection opens...
	this.socket.onopen = function(){
		self.output('Socket: Connection established!');
	}
	// When we get a message...
	this.socket.onmessage = function(data){
		var words = data.data.split(" ");
		// Follow command words
		if( words[0] == 'CMD' ){
			switch(words[1]){
				// Create the Worker for later use
				case 'create':
					if(self.worker) break;
					self.worker = new Worker(workerPath);
					self.worker.parent = self;
					self.worker.addEventListener('error', self.handleWorkerError, false);
					self.worker.addEventListener('message', self.handleWorkerMessage, false);
					curState = STATE_READY;
					self.output("Socket: created worker");
					break;
				// Get ready to accept arguments to pass to the worker	
				case 'newcalc':
					curState = STATE_LISTENING;
					self.output("Socket: listening for new calc");
					break;
				// Send all the info from the server to the worker	
				case 'docalc':
					curState = STATE_CALCULATING;
					self.worker.postMessage({calc: words[2], data: workerData});
					workerData = [];
					self.output("Socket: sending data to worker");
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
					self.output("Socket: while listening, received " + data.data);
					break;
				// Error
				default:
					self.output("Server: " + String(data.data).substring(0,1000));
					break;
			}						
		}
	}
	
	// When the connection closes...
	this.socket.onclose = function(){
		self.output("Socket: Connection closing.");
		if(self.worker) self.worker.terminate();
	}

	$(window).unload(function(){
		if(self.worker) self.worker.terminate();
	});
}

Nebula.prototype = {
	// Make it so the workers can break up long messages into chunks to send
	// TODO: figure out exactly why max length is 2040?
	sendChunks: function(msg, chunkSize){
		console.log("Sending chunk",msg);
		var i = 0;
		msg = String(msg);
		do {
			var chunk = msg.substring(i, Math.min(i+chunkSize, msg.length));
			this.socket.send(chunk);
			i += chunkSize
		} while( i < msg.length );
	},
	
	// WebWorker handler functions (for use when worker is created)
	handleWorkerMessage: function(event){
		this.parent.sendChunks(event.data, this.parent.chunkSize);
		this.parent.sendChunks("end_calc", this.parent.chunkSize);
		this.parent.output("Worker: " + String(event.data).substring(0,10));
		curState = STATE_READY;
	},
	
	handleWorkerError: function(event){
		this.parent.output(['Worker: ERROR Line ', event.lineno, ' in ', event.filename, ': ', event.message].join(''));
	},
	
	// Helper function for page form
	send: function( msg ){
		this.socket.send(msg);
		$('input[type=text]').attr('value', '');
	},

	// Easily visible output
	output: function( msg ){
		$('#output').append('<div>' + msg + '</div>');
	}
}