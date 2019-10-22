# wamp-stream-service
The queue with persistent wamp connection behind the service makes it suited
 for handling a lot of traffic asynchronously



Available artisan commands:

    wamp:queue:work    Start processing wamp server messages on the queue as a daemon
    wamp:router:start   Start Thruway WAMP router.

### Web Service
Endpoints
 - **api** : See tests for available options

       /v1/notifications/create - send a message to a connected sessionId
       /v1/notifications/createBroadcast - sends a message to all connected sessions
    The api requires basic authentication (user:pass@domain). See: `Http/Middleware/AuthenticateOnceWithBasicAuth.php`
 - **websocket**: where public websocket clients connect
    You must implement authentication on the router. The default authenticator is 
    (dummy) allows everyone to connect.

       /ws - reverse proxy to wamp-router using apache
### Queue Worker
The queue worker connects to the router in order to send wamp messages. So the router must 
be started before the queue worker. 

The artisan command `wamp:queue:work` has all the options the `queue:work` 
command has. The default queue is: `wamp`

Setting framework queues:

`php artisan queue:failed-table && php artisan migrate`

See `config/wamp-client.php` for wamp client config options

### Scheduler
The default  lumen scheduler command, is set to clean failed jobs at daily interval. Tweak value for the volumes of jobs.
### WAMP Router
The server is very versatile and can support a lot of functionality (extending)

See `Libraries/WampServer/Thruway/TokenAuthenticator.php` as sample on how to use token auth and custom sessionId assignment    

See `config/wamp-server.php` for config options

WAMP component from: https://github.com/voryx/Thruway  
  
Autobahn js lib is also in the repository for web integrations

**NOTE:**
This code was migrated from an old symfony app, and has not been thoroughly tested,
 so expect bugs.
