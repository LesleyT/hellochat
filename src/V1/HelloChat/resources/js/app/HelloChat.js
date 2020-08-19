/*


    - Generate key between Admin user & Messenger user when Admin connects to Messenger


*/

import {WelcomeScreen} from "./views/client/WelcomeScreen.js";
import {ChatScreen} from "./views/client/ChatScreen.js";
import {EndScreen} from "./views/client/EndScreen.js";
import {ClosedScreen} from "./views/client/ClosedScreen.js";
import { Connection } from './classes/Connection.js';

export class HelloChat {

    current;
    phases;
    screen;

    elements;

    connection;

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

        this.phases = {
            start : new WelcomeScreen(this, this.screen),
            pending : new ChatScreen(this, this.screen),
            end : new EndScreen(this, this.screen),
            close : new ClosedScreen(this, this.screen),
        };

        this.connection = new Connection();
        this.connection.makeConversationOnSession = true;        
        
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

            let contact = document.createElement('figure');
            contact.classList.add('hc__header__contact');

                let image = document.createElement('img');
                image.classList.add('hc__contact__image');
                image.src = window.HelloChat.config.defaultIcon;

                let figcaption = document.createElement('figcaption');
                    this.elements.name = document.createElement('h2');
                    this.elements.name.classList.add('hc__contact__name');
                    this.elements.name.innerHTML = window.HelloChat.localization.header.chatPlaceholder;

        contact.appendChild(image);

        figcaption.appendChild(this.elements.name);
        contact.appendChild(figcaption);
        
        header.appendChild(contact);
        
        this.screen.appendChild(header);
        document.body.appendChild(this.screen);
    }

    startPhase(phase){
        document.body.classList.add('transition');
        this.endPhase();

        if(typeof this.phases[phase] != 'undefined'){
            this.current = phase;
            this.phases[this.current].run();
        }

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

    setContact(name){
        if(typeof name != 'undefined' && name != null){
            this.elements.name.innerHTML = name; 
        }
    }
}