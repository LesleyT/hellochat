import { NotificationManager } from '../../classes/NotificationManager.js';
import { Textarea } from '../../utility/Textarea.js';
import { Message } from '../../classes/Message.js';

export class ChatScreen {

    parent;
    context;
    elements;

    messages;
    timer;

    wait;

    defaultWait;
    speedWait;
    chatWait;
    chatTimer;
    waitLimiter;
    currentWait;

    conversation;

    loaded;

    notifier;

    isUserTyping;

    unload;

    constructor(context, parent){
        this.context = context;
        this.parent = parent;
        this.elements = {};

        this.messages = [];

        
        this.defaultWait = 6000;
        this.speedWait = 3000;
        this.chatWait = this.defaultWait;
        this.waitLimiter = 5;
        this.currentWait = 0;
        
        this.wait = 3000;

        this.notifier = new NotificationManager(this.context);

        this.unload = function(event) {
            event.preventDefault();
            event.returnValue = '';
            this.setTyping(false);
        }.bind(this);
    }

    build(){
        this.destroy();
        
        this.elements.messages = document.createElement('div');
        this.elements.messages.classList.add('hc__messages');
        this.elements.messages.classList.add('main__screen');
        this.elements.messages.classList.add('client');


            let head  = document.createElement('div');
            head.classList.add('hc__head');
            head.innerHTML = '<span class="owner">Powered by: <a target="_blank" href="https://hellogoodday.nl">Goodday</a></span>';

        this.elements.form = document.createElement('form');
        this.elements.form.classList.add('hc__form--message');
        this.elements.form.method = 'POST';
        this.elements.form.addEventListener('submit', function(event){ this.createMessage(event, true); }.bind(this), true );

            let inputContainer = document.createElement('div');
            inputContainer.classList.add('hc__form__input');

                this.elements.input = document.createElement('textarea');
                this.elements.input.name = 'message';
                this.elements.input.placeholder = window.HelloChat.localization.chat.chatMessage;
                this.elements.input.addEventListener('input', function(){
                    Textarea.fitToText.call(this.elements.input);
                    if(typeof this.elements.input.parentNode.style.height != 'undefined' && this.elements.input.parentNode.style.height != ''){
                        this.elements.messages.style.marginBottom = 'calc(min(90px, ' + this.elements.input.parentNode.style.height + ') - 1.8rem + 60px)';
                    } else { this.elements.messages.style.marginBottom = ''; }
                }.bind(this), true);
                this.elements.input.addEventListener('input', function(){ this.setTyping(true); }.bind(this), true);
                this.elements.input.onblur = function () { 
                    this.setTyping(false);
                }.bind(this); 

            inputContainer.appendChild(this.elements.input);

            let submit = document.createElement('button');
            submit.type = 'submit';
            submit.classList.add('hc__form__submit');
            
        this.elements.messages.appendChild(head);

        this.elements.form.appendChild(inputContainer);
        this.elements.form.appendChild(submit);

        this.parent.appendChild(this.elements.messages);
        this.parent.appendChild(this.elements.form);

        setTimeout(function(){ this.elements.input.focus(); }.bind(this), 0);
    }

    chatUpdateChecker(){
        if(this.chatTimer){
            clearTimeout(this.chatTimer);
            if(!sessionStorage.getItem('hc_latest')){
                this.loadAllPreviousMessages();
            } else {
                this.loadLatestMessages();
            }
        }
    }
    
    async createMessage(event, postToServer){
        event.preventDefault();
        let created = new Date();
        let message = new Message({
            content : this.elements.input.value,
            created : created.getDate().pad(2) + '-' + (created.getMonth() + 1).pad(2) + '-' + created.getFullYear() + ' ' + created.getHours().pad(2) + ':' + (created.getMinutes()).pad(2) + ':' + created.getSeconds().pad(2),
            owner : this.context.getTransitionData()
        });
        message.display(this.elements.messages, true);

        if(postToServer === true){
            this.sendMessage(message);
        }
    }

