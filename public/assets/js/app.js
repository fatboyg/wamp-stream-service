/**
 * Created by unzel on 10.12.2016.
 */


var myApp = function()
{
    var wampConf = {
        dsn: window.wsUrl,
        topic: 'org.robits.notifications',
        realm: 'web', // auth domain
        authToken: 'test'
    };

    this.wamp = new WampClient(wampConf);

    this.wamp.init()
};



function WampClient(config)
{

    this.config = config;
    var self = this;
    self.authError = null;

    var connection = new autobahn.Connection({
        url: self.config.dsn,
        realm: self.config.realm,

        authmethods: ["token", "dummy"],
        onchallenge: function(session, method, extra) {return self.onAuthChallenge(session, method, extra);}
    });

    connection.onopen = function(session, details) {
        session.subscribe(self.config.topic, self.onTopicMessage);
    };

    self.onAuthChallenge = function (session, method, extra) {
        if (method === "token"){
            return config.authToken;
        }
        else {
            self.authError = true;
            console.error("Can't auth using " + method);
            return "";
        }
    };

    self.onTopicMessage = function (args) {
        console.log('onTopicMessage: We have a nice msg', args);
    };

    self.init = function()
    {
        connection.open();
    };


    this.getConnection = function () {
      return connection;
    };


    return self;
}
