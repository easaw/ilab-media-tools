/**
 * Created by jong on 7/29/15.
 */

var ILabCrop=function($,settings){
    this.settings=settings;
    this.modalContainer=$('#ilabm-container-'+settings.modal_id);
    this.cropper=this.modalContainer.find('.ilabc-cropper');
    this.cropperData={};
    this.modal_id=settings.modal_id;

    var resizeTimerId;
    var isResizing=false;

    var didResize = false;
    var hadResized = false;

    this.modalContainer.find('.ilabm-editor-tabs').ilabTabs({
        currentValue: this.settings.size,
        tabSelected:function(tab){
            ILabModal.loadURL(tab.data('url'),true,function(response){
                this.bindUI(response);
            }.bind(this));
        }.bind(this)
    });

    $(window).resize(function() {
        didResize = true;
    });

    this.animFrame = function() {
        if (didResize) {
            this.updatePreviewWidth();
            clearTimeout(resizeTimerId);
            resizeTimerId = setTimeout(function(){
                this.bindUI(this.settings);
            }.bind(this), 125);
            hadResized = true;
        } else if (hadResized) {
            data=this.cropper.cropper('getData');
            this.settings.prev_crop_x=data.x;
            this.settings.prev_crop_y=data.y;
            this.settings.prev_crop_width=data.width;
            this.settings.prev_crop_height=data.height;
        }

        didResize = false;
        hadResized = false;

        requestAnimationFrame(this.animFrame);
    }.bind(this);

    requestAnimationFrame(this.animFrame);


    this.modalContainer.find('.ilabc-button-crop').on('click',function(e){
        e.preventDefault();
        this.crop();
        return false;
    }.bind(this));

    this.updatePreviewWidth=function() {
        var width =  this.modalContainer.find('.ilab-crop-preview-title').width();
        this.modalContainer.find('.ilab-crop-preview').css({
            'height' : (width / this.settings.aspect_ratio) + 'px',
            'width' : width + 'px'
        });
    }.bind(this);

    this.bindUI=function(settings){
        this.settings=settings;

        this.cropper.cropper('destroy');
        this.cropper.off('built.cropper');

        if (settings.hasOwnProperty('cropped_src') && settings.cropped_src !== null)
        {
            this.modalContainer.find('.ilab-current-crop-img').attr('src',settings.cropped_src);
        }

        if (settings.hasOwnProperty('size_title') && (settings.size_title !== null))
        {
            this.modalContainer.find('.ilabc-crop-size-title').text("Current "+settings.size_title+" ("+settings.min_width+" x "+settings.min_height+")");
        }

        if (typeof settings.aspect_ratio !== 'undefined')
        {
            this.updatePreviewWidth();

            if ((typeof settings.prev_crop_x !== 'undefined') && (settings.prev_crop_x !== null)) {
                this.cropperData = {
                    x : settings.prev_crop_x,
                    y : settings.prev_crop_y,
                    width : settings.prev_crop_width,
                    height : settings.prev_crop_height
                };
            }

            this.cropper.on('built.cropper',function(){
                this.updatePreviewWidth();
            }.bind(this)).on('crop.cropper',function(e){
                //console.log(e.x, e.y, e.width, e.height);
            }).cropper({
                viewMode: 1,
                aspectRatio : settings.aspect_ratio,
                minWidth : settings.min_width,
                minHeight : settings.min_height,
                modal : true,
                zoomable: false,
                mouseWheelZoom: false,
                dragCrop: false,
                autoCropArea: 1,
                movable: false,
                data : this.cropperData,
                checkImageOrigin: false,
                checkCrossOrigin: false,
                responsive: true,
                preview: '#ilabm-container-'+this.modal_id+' .ilab-crop-preview'
            });
        }
    }.bind(this);

    this.crop=function(){
        this.displayStatus('Saving crop ...');

        var data = this.cropper.cropper('getData');
        data['action'] = 'ilab_perform_crop';
        data['post'] = this.settings.image_id;
        data['size'] = this.settings.size;
        jQuery.post(ajaxurl, data, function(response) {
            if (response.status=='ok') {
                this.modalContainer.find('.ilab-current-crop-img').one('load',function(){
                   this.hideStatus();
                }.bind(this));
                this.modalContainer.find('.ilab-current-crop-img').attr('src', response.src);
            }
            else
                this.hideStatus();
        }.bind(this));
    }.bind(this);

    this.displayStatus=function(message){
        this.modalContainer.find('.ilabm-status-label').text(message);
        this.modalContainer.find('.ilabm-status-container').removeClass('is-hidden');
    }.bind(this);

    this.hideStatus=function(){
        this.modalContainer.find('.ilabm-status-container').addClass('is-hidden');
    }.bind(this);

    this.bindUI(settings);
};
