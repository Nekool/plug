/**
 * Created by yann.pedron on 27/10/2017.
 */
$(document).ready(function() {
    $('#selectAll').click(function (event) {
        event.preventDefault();
        $('input[type="checkbox"]').prop('checked', true);
    });
    $('#selectNone').click(function (event) {
        event.preventDefault();
        $('input[type="checkbox"]').prop('checked', false);
        $('input[id="checkid"]').prop('checked', true);

    });
    jQuery.post(
        console.log("popop")
    );
    })