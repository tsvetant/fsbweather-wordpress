jQuery(document).ready(function(){

	/* FILE UPLOAD */ 
	var targetfield= '';
	var fwb_send_to_editor = window.send_to_editor;
	jQuery('.fwb_uploadbtn').click(function(){
		targetfield = jQuery(this).prev('.fwb_uploadimg');
		tb_show('', 'media-upload.php?type=image&TB_iframe=true');
		window.send_to_editor = function(html) {
			imgurl = jQuery('img',html).attr('src');
			jQuery(targetfield).val(imgurl);
			tb_remove();
			window.send_to_editor = fwb_send_to_editor;
		}
		return false;
	});	

	/* SELECT CHANGE */
	jQuery('select#fsbwselect').on('change', function() {
		var val = this.value;
   		switch (val) { 

        	case 'option0': 
        		jQuery('.pattopt_1').fadeOut();
        		jQuery('.pattopt_2').fadeOut();
        		jQuery('.pattopt_3').fadeOut();
        	break;

        	case 'option1': 
        		jQuery('.pattopt_2, .pattopt_3').fadeOut();
        		setTimeout(function(){
        			jQuery('.pattopt_1').fadeIn();
        		},400)
        	break;

        	case 'option2': 
        		jQuery('.pattopt_1, .pattopt_3').fadeOut();
        		setTimeout(function(){
        			jQuery('.pattopt_2').fadeIn();
        		},400)
        	break;

        	case 'option3': 
        		jQuery('.pattopt_1, .pattopt_2').fadeOut();
        		setTimeout(function(){
        			jQuery('.pattopt_3').fadeIn();
        		},400)
        	break;
		}
	});

	var defaultval = jQuery('select#fsbwselect').find(":selected").text();
	switch (defaultval) { 

    	case 'None': 
    		return true;
    	break;

    	case 'Pattern': 
    		jQuery('.pattopt_1').fadeIn();
    	break;

    	case 'Shards': 
    		jQuery('.pattopt_2').fadeIn();
    	break;

    	case 'Gradient': 
    		jQuery('.pattopt_3').fadeIn();
    	break;
	}

});