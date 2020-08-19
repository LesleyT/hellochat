export class SettingsScreen {
    
    context;
    parent;
    elements;

    values;
    textareas;
    passwords;

    constructor(context, parent){
        this.context = context;
        this.parent = parent;
        this.elements = {};

        this.values = {
            'color' : {},
            'text' : {}
        };
        this.textareas = ['initialMessage', 'introDescription', 'formPrivacy', 'inactiveDescription', 'endDescription'];
        this.passwords = ['systemPassword'];
    }

    build(){
        this.destroy();
       
        this.elements.settings = document.createElement('div');
        this.elements.settings.classList.add('hc__settings');
        this.elements.settings.classList.add('main__screen');

        let h2 = document.createElement('h2');
        h2.classList.add('hc__settings__title')
        h2.innerHTML = window.HelloChat.localization.settings.settingsTitle;


        let save = document.createElement('button');
        save.type = 'button';
        save.classList.add('hc__save__settings');
        save.innerHTML = 'save';
        save.addEventListener('click', function(){ this.store(); }.bind(this), true);

        
        this.elements.settings.appendChild(h2);
        
        this.elements.settings.appendChild(this.buildColorFields());
        this.elements.settings.appendChild(this.buildTextFields());   
        this.elements.settings.appendChild(save);
        
        this.parent.appendChild(this.elements.settings);
        
        let header = document.querySelector('.hc__header');
        if(header){
            let close = document.createElement('button');
            close.type = "button";
            close.classList.add('hc__close__settings');
            close.addEventListener('click', function(){
                this.context.loadScreen();
            }.bind(this), true);
            header.appendChild(close);

            let edit = header.querySelector('.hc__edit__settings');
            if(edit){
                edit.style.display = 'none';
            }
        }

    }

    nl2br (str, is_xhtml) {
        if (typeof str === 'undefined' || str === null) {
            return '';
        }
        var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
        return ((str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2')).replace(/(\r\n|\n|\r)/gm, "");;
    }

    br2nl (str, replaceMode) {
        var replaceStr = (replaceMode) ? "\n" : '';
        return str.replace(/<\s*\/?br\s*[\/]?>/gi, replaceStr);
    }

    async store(){
        let inputs = this.elements.settings.querySelectorAll('input, textarea');
        if(inputs){
            var output = {};
            const regex = /(?!^|\[)\w+(?=\]|$)/g
            for(var i = 0; i< inputs.length; i++){
                var attr = inputs[i].getAttribute('name').match(regex);
                var t = output;
                for(var j = 0; j< attr.length; j++){
                    if(t[attr[j]] === undefined || t[attr[j]] == 'undefined'){
                        t[attr[j]] = {}
                    }
                    if(j === attr.length - 1) {
                        t[attr[j]] = this.nl2br(inputs[i].value);
                    }
                    else {
                        t = t[attr[j]]
                    }
                }
            }


            let accessToken = sessionStorage.getItem('hc_token');
            if(!accessToken){
                this.context.startPhase('connect');
                return;
            }

            let endpoint = 'settings';
            let headers = {
                'Content-Type': 'application/json',
                'Authorization' : accessToken
            };

            let response = await fetch(window.HelloChat.config.api + endpoint, {
                method : 'PATCH',
                headers: headers,
                body : JSON.stringify(output)
            }).then(function(response){ return response.json(); })
            .then(function(response){ return response; })
            .catch(function(error){ console.log(error); });

            if(response.statusCode === 200){
                //show success
                window.location.reload();
            } else {
                //error handle
            }
        }
    }

    destroy(){
        if(this.parent){
            let view;
            view = this.parent.querySelector('div.hc__settings ');
            if(view){ view.remove(); }

            let close = document.querySelector('.hc__close__settings');
            if(close){
                close.remove();
            }

            let edit = document.querySelector('.hc__edit__settings');
            if(edit){
                edit.style.display = '';
            }
        }
    }

    async run(){       
        await this.loadSettings();
        console.log(this.values);
        this.build();
    }

    async loadSettings(){
        let accessToken = sessionStorage.getItem('hc_token');
        if(!accessToken){
            this.context.startPhase('connect');
            return;
        }

        let endpoint = 'settings';
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
            this.values = response.data.settings;
        }
    }

    buildTextFields(){
        let labels = this.getDefaultLabels();

        let element;
        let sub;

        let section = document.createElement('div');
        section.classList.add('hc__settings__section');

        let h2 = document.createElement('h2');
        h2.innerHTML = window.HelloChat.general.localization.text.title;
        section.appendChild(h2);
        
        for(let index in labels){
            if(labels.hasOwnProperty(index)){
                sub = document.createElement('div');
                sub.classList.add('hc__settings__subsection');
                
                let h3 = document.createElement('h3');
                h3.innerHTML = window.HelloChat.general.localization.text[index];
                sub.appendChild(h3);        

                element = labels[index];
                for(let name in element){
                    if(element.hasOwnProperty(name)){
                        let h4 = document.createElement('h4');
                        h4.innerHTML = window.HelloChat.general.localization.text[name];
                        sub.appendChild(h4);        
                        
                        if(this.textareas.indexOf(name) >= 0){
                            sub.appendChild(this.buildField({
                                'element' : 'textarea',
                                'name' : 'settings['+index+']'+'['+name+']',
                                'value' : this.br2nl((typeof this.values[index] != 'undefined' && typeof this.values[index][name] != 'undefined') ? this.values[index][name] : element[name], true)
                            }));
                        } else if(this.passwords.indexOf(name) >= 0){
                            sub.appendChild(this.buildField({
                                'element' : 'input',
                                'type' : 'password',
                                'name' : 'settings['+index+']'+'['+name+']',
                                'value' : 'This is a placeholder value..'
                            }));
                        } else {
                            sub.appendChild(this.buildField({
                                'element' : 'input',
                                'type' : 'text',
                                'name' : 'settings['+index+']'+'['+name+']',
                                'value' : this.br2nl((typeof this.values[index] != 'undefined' && typeof this.values[index][name] != 'undefined') ? this.values[index][name] : element[name], true)
                            }));
                        }

                        
                    }
                }
                section.appendChild(sub);
            }
        }
        
        return section;
    }

    buildColorFields(){
        let labels = {
            'main' : { "mainColor" : "#0984e3", "primaryShade" : "#076dbb" },
            'secondary' : { "secondaryColor" : "#fdcb6e", "secondaryShade" : "#efba59" },
            'ui' : { "uiText" : "#ffffff" },
        };

        let element;
        let sub;
        
        let section = document.createElement('div');
        section.classList.add('hc__settings__section');

        let h2 = document.createElement('h2');
        h2.innerHTML = window.HelloChat.general.localization.color.title;
        section.appendChild(h2);
        for(let index in labels){
            if(labels.hasOwnProperty(index)){
                sub = document.createElement('div');
                sub.classList.add('hc__settings__subsection');

                let h3 = document.createElement('h3');
                h3.innerHTML = window.HelloChat.general.localization.color[index];
                sub.appendChild(h3);

                element = labels[index];
                for(let name in element){
                    if(element.hasOwnProperty(name)){

                        let h4 = document.createElement('h4');
                        h4.innerHTML = window.HelloChat.general.localization.color[name];
                        sub.appendChild(h4);
                        
                        sub.appendChild(this.buildField({
                            'element' : 'input',
                            'type' : 'color',
                            'name' : 'settings['+index+']'+'['+name+']',
                            'value' : (typeof this.values[index] != 'undefined' && typeof this.values[index][name] != 'undefined') ? this.values[index][name] : element[name]
                        }));
                        
                    }
                    section.appendChild(sub);
                }
            }
        }
        return section;
    }

    buildField(config) {
        let element = document.createElement(config.element);
        delete config.element;
        for(let index in config){
            element[index] = config[index];
        }
        if(element.type == 'password'){
            console.log(element.type);
            let passwordContainer = document.createElement('div');
            passwordContainer.classList.add('hc__password__container');
            passwordContainer.classList.add('inactive');
            passwordContainer.addEventListener('click', function(){
                this.classList.remove('inactive');
            });
            passwordContainer.appendChild(element)
            return passwordContainer;
        }
        return element;
    }

    getDefaultLabels(){
        return {
            'initial' : { 
                'initial' : 'Openingsbericht', 
                'initialMessage' : 'Om u zo snel mogelijk te kunnen helpen vragen wij u om uw postcode, klantennummer en pasnummer door te sturen. <br/><br/>Een van onze medewerkers zal uw spoedig helpen.', 
                // 'initialEmail' : '#', 
                'initialName' : 'Blicon' 
            },
            'header' : { 
                'chatPlaceholder' : 'Verbinden..' 
            },
            'welcome' : { 
                'introTitle' : 'Hallo daar!', 
                'introDescription' : 'Heb je vragen of ben je opzoek naar iets? Stel dan gerust een vraag zodat een van onze werknemers je zo snel mogelijk kan helpen!', 
                'formName' : 'Naam..', 
                'formEmail' : 'Email..', 
                'formPrivacy' : 'Ik ga akkoord met het [url]verwerken[/url] van mijn persoonsgegevens.', 
                'formSubmit' : 'Versturen' 
            },
            'chat' : { 
                'chatMessage' : 'Type je bericht hier..', 
                'chatClose' : 'Chat sluiten', 
                'chatNotification' : 'Blicon: Nieuw chat bericht', 
                'chatOverview' : 'Naar chat overzicht', 
            },
            'connect' : { 
                'connectButton' : 'Verbind met Chat' 
            },
            'users' : { 
                'userTitle' : 'Chat Overzicht', 
                'userButton' : 'Claim chat', 
                'userActive' : 'Open', 
                'userInactive' : 'Geclaimed', 
                'userJoin' : 'Open chat', 
            }, 
            'archive' : { 
                'archiveTitle' : 'Chat Archief', 
                'archiveButton' : 'Bekijk chat', 
                'archiveRefresh' : 'Pagina vernieuwen', 
                'archiveUsers' : 'Chats', 
                'archiveFilter' : 'Medewerker', 
                'archiveFilterDefault' : 'Chats door: ', 
            }, 
            'end' : { 
                'endTitle' : 'De chat is gesloten',
                'endDescription' : 'Bedankt, we wensen u nog een fijne dag!',
            },
            "detail" : { 
                "detailMail" : "Stuur een e-mail", 
                "detailConversation" : "Gesprek", 
                "detailRemove" : "Verwijderen gesprek", 
                'detailArchive' : 'Archief' 
            },
            "confirmation" : { 
                "confirmationMessage" : "Weet je zeker dat je dit wilt doen", 
                "confirmationYes" : "Ja", 
                "confirmationNo" : "Annuleren", 
            },
            "settings" : { 
                "settingsTitle" : "Instellingen", 
                "settingsMenu" : "Instellingen aanpassen" 
            },
            'system' : {
                'inactiveDescription' : 'Sorry, op dit moment zijn er geen medewerkers actief op de chat.',
                'inactiveButton' : 'Neem contact op',
                'inactiveUrl' : '',
                'activateLabel' : 'Chat actief',
                'systemPassword' : 'This is a placeholder value..'
            }
        }
    }
}