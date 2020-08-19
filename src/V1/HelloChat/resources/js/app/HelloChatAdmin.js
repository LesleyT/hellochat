import {ConnectScreen} from "./views/admin/ConnectScreen.js";
import {ArchiveScreen} from "./views/admin/ArchiveScreen.js";
import {ChatScreen} from "./views/admin/ChatScreen.js";
import {SettingsScreen} from "./views/admin/SettingsScreen.js";
import {DetailScreen} from "./views/admin/DetailScreen.js";
import {UserScreen} from "./views/admin/UserScreen.js";
import { Connection } from './classes/Connection.js';

export class HelloChat {

    current;
    phases;
    screen;

    connection;

    elements;
    
    excludeScreeens;

    isActive;

    static instance;

    static getInstance(){
        if(typeof HelloChat.instance == 'undefined' || HelloChat.instance === null){
            HelloChat.instance = new HelloChat();
        }
        return HelloChat.instance;
    }

    
    constructor(){  
        this.elements = {};

        this.build();

        this.excludeScreeens = ['pending', 'settings'];

        this.phases = {
            connect : new ConnectScreen(this, this.screen),
            start : new UserScreen(this, this.screen),
            pending : new ChatScreen(this, this.screen),
            archive : new ArchiveScreen(this, this.screen),
            detail : new DetailScreen(this, this.screen),
            settings : new SettingsScreen(this, this.screen),
        };

        this.connection = new Connection();

        this.isActive = true;

        window.onfocus = function () { 
            this.isActive = true; 
        }.bind(this); 

        window.onblur = function () { 
            this.isActive = false; 
        }.bind(this); 
    }

    build(){
        this.screen = document.createElement('main');
        this.screen.classList.add('hc__container');

        let header = document.createElement('div');
        header.classList.add('hc__header');

            this.elements.settings = document.createElement('button');
            this.elements.settings.classList.add('hc__settings');
            this.elements.settings.addEventListener('click', this.toggleMenu, true);

                let image = document.createElement('img');
                image.src = window.HelloChat.config.settingsIcon;

            this.elements.menu = document.createElement('div');
            this.elements.menu.classList.add('hc__settings__menu');

            
            let activateContainer = document.createElement('div');
            activateContainer.classList.add('hc__activate__container');

            let activateLabel = document.createElement('label');
            activateLabel.classList.add('hc__activate__label');
            activateLabel.innerHTML = window.HelloChat.localization.system.activateLabel;

            let activate = document.createElement('input');
            activate.type = 'checkbox';
            activate.classList.add('hc__activate__chat');
            if(window.HelloChat.active === true){ activate.checked = true; }
            activate.addEventListener('change', function(event){ 
                let success = this.activateChat(activate.checked);

                //error handle
            }.bind(this, activate), true);

            activateContainer.appendChild(activate);
            activateContainer.appendChild(activateLabel);

            let button = document.createElement('button');
            button.classList.add('hc__edit__settings');
            button.innerHTML = window.HelloChat.localization.settings.settingsMenu;
            button.addEventListener('click', function(event){ 
                this.startPhase('settings'); 
            }.bind(this), true);
            
        this.elements.menu.appendChild(activateContainer);
        this.elements.menu.appendChild(button);
        this.elements.settings.appendChild(image);
        
        header.appendChild(this.elements.settings);
        header.appendChild(this.elements.menu);
        
        this.screen.appendChild(header);
        document.body.appendChild(this.screen);


        if(!window.HelloChat.active){
            this.createInactive();
        }
    }

    async startPhase(phase){
        document.body.classList.add('transition');
        this.endPhase();

        let result = (phase === 'connect') ? true : await this.connection.tryReconnect();
        if(result){
            if(typeof this.phases[phase] != 'undefined'){
                if(this.excludeScreeens.indexOf(phase) == -1){
                    sessionStorage.setItem('hc_current_screen', JSON.stringify({
                        screen : phase,
                        data : this.getTransitionData()
                    }));
                }
                this.current = phase;
                this.phases[this.current].run() 
            }
        } else {
            this.startPhase('connect');
        }
        this.elements.settings.classList.remove('active');

        setTimeout(function(){ document.body.classList.remove('transition'); }.bind(this),  100);
    }

    endPhase(){
        if(typeof this.current != 'undefined'){
            this.phases[this.current].destroy();   
        }
    }

    getTransitionData(){
        return this.transitionData;
    }

    setTransitionData(data){
        this.transitionData = data;
    }

    toggleMenu(){
        this.classList.toggle('active');
    }

    maybeReconnect(response){
        if(response.statusCode === 401){
            this.connection.clearData();
            this.startPhase('connect');
            return true;
        } return false;
    }

    async activateChat(active){
        let accessToken = sessionStorage.getItem('hc_token');
        if(!accessToken){
            return false;
        }

        let endpoint = 'settings';
        let headers = {
            'Content-Type': 'application/json',
            'Authorization' : accessToken
        };

        let response = await fetch(window.HelloChat.config.api + endpoint, {
            method : 'PATCH',
            headers: headers,
            body : JSON.stringify({
                'system' : {
                    'active' : active
                }
            })
        }).then(function(response){ return response.json(); })
        .then(function(response){ return response; })
        .catch(function(error){ console.log(error); });
        console.log(active == false, this.elements.inactive);
        if(response.statusCode === 200){
            if(active == true && this.elements.inactive){
                this.elements.inactive.classList.remove('active');
                setTimeout(function(){
                    this.elements.inactive.remove();
                    delete this.elements.inactive;
                }.bind(this), 200);
            } else if (active == false && !this.elements.inactive){
                this.createInactive();
            }

            return true;
        } else {
            return false;
        }
    }

    createInactive(){
        this.elements.inactive = document.createElement('div');
        this.elements.inactive.classList.add('hc__inactive_message');
        this.elements.inactive.innerHTML = window.HelloChat.defaultLocalization.system.inactiveMessage;
        this.screen.appendChild(this.elements.inactive);

        setTimeout(function(){
            this.elements.inactive.classList.add('active');
        }.bind(this), 200);
    }

    loadScreen(){
        let data = sessionStorage.getItem('hc_current_screen');
        let screen = 'start';
        try{
            if(data){ 
                data = JSON.parse(data); 
                screen = data.screen;
                HelloChat.getInstance().setTransitionData(data.data);
            }
        } catch(Exception){ }
        this.startPhase(screen);
    }

}