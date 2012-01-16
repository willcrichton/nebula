function handleMessage(event) {
    var data = event.data;
	switch(data.calc){
		case 'primes':
			var numPrimes = data.data[0];
			var primes = [2];
			
			var p = 3;
			while( primes.length < numPrimes ){
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
			
			postMessage( primes.join(", ") );
			break;
			
		case 'sum':
			var sum = 0;
			for( i in data.data )
				sum += data.data[i];
				
			postMessage( sum );
			break;
	}
		
}
addEventListener('message', handleMessage, false);

