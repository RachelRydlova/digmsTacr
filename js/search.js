/**
 * Univerzalni live search hledadni
 * v HTML: input#liveSearch
 * HTML Wrapper#liveSearchWrapper
 * HTML prohledavana položka .ls-item
 * v položce prohledavanace uzly .ls-source
 */

$('#liveSearch').on('keyup', function (e) {
    let code = e.keyCode || e.which;
    if (code == 13) {
        e.preventDefault();
    }
    let input = $(this);
    let wrapper = $('#liveSearchWrapper');
    liveSearchIn(input, wrapper);
});
$('#liveSearch').on('keypress', function (e) {
    let code = e.keyCode || e.which;
    if (code == 13) {
        e.preventDefault();
    }
});

// Search
function liveSearchIn(input, wrapper) {
    // Declare variables
    console.log('test');
    let filter, cards, sources, i;

    filter = input.val().toUpperCase();
    cards = wrapper.find('.ls-item');

    // Loop through all table rows, and hide those who don't match the search query
    for (i = 0; i < cards.length; i++) {
        let display = false;
        sources = $(cards[i]).find('.ls-source');
        sources.each(function () {
            $(this).unmark();
            let lsSource = $(this);

            if (lsSource.html().toUpperCase().indexOf(filter) > -1) {
                display = true;
                cards[i].style.display = "";
                $(lsSource).unmark({
                    done: function() {
                        $(lsSource).mark(filter, {
                            'element':'span',
                            'className':'mark'
                        });
                    }
                });
            }
        });
        if (!display) {
            cards[i].style.display = "none";
        }
    }
}
