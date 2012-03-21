<!DOCTYPE html>
<html>
	<head>
		<title>Testing</title>
		
		<link rel='stylesheet' type='text/css' href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,400,700' />
		<link rel="stylesheet" type="text/css" href="style.css" />
		
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
		<script src="nebula.js"></script>
		<script>
			var neb;
			$(document).ready(function(){ 
				neb = new Nebula('ws://localhost:8754/scifair/server.php', 'worker.js'); 
			});
		</script>
	</head>
	<body>
		<div id="container">
			<h1>Sample Site</h1>
			<img src="http://www.dummyimage.com/600x50" />
			<form id="input" onsubmit="neb.send(this.command.value); return false;">
				<h2>Input</h2>
				<input type="text" name="command" placeholder="Send a command" /><br />
				<input type="checkbox" name="continuous" /> Continuous?<br />
				<input type="submit" value="Submit" />
			</form>
			<div id="output">
				<h2>Output</h2>
			</div>
		</div>
	</body>
</html>