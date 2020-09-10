(function(){
	
	let base = documnet.body.querSelector('head base');
	if(base){
		let script = document.createElement('script');
		script.src = base.href + 'hellochat/src/src/V1/HelloChat/resources/js/client/admin.js';
		document.body.appendChild(script);
	}
	
})();