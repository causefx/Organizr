function emulateTransitionEnd($el, duration) {
    var called = false;

    $el.one(Support.transition.end, function () {
        called = true;
    });
    var callback = function () {
        if (!called) {
            $el.trigger( Support.transition.end );
        }
    }
    setTimeout(callback, duration);
}