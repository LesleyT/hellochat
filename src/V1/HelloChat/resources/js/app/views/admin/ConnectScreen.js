export class ConnectScreen {
    
    context;
    parent;
    elements;

    constructor(context, parent){
        this.context = context;
        this.parent = parent;
        this.elements = {};
    }

    build(){
        this.destroy();

        let connect = document.createElement('div');
        connect.classList.add('hc__connect');

        let button = document.createElement('button');
        button.classList.add('hc__connect__user');
        button.innerHTML = window.HelloChat.localization.connect.connectButton;
        button.addEventListener('click', function(){ this.context.connection.getAuthorization(function(response){
                this.handleConnection.call(this, response);
        }.bind(this)); }.bind(this), true);

        connect.appendChild(button);

        this.parent.appendChild(connect);        
    }

    async handleConnection(response){
        if(response.statusCode !== 200){
            //error handle
            return;
        }

        let data = response.data;
        let user;
        if(!(user = await this.context.connection.maybeStartConnection())){
            let session = await this.attemptLogin(data.username);
            if(!session){
                this.context.connection.clearData();
                let response = await this.context.connection.createUser({
                    fullname : data.name,
                    username : data.username,
                    role : "admin"
                });
                if(response === false || response.statusCode === 409){
                    //error handle                    
                    return;
                }
            } else {
                this.context.connection.storeUser(data);
            }
        }
        let activated = await this.context.activateChat(true);
        if(activated){
            this.context.startPhase('start');
        } else {
            //error handle
        }
    }

    async attemptLogin(username){
        let endpoint = 'sessions';
        let response = await fetch(window.HelloChat.config.api + endpoint, {
            method : 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                username : username
            })
        }).then(function(response){ return response.json(); })
        .then(function(response){ return response; })
        .catch(function(error){ console.log(error); });
        
        if(response.statusCode == 201){
            this.context.connection.storeSession(response.data);
            return true;
        } return false;
    }

    destroy(){
        let menu = this.parent.querySelector('.hc__settings');
        if(menu){ menu.style.display = ''; }  

        let view = this.parent.querySelector('.hc__connect');
        if(view){ view.remove(); }
    }

    async run(){   
        this.build();
        
        let menu = this.parent.querySelector('.hc__settings');
        if(menu){ menu.style.display = 'none'; }    
    }
}