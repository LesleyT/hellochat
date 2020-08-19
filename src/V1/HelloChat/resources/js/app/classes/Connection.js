import { ExportNumber } from '../utility/Number.js';
// store userdata & sessions data to be loaded again - store for browser session

export class Connection {
        
    user;
    session;
    conversation;

    initiated;
    lookahead;
    wait;

    timer;

    makeConversationOnSession;

    constructor(){
        this.makeConversationOnSession = false;
        this.initiated = false;
        this.lookahead =  300;
        this.wait = 60000;
                
        // this.init(data);
    }

    async tryReconnect(){
        return await this.maybePatch();
    }

    async init(mode, data){
        if(this.initiated ===  true){ return; }
        if(mode === 'new'){
            return this.createUser({
                fullname : data['name'],
                username : data['email']
            });
        } else if(mode === 'previous'){
            return await this.maybeStartConnection();
        }
    }

    async createUser(owner){
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

            this.storeSession(response.data);
            if(this.makeConversationOnSession && (update === false || (this.makeConversationOnSession && !sessionStorage.getItem('hc_conversation')))){
                let created = new Date();
                return await this.createConversation({
                    participantOne : response.data.session_id,
                    created : created.getDate().pad(2) + '-' + (created.getMonth() + 1).pad(2) + '-' + created.getFullYear() + ' ' + created.getHours().pad(2) + ':' + (created.getMinutes()).pad(2) + ':' + created.getSeconds().pad(2)
                }, {
                    token : (typeof options != 'undefined' && typeof options.token != 'undefined') ? options.token : response.data.accesstoken
                });
            }
            return response;
        } return false;
    }

    async createConversation(data, options){
        let endpoint = 'conversations';
        let headers = {
            'Content-Type': 'application/json'
        };
        if(typeof options.token != 'undefined'){
            headers.Authorization = options.token;
        }
        let response = await fetch(window.HelloChat.config.api + endpoint, {
            method : 'POST',
            headers: headers,
            body: JSON.stringify(data)
        }).then(function(response){ return response.json(); })
        .then(function(response){ return response; })
        .catch(function(error){ console.log(error); });

        if(response.statusCode == 201){
            this.storeConversation(response.data);
            this.initiated = true;
            return response;
        }
        return false;        
    }


    storeUser(data){
        if(typeof data.user != 'undefined'){
            localStorage.setItem('hc_user', data.user);      
        }
        if(typeof data.username != 'undefined'){
            localStorage.setItem('hc_user', data.username);      
        }
        if(typeof data.email != 'undefined'){
            localStorage.setItem('hc_user', data.email);      
        }
    }

    storeSession(data){
        sessionStorage.setItem('hc_id', data.user_id);
        sessionStorage.setItem('hc_token', data.accesstoken);
        sessionStorage.setItem('hc_session', data.session_id);
        sessionStorage.setItem('hc_access_expire', Math.floor(Date.now() / 1000) + data.access_token_expires_in);
        sessionStorage.setItem('hc_refresh_expire', Math.floor(Date.now() / 1000) + data.refresh_token_expires_in);
    }

    storeConversation(data){
        sessionStorage.setItem('hc_conversation', data.conversation[0].id);
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

    async loginPing(){
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
                if(session === true && !(session = await this.loginPing())){ return false; }
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

        if(this.makeConversationOnSession !== false){
            let conversation = sessionStorage.getItem('hc_conversation');
            if(success ===  true && !conversation){
                sessionId = sessionStorage.getItem('hc_session');
                accessToken = sessionStorage.getItem('hc_token');
                
                let created = new Date();
                this.createConversation({
                    participantOne : sessionId,
                    created : created.getDate().pad(2) + '-' + (created.getMonth() + 1).pad(2) + '-' + created.getFullYear() + ' ' + created.getHours().pad(2) + ':' + (created.getMinutes()).pad(2) + ':' + created.getSeconds().pad(2)
                }, {
                    token : accessToken
                });
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