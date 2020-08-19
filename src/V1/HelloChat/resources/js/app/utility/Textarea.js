export class Textarea {

    static fitToText(){
        if(this.value == ''){
            this.parentNode.style.height = "";    
        } else {
            this.parentNode.style.height = this.scrollHeight + "px"
        }
    }

}