function handleMessage(event) {
    var data = event.data;
	switch(data.calc){
		case 'primes':
			var numPrimes = parseInt(data.data[1]);
			var primes = [];
			
			var p = parseInt(data.data[0]);
			if( p%2 == 0 ) p++;
			while( p <= numPrimes ){
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