    async sendMessage(message){
        let response = await message.send();
        this.messages.push(message);

        if(response.statusCode === 201){
            this.isUserTyping = false;
            this.loaded[response.data.messages[0].id] = true;
            this.elements.input.value = '';
            this.elements.input.parentNode.style.height = '';
            this.elements.messages.style.marginBottom = '';
            this.elements.input.focus();
            this.setTyping(false);
        } else {
           if(this.maybeCloseChat(response)){ return false; }
            //error handle
        }
        
    }

    maybeCloseChat(response){
        if(typeof response.data != 'undefined' && response.data.done === true){
            this.context.startPhase('end');
            this.context.connection.clearData();
            return true;
        } return false;
    }

    async loadLatestMessages(){
        let latest = sessionStorage.getItem('hc_latest');
        let conversation = sessionStorage.getItem('hc_conversation');
        let accessToken = sessionStorage.getItem('hc_token');
        if(!latest || !accessToken || !conversation){
            //try to authenticate token, user or conversation
        }

        let endpoint = 'messages/latest/'+conversation+'/'+latest;
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

        if(response.statusCode === 200){
            if(this.maybeCloseChat(response)){ return false; }
            this.isTyping(response);
            if(typeof response.data.messages == 'undefined'){
                this.chatTimer = setTimeout(this.chatUpdateChecker.bind(this), this.chatWait);
                return true;
            }
            
            let user = this.context.getTransitionData().email;
            let latest = -1;
            let contact;
            for(let i = 0; i < response.data.messages.length; i++){
                if(typeof this.loaded[response.data.messages[i].id] == 'undefined'){
                    let message = new Message({
                        content : response.data.messages[i].content,
                        created : response.data.messages[i].created,
                        owner : {
                            name : response.data.messages[i].name,
                            email : response.data.messages[i].email
                        }
                    });
                    message.display(this.elements.messages, (user == response.data.messages[i].email));
                    latest = i;
                    this.loaded[response.data.messages[i].id] = true;
                    
                    if((user != response.data.messages[i].email)){
                        contact = response.data.messages[i].name;
                    }
                }
            }
            this.context.setContact(contact);
            if(latest >= 0){
                sessionStorage.setItem('hc_latest', response.data.messages[latest].id);
                this.notifier.send(window.HelloChat.localization.chat.chatNotification, response.data.messages[latest].content, window.HelloChat.config.notificationIcon);
            }
        }
        this.chatTimer = setTimeout(this.chatUpdateChecker.bind(this), this.chatWait);
    }

    async loadAllPreviousMessages(){        
        let conversation = sessionStorage.getItem('hc_conversation');
        let accessToken = sessionStorage.getItem('hc_token');
        if(!accessToken || !conversation){
            //try to authenticate token, user or conversation
        }
        
        let endpoint = 'messages/all/'+conversation;
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

        if(response.statusCode === 404){
            this.context.startPhase('start'); return;
        }

        if(response.statusCode === 200){
            if(this.maybeCloseChat(response)){ return false; }
            this.isTyping(response);
            if(typeof response.data.messages == 'undefined'){
                this.chatTimer = setTimeout(this.chatUpdateChecker.bind(this), this.chatWait);
                return true;
            }

            let user = this.context.getTransitionData().email;
            let latest = -1;
            let contact;
            for(let i = 0; i < response.data.messages.length; i++){
                if(typeof this.loaded[response.data.messages[i].id] == 'undefined'){
                    let message = new Message({
                        content : response.data.messages[i].content,
                        created : response.data.messages[i].created,
                        owner : {
                            name : response.data.messages[i].name,
                            email : response.data.messages[i].email
                        }
                    });
                    message.display(this.elements.messages, (user == response.data.messages[i].email));
                    latest = i;
                    this.loaded[response.data.messages[i].id] = true;
                    
                    if((user != response.data.messages[i].email)){
                        contact = response.data.messages[i].name;
                    }
                }
            }
            this.context.setContact(contact);
            if(latest >= 0){
                sessionStorage.setItem('hc_latest', response.data.messages[latest].id);
                this.notifier.send(window.HelloChat.localization.chat.chatNotification, response.data.messages[latest].content, window.HelloChat.config.notificationIcon);
            }
        }
        this.chatTimer = setTimeout(this.chatUpdateChecker.bind(this), this.chatWait);
    }

