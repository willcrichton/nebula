UTF8 = {
	encode: function(s){
		for(var c, i = -1, l = (s = s.split("")).length, o = String.fromCharCode; ++i < l;
			s[i] = (c = s[i].charCodeAt(0)) >= 127 ? o(0xc0 | (c >>> 6)) + o(0x80 | (c & 0x3f)) : s[i]
		);
		return s.join("");
	},
	decode: function(s){
		for(var a, b, i = -1, l = (s = s.split("")).length, o = String.fromCharCode, c = "charCodeAt"; ++i < l;
			((a = s[i][c](0)) & 0x80) &&
			(s[i] = (a & 0xfc) == 0xc0 && ((b = s[i + 1][c](0)) & 0xc0) == 0x80 ?
			o(((a & 0x03) << 6) + (b & 0x3f)) : o(128), s[++i] = "")
		);
		return s.join("");
	}
};

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
	this.debug = false;
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
					if(self.debug) self.output("Socket: created worker");
					break;
				// Get ready to accept arguments to pass to the worker	
				case 'newcalc':
					curState = STATE_LISTENING;
					if(this.debug) self.output("Socket: listening for new calc");
					break;
				// Send all the info from the server to the worker	
				case 'docalc':
					curState = STATE_CALCULATING;
					self.worker.postMessage({calc: words[2], data: workerData});
					workerData = [];
					if(self.debug) self.output("Socket: sending data to worker");
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
					if(self.debug) self.output("Socket: while listening, received " + data.data);
					break;
				// Error
				default:
					self.output("Server: " + String(data.data).substring(0,1000));
					if($("input[name=continuous]").is(":checked") && $("#output div").length < 26) self.send("find_primes 100");
					break;
			}						
		}
	}
	
	// When the connection closes...
	this.socket.onclose = function(){
		if(self.debug) self.output("Socket: Connection closing.");
		if(self.worker) self.worker.terminate();
	}

	$(window).unload(function(){
		if(self.worker) self.worker.terminate();
	});
}

Nebula.prototype = {
	// Make it so the workers can break up long messages into chunks to send
	// TODO: figure out exactly why max length is 2040?
	sendChunks: function(msg){
		var i = 0;
		msg = UTF8.encode(String(msg)); 
		do {
			var chunk = msg.substring(i, Math.min(i+this.chunkSize, msg.length));
			console.log("Sending chunk",chunk);
			this.socket.send(chunk);
			i += this.chunkSize
		} while( i < msg.length );
	},
	
	// WebWorker handler functions (for use when worker is created)
	handleWorkerMessage: function(event){
		//this.parent.sendChunks(event.data);
		this.parent.sendChunks("test");
		/*** THIS IS THE UTF-8 ISSUE: SENDING END CALC JUST AFTER ANSWER GETS THE TWO CONFUSED?? ***/
		setTimeout('neb.sendChunks("end_calc");',10);
		if(this.parent.debug) this.parent.output("Worker: " + String(event.data).substring(0,10));
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