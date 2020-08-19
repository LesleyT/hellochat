export class UserScreen {
    
    context;
    parent;
    elements;

    

    userWait;
    userTimer;

    constructor(context, parent){
        this.context = context;
        this.parent = parent;
        this.elements = {};

        this.userWait = 30000;
    }

    build(){
        this.destroy();

        let user = document.createElement('div');
        user.classList.add('hc__users');
        user.classList.add('main__screen');

        let title = document.createElement('h2');
        title.classList.add('hc__user__title');
        
        title.innerHTML = window.HelloChat.localization.users.userTitle;

        let dispaly = document.createElement('div');
        dispaly.classList.add('hc__switch__container'); 

        let label = document.createElement('label');
        label.classList.add('hc__display__label');      
        label.setAttribute('data-active', window.HelloChat.localization.users.userActive);
        label.setAttribute('data-inactive', window.HelloChat.localization.users.userInactive);

        this.elements.checkbox = document.createElement('input');
        this.elements.checkbox.classList.add('hc__display_switch');
        this.elements.checkbox.type = 'checkbox';
        this.elements.checkbox.addEventListener('change', function(){ 
            this.filterChats(); 
            sessionStorage.setItem('hc_user_panel', (this.elements.checkbox.checked == true) ? 'claimed' : 'open');
        }.bind(this), true);
        
        if(sessionStorage.getItem('hc_user_panel') == 'claimed'){
            this.elements.checkbox.checked = true;
        }
        
        this.elements.list = document.createElement('ul');
        this.elements.list.classList.add('hc__user__container');

        dispaly.appendChild(this.elements.checkbox);
        dispaly.appendChild(label);

        user.appendChild(title);
        user.appendChild(dispaly);
        user.appendChild(this.elements.list);       

        this.parent.appendChild(user);
 
        let menu = this.parent.querySelector('.hc__settings__menu');
        let settings = this.parent.querySelector('.hc__edit__settings');
        if(menu){
            let refresh = document.createElement('button');
            refresh.classList.add('hc__refresh__data');
            refresh.innerHTML = window.HelloChat.localization.archive.archiveRefresh;
            refresh.addEventListener('click', this.refreshUser.bind(this), true);
            settings.parentNode.insertBefore(refresh, settings);


            let archive = document.createElement('button');
            archive.classList.add('hc__archive');
            archive.innerHTML = window.HelloChat.localization.detail.detailArchive;
            archive.addEventListener('click', this.toArchive.bind(this), true);
            settings.parentNode.parentNode.appendChild(archive);

        }
    }

    refreshUser(){
        this.context.startPhase('start');
    }

    toArchive(){
        this.context.startPhase('archive');
    }

    destroy(){
        let view;
        
        view = this.parent.querySelector('.hc__users');
        if(view){ view.remove(); }
        
        view = this.parent.querySelector('button.hc__archive');
        if(view){ view.remove(); }

        view = this.parent.querySelector('button.hc__refresh__data');
        if(view){ view.remove(); }

        
        
        if (this.userTimer) { clearTimeout(this.userTimer); }
    }

    async run(){       
        this.build();
        let user;
        if((user = await this.context.connection.loginPing())){
            this.context.setTransitionData(user.data.users[0]);
            this.fetchOpenConversations();
        } else {
            this.context.startPhase('connect');
        }
    }



    async fetchOpenConversations(){
        let accessToken = sessionStorage.getItem('hc_token');
        if(!accessToken){
            this.context.startPhase('connect');
            return;
        }

        let endpoint = 'conversations/open';
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

        if(this.context.maybeReconnect(response)){ return; };
        
        if(response.statusCode === 200){
            let latest = -1;
            for(let i = 0; i < response.data.conversations.length; i++){
                this.elements.list.appendChild(this.createConversation(response.data.conversations[i]));
                latest = response.data.conversations[i].id;
            }
            if(latest > 0){
                sessionStorage.setItem('hc_latest_chat', latest);
            }
            this.filterChats();
        }

        this.userTimer = setTimeout(this.checkNewConversations.bind(this), this.userWait);
    }

    async checkNewConversations(){
        let accessToken = sessionStorage.getItem('hc_token');
        let chat = sessionStorage.getItem('hc_latest_chat');
        if(!accessToken){
            this.context.startPhase('connect');
            return;
        }

        let endpoint = (!chat) ? 'conversations/open' : 'conversations/latest/'+chat;
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
            let latest = -1;
            for(let i = 0; i < response.data.conversations.length; i++){
                this.elements.list.appendChild(this.createConversation(response.data.conversations[i]));
                latest = response.data.conversations[i].id;
            }
            if(latest > 0){
                sessionStorage.setItem('hc_latest_chat', latest);
            }
            this.filterChats();
        }

        this.userTimer = setTimeout(this.checkNewConversations.bind(this), this.userWait);
    }


    filterChats(){
        let showClaimed = this.elements.checkbox.checked === true;
        let items = this.elements.list.querySelectorAll('.hc__user__item');
        if(items){
            for(let i = 0; i < items.length; i++){
                items[i].style.display = ((!showClaimed &&  items[i].dataset.claimed == 'false') || (showClaimed &&  items[i].dataset.claimed == 'true')) ? '' : 'none';
            }
        }
    }


    createConversation(conversation){
        let conversationOwner = (conversation.participantTwo.id) ? true : false;

        let item = document.createElement('li');
        item.classList.add('hc__user__item');
        item.setAttribute('data-session', conversation.id);
        item.setAttribute('data-claimed', conversationOwner);

        let h2 = document.createElement('h2');
        h2.setAttribute('data-session', conversation.id);
        h2.innerHTML = conversation.participantOne.name;

        let date = document.createElement('p');
        date.innerHTML = conversation.created;

        let button = document.createElement('button');
        button.classList.add('hc__user__claim');
        button.innerHTML = window.HelloChat.localization.users[conversationOwner ? 'userJoin' : 'userButton'];
        
        item.appendChild(h2);
        item.appendChild(date);
        item.appendChild(button);

        if(conversationOwner){  
            item.addEventListener('click', function(element, e){ 
                this.context.connection.getAuthorization(function(element, response){
                    this.joinConversation.call(this, element, response) 
                }.bind(this, element));
            }.bind(this, item), true);

        } else {
            item.addEventListener('click', function(element, e){ 
                this.context.connection.getAuthorization(function(element, response){
                    this.claimConversation.call(this, element, response) 
                }.bind(this, element));
            }.bind(this, item), true);
        }

        return item;
    }

    async claimConversation(element, response){
        if(response.statusCode !== 200){
            this.context.startPhase('connect');
            return;
        }
        
        let conversationId = element.dataset.session;
        let sessionId = sessionStorage.getItem('hc_session');
        let accessToken = sessionStorage.getItem('hc_token');
        if(!accessToken || !sessionId){
            this.context.startPhase('connect');
            return;
        }
        
        if(!conversationId){
            this.context.startPhase('start');
            return;
        }

        let endpoint = 'conversations/'+conversationId;
        let headers = {
            'Content-Type': 'application/json',
            'Authorization' : accessToken
        };

        response = await fetch(window.HelloChat.config.api + endpoint, {
            method : 'PATCH',
            headers: headers,
            body: JSON.stringify({
                participantTwo : sessionId
            })
        }).then(function(response){ return response.json(); })
        .then(function(response){ return response; })
        .catch(function(error){ console.log(error); });

        console.log(response);

        if(response.statusCode === 200){
            sessionStorage.setItem('hc_conversation', conversationId);
            if(!sessionStorage.getItem('hc_conversation')){
                //error handeling
            } else {
                this.context.startPhase('pending');
            }
        }
    }

    async joinConversation(element, response){
        if(response.statusCode !== 200){
            this.context.startPhase('connect');
            return;
        }
        
        let conversationId = element.dataset.session;
        let sessionId = sessionStorage.getItem('hc_session');
        let accessToken = sessionStorage.getItem('hc_token');
        if(!accessToken || !sessionId){
            this.context.startPhase('connect');
            return;
        }
        
        if(!conversationId){
            this.context.startPhase('start');
            return;
        }

        let endpoint = 'conversations/'+conversationId;
        let headers = {
            'Content-Type': 'application/json',
            'Authorization' : accessToken
        };

        response = await fetch(window.HelloChat.config.api + endpoint, {
            method : 'GET',
            headers: headers
        }).then(function(response){ return response.json(); })
        .then(function(response){ return response; })
        .catch(function(error){ console.log(error); });

        if(response.statusCode === 200){
            let user = await this.context.connection.loginPing();
            if(user.statusCode !== 200){ 
                // error handeling
            }
            
            if(sessionId == response.data.conversations[0].participantTwo.id || user.data.users[0].id == response.data.conversations[0].participantTwo.user){
                sessionStorage.setItem('hc_conversation', conversationId);
                if(!sessionStorage.getItem('hc_conversation')){
                    //error handeling
                    this.context.setTransitionData(null);
                } else {
                    this.context.startPhase('pending');
                }
            }
        } else {
            this.context.setTransitionData(null);
        }
    }
}