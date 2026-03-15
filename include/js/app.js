$(function () {
    $('.confirm-delete').on('click', function (e) {
        if (!confirm('Ar tikrai norite ištrinti?')) {
            e.preventDefault();
        }
    });
});
