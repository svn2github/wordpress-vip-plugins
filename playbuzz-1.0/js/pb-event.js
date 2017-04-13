
function PbEvent(sender){

    this.sender = sender;
    this.listeners = [];
}

PbEvent.prototype.listen = function (listener) {
    this.listeners.push(listener);
};

PbEvent.prototype.notify  = function (args) {
    var index;

    for (index = 0; index < this.listeners.length; index++) {
        this.listeners[index](this.sender, args);
    }
};


window.PbEvent = PbEvent;