export class ArchiveScreen {
    
    context;
    parent;
    elements;

    filters;

    userWait;
    userTimer;

    constructor(context, parent){
        this.context = context;
        this.parent = parent;
        this.elements = {};

        this.filters = {};

        this.userWait = 300000;
    }

    build(){
        this.destroy();
        
        let archive = document.createElement('div');
        archive.classList.add('hc__archive');
        archive.classList.add('main__screen');

        let title = document.createElement('h2');
        title.classList.add('hc__user__title');
        title.innerHTML = window.HelloChat.localization.archive.archiveTitle;

        this.elements.list = document.createElement('ul');
        this.elements.list.classList.add('hc__user__container');

        archive.appendChild(title);
        archive.appendChild(this.elements.list);

        this.parent.appendChild(archive);

        let settings = this.parent.querySelector('.hc__edit__settings');
        if(settings){
            let refresh = document.createElement('button');
            refresh.classList.add('hc__refresh__data');
            refresh.innerHTML = window.HelloChat.localization.archive.archiveRefresh;
            refresh.addEventListener('click', this.refreshArchive.bind(this), true);
            settings.parentNode.insertBefore(refresh, settings);
            
            let users = document.createElement('button');
            users.classList.add('hc__users');
            users.innerHTML = window.HelloChat.localization.archive.archiveUsers;
            users.addEventListener('click', this.toUsers.bind(this), true);
            settings.parentNode.parentNode.appendChild(users);   

            this.elements.select = document.createElement('select');
            this.elements.select.classList.add('hc__archive__filter');
            this.elements.select.addEventListener('change', function(){ this.applyFilter(); }.bind(this), true);
            settings.parentNode.parentNode.appendChild(this.elements.select);
        }

        
    }

    refreshArchive(){
        this.context.startPhase('archive');
    }

    toUsers(){
        this.context.startPhase('start');
    }

    async fetchClosedConversations(){
        let accessToken = sessionStorage.getItem('hc_token');
        if(!accessToken){
            this.context.startPhase('connect');
            return;
        }

        let endpoint = 'conversations/closed';
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
            this.elements.list.innerHTML = '';
            if(this.elements.list.length > 0){
                this.elements.list.classList.remove('empty');
            } else {
                this.elements.list.classList.add('empty');
            }
            for(let i = 0; i < response.data.conversations.length; i++){
                this.elements.list.appendChild(this.createConversation(response.data.conversations[i]));
                this.filters[response.data.conversations[i].participantTwo.userId] = response.data.conversations[i].participantTwo.name;
            }
        }
        this.createFilter();
        this.userTimer = setTimeout(this.fetchClosedConversations.bind(this), this.userWait);
    }

    createFilter(){
        this.elements.select.innerHTML = '<option value="">'+window.HelloChat.localization.archive.archiveFilter+'</option>';
        let option;
        let counter = 0;
        for(let index in this.filters){
            if(this.filters.hasOwnProperty(index)){
                option = document.createElement('option');
                option.value = index;
                option.innerHTML = this.filters[index];
                this.elements.select.appendChild(option);
                counter++;
            }
        }

        if(counter > 0){
            this.elements.select.classList.add('active');
        } else {
            this.elements.select.classList.remove('active');
        }
        this.applyFilter();
    }

    applyFilter(){
        let selected = this.elements.select.value;
        let items = document.querySelectorAll('li.hc__user__item');
        for(let i = 0; i < items.length; i++){
            if(items[i].dataset.filter == selected || selected == ''){
                items[i].style.display = '';
            } else {
                items[i].style.display = 'none';
            }
        }
    }

    createConversation(conversation){
        let item = document.createElement('li');
        item.classList.add('hc__user__item');
        item.setAttribute('data-session', conversation.id);
        item.setAttribute('data-filter', conversation.participantTwo.userId);
        item.addEventListener('click', function(element, e){ this.toConversation.call(this, e, element) }.bind(this, item), true);

        let h2 = document.createElement('h2');
        h2.setAttribute('data-session', conversation.id);
        h2.innerHTML = conversation.participantTwo.name + '<span></span>' + conversation.participantOne.name;

        let date = document.createElement('p');
        date.innerHTML = conversation.created;

        let button = document.createElement('button');
        button.classList.add('hc__user__claim');
        button.innerHTML = window.HelloChat.localization.archive.archiveButton;
        
        item.appendChild(h2);
        item.appendChild(date);
        item.appendChild(button);

        return item;
    }
    
    toConversation(event, element){
        this.context.setTransitionData({
            id : element.dataset.session
        });
        this.context.startPhase('detail');
    }

    destroy(){
        let view;
       
        view = this.parent.querySelector('.hc__archive');
        if(view){ view.remove(); }

        view = this.parent.querySelector('button.hc__users');
        if(view){ view.remove(); }

        view = this.parent.querySelector('.hc__refresh__data');
        if(view){ view.remove(); }

        if(this.elements.select){ this.elements.select.remove(); }
        
        if (this.userTimer) { clearTimeout(this.userTimer); }
    }

    async run(){       
       this.build();
       this.fetchClosedConversations();
    }
}