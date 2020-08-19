import { ExportNumber } from '../utility/Number.js';

export class Message{
        
    owner;
    content;
    created;

    constructor(data){
        this.owner = data.owner;
        this.content = this.nl2br(data.content);
        this.created = data.created
    }

    async send(){
        let conversation = sessionStorage.getItem('hc_conversation');
        if(typeof this.owner == 'undefined'){
            this.owner = {
                email : sessionStorage.getItem('hc_user')
            };
        }

        let accessToken = sessionStorage.getItem('hc_token');
        if(!accessToken || !conversation){
            //try to authenticate token, user or conversation
        }

        let endpoint = 'messages';
        let headers = {
            'Content-Type': 'application/json',
            'Authorization' : accessToken
        };

        let response = await fetch(window.HelloChat.config.api + endpoint, {
            method : 'POST',
            headers: headers,
            body: this.getMessageBody(conversation)
        }).then(function(response){ return response.json(); })
        .then(function(response){ return response; })
        .catch(function(error){ console.log(error); });

        return response;
    }

    getMessageBody(conversation){
        return JSON.stringify({
            name : this.owner.name,
            email : this.owner.email,
            content : this.content,
            created : this.created,
            conversationId : conversation
        });
    }

    display(parent, isOwner, scrollTo){
        let message = this.build(isOwner);

        if(scrollTo !== false){
            setTimeout(function(){ 
                this.scrollIntoView({behavior: "smooth", block: "end", inline: "end"});
            }.bind(message), 100);
        }

        parent.appendChild(message);
    }

    build(isOwner){
        let created = this.created.split(/\D/);
        created = new Date(created[2], --created[1], created[1], created[3], created[4], created[5]);
        
        let message = document.createElement('article');
        message.classList.add(isOwner ? 'hc__message__object-send' : 'hc__message__object-received');
        message.setAttribute('data-owner', this.owner.name);

            let content = document.createElement('p');
            content.innerHTML = this.content;

            let dateTime = document.createElement('time');
            dateTime.setAttribute('datetime', created.getFullYear() + '-' + (created.getMonth() + 1).pad(2) + '-' + created.getDate().pad(2) + ' ' + created.getHours().pad(2) + ':' + (created.getMinutes()).pad(2) + ':' + created.getSeconds().pad(2));
                let date = document.createElement('span');
                date.innerHTML = created.getFullYear() + '-' + (created.getMonth().pad(2) + 1) + '-' + created.getDate().pad(2);

                let time = document.createElement('span');
                time.innerHTML =  created.getHours() + ':' + created.getMinutes().pad(2);// + ':' + created.getSeconds().pad(2);

        dateTime.appendChild(date);
        dateTime.appendChild(time);

        message.appendChild(content);
        message.appendChild(dateTime);
        
        return message;
    }

    nl2br (str, is_xhtml) {
        if (typeof str === 'undefined' || str === null) {
            return '';
        }
        var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
        return ((str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/gm, '$1' + breakTag + '$2')).replace(/(\r\n|\n|\r)/gm, "");
    }

}