function update(btn, purl) {
    if ($(btn).prop("disabled") || $(btn).hasClass("disabled"))
        return;
    var editedArea = $("DIV.container.settings DIV.card-block");
    var values = {};

    //check for updated INPUT elements
    editedArea.find("INPUT[data-value]").each(function () {
        if ($(this).attr("data-value") != $(this).val()) {
            name = $(this).attr("name");
            value = $(this).is(':checkbox') ? $(this).is(":checked") : $(this).val();

            var valid = false;
            valid = isValid(name, value);

            if (!valid) {
                $(this).parent("TD").addClass("has-danger");
                throw "Validation error for field " + name;
            } else {
                $(this).parent("TD").removeClass("has-danger");
            }

            values[name] = value;
        }
    });
    
        //check for updated TEXTAREA elements
    editedArea.find("TEXTAREA[data-value]").each(function () {
        if ($(this).attr("data-value") != $(this).val()) {
            name = $(this).attr("name");
            value = $(this).val();

            var valid = false;
            valid = isValid(name, value);

            if (!valid) {
                $(this).parent("TD").addClass("has-danger");
                throw "Validation error for field " + name;
            } else {
                $(this).parent("TD").removeClass("has-danger");
            }

            values[name] = value;
        }
    });

    //check for updated SELECT elements
    editedArea.find("SELECT[data-value]").each(function () {
        if ($(this).attr("data-value") != $(this).val() && $(this).val() != "") {
            name = $(this).attr("name");
            value = $(this).val();

            var valid = false;
            valid = isValid(name, value);

            if (!valid) {
                $(this).parent("TD").addClass("has-danger");
                throw "Validation error for field " + name;
            } else {
                $(this).parent("TD").removeClass("has-danger");
            }

            values[name] = value;
        }
    });

    if (!($.isEmptyObject(values) > 0)) {
        $(btn).prop("disabled", true);
        $(btn).attr("disabled", "disabled");
        $.nette.ajax({
            url: purl,
            method: 'POST',
            data: values,
            complete: function (payload) {
                $(btn).prop("disabled", true);
                $(btn).attr("disabled", "disabled");
                $(btn).removeAttr("disabled");
            }
        });
    }
}

function del(btn, purl) {
    if ($(btn).prop("disabled") || $(btn).hasClass("disabled"))
        return;
    $(btn).prop("disabled", true);
    $(btn).attr("disabled", "disabled");
    $.nette.ajax({
        url: purl,
        method: 'POST',
        complete: function (payload) {
            $(btn).prop("disabled", true);
            $(btn).attr("disabled", "disabled");
            $(btn).removeAttr("disabled");
        }
    });
}

function isValid(name, value1, value2 = null) {
    switch (name) {
        case "startTime":
        case "endTime":
        case "closeTime":
            var re = /^(19|20)\d\d-(0[1-9]|1[012])-([012]\d|3[01])T([01]\d|2[0-3]):([0-5]\d)$/;
            return re.test(value1);
        case "link":
            var re = /^(?:(?:https?|ftp):\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$/i;
            return re.test(value1);
    }
    return true;

}

function map(){
    var place=$("INPUT[name='place']").val();
    window.open('https://www.google.com/maps/search/?api=1&query='+encodeURI(place), "_blank");
}

function link(){
    var link=$("INPUT[name='link']").val();
    window.open(link, "_blank");
}