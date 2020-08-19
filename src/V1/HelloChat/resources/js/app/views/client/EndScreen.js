export class EndScreen {
    
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

        let end = document.createElement('article');
        end.classList.add('hc__closed__information');

            let endTitle = document.createElement('h2');
            endTitle.classList.add('end__title');
            endTitle.innerHTML = window.HelloChat.localization.end.endTitle;

            let endDescription = document.createElement('p');
            endDescription.classList.add('end__description');
            endDescription.innerHTML = window.HelloChat.localization.end.endDescription;

            
        end.appendChild(endTitle);
        end.appendChild(endDescription);
        container.appendChild(end);
            
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