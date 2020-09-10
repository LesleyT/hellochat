export class UserManager {
    
    context;

    constructor(context){
        this.context = context;
    }

    
    // data is object with username & fullname
    async requestUser(data){
        let user;
        if(!(user = await this.context.connection.maybeStartConnection())){
            let session = await this.context.connection.attemptLogin(data.username);
            if(!session){
                this.clearData();
                this.context.connection.getAuthorization(async function(data, response){
                    response = await this.createUser({
                        fullname : data.name,
                        username : data.username,
                        role : "admin"
                    });
                    if(response === false || response.statusCode === 409){
                        //error handle                    
                    }
                }.bind(this, data));
            } else {
                this.context.connection.storeUser(data);
            }
        }
        console.log(user);
        this.context.startPhase('start');
    }


    createNewUser(){
        
    }

    fetchPreviousUser(){

    }




    async createUser(){
        let endpoint = 'users';
        let response = await fetch(window.HelloChat.config.api + endpoint, {
            method : 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(owner)
        }).then(function(response){ return response.json(); })
        .then(function(response){ return response; })
        .catch(function(error){ console.log(error); });

        if(response.statusCode == 409){
            return response;
        }

        if(response.statusCode == 201){
            this.storeUser(response.data);
            return await this.createSession(response.data);
        }
        return false;
    }

    async createConversation(response){
        let created = new Date();
        let data = {
            participantOne : response.data.session_id,
            created : created.getDate().pad(2) + '-' + (created.getMonth() + 1).pad(2) + '-' + created.getFullYear() + ' ' + created.getHours().pad(2) + ':' + (created.getMinutes()).pad(2) + ':' + created.getSeconds().pad(2)
        };

        let options = {
            token : (typeof options != 'undefined' && typeof options.token != 'undefined') ? options.token : response.data.accesstoken
        };

        let endpoint = 'conversations';
        let headers = {
            'Content-Type': 'application/json'
        };
        if(typeof options.token != 'undefined'){
            headers.Authorization = options.token;
        }
        response = await fetch(window.HelloChat.config.api + endpoint, {
            method : 'POST',
            headers: headers,
            body: JSON.stringify(data)
        }).then(function(response){ return response.json(); })
        .then(function(response){ return response; })
        .catch(function(error){ console.log(error); });

        if(response.statusCode == 201){
            this.storeConversation(response.data);
            this.initiated = true;
            return true;
        }
        return false;    



        //FROM MAYBEPATCH

        // let conversation = sessionStorage.getItem('hc_conversation');
        // if(success ===  true && !conversation){
        //     sessionId = sessionStorage.getItem('hc_session');
        //     accessToken = sessionStorage.getItem('hc_token');
                
        //     let created = new Date();
        //     this.createConversation({
        //         participantOne : sessionId,
        //         created : created.getDate().pad(2) + '-' + (created.getMonth() + 1).pad(2) + '-' + created.getFullYear() + ' ' + created.getHours().pad(2) + ':' + (created.getMinutes()).pad(2) + ':' + created.getSeconds().pad(2)
        //     }, {
        //         token : accessToken
        //     });
        // }
    }


    storeUser(data){
        console.trace(data);
        localStorage.setItem('hc_user', data.username);      
    }

    storeSession(data){
        console.trace(data);
        sessionStorage.setItem('hc_id', data.user_id);
        sessionStorage.setItem('hc_token', data.accesstoken);
        sessionStorage.setItem('hc_session', data.session_id);
        sessionStorage.setItem('hc_access_expire', Math.floor(Date.now() / 1000) + data.access_token_expires_in);
        sessionStorage.setItem('hc_refresh_expire', Math.floor(Date.now() / 1000) + data.refresh_token_expires_in);
    }

    storeConversation(data){
        sessionStorage.setItem('hc_conversation', data.conversation[0].id);
    }

    

    clearData(){
        sessionStorage.removeItem('hc_id');
        sessionStorage.removeItem('hc_latest');
        sessionStorage.removeItem('hc_latest_chat');
        sessionStorage.removeItem('hc_session');
        sessionStorage.removeItem('hc_token');
        sessionStorage.removeItem('hc_conversation');
        sessionStorage.removeItem('hc_user_panel');
        sessionStorage.removeItem('hc_refresh_expire');
        sessionStorage.removeItem('hc_access_expire');
        
        localStorage.removeItem('hc_user');
    }


}