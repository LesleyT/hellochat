export class NotificationManager {

    context;
    supported;

    isActive;

    idleTime;
    idleInterval;
    idleLimit;

    constructor(context){
        this.context = context;
        this.supported = ('Notification' in window) ? true : false;
        
        if(typeof window.ghn == 'undefined'){
            window.ghn = {
                isActive : true,
                idleTime : 0,
                idleLimit : 1,
                idleInterval : setInterval(function(){
                    window.ghn.idleTime++;
                    if(window.ghn.idleTime > window.ghn.idleLimit){
                        window.ghn.isActive = false;
                    }
                }.bind(this), 30000),
            };
    
            window.onmousemove = function() {
                window.ghn.isActive = true;
                window.ghn.idleTime = 0;
            }.bind(this); 
    
            window.onkeypress = function() {
                window.ghn.isActive = true;
                window.ghn.idleTime = 0;
            }.bind(this); 
        }
    }

    askConsent(){
        
        if(!this.supported){ return; }
        
        if(Notification && Notification.permission === 'default') {
            
            Notification.requestPermission(function (permission) {
                if(!('permission' in Notification)) {
                    
                    Notification.permission = permission;
                }
            });
        }
    }

    send(title, text, icon) {
        
        if(!this.supported){ return; }
        
        if(typeof window.ghn != 'undefined' && window.ghn.isActive){ return; } 
        

        if (Notification.permission === 'granted') {
            
          //var text = "your Notification Body goes here";
          this.build(title, text, icon);
        }
    }

    build(title, text, icon) {
        
        if(!this.supported){ return; }
        
        let notification = new Notification(title, {
            icon: icon,
            body: text,
            tag: 'hellochat'
        });
        
        notification.onclick = function(e) {
            
            e.preventDefault();
            parent.focus();
            window.focus(); //just in case, older browsers
            this.close();
        };
        
        setTimeout(notification.close.bind(notification), 8000);
    }   

}