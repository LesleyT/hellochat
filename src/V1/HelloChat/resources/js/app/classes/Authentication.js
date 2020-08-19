export class Authentication {
    
    context;

    timer;
    wait;

    constructor(context){
        this.context = context;
        this.wait = 60000;
    }





    async ping(){
        let accessToken = sessionStorage.getItem('hc_token');
        let userId = sessionStorage.getItem('hc_id');
        if(!accessToken || !userId){
            return false;
        }

        let endpoint = 'users/ping/'+userId;
        let headers = {
            'Content-Type': 'application/json',
            'Authorization' : accessToken
        };

        let response = await fetch(window.HelloChat.config.api + endpoint, {
            method : 'GET',
            headers: headers
        }).then(function(response){ return response.json(); })
        .then(function(response){ return response; })
        .catch(function(error){ console.log(error); });
        if(response.statusCode != 401){
            return response;
        } else {
            return false;
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
            this.context.user.storeSession(response.data);
            return true;
        } return false;
    }

    
    /* CHECKS whether user is supposed to be able to see the current page */    

    async getAuthorization(callback){
        let endpoint = 'authorization';
        let response = await fetch(window.HelloChat.config.api + endpoint, {
            method : 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        }).then(function(response){ return response.json(); })
        .then(function(response){ return response; })
        .catch(function(error){ console.log(error); });
        
        if(response.statusCode == 200){
            callback(response);
        } else {
            //error handle
            let result = this.maybePatch();
            if(result){
                this.getAuthorization(callback);
            }
            
        }
    }


    /* CHECKS NEW SESSIONS NEEDS TO BE CREATED OR OLD SESSION NEEDS TO BE PATCHED */

    async maybeStartConnection(){
        let user = localStorage.getItem('hc_user');
        if(user != 'undefined' && user != null){
            let sessionId = sessionStorage.getItem('hc_session');
            let accessToken = sessionStorage.getItem('hc_token');
            let accessExpiry = sessionStorage.getItem('hc_access_expire');

            let session = false;
            if(sessionId != 'undefined' && sessionId !== null && accessExpiry != 'undefined' && accessExpiry !== null && accessToken != 'undefined' && accessToken !== null){
                session = await this.maybePatch();
                if(session === true && !(session = await this.ping())){ return false; }
            } else {
                session = await this.createSession({
                    username : user
                }, false, {
                    session : sessionId,
                    token : accessToken
                });
            }
            return session == false ? false : session;
        }
        return false;
    }


    
    /* CHECKS IF SESSION NEEDS TO BE PATCHED */

    async maybePatch(){
        let success = false;

        let user = localStorage.getItem('hc_user');
        let sessionId = sessionStorage.getItem('hc_session');
        let accessToken = sessionStorage.getItem('hc_token');
        let accessExpiry = sessionStorage.getItem('hc_access_expire');
        if(user != 'undefined' && user !== null && sessionId != 'undefined' && sessionId !== null && accessExpiry != 'undefined' && accessExpiry !== null && accessToken != 'undefined' && accessToken !== null){   
            if((Math.floor(Date.now() / 1000) + this.lookahead) >= parseInt(accessExpiry)){
                success = await this.createSession({
                    username : user
                }, true, {
                    session : sessionId,
                    token : accessToken
                });
            } else { 
                this.initiated = true;
                success = true; 
            }
        }

        this.timer = setTimeout(this.patchTimer.bind(this), this.wait);
        return success;
    }

    patchTimer(){
        if(this.timer){
            clearTimeout(this.timer);
            this.maybePatch();
        }
    }

    async tryReconnect(){
        return await this.maybePatch();
    }


    async createSession(data, update, options){
        let endpoint = 'sessions';
        let headers = {
            'Content-Type': 'application/json'
        };

        if(update === true){
            if(typeof options.session != 'undefined'){
                endpoint += '/' + options.session;
            }
            if(typeof options.token != 'undefined'){
                headers.Authorization = options.token;
            }
        }

        let response = await fetch(window.HelloChat.config.api + endpoint, {
            method : (update === true) ? 'PATCH' : 'POST',
            headers: headers,
            body: JSON.stringify(data)
        }).then(function(response){ return response.json(); })
        .then(function(response){ return response; })
        .catch(function(error){ console.log(error); });
             
        if(response.statusCode == 200 || response.statusCode == 201){
            this.timer = setTimeout(this.patchTimer.bind(this), this.wait);

            this.context.user.storeSession(response.data);
            return response;
        } 
        return false;
    }


}
    