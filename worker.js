function handleMessage(event) {
    var msg = event.data;
    this.postMessage("Hello from a Web Worker!");
}
this.addEventListener('message', handleMessage, false);
