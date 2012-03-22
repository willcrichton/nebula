/***************************************
* worker.js - a sample worker for Nebula
* Created by Will Crichton, 2012
* Handles basic calculations, e.g. finding prime numbers or adding numbers
***************************************/
// Workers are given messages from the originating javascript back on the index, so we use a handler to capture those messages
function handleMessage(event) {
    var data = event.data;
	switch(data.calc){	// the "calc" field indicates which calculation to perform
		case 'primes':
			// From the message we're sent, we can get the lower/upper bounds of primes to check
			var primes = [];
			var p = parseInt(data.data[0]);
			var maxPrime = parseInt(data.data[1]);
			if( p%2 == 0 ) p++;
			// Do our horribly inefficient algorithm for finding primes
			while( p <= maxPrime ){
				isPrime = false;
				for( var i = 3; i <= Math.sqrt(p); i += 2 ) {
					if( p%i == 0 ) {
						isPrime = true;
						break;
					}
				}
				
				if( !isPrime ) primes.push( p );
				p += 2;
			}
			
			// Once we have our list of primes, send the messsage back to the index via postMessage
			postMessage( primes.join(" ") );
			break;
			
		// just a test case, don't worry about this
		case 'sum':
			var sum = 0;
			for( i in data.data )
				sum += data.data[i];
				
			postMessage( sum );
			break;
	}
}
// Gotta add the function as a listener to the 'message' event
addEventListener('message', handleMessage, false);

