$(function () {
    var root = $(".notice-md3-shell");
    var items = $(".notice-md3-section, .notice-md3-field");

    if (!root.length && !items.length) {
        return;
    }

    root.find(".notice-md3-hero__desc").remove();

    $(".error.message").each(function () {
        $(this).closest(".notice-md3-field, .notice-md3-section").addClass("mdui-panel-item-open");
    });

});
