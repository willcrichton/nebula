<!DOCTYPE html>
<html>
	<head>
		<title>Science Fair Testing</title>
	</head>
	<body>
		<script>
			function workerFromJS( script ){
				var bb = new BlobBuilder();
				bb.append( script );
				var url = window.URL.createObjectURL(bb.getBlob());
				return new Worker(url);
			}
		
			var myWorker = new Worker("worker.js");
			
			function handleMessage( event ){
				alert('test');
				console.log( event.data );
			}
			myWorker.addEventListener( 'message', handleMessage, false );
			
			function handleError(e) {
				console.log(['ERROR: Line ', e.lineno, ' in ', e.filename, ': ', e.message].join(''));
			}
			myWorker.addEventListener( 'error', handleError, false );
			
			myWorker.postMessage("Test");
			
			myWorker.terminate();
		</script>
	</body>
</html>