$(function() {

    var sortStrategies = jsGrid.sortStrategies;


    module("sortStrategies");

    test("string sorting", function() {
        var data = ["c", "a", "d", "b"];

        data.sort(sortStrategies["string"]);

        deepEqual(data, ["a", "b", "c", "d"]);
    });

    test("string sorting should be robust", function() {
        var data = ["a", 1, true, "b"];

        data.sort(sortStrategies["string"]);

        deepEqual(data, [1, "a", "b", true]);
    });

    test("number sorting", function() {
        var data = [5, 3.2, 1e2, 4];

        data.sort(sortStrategies["number"]);

        deepEqual(data, [3.2, 4, 5, 100]);
    });

    test("date sorting", function() {
        var date1 = new Date(2010, 0, 1),
            date2 = new Date(2011, 0, 1),
            date3 = new Date(2012, 0, 1);

        var data = [date2, date3, date1];

        data.sort(sortStrategies["date"]);

        deepEqual(data, [date1, date2, date3]);
    });

    test("numberAsString sorting", function() {
        var data = [".1", "2.1", "4e5", "2"];

        data.sort(sortStrategies["numberAsString"]);

        deepEqual(data, [".1", "2", "2.1", "4e5"]);
    });
});
