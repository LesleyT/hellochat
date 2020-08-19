import { EmailValidator } from '../../validators/EmailValidator.js'
;
export class WelcomeScreen {
    
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
        let container = document.createElement('div');
        container.classList.add('hc__welcome');
        container.classList.add('main__screen');



        let intro = document.createElement('article');
        intro.classList.add('hc__intro__information');

            let introTitle = document.createElement('h2');
            introTitle.classList.add('intro__title');
            introTitle.innerHTML = window.HelloChat.localization.welcome.introTitle;

            let introDescription = document.createElement('p');
            introDescription.classList.add('intro__description');
            introDescription.innerHTML = window.HelloChat.localization.welcome.introDescription;

        intro.appendChild(introTitle);
        intro.appendChild(introDescription);
        container.appendChild(intro);

        

        let form = document.createElement('form');
        form.method = 'POST';
        form.classList.add('hc__form--intro');
        form.addEventListener('submit', function(event){
            this.submit(event);
        }.bind(this), true);

            let formName = document.createElement('input');
            formName.type = "text";
            formName.name = "hc-name";
            formName.placeholder = window.HelloChat.localization.welcome.formName;
            this.elements.name = formName;

            let formEmail = document.createElement('input');
            formEmail.type = "email";
            formEmail.name = "hc-email";
            formEmail.placeholder = window.HelloChat.localization.welcome.formEmail;
            this.elements.email = formEmail;


            let privacyLabel = document.createElement('label');
            privacyLabel.classList.add('hc__checkbox');

                let formPrivacy = document.createElement('input');
                formPrivacy.type = "checkbox";
                formPrivacy.name = "hc-privacy";
                this.elements.privacy = formPrivacy;

                let checkboxMask = document.createElement('span');
                checkboxMask.classList.add("hc__checkbox__display");

            privacyLabel.appendChild(formPrivacy);
            privacyLabel.appendChild(checkboxMask);

            let text = window.HelloChat.localization.welcome.formPrivacy;
            if(text.indexOf('[url]') !== -1 && text.indexOf('[/url]') !== -1){
                text = text.replace('[url]', '<a href="'+window.HelloChat.config.privacy+'" target="_blank">');
                text = text.replace('[/url]', '</a>');
            }
            privacyLabel.innerHTML += text;

            let formSubmit = document.createElement('input');
            formSubmit.type = "submit";
            formSubmit.name = "hc-submit";
            formSubmit.value = window.HelloChat.localization.welcome.formSubmit;
            
        form.appendChild(formName);
        form.appendChild(formEmail);
        form.appendChild(privacyLabel);
        form.appendChild(formSubmit);
        container.appendChild(form);

        let powered = document.createElement('div');
        powered.classList.add('hc__footer');
        powered.innerHTML = '<span class="owner">Powered by: <a target="_blank" href="https://hellogoodday.nl">Goodday</a></span>';
        container.appendChild(powered);

        this.parent.appendChild(container);


        let header = document.querySelector('.hc__header');
        if(header){
            header.classList.add('disable');
        }
    }

    destroy(){
        if(this.parent){
            let view = this.parent.querySelector('.hc__welcome');
            if(view){ view.remove(); }
        }

        let header = document.querySelector('.hc__header');
        if(header){
            header.classList.remove('disable');
        }
    }

    async run(){  
        if(window.HelloChat.active === false){ 
            this.context.startPhase('close');
            return;
        }     

        let connected = await this.context.connection.init('previous'); 
        if(connected){
            if(typeof connected.data.users == 'undefined'){
                connected.data = { users : [await this.context.connection.loginPing()] };
                connected.data.users[0].email = connected.user;
            }
            this.context.setTransitionData(connected.data.users[0]);
            this.context.startPhase('pending');
            return;
        } else { this.context.connection.clearData(); }
        this.build();
    }


    submit(event){
        event.preventDefault();
        let data = {};
        let errors = [];

        let element;
        for(let i = 0; i < event.target.elements.length; i++){
            element = event.target.elements[i];
            if(element.type == 'text'){
                if(element.value == ''){
                    errors.push(element);
                    element.classList.add('error');
                } else { 
                    data['name'] = element.value; 
                    element.classList.remove('error');
                }
            }

            if(element.type == 'email'){
                if(!EmailValidator.isValid(element.value)){
                    errors.push(element);
                    element.classList.add('error');
                } else { 
                    data['email'] = element.value;
                    element.classList.remove('error');
                }
            }
            
            if(element.type == 'checkbox'){
                if(!element.checked){
                    errors.push(element);
                    element.parentNode.classList.add('error');
                } else { 
                    data[element.name] = element.checked;
                    element.parentNode.classList.remove('error'); 
                }
            }
        }

        if(errors.length == 0){
            this.context.connection.initiated = false;
            this.context.setTransitionData(data);
            this.context.startPhase('pending');
        } else {
            this.context.setTransitionData();
        }
    }

}