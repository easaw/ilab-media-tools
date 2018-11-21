(function($){
    $.fn.ilabTabs=function(options){
        var settings= $.extend({},options);

        var lastTabWidth = null;
        var tabsVisible = true;

        return this.each(function(){
            var container=$(this);
            var windowContainer = container;

            var parentContainer = container.parent();
            while(true) {
                if (parentContainer.hasClass('ilabm-container')) {
                    windowContainer = parentContainer;
                    break;
                }

                parentContainer = parentContainer.parent();
                if (!parentContainer) {
                    break;
                }
            }

            var sidebar = parentContainer.find('.ilabm-sidebar');

            var selectContainer = container.find('.ilabm-tabs-select-ui');

            var tabsContainer = container.find('.ilabm-tabs-ui');
            var tabs=tabsContainer.find('.ilabm-editor-tab');

            var label=selectContainer.find('.ilabm-tabs-select-label');
            if (label && settings.hasOwnProperty('label')) {
                label.text(settings.label);
            }

            tabs.removeClass('active-tab');
            tabs.on('click',function(e){
                e.preventDefault();

                tabs.removeClass('active-tab');
                var tab=$(this);
                tab.addClass('active-tab');

                if (select)
                    select.val(tab.data('value'));

                settings.currentValue=tab.data('value');

                if (settings.hasOwnProperty('tabSelected'))
                    settings.tabSelected(tab);

                return false;
            });

            var select=selectContainer.find('.ilabm-tabs-select');
            if (select)  {
                select.on('change',function(){
                    tabs.removeClass('active-tab');
                    tabs.each(function(){
                        var tab=$(this);
                        if (tab.data('value')==select.val())
                            tab.addClass('active-tab');
                    });
                    var option=select.find(":selected");
                    if (settings.hasOwnProperty('tabSelected'))
                        settings.tabSelected(option);
                });
            }

            if (settings.hasOwnProperty('currentValue'))
            {
                if (select) {
                    select.val(settings.currentValue);
                }

                tabs.each(function(){
                   var tab=$(this);
                    if (tab.data('value')==settings.currentValue)
                        tab.addClass('active-tab');
                });
            }

            var checkOverflow=function(){
                if (lastTabWidth == null) {
                    lastTabWidth = tabsContainer.width();
                }

                if (sidebar.width() + lastTabWidth > windowContainer.width()) {
                    if (tabsVisible) {
                        tabsVisible = false;
                        selectContainer.show();
                        tabsContainer.hide();
                    }
                } else {
                    if (!tabsVisible) {
                        tabsVisible = true;
                        selectContainer.hide();
                        tabsContainer.show();
                    }
                }
            };

            $(window).on('resize',checkOverflow);

            selectContainer.hide();
            checkOverflow();
        });
    };

}(jQuery));

/**
 * Created by jong on 8/1/15.
 */

var ILabModal=(function(){
    var _dirty=false;
    var _data={};

    var cancel=function(){
        jQuery('.ilabm-backdrop').remove();
    };

    var makeDirty=function(){
        _dirty=true;
    };

    var isDirty=function(){
        return _dirty;
    };

    var makeClean=function(){
        _dirty=false;
    };

    var loadURL=function(url,partial,partialCallback){
        if (_dirty)
        {
            if (!confirm('You\'ve made changes, continuing will lose them.\n\nContinue?'))
                return false;
        }

        _dirty=false;

        jQuery.get(url, function(data) {
            if (partial) {
                partialCallback(data);
                //jQuery('#ilabm-container').remove();
                //jQuery('body').append(data);
                //jQuery('#ilabm-window-area').unbind().html('').append(data);
            } else {
                jQuery('body').append(data);
            }
        });
    };

    return {
        cancel: cancel,
        makeDirty:makeDirty,
        isDirty:isDirty,
        makeClean:makeClean,
        loadURL:loadURL
    };
})();

jQuery(document).ready(function($){
    $(document).on('click', 'a.ilab-thickbox', function(e) {
        e.preventDefault();
        var currEl = $(this);
        var partial=currEl.hasClass('ilab-thickbox-partial');

        ILabModal.loadURL(currEl.attr('href'),partial,null);

        return false;
    });
});
//# sourceMappingURL=ilab-modal.js.map
