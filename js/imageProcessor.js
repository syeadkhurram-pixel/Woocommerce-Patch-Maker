class ImageProcessor {
    constructor(imageSrc, canvas) {
		this.images = [];
        this.imageSrc = imageSrc;
        this.canvas = canvas;
        this.context = this.canvas.getContext('2d' , {willReadFrequently: true, alpha: true});
        this.image = new Image();
		this.image.crossOrigin = 'Anonymous'; 
        this.image.onload = () =>  this.processImage();
        this.image.src = this.imageSrc;
		
		this.handleFillStyle = "#222";
		
       	this.isDragging = false;
        this.isResizing = false;
        this.isRotating = false;
        this.dragHandle = null;
        this.handles = [];
        this.rotationHandle = null;
		this.handleSize = 10;
        this.rotationHandleSize = 20;

        
        this.image2X = 0;
        this.image2Y = 0;
        this.image2Width = 0;
        this.image2Height = 0;
		this.imageAngle = 0;
		
		this.patchWidth = this.canvas.width * 0.33;
		this.patchHeight = this.canvas.width * 0.33;
        
		
		this.keyMaps = new Map([
			['ArrowUp', () => this.image2Y -= 1],
			['ArrowDown', () => this.image2Y += 1],
			['ArrowLeft', () => this.image2X -= 1],
			['ArrowRight', () => this.image2X += 1]
		]);
		
		this.offCanvas = document.createElement('canvas');
		this.offContext = this.offCanvas.getContext('2d');
		
		this.init();
    }
	
	
	loadImage(src){
		const image = new Image();
		return new Promise((resolve , reject) =>{
			image.onload = () => resolve(image);
		})
		image.src = src;
	}
	
	exportImage(background){
		this.offCanvas.width =  this.canvas.width;
		this.offCanvas.height = this.canvas.height;
		if(this.image2)
		this.hideControls();
		
		this.offContext.clearRect(0,0, this.offCanvas.width, this.offCanvas.height);
		
		this.offContext.drawImage(background , 0,0, this.offCanvas.width, this.offCanvas.height);
		this.offContext.drawImage(this.canvas , 0,0,this.offCanvas.width, this.offCanvas.height,  0,0,this.offCanvas.width, this.offCanvas.height);
		
		return this.offCanvas.toDataURL();
	}
	



    processImage() {
		const aspectRatio = this.image.width / this.image.height;

		let width = this.patchWidth;
		let height = width / aspectRatio;

		if (height > width) {
			height = width;
			width = height * aspectRatio;
		}

		const x = (this.canvas.width - width) / 2;
		const y = (this.canvas.height - height) / 2;

		this.imageX = x;
		this.imageY = y;
		this.imageWidth = width;
		this.imageHeight = height;

		this.context.drawImage(this.image, x, y, this.imageWidth, this.imageHeight);

		if(this.image2) this.redraw();

    }


	
	drawImage(src , opt = {w: 100 , h: 100}){
	
		const image = new Image();
		
		image.onload = () => {
			const aspectRatio = image.width / image.height;
			this.aspectRatio = aspectRatio;
			let width = opt.w;
			let height = opt.w / aspectRatio;

			if (height > this.patchWidth * 0.60) {
				height = this.patchWidth * 0.60;
				width = height * aspectRatio;
			}
			
			const x = (this.canvas.width - width) / 2;
			const y = (this.canvas.height - height) / 2;

			this.image2X = x;
			this.image2Y = y;
			this.image2Width = width;
			this.image2Height = height;
			this.imageAngle = 0;
			
			this.startMouseX = 0;
			this.startMouseY = 0;
			this.startImage2X = 0;
			this.startImage2Y = 0;
			this.startWidth = this.image2Width;
			this.startHeight = this.image2Height;
			this.startAngle = this.imageAngle;
			
			this.image2 = this.convertToBlack(image);
			this.redraw();
		}
		
		image.src = src;
	}
	
	
	convertToBlack(image){
		const canvas = document.createElement('canvas');
		canvas.width = image.width;
		canvas.height = image.height;
		const ctx = canvas.getContext('2d');
		ctx.drawImage(image , 0, 0, image.width , image.height);
		const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
		
		const data = imageData.data;

                for (let i = 0; i < data.length; i += 4) {
                    const red = data[i];
                    const green = data[i + 1];
                    const blue = data[i + 2];

                    const brightness = red * 0.3 + green * 0.59 + blue * 0.11;

                    if (brightness > 140) {
                        
                        data[i + 3] = 0;  
                    } else {
                        
                        data[i] = 30;
                        data[i + 1] = 30;
                        data[i + 2] = 30;
                    }
                }

               
                ctx.putImageData(imageData, 0, 0);
		
		return canvas;
		
	}
	
	
	hideControls(){
		this.handleFillStyle = "transparent";
		this.redraw();
	}
	
	showControls(){
		this.handleFillStyle = "#222";
		this.redraw();
		
	}
	
	
	 update(image , canvas){
		 
		this.context.clearRect(0, 0, this.canvas.width, this.canvas.height);
		this.image.src = image;
	}



    redraw() {
		
		
        this.context.clearRect(0, 0, this.canvas.width, this.canvas.height);
		
        this.context.drawImage(this.image, this.imageX, this.imageY, this.imageWidth, this.imageHeight);
		
		this.context.save();
		
		this.context.translate(this.image2X + this.image2Width / 2 , this.image2Y + this.image2Height / 2 );
        this.context.rotate(this.imageAngle * Math.PI / 180);
		
        this.context.drawImage(this.image2, -this.image2Width / 2, -this.image2Height / 2, this.image2Width, this.image2Height);

		this.context.restore();
        if(this.image2){
        	this.drawHandles();
			this.drawFrame();
			this.drawRotationHandle();
		}
    }
	
	drawHandles() {
        this.handles = [
            { x: this.image2X - 20, y: this.image2Y - 20 }, 
            { x: this.image2X + this.image2Width + 20, y: this.image2Y - 20}, 
            { x: this.image2X + this.image2Width + 20, y: this.image2Y + this.image2Height + 20 }, 
            { x: this.image2X - 20, y: this.image2Y + this.image2Height + 20 } 
        ];
		

        this.handles.forEach(handle => {
            this.drawCircle(handle.x - this.handleSize / 2, handle.y - this.handleSize / 2, this.handleSize);
        });
    }
	
	drawCircle( x, y, radius) {
		this.context.beginPath(); 
		this.context.arc(x, y, radius, 0, 2 * Math.PI); 
		this.context.fillStyle = this.handleFillStyle; 
		this.context.fill(); 
		
}

    drawRotationHandle() {
        const centerX = this.image2X + this.image2Width / 2;
        const centerY = this.image2Y + this.image2Height / 2;
        const rotationHandleX = centerX + (this.image2Width / 2 + 30) * Math.cos(this.imageAngle * Math.PI / 180);
        const rotationHandleY = centerY + (this.image2Width / 2 + 30) * Math.sin(this.imageAngle * Math.PI / 180);

        this.rotationHandle = { x: rotationHandleX, y: rotationHandleY };
        this.context.fillStyle = this.handleFillStyle;
        this.context.fillRect(this.rotationHandle.x - this.rotationHandleSize / 2, this.rotationHandle.y - this.rotationHandleSize / 2, this.rotationHandleSize, this.rotationHandleSize);
    }
	
	
	onMouseDown(e) {
        const mousePos = this.getMousePos(e);
        this.handles.forEach((handle, index) => {
            if (this.isOverHandle(mousePos, handle)) {
                this.isResizing = true;
                this.dragHandle = index;
                this.canvas.style.cursor = 'nwse-resize';
                this.startMouseX = mousePos.x;
                this.startMouseY = mousePos.y;
                this.startImage2X = this.image2X;
                this.startImage2Y = this.image2Y;
                this.startWidth = this.image2Width;
                this.startHeight = this.image2Height;
            }
        });

        if (!this.isResizing) {
            if (this.isOverHandle(mousePos, this.rotationHandle)) {
                this.isRotating = true;
                this.startMouseX = mousePos.x;
                this.startMouseY = mousePos.y;
                this.startAngle = this.imageAngle;
                this.canvas.style.cursor = 'pointer';
            } else if (!this.isResizing) {
                if (this.isOverImage(mousePos)) {
                    this.isDragging = true;
                    this.startMouseX = mousePos.x;
                    this.startMouseY = mousePos.y;
                    this.startImage2X = this.image2X;
                    this.startImage2Y = this.image2Y;
                    this.canvas.style.cursor = 'move';
                }
            }
        }
    }
	
	isOverImage(mousePos){
		return (
			mousePos.x >= this.image2X && mousePos.x <= this.image2X + this.image2Width &&
            mousePos.y >= this.image2Y && mousePos.y <= this.image2Y + this.image2Height
		);
	}

    onMouseMove(e) {
        const mousePos = this.getMousePos(e);

        if (this.isResizing) {
            const deltaX = mousePos.x - this.startMouseX;
            const deltaY = mousePos.y - this.startMouseY;

            if (this.dragHandle === 0) { 
                const newWidth = Math.max(this.startWidth - deltaX, 10);
                const newHeight = Math.max(newWidth / this.aspectRatio, 10);
                this.image2X = this.startImage2X + (this.startWidth - newWidth);
                this.image2Y = this.startImage2Y + (this.startHeight - newHeight);
                this.image2Width = newWidth;
                this.image2Height = newHeight;
            } else if (this.dragHandle === 1) { 
                const newWidth = Math.max(this.startWidth + deltaX, 10);
                const newHeight = Math.max(newWidth / this.aspectRatio, 10);
                this.image2Y = this.startImage2Y + (this.startHeight - newHeight);
                this.image2Width = newWidth;
                this.image2Height = newHeight;
            } else if (this.dragHandle === 2) { 
                const newWidth = Math.max(this.startWidth + deltaX, 10);
                const newHeight = Math.max(newWidth / this.aspectRatio, 10);
                this.image2Width = newWidth;
                this.image2Height = newHeight;
            } else if (this.dragHandle === 3) { 
                const newWidth = Math.max(this.startWidth - deltaX, 10);
                const newHeight = Math.max(newWidth / this.aspectRatio, 10);
                this.image2X = this.startImage2X + (this.startWidth - newWidth);
                this.image2Width = newWidth;
                this.image2Height = newHeight;
            }

            this.redraw();
        } else if (this.isDragging) {
            const dx = mousePos.x - this.startMouseX;
            const dy = mousePos.y - this.startMouseY;
            this.image2X = this.startImage2X + dx;
            this.image2Y = this.startImage2Y + dy;

            this.redraw();
        } else if (this.isRotating) {
            const centerX = this.image2X + this.image2Width / 2;
            const centerY = this.image2Y + this.image2Height / 2;
            const angle = Math.atan2(mousePos.y - centerY, mousePos.x - centerX) * 180 / Math.PI;
            this.imageAngle = this.startAngle + (angle - Math.atan2(this.startMouseY - centerY, this.startMouseX - centerX) * 180 / Math.PI);

            this.redraw();
        } else {
            let cursor = 'default';
            this.handles.forEach(handle => {
                if (this.isOverHandle(mousePos, handle)) {
                    cursor = 'nwse-resize';
                }
            });
            if (this.isOverHandle(mousePos, this.rotationHandle)) {
                cursor = 'pointer';
            }
            this.canvas.style.cursor = cursor;
        }
    }

    onMouseUp() {
        this.isDragging = false;
        this.isResizing = false;
        this.isRotating = false;
        this.dragHandle = null;
        this.canvas.style.cursor = 'default';
    }

    getMousePos(e) {
        const rect = this.canvas.getBoundingClientRect();
        return {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top
        };
    }

    isOverHandle(mousePos, handle) {
		if(!this.image2) return false;
		
        return mousePos.x >= handle.x - 50 / 2 &&
               mousePos.x <= handle.x + 50 / 2 &&
               mousePos.y >= handle.y - 50 / 2 &&
               mousePos.y <= handle.y + 50 / 2;
    }
	
	drawFrame() {
        this.context.strokeStyle = this.handleFillStyle;
        this.context.lineWidth = 2;

        this.context.beginPath();
        this.context.moveTo(this.handles[0].x, this.handles[0].y); 
        this.context.lineTo(this.handles[1].x, this.handles[1].y); 
        this.context.lineTo(this.handles[2].x, this.handles[2].y); 
        this.context.lineTo(this.handles[3].x, this.handles[3].y); 
        this.context.closePath(); 
        this.context.stroke();
    }
	
	
	onClick(e){
		if(!this.image2) return false;
		var isOverHandle = false;
		
		this.handles.forEach(handle => {
              if(this.isOverHandle(this.getMousePos(e) , handle)){
				  isOverHandle = true;
				  return;
			  }
				
        });
		
		
		if(this.isOverImage(this.getMousePos(e)) || isOverHandle)
			this.showControls();
		else
			this.hideControls();
	}
	
	handleMovement(e){
		const ACTION = this.keyMaps.get(e.key);
		if(!ACTION) return;
		e.preventDefault();
		ACTION();
		this.redraw();
	}
	
	getTouchPos(e) {
    const rect = this.canvas.getBoundingClientRect();
    const touch = e.touches[0] || e.changedTouches[0]; 
    return {
        x: touch.clientX ,
        y: touch.clientY 
    };
}

drawDebugPoint(x, y) {
    this.context.fillStyle = "red";
    this.context.beginPath();
    this.context.arc(x, y, 5, 0, Math.PI * 2); 
    this.context.fill();
}

onTouchStart(e) {
    e.preventDefault(); 
    const pos = this.getTouchPos(e);
	
    this.onMouseDown(this.convertTouchEvent(e));
}

onTouchMove(e) {
    e.preventDefault();
    const pos = this.getTouchPos(e);
    this.onMouseMove(this.convertTouchEvent(e));
}

onTouchEnd(e) {
    const pos = this.getTouchPos(e);
    this.onMouseUp(this.convertTouchEvent(e));
}

convertTouchEvent(e) {
    const touchPos = this.getTouchPos(e);
    return {
        clientX: touchPos.x,
        clientY: touchPos.y
    };
}
	
	

	init(){
		
		
		this.context.clearRect(0, 0, this.canvas.width, this.canvas.height);
		
		
		
		this.canvas.addEventListener('mousedown', this.onMouseDown.bind(this));
        this.canvas.addEventListener('mousemove', this.onMouseMove.bind(this));
        this.canvas.addEventListener('mouseup', this.onMouseUp.bind(this));
		this.canvas.addEventListener('click', this.onClick.bind(this));
		this.canvas.addEventListener('keydown', (e) => this.handleMovement(e));
		
		this.canvas.addEventListener('touchstart', this.onTouchStart.bind(this), { passive: false });
		this.canvas.addEventListener('touchmove', this.onTouchMove.bind(this), { passive: false });
		this.canvas.addEventListener('touchend', this.onTouchEnd.bind(this));
	}
	
}

