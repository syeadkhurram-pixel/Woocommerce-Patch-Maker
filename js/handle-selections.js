jQuery(document).ready(function($){
	var isRender = Boolean(is_render);
	
	
	
	$('ul.xt_woovs-swatches li img').on('click',async function(){
		
		let title = $(this).attr('alt');
		
		
		let dk_var_preview = $('.dk_var_preview');
		
		if (dk_var_preview.length) {
			
			dk_var_preview.html('<b>Color:</b> '+title); 
			
		} else {
			
			$('<span class="dk_var_preview"><b>Color:</b> ' + title + '</span>').insertAfter('span.xt_woovs-attribute-value'); 
			
		}
		
	});
	
	
	var ImageProcess = null;
	
	
 	setTimeout(async function() {
		
		const CANVAS = document.createElement('canvas');

		CANVAS.className="main-canvasx";
		
		CANVAS.setAttribute('tabindex', '0'); 
		
		const CANVAS_DRAW = await  draw_canvas(CANVAS);
		
		
		
		
			$('[name="patch-color"],[name="patch-shape"]').on('change' , function(){
				
				
				if(!$('[name="patch-color"]:checked').length){
					
					$('[name="patch-color"]')[0].checked = true;
					
				}
				if(!$('[name="patch-shape"]:checked').length){
					
					$('[name="patch-shape"]')[0].checked = true;
				}
				
				if(!$('.xt_woovs-single-product .xt_woovs-swatches .xt_woovs-selected').length){
					$('.xt_woovs-single-product .xt_woovs-swatches .swatch.swatch-image')[0].click();
				}
				
				let patch_color = $('[name="patch-color"]:checked').val();
				
				let patch_shape = $('[name="patch-shape"]:checked').val();
				
				process_canvas(patch_color,patch_shape,CANVAS_DRAW);

			});
		
			$('.xt_woovs-single-product .xt_woovs-swatches .swatch.swatch-image').click(async function () {
				
				if(!$('[name="patch-color"]:checked').length){
					
					$('[name="patch-color"]')[0].checked = true;
					
				}
				
				if(!$('[name="patch-shape"]:checked').length){
					
					$('[name="patch-shape"]')[0].checked = true;
					
				}
				
				let patch_color = $('[name="patch-color"]:checked').val();
				let patch_shape = $('[name="patch-shape"]:checked').val();
				
				
				const CANVAS_DRAW =  await draw_canvas(CANVAS);
				
				
				CANVAS.style.display = "block";
				
				process_canvas(patch_color,patch_shape,CANVAS_DRAW);
				
			})
		
		
			$('#patch_image').on('change' , function(e){
				
				var file = e.target.files[0];
				
				if(!ImageProcess) return;
				ImageProcess.drawImage(URL.createObjectURL(file));
				
			})



			$('.single_add_to_cart_button').on('click' , function(e){
				
				if(!ImageProcess) return true;

					e.preventDefault();
				
					let button = $(this);
				
					let image = $('.wpgs_image.slick-slide.slick-current.slick-active img')[0];

				
					$('#image_data').val(ImageProcess.exportImage(image))

					
					
					 button.off('click');
        
					 button.trigger('click');
        
					 button.on('click', arguments.callee);


			});
		
		
		$(document).on('click' , ".thumbnail_image" , function(){
	
			CANVAS.style.display = "none";
			
		})
		
	}, 2000)
	

	function process_canvas(patch_color,patch_shape,CANVAS_DRAW){

		if(!isRender) return;
		
		var selectedData = get_selected_patch(patch_color , patch_shape);
		
		var patchWidth  = CANVAS_DRAW.width * 0.33;
		
		if(patch_shape==="Hexagon"){
			
			patchWidth = patchWidth * 1.1;
			
		}
		else if(patch_shape==="Square"){
			
			patchWidth = patchWidth * 0.65;
			
		}
		else if(patch_shape==="Circle" || patch_shape==="Arrow" || patch_shape==="Georgia" || patch_shape==="Pocket" || patch_shape==="Triangle" ){
			
			patchWidth = patchWidth * 0.8;
			
		}
		
		else if(patch_shape==="Diamond"){
			
			patchWidth = patchWidth * 0.9;
			
		}
		
		else if(patch_shape==="Shield"){
			
			patchWidth = patchWidth * 0.8;
			
		}
		
		else if(patch_shape==="Oval"){
			
			patchWidth = patchWidth * 1.1;
			
		}
		
		else if(patch_shape==="Texas"){
			
			patchWidth = patchWidth * 1;
			
		}
		
		else if(patch_shape==="Rectangle"){
			
			patchWidth = patchWidth * 1;
			
		}
		
		else{
			patchWidth = patchWidth * 1;
			
		}
		
		
		if(!ImageProcess){
			
			ImageProcess = drawImage(selectedData.thumbnail_url , CANVAS_DRAW);
			ImageProcess.patchWidth = patchWidth;
			
		}
		else{
			
			ImageProcess.patchWidth = patchWidth;
			
			ImageProcess.update(selectedData.thumbnail_url , CANVAS_DRAW);
		}
	}
	

	
async function draw_canvas(CANVAS) {
	return new Promise((resolve) => {
		
		var checkLoadingComplete = setInterval(function() {
			if (
				!$('div.blockUI.blockOverlay').is(':visible') 
				&& $('.woocommerce-product-gallery .wpgs-for').is(':visible')
			) {
				
				clearInterval(checkLoadingComplete);
				
				let gallery_element = $('.woocommerce-product-gallery.images.wpgs-wrapper .wpgs-for');
				
				CANVAS.width = gallery_element.innerWidth();
				
				CANVAS.height = gallery_element.innerHeight();
				
				
				gallery_element.append(CANVAS);
				
				resolve(CANVAS);
				
			}
		}, 500); 
	});
}
	
	
});

function get_selected_patch(color , shape){
	
	
	var patchData = data.patch_data;
	
	return patchData.find(e=> e.patch_shape == shape && e.patch_color == color);
	
}

function drawImage(src , canvas){
	var imageProcess =  new ImageProcessor(src , canvas);
	
	return imageProcess;
	
}
