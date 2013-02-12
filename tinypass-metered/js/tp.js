if(typeof tpShowOfferCustom == 'undefined') {
	function tpShowOfferCustom(){
		try{	
			if(typeof window.getTPMeter == 'function'){
				var meter = getTPMeter();
				meter.showOffer();	
			}
		}catch(ex){console.log(ex)}
	}
}
		

