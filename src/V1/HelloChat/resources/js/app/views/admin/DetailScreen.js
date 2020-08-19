import { Message } from '../../classes/Message.js';

export class DetailScreen {
    
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
       
        let detail = document.createElement('div');
        detail.classList.add('hc__detail');
        detail.classList.add('main__screen');

        this.elements.title = document.createElement('h2');
        this.elements.title.classList.add('hc__user__title')

        this.elements.date = document.createElement('span');
        this.elements.date.classList.add('date')

        let action = document.createElement('div');
        action.classList.add('hc__detail__actions');

        this.elements.mail = document.createElement('a');
        this.elements.mail.classList.add('button');
        this.elements.mail.innerHTML = window.HelloChat.localization.detail.detailMail;

        let title = document.createElement('h2');       
        title.classList.add('hc__detail__title');
        title.innerHTML = window.HelloChat.localization.detail.detailConversation;

        this.elements.messages = document.createElement('div');
        this.elements.messages.classList.add('hc__messages');
    
        detail.appendChild(this.elements.title);
        detail.appendChild(this.elements.date);

        action.appendChild(this.elements.mail);
        detail.appendChild(action);
        
        detail.appendChild(title);

        detail.appendChild(this.elements.messages);

        this.parent.appendChild(detail);

        let menu = this.parent.querySelector('.hc__settings__menu');
        if(menu){
            let remove = document.createElement('button');
            remove.classList.add('hc__remove__data');
            remove.innerHTML = window.HelloChat.localization.detail.detailRemove;
            remove.addEventListener('click', function(){ this.createConfirmation(function(){ this.deleteConversation(); }.bind(this));  }.bind(this), true);
            menu.appendChild(remove);

            let archive = document.createElement('button');
            archive.classList.add('hc__archive');
            archive.innerHTML = window.HelloChat.localization.detail.detailArchive;
            archive.addEventListener('click', this.toArchive.bind(this), true);
            menu.parentNode.appendChild(archive);

        }
    }

    createConfirmation(callback){
        if(typeof callback != 'function'){ return; }

        
        let settings = this.parent.querySelector('.hc__settings');
        if(settings){
            settings.classList.remove('active');
        }
        
        let container = document.createElement('div');
        container.classList.add('hc__confirmation');

        let inner = document.createElement('div');
        inner.classList.add('hc__confirmation__inner');

        let p = document.createElement('p');
        p.classList.add('hc__confirmation_content');
        p.innerHTML = window.HelloChat.localization.confirmation.confirmationMessage;

        let yes = document.createElement('button');
        yes.classList.add('hc__confirmation__true');
        yes.innerHTML = window.HelloChat.localization.confirmation.confirmationYes
        yes.addEventListener('click', function(callback, event){
            event.target.parentNode.parentNode.remove();
            callback();
        }.bind(this, callback));

        let no = document.createElement('button');
        no.classList.add('hc__confirmation__false');
        no.innerHTML = window.HelloChat.localization.confirmation.confirmationNo;
        no.addEventListener('click', function(callback, event){
            event.target.parentNode.parentNode.remove();
        }.bind(this, callback));

        inner.appendChild(p);
        inner.appendChild(yes);
        inner.appendChild(no);
        container.appendChild(inner);
        
        this.parent.appendChild(container);
    }

    createMessage(data){
        let message = new Message({
            content : data.content,
            created : data.created,
            owner : {
                name : data.name,
                email : data.email
            }
        });
        message.display(this.elements.messages, data.isOwner, false);
    }

    toArchive(){
        this.context.startPhase('archive'); 
    }

    async fetchConversation(){
        let accessToken = sessionStorage.getItem('hc_token');
        let chat = sessionStorage.getItem('hc_current') ;
        if(!accessToken){
            this.context.startPhase('connect');
            return;
        }
        if(chat < 1){
            this.context.setTransitionData();
            this.context.startPhase('archive');
            return;
        }

        let endpoint = 'conversations/'+chat;
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

        if(response.statusCode !== 200){
            this.context.setTransitionData();
            this.context.startPhase('archive');
            return;
        } else if(response.statusCode === 200){
            this.elements.title.innerHTML = response.data.conversations[0].participantOne.name;
            this.elements.date.innerHTML = response.data.conversations[0].created;
            this.elements.mail.href = 'mailto:' + response.data.conversations[0].participantOne.name;
            for(let i = 0; i < response.data.conversations[0].messages.data.messages.length; i++){
                let data = response.data.conversations[0].messages.data.messages[i];
                data.isOwner = (response.data.conversations[0].participantTwo.email == data.email);
                data.name = response.data.conversations[0][(data.isOwner) ? 'participantTwo' : 'participantOne'].name; 
                this.createMessage(data);
            }
        }
    }

    async deleteConversation(){
        let accessToken = sessionStorage.getItem('hc_token');
        let conversationId = sessionStorage.getItem('hc_current');
        if(!accessToken){
            this.context.startPhase('connect');
            return;
        }
        
        let endpoint = 'conversations/'+conversationId;
        let headers = {
            'Content-Type': 'application/json',
            'Authorization' : accessToken
        };

        let response = await fetch(window.HelloChat.config.api + endpoint, {
            method : 'DELETE',
            headers: headers
        }).then(function(response){ return response.json(); })
        .then(function(response){ return response; })
        .catch(function(error){ console.log(error); });

        if(response.statusCode === 200){
            this.toArchive();
        }
    }

    destroy(){
        if(this.parent){
            let view = this.parent.querySelector('.hc__detail');
            if(view){ view.remove(); }
        }

        let settings = this.parent.querySelector('.hc__settings');
        if(settings){
            settings.classList.remove('active');
        }

        let remove = this.parent.querySelector('.hc__remove__data');
        if(remove){
            remove.remove();
        }

        let archive = this.parent.querySelector('.hc__archive');
        if(archive){
            archive.remove();
        }

        sessionStorage.removeItem('hc_current'); 
    }

    async run(){      
        let data = this.context.getTransitionData();
        if(typeof data == 'undefined' || data == null || typeof data.id == 'undefined'){
            this.context.startPhase('archive');
            return;
        }
        this.build();
        sessionStorage.setItem('hc_current', data.id); 

        this.fetchConversation();
    }
}