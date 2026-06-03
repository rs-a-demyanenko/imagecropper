/**
 * ImageCropper Editor Class
 * Handles image cropping, resizing, rotating in browser
 */

var ImageCropperEditor = (function() {
    'use strict';

    function ImageCropperEditor(container, existingData) {
        this.container = container;
        this.settings = JSON.parse(container.getAttribute('data-settings'));
        this.existingData = existingData || null;
        
        this.cropper = null;
        this.currentImage = null;
        this.dataInput = container.querySelector('.imagecropper-data');
        this.fileInput = container.querySelector('.imagecropper-file');
        this.previewContainer = container.querySelector('.imagecropper-preview-container');
        this.mainImage = container.querySelector('.imagecropper-main-image');
        this.thumbPreview = container.querySelector('.imagecropper-thumb-image');
        this.dimWidth = container.querySelector('.imagecropper-dim-width');
        this.dimHeight = container.querySelector('.imagecropper-dim-height');
        
        this.init();
    }

    ImageCropperEditor.prototype.init = function() {
        var self = this;
        
        // File input handler
        this.fileInput.addEventListener('change', function(e) {
            self.handleFileSelect(e);
        });
        
        // Control buttons
        var rotateLeftBtn = this.container.querySelector('.imagecropper-rotate-left');
        var rotateRightBtn = this.container.querySelector('.imagecropper-rotate-right');
        var flipHBtn = this.container.querySelector('.imagecropper-flip-h');
        var flipVBtn = this.container.querySelector('.imagecropper-flip-v');
        var resetBtn = this.container.querySelector('.imagecropper-reset');
        var removeBtn = this.container.querySelector('.imagecropper-remove');
        
        if (rotateLeftBtn) {
            rotateLeftBtn.addEventListener('click', function() { self.rotate(-90); });
        }
        if (rotateRightBtn) {
            rotateRightBtn.addEventListener('click', function() { self.rotate(90); });
        }
        if (flipHBtn) {
            flipHBtn.addEventListener('click', function() { self.flip('h'); });
        }
        if (flipVBtn) {
            flipVBtn.addEventListener('click', function() { self.flip('v'); });
        }
        if (resetBtn) {
            resetBtn.addEventListener('click', function() { self.reset(); });
        }
        if (removeBtn) {
            removeBtn.addEventListener('click', function() { self.remove(); });
        }
        
        // Load existing data if present
        if (this.existingData && this.existingData.url) {
            this.loadExistingImage(this.existingData);
        }
    };

    ImageCropperEditor.prototype.handleFileSelect = function(e) {
        var file = e.target.files[0];
        if (!file || !file.type.match('image.*')) {
            return;
        }
        
        var self = this;
        var reader = new FileReader();
        
        reader.onload = function(event) {
            self.loadImage(event.target.result);
        };
        
        reader.readAsDataURL(file);
    };

    ImageCropperEditor.prototype.loadExistingImage = function(imageData) {
        var self = this;
        
        // Load from existing URL
        var img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = function() {
            self.showPreview();
            self.updateDimensions(imageData.width, imageData.height);
            self.updateData(imageData);
            self.updateThumbPreview(imageData.thumb_url);
        };
        img.src = imageData.url;
        this.mainImage.src = imageData.url;
    };

    ImageCropperEditor.prototype.loadImage = function(src) {
        var self = this;
        
        this.currentImage = new Image();
        this.currentImage.crossOrigin = 'anonymous';
        this.currentImage.onload = function() {
            self.showPreview();
            self.initCropper();
        };
        this.currentImage.src = src;
        this.mainImage.src = src;
    };

    ImageCropperEditor.prototype.showPreview = function() {
        this.previewContainer.style.display = 'block';
    };

    ImageCropperEditor.prototype.initCropper = function() {
        var self = this;
        
        // Destroy existing cropper if any
        if (this.cropper) {
            this.cropper.destroy();
        }
        
        // Initialize Cropper.js
        this.cropper = new Cropper(this.mainImage, {
            aspectRatio: this.settings.width / this.settings.height,
            viewMode: 1,
            preview: '.imagecropper-thumb-image',
            autoCropArea: 0.8,
            responsive: true,
            restore: false,
            center: true,
            highlight: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
            
            ready: function() {
                self.updateCropData();
            },
            
            crop: function() {
                self.updateCropData();
            }
        });
    };

    ImageCropperEditor.prototype.updateCropData = function() {
        if (!this.cropper) {
            return;
        }
        
        var canvas = this.cropper.getCroppedCanvas({
            width: this.settings.width,
            height: this.settings.height,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });
        
        if (!canvas) {
            return;
        }
        
        var croppedData = this.cropper.getData();
        this.updateDimensions(
            Math.round(croppedData.width),
            Math.round(croppedData.height)
        );
        
        // Convert to blob and upload
        var self = this;
        canvas.toBlob(function(blob) {
            self.uploadCroppedImage(blob, canvas);
        }, 'image/jpeg', this.settings.quality / 100);
    };

    ImageCropperEditor.prototype.uploadCroppedImage = function(blob, canvas) {
        var self = this;
        
        // Create thumbnail canvas
        var thumbCanvas = document.createElement('canvas');
        thumbCanvas.width = this.settings.thumb_width;
        thumbCanvas.height = this.settings.thumb_height;
        var thumbCtx = thumbCanvas.getContext('2d');
        thumbCtx.drawImage(canvas, 0, 0, this.settings.thumb_width, this.settings.thumb_height);
        
        thumbCanvas.toBlob(function(thumbBlob) {
            // Upload both images to server
            self.uploadToServer(blob, thumbBlob, canvas, thumbCanvas);
        }, 'image/jpeg', this.settings.quality / 100);
    };

    ImageCropperEditor.prototype.uploadToServer = function(croppedBlob, thumbBlob, croppedCanvas, thumbCanvas) {
        var self = this;
        
        // In Bitrix, we'll use AJAX to upload to a temporary location
        // For now, we'll store as base64 (in real implementation, use proper upload)
        var croppedUrl = croppedCanvas.toDataURL('image/jpeg', this.settings.quality / 100);
        var thumbUrl = thumbCanvas.toDataURL('image/jpeg', this.settings.quality / 100);
        
        var cropData = this.cropper.getData();
        
        var imageData = {
            url: croppedUrl,
            thumb_url: thumbUrl,
            width: this.settings.width,
            height: this.settings.height,
            thumb_width: this.settings.thumb_width,
            thumb_height: this.settings.thumb_height,
            crop: {
                x: Math.round(cropData.x),
                y: Math.round(cropData.y),
                width: Math.round(cropData.width),
                height: Math.round(cropData.height),
                rotate: cropData.rotate || 0,
                scaleX: cropData.scaleX || 1,
                scaleY: cropData.scaleY || 1
            },
            original_width: this.currentImage ? this.currentImage.naturalWidth : 0,
            original_height: this.currentImage ? this.currentImage.naturalHeight : 0,
            timestamp: new Date().getTime()
        };
        
        this.updateData(imageData);
        this.updateThumbPreview(thumbUrl);
    };

    ImageCropperEditor.prototype.updateData = function(imageData) {
        this.dataInput.value = JSON.stringify(imageData);
    };

    ImageCropperEditor.prototype.updateDimensions = function(width, height) {
        if (this.dimWidth) {
            this.dimWidth.textContent = width;
        }
        if (this.dimHeight) {
            this.dimHeight.textContent = height;
        }
    };

    ImageCropperEditor.prototype.updateThumbPreview = function(url) {
        if (this.thumbPreview) {
            this.thumbPreview.innerHTML = '<img src="' + url + '" alt="" style="max-width: 100%; max-height: 100%;">';
        }
    };

    ImageCropperEditor.prototype.rotate = function(degree) {
        if (this.cropper) {
            this.cropper.rotate(degree);
            this.updateCropData();
        }
    };

    ImageCropperEditor.prototype.flip = function(direction) {
        if (this.cropper) {
            if (direction === 'h') {
                var scaleX = this.cropper.getData().scaleX || 1;
                this.cropper.scaleX(-scaleX);
            } else if (direction === 'v') {
                var scaleY = this.cropper.getData().scaleY || 1;
                this.cropper.scaleY(-scaleY);
            }
            this.updateCropData();
        }
    };

    ImageCropperEditor.prototype.reset = function() {
        if (this.cropper) {
            this.cropper.reset();
            this.updateCropData();
        }
    };

    ImageCropperEditor.prototype.remove = function() {
        if (this.cropper) {
            this.cropper.destroy();
            this.cropper = null;
        }
        
        this.currentImage = null;
        this.mainImage.src = '';
        this.previewContainer.style.display = 'none';
        this.fileInput.value = '';
        this.dataInput.value = '';
        
        if (this.dimWidth) {
            this.dimWidth.textContent = '0';
        }
        if (this.dimHeight) {
            this.dimHeight.textContent = '0';
        }
        if (this.thumbPreview) {
            this.thumbPreview.innerHTML = '';
        }
    };

    return ImageCropperEditor;
})();

// Initialize on DOM ready
if (typeof BX !== 'undefined' && BX.ready) {
    BX.ready(function() {
        // Auto-initialize is handled by PHP template
    });
}
