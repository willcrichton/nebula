#include <iostream>
#include <string>
#include <stdlib.h>
#include <math.h>
#include <vector>
using namespace std;

int main(){
	string input = "";
	while( !cin.eof() ) cin >> input;
	
	vector<int> primes;
	primes.push_back(2);
	
	int maxPrime = atoi(input.c_str());
	int p = 3;
	while( p <= maxPrime ){
		bool isPrime = true;
		for( int i = 3; i <= sqrt((double) i); i += 2 ){
			if( p%i == 0 ){
				isPrime = false;
				break;
			}
		}
		
		if( isPrime ) primes.push_back( p );
		p += 2;
	}

	for( int i = 0; i < primes.size(); i++ )
		cout << primes[i] << ", ";
			
	return 0;
}