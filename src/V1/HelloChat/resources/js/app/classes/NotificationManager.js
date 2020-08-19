export class NotificationManager {

    context;
    supported;

    constructor(context){
        this.context = context;
        this.supported = ('Notification' in window) ? true : false;
    }

    askConsent(){
        console.log(1);
        if(!this.supported){ return; }
        console.log(2);
        if(Notification && Notification.permission === 'default') {
            console.log(3);
            Notification.requestPermission(function (permission) {
                if(!('permission' in Notification)) {
                    console.log(4);
                    Notification.permission = permission;
                }
            });
        }
    }

    send(title, text, icon) {
        if(!this.supported){ return; }
        if(this.context.isActive){ console.log('cant'); return; }

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