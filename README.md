# nebula
* * *
### Purpose ###
Nebula (__N__etwork of __E__nvironment-__B__ased __U__ser __L__oad-bearing __A__utomatons) is my personal entry for the Google Science Fair 2012, designed to ease the workload on servers by using new JavaScript WebWorkers to distribute calculations or other work to the clients. Similar to P2P file sharing, clients connecting to a server create a WebWorker which operates independently of the main site, so the server communicates with the WebWorker via WebSockets while the client can still browse the site as he wishes.

### Acknowledgement ###
Thanks to my brother, [Alex](http://www.github.com/alexcrichton) for the inspiration to do this.