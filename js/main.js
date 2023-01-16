
$(function(){
    $('a, button').click(function() {
        $(this).toggleClass('active');
    });

    $('#infoSkolacek').on('click', function (){
        $('#infoSkolacek').hide();
    });
});
