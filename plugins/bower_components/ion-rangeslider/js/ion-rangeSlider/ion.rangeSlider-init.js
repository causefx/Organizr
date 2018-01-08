$("#range_01").ionRangeSlider();

$("#range_02").ionRangeSlider({
    min: 100,
    max: 1000,
    from: 550
});
$("#range_03").ionRangeSlider({
    type: "double",
    grid: true,
    min: 0,
    max: 1000,
    from: 200,
    to: 800,
    prefix: "$"
});
$("#range_04").ionRangeSlider({
    type: "double",
    grid: true,
    min: -1000,
    max: 1000,
    from: -500,
    to: 500
});
$("#range_16").ionRangeSlider({
    grid: true,
    min: 18,
    max: 70,
    from: 30,
    prefix: "Age ",
    max_postfix: "+"
});
$("#range_18").ionRangeSlider({
    type: "double",
    min: 100,
    max: 200,
    from: 145,
    to: 155,
    prefix: "Weight: ",
    postfix: " million pounds",
    decorate_both: false
});
$("#range_22").ionRangeSlider({
    type: "double",
    min: 1000,
    max: 2000,
    from: 1200,
    to: 1800,
    hide_min_max: true,
    hide_from_to: true,
    grid: true
});

