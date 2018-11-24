(function($){

    ImgixComponents.ImgixColor=function(delegate, container)
    {
        this.color;
        this.opacity;
        this.hasOpacity = false;

        this.delegate=delegate;
        this.container=container;

        this.type=container.data('param-type');
        this.resetButton=container.find('.imgix-param-reset > a');
        this.param=container.data('param');
        this.defaultValue=container.data('default-value');

        var colorPickerRef=this;

        var minicolor = null;

        if (this.type=='blend-color') {
            this.blendParam=container.data('blend-param');
            this.blendSelect = container.find('.imgix-param-blend');

            var currentBlend=container.data('blend-value');
            this.blendSelect.val(currentBlend);

            this.blendSelect.on('change',function(){
                colorPickerRef.delegate.preview();
            });
        } else {
            this.blendSelect = null;
        }



        container.find('.ilab-color-input').each(function(){
            colorPickerRef.hasOpacity = (($(this).data('opacity') != null) && ($(this).data('opacity') !== false));

            colorPickerRef.color = $(this).val().replace('#', '');
            colorPickerRef.opacity = (colorPickerRef.hasOpacity) ? $(this).data('opacity') : 0;

            $(this).minicolors({
                format: 'hex',
                position: 'bottom right',
                opacity: colorPickerRef.hasOpacity ? "'"+$(this).data('opacity')+"'" : false,
                change:function(newColor, newOpacity) {
                    var oldOpacity = colorPickerRef.opacity;

                    colorPickerRef.color = newColor.replace('#', '');
                    colorPickerRef.opacity = newOpacity;

                    if (colorPickerRef.hasOpacity) {
                        if ((colorPickerRef.opacity > 0) || (oldOpacity != colorPickerRef.opacity)) {
                            colorPickerRef.delegate.preview();
                        }
                    } else {
                        colorPickerRef.delegate.preview();
                    }
                }
            });

            colorPickerRef.minicolor = $(this);
        });

        this.resetButton.on('click',function(e){
            e.preventDefault();
            colorPickerRef.reset();
            return false;
        });
    };

    ImgixComponents.ImgixColor.prototype.destroy=function() {
        if (this.type=='blend-color') {
            this.blendSelect.off('change');
        }
        this.resetButton.off('click');
    };

    ImgixComponents.ImgixColor.prototype.reset=function(data) {
        var blend='none';
        var val;

        if ((data !== undefined) && data.hasOwnProperty(this.blendParam))
        {
            blend=data[this.blendParam];
        }

        if ((data !== undefined) && data.hasOwnProperty(this.param))
        {
            val=data[this.param];
        }
        else
            val=this.defaultValue;

        val=val.replace('#','');
        if (val.length==8)
        {
            this.opacity=parseInt('0x'+val.substring(0,2))/255.0;
            val = val.substring(2);
        }

        this.color = val;
        this.minicolor.minicolors('value', {color:'#'+this.color, opacity: this.opacity });

        if (this.type=='blend-color') {
            this.blendSelect.val(blend);
        }

        this.delegate.preview();
    };

    ImgixComponents.ImgixColor.prototype.saveValue=function(data) {
        if (this.hasOpacity) {
            if (this.opacity > 0) {
                data[this.param] = '#'+ImgixComponents.utilities.byteToHex(Math.round(parseFloat(this.opacity) * 255.0))+this.color;
                if (this.type == 'blend-color') {
                    if (this.blendSelect && (this.blendSelect.val() != 'none')) {
                        data[this.blendParam] = this.blendSelect.val();
                    }
                }
            }
        } else {
            data[this.param] = '#'+this.color;
        }

        return data;
    };

}(jQuery));
