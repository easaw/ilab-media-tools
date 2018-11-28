jQuery(document).ready(function($){
    var noticeContainer = $('.ilab-notification-container');
    if (noticeContainer && (noticeContainer.length > 0)) {
        $('.notice').each(function(){
            $(this).appendTo(noticeContainer);
        });
    }
});