

$('input[type="file"].file-hidden').change(function(){
	$('#upload-fake').val($(this).val());
});

$('.load-modal').click(function(){
	var target = $(this).attr('data-target');
	$(target).find('iframe').attr('src',$(this).attr('data-url'));
});


/**
 *  VERIFICA O ALIAS D UM INPUT
 *  @alvo : objeto(exemplo: $('.eu_sou_alvo')) que vai receber o texto do $(this)
 *  
 */
$.fn.verifica_alias = function(alvo,hidden,add){

	var alias='';var time='';var oldalias=hidden.val();
			
	function carrega_alias(obj,alvo,hidden,add){
	
		if ( typeof time != 'undefined' && time != '' ){
			clearTimeout(time);
		}
		
		time = setTimeout(function(){
		
			var prealias = obj.val().toLowerCase();
			
			prealias = troca_string(prealias);
			
			if ( prealias == oldalias ){ 
				alvo.val(prealias);
				hidden.val(prealias);
				return false; 
			}
			
			if ( typeof add != 'undefined' ){
				add++;
				prealias+='-'+add;
			} else {
				add = 0;
			}

			if ( alias == '' ){
				jQuery.ajax({
					 url: "/Odin/artigo/alias",
					context: document.body
				})
				.done(function(ajax){
					alias = new Array();
					alias = JSON.parse(ajax);
					verifica_string(obj,prealias,alias,alvo,hidden,add);
				})
				.fail(function() {
					alert( "Erro no ajax" );
				});
			} else {
				verifica_string(obj,prealias,alias,alvo,hidden,add);
			}
		},200);
		
		function verifica_string(obj,prealias,alias,alvo,hidden,add){
		
			console.log(prealias);
			alias.forEach(function(_alias){
				console.log(_alias.alias);
				if (_alias.alias == prealias){
					carrega_alias(obj,alvo,hidden,add);
				}
			});
			
			alvo.val(prealias);
			hidden.val(prealias);
			return true;
		}

		function troca_string(prealias){

			prealias = loop_replace(prealias," ", "-");
			prealias = loop_replace(prealias,/[áàâãä]/g, "a");
			prealias = loop_replace(prealias,/[éèêë]/, "e");
			prealias = loop_replace(prealias,/[íìïî]/, "i");
			prealias = loop_replace(prealias,/[óòôõö]/, "o");
			prealias = loop_replace(prealias,/[úùü]/, "u");
			prealias = loop_replace(prealias,/ç/, "c");
			prealias = loop_replace(prealias,/[\]\[><}{)(:;.+,'"°|!?/*%~^`´=¨¬¢£$³²¹&#@\\]/, "");
			
			function loop_replace(prealias,regex,letra){
				
				while (prealias.search(regex) >= 0){
					prealias = prealias.replace(regex,letra);
				}
				
				return prealias;
			}
			
			return prealias;
			
		}
	}
	
	$(this).bind("blur keyup", function(){
		carrega_alias($(this),alvo,hidden,add);
	});
}