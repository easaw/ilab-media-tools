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