    async setTyping(value){
        if(this.isUserTyping !== true || value != true){
            let conversation = sessionStorage.getItem('hc_conversation');
            let accessToken = sessionStorage.getItem('hc_token');
            if(!accessToken || !conversation){
                //try to authenticate token, user or conversation
            }
    
            let endpoint = 'conversations/'+conversation;
            let headers = {
                'Content-Type': 'application/json',
                'Authorization' : accessToken
            };
    
            this.isUserTyping = (value) ? true : false;
            let response = await fetch(window.HelloChat.config.api + endpoint, {
                method : 'PATCH',
                headers: headers,
                body : JSON.stringify({ 'typing' : (value) ? 'Y' : 'N', 'participant' : 'participantOne' })
            }).then(function(response){ return response.json(); })
            .then(function(response){ return response; })
            .catch(function(error){ console.log(error); });

            if(response.statusCode === 200){

            } else {
                this.isUserTyping = (value) ? false : true;
            }
        }
    }

    isTyping(response){
        if(typeof response.data != 'undefined' && response.data.isTyping === true){
            if(this.currentWait < this.waitLimiter){
                this.chatWait = this.speedWait;
                this.elements.form.classList.add('is__typing');

                this.currentWait++;
            } else if(this.currentWait == this.waitLimiter) {
                this.currentWait++;
                this.chatWait = this.defaultWait;
            } else {
                this.currentWait = 0;
                this.chatWait = this.defaultWait;
            }
            return true;
        }
       
        this.chatWait = this.defaultWait;
        this.elements.form.classList.remove('is__typing');

        return false;
    }

    destroy(){
        window.removeEventListener('beforeunload', this.unload, true);
        if(this.parent){
            let view;
            
            view = this.parent.querySelector('.hc__messages');
            if(view){ view.remove(); }

            view = this.parent.querySelector('.hc__form--message');
            if(view){ view.remove(); }
        }
        this.loaded = {};
        this.stopTimer();
    }

    stopTimer(){
        if (this.timer) {
            clearTimeout(this.timer);
        }
    }

    async run(){ 
        this.notifier.askConsent();      
        this.build();
        this.loaded = {};
        if(await this.connect()){
            window.addEventListener('beforeunload', this.unload, true);
            setTimeout(function(){ this.automatedMessage(); }.bind(this), 1000);
            setTimeout(function(){
                this.loadAllPreviousMessages();
            }.bind(this), 2000);
        } else {
            this.context.clearData();
            this.context.startPhase('start');
            return;
        }
    }

    automatedMessage(){
        let created = new Date();
        let message = new Message({
            content : window.HelloChat.localization.initial.initialMessage,
            created : created.getDate().pad(2) + '-' + (created.getMonth() + 1).pad(2) + '-' + created.getFullYear() + ' ' + created.getHours().pad(2) + ':' + (created.getMinutes()).pad(2) + ':' + created.getSeconds().pad(2),
            owner : {
                name : window.HelloChat.localization.initial.initialName,
                email : window.HelloChat.localization.initial.initialEmail
            }
        });

        message.display(this.elements.messages, true);
    }


    async connect(){
        let data = this.context.getTransitionData();
        let user = await this.context.connection.maybeStartConnection();
     
        if(!user){
            let session = await this.attemptLogin(data.email);
            if(!session){
                this.context.connection.clearData();
                let response = await this.context.connection.createUser({
                    fullname : data.name,
                    username : data.email
                });
                if(response === false || response.statusCode === 409){
                    return false;       
                }
            } else {
                data.email = session.user;
                this.context.connection.storeUser(data);
                let created = new Date();

                let response = await this.context.connection.createConversation({
                    participantOne : session.session_id,
                    created : created.getDate().pad(2) + '-' + (created.getMonth() + 1).pad(2) + '-' + created.getFullYear() + ' ' + created.getHours().pad(2) + ':' + (created.getMinutes()).pad(2) + ':' + created.getSeconds().pad(2)
                }, {
                    token : session.accesstoken
                });
                
                if(response.statusCode == 201){
                    this.context.connection.storeConversation(response.data);
                } else {
                    //error handle
                    return false;
                }
            }
        }
        return true;
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
            return response.data;
        } return false;
    }

}
