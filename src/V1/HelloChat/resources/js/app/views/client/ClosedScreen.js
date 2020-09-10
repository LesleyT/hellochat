export class ClosedScreen {
    
    parent;
    context;

    constructor(context, parent){
        this.context = context;
        this.parent = parent;
    }

    build(){
        this.destroy();
        let container = document.createElement('div');
        container.classList.add('hc__welcome');
        container.classList.add('main__screen');

        let closed = document.createElement('article');
        closed.classList.add('hc__closed__information');

            let closedDescription = document.createElement('p');
            closedDescription.classList.add('closed__description');
            closedDescription.innerHTML = window.HelloChat.localization.system.inactiveDescription;

        closed.appendChild(closedDescription);
        container.appendChild(closed);
            
        let closedbutton = document.createElement('a');
        closedbutton.classList.add('closed__button');
        closedbutton.href = window.HelloChat.localization.system.inactiveUrl;
        closedbutton.innerHTML = window.HelloChat.localization.system.inactiveButton;
        closedbutton.target = '_top';
        container.appendChild(closedbutton);

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
        let view = document.querySelector('.hc__welcome');
        if(view){ view.remove(); }

        let header = document.querySelector('.hc__header');
        if(header){
            header.classList.remove('disable');
        }
    }

    run(){       
        this.build();
    }

}