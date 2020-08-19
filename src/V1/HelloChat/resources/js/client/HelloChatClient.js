export class HelloChatClient {

    isAdmin;

    config;
    elements;

    constructor(isAdmin){  
        this.isAdmin = isAdmin;
        this.elements = {};
        this.displayMessage();
        this.build();

        if(this.isAdmin){ return; }
    }

    build(){
        this.elements.screen = document.createElement('iframe');
        this.elements.screen.classList.add('hc__client__frame');
        this.elements.screen.src = window.HelloChatClient.url + (this.isAdmin ? '?mode=0' : '?mode=1');
        this.elements.screen.classList.toggle('active');
        if(sessionStorage.getItem('hc__chat__activated') == 'true'){
            this.elements.screen.classList.toggle('active');
        }

        this.elements.button = document.createElement('button');
        this.elements.button.classList.add('hc__floating_bubble');
        if(this.isAdmin){
            this.elements.button.addEventListener('click', function(){
                this.elements.screen.classList.toggle('active');
                sessionStorage.setItem('hc__chat__activated', this.elements.screen.classList.contains('active'));
            }.bind(this), true);
        } else {
            this.elements.button.addEventListener('click', function(){ 
                if(!sessionStorage.getItem('hc__message__shown')){
                    this.closeMessage.call(this.elements.close);
                }
                this.elements.screen.classList.toggle('active');
            }.bind(this), true);
        }

        document.body.appendChild(this.elements.screen);
        document.body.appendChild(this.elements.button);
    }
    
    displayMessage(){
        if(!sessionStorage.getItem('hc__message__shown')){
            this.createMessage();
        }
    }

    createMessage(){
        let message = document.createElement('div');
        message.classList.add('hc__chat__invite');
        setTimeout(function(){ this.classList.add('active'); }.bind(message), 4000);

        let inner = document.createElement('div');
        inner.classList.add('hc__inner__invite');
        inner.addEventListener('click', function(){ this.elements.screen.classList.toggle('active'); this.closeMessage.call(this.elements.close) }.bind(this), true);

        let p = document.createElement('p');
        p.innerHTML = window.HelloChat.localization.welcome.introDescription;

        this.elements.close = document.createElement('button');
        this.elements.close.classList.add('hc__close_invite');
        this.elements.close.addEventListener('click', function(){ this.closeMessage.call(this.elements.close); }.bind(this), true);

        inner.appendChild(p);
        message.appendChild(inner);
        message.appendChild(this.elements.close);

                
        document.body.appendChild(message);
    }

    closeMessage(){
        sessionStorage.setItem('hc__message__shown', true);
        this.parentNode.classList.remove('active');
        setTimeout(function(){ this.remove(); }.bind(this.parentNode), 200);
    }

}