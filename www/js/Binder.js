/**
 * Binder - javascript library used for detection of binding data from area to php via ajax request.
 * 
 * Distributed under Beerware licence (https://en.wikipedia.org/wiki/Beerware). Beerware rev.42:
 * <matej@penzion-rataje.eu> wrote this file. As long as you retain this notice you can do whatever you want with this stuff. If we meet some day, and you think this stuff is worth it, you can buy me a beer in return. Poul-Henning Kamp
 * 
 * Usage = After document load, call function Binder(settings) , where settings is object containing default values. Settings object must contain object area, specifying html caller element containing one row.
 * Caller element should contain attributes data-binder-table and data-binder-id, specifying table name and id of record.
 * Caller element may contain save button, specified by its class (default binder-save-btn)
 * Caller element may contain delete button, specified by its class (default binder-delete-btn)
 * Caller element must contain database field elements, containing the data - INPUT, SELECT or TEXTAREA fields. Their name should correspond to its database field name. Each field element should also have attribute data-value containing original value, loaded from database.
 * 
 * Optional settings attribute is saveAllSelector, which can contain selector for specifying a button that saves all visible binder records.
 * 
 * Every element having attribute data-value is being treated as db field element. Whenever its changed, binder checks for all changes in the record (differences between the actual value and data-value attribute). If there is at least one change, binder changes the class of save button from btn-primary (btn-secondary etc. corresponding to Bootstrap v4 class names) to btn-outline-* to mark change saveable.
 * After clicking on save button, data with changed fields are sent via ajax to backend url. Url is taken from save button's href attribute. Object is having properties id, table and changes. Changes property contains key value pairs of changed items in records.
 * After succesfull save, save button class is reinstantized back to normal class.
 * 
 * After clicking on delete button, object, containing just id and table properties is sent to url, specified by delete button's href attribute. After succesfull ajax request, whole binder area gets deleted.
 */


window.onload = function(e){ 
    if (!window.jQuery) {
        throw "Jquery is neccessary to run Binder!";
    }
};

function Binder (settings) {
    this.area = $(settings.area);
    this.TABLE_NAME_ATTRIBUTE = !settings.tableNameAttribute ? "data-binder-table" : settings.tableNameAttribute;
    this.RECORD_ID_ATTRIBUTE = !settings.recordIdAttribute ? "data-binder-id" : settings.recordIdAttribute;
    this.ORIGINAL_VALUE_ATTRIBUTE = !settings.originalValueAttribute ? "data-value" : settings.originalValueAttribute;
    this.DBFIELD_NAME_ATTRIBUTE = !settings.dbFieldNameAttribute ? "name" : settings.dbFieldNameAttribute;
    this.SPINNER_CLASS = !settings.spinClass ? "fa-spin" : settings.spinClass;
    this.SAVE_BTN_CLASS = !settings.saveBtnClass ? "binder-save-btn" : settings.saveBtnClass;
    this.DELETE_BTN_CLASS = !settings.deleteBtnClass ? "binder-delete-btn" : settings.deleteBtnClass;
    this.BUTTON_CHECKED_CLASS = !settings.checkedBtnClass ? "active" : settings.checkedBtnClass;
    this.SAVE_ALL_SELECTOR = !settings.saveAllSelector;
    this.isValid = !settings.isValid ? true : settings.isValid;
    this.changed = false;
    this.bindChangeEvents();
    this.bindSaveEvent();
    this.bindSaveAllEvent();
    this.bindDeleteEvent();
}

Binder.prototype.bindChangeEvents = function () {
    var binderObj = this;
    var targets = binderObj.area.find("[" + this.ORIGINAL_VALUE_ATTRIBUTE + "]");
    if (targets.length > 0) {
        targets.each(function () {
            if ($(this).prop("tagName") === "BUTTON") {
                $(this).off("click");
                $(this).click(function(){
                    binderObj.getChanges();
                    binderObj.saveButtonState(binderObj.changed);
                });
            } else {
                $(this).off("change");
                $(this).change(function(){
                    binderObj.getChanges();
                    binderObj.saveButtonState(binderObj.changed);
                });
            }
        });
    }
};

Binder.prototype.bindSaveEvent = function () {
    var binderObj = this;
    var targets = binderObj.area.find("." + this.SAVE_BTN_CLASS);
    if (targets.length > 0) {
        targets.each(function () {
            $(this).off("click");
            $(this).click(function () {
                binderObj.extractBind();
                binderObj.save($(this));
            });
        });
    }
};

Binder.prototype.bindSaveAllEvent = function () {
    var binderObj = this;
    var targets = binderObj.area.find(this.SAVE_ALL_SELECTOR);
    if (targets.length > 0) {
        targets.each(function () {
            $(this).click(function () {
                binderObj.extractBind();
                binderObj.save($(this));
            });
        });
    }
};

Binder.prototype.bindDeleteEvent = function () {
    var binderObj = this;
    var targets = binderObj.area.find("." + this.DELETE_BTN_CLASS);
    if (targets.length > 0) {
        targets.each(function () {
            $(this).off("click");
            $(this).click(function () {
                binderObj.extractBind();
                binderObj.delete($(this));
            });
        });
    }
};

Binder.prototype.save = function(caller){
    if(typeof this.area == "undefined")
        throw "Binder performing error - undefined area!";
    if(typeof this.bind == "undefined")
        throw "Binder performig error - undefined binding object!";
    var binderObj = this;
    if (!($.isEmptyObject(binderObj.bind.changes) > 0)) {
        binderObj.disableBtn(caller, true, true);
        $.nette.ajax({
            url: caller.attr("href"),
            method: 'POST',
            data: binderObj.bind,
            complete: function (payload) {
                binderObj.disableBtn(caller, false);
                binderObj.commit();
            }
        });
    }
};

Binder.prototype.delete = function(caller){
    if(typeof this.area == "undefined")
        throw "Binder performing error - undefined area!";
    if(typeof this.bind == "undefined")
        throw "Binder performig error - undefined binding object!";
    var binderObj = this;
    binderObj.disableBtn(caller, true, true);
    $.nette.ajax({
        url: caller.attr("href"),
        method: 'POST',
        data: binderObj.bind,
        complete: function (payload) {
            binderObj.area.remove();
        }
    });
};

Binder.prototype.commit = function () {
    var binderObj = this;
    var targets = binderObj.area.find("[" + binderObj.ORIGINAL_VALUE_ATTRIBUTE + "]");
    if (targets.length > 0) {
        targets.each(function () {
            $(this).attr(binderObj.ORIGINAL_VALUE_ATTRIBUTE, binderObj.getValue($(this)));
        });
    }
    binderObj.saveButtonState(false);
    this.changed = false;
};

Binder.prototype.saveButtonState = function (commitPending){
    var binderObj = this;
    var targets = binderObj.area.find("." + this.SAVE_BTN_CLASS);
    if (targets.length > 0) {
        if (commitPending) {
            var cls = null;
            if (targets.hasClass("btn-primary"))
                cls = "btn-primary";
            if (targets.hasClass("btn-secondary"))
                cls = "btn-secondary";
            if (targets.hasClass("btn-success"))
                cls = "btn-success";
            if (targets.hasClass("btn-danger"))
                cls = "btn-danger";
            if (targets.hasClass("btn-warning"))
                cls = "btn-warning";
            if (targets.hasClass("btn-info"))
                cls = "btn-info";
            if (targets.hasClass("btn-light"))
                cls = "btn-light";
            if (targets.hasClass("btn-dark"))
                cls = "btn-dark";
            if (cls === null)
                return;
            var newCls = cls.replace("btn-", "btn-outline-");
            targets.removeClass(cls);
            targets.addClass(newCls);
        } else {
            var classList = targets.attr("class").split(" ");
            for (var cls in classList) {
                if (classList[cls].indexOf("btn-outline") > -1) {
                    var newCls = classList[cls].replace("outline-", "");
                    targets.removeClass(classList[cls]);
                    targets.addClass(newCls);
                    break;
                }
            }
        }
    }
};

Binder.prototype.extractBind = function() {
    var obj = {};
    var objId = this.area.attr(this.RECORD_ID_ATTRIBUTE);
    var tableName = this.area.attr(this.TABLE_NAME_ATTRIBUTE);
    var changes = this.getChanges();
    obj.id = objId;
    obj.table = tableName;
    obj.changes = changes;
    this.bind  = obj;
};

Binder.prototype.getChanges = function () {
    var values = {};
    var binderObj = this;
    var targets = binderObj.area.find("[" + binderObj.ORIGINAL_VALUE_ATTRIBUTE + "]");
    if (targets.length > 0) {
        targets.each(function () {
            var tagName = $(this).prop("tagName");
            var name = binderObj.parseNameFromElement($(this));
            var value = binderObj.getValue($(this));
            if (["INPUT", "SELECT", "TEXTAREA", "BUTTON"].indexOf(tagName) > -1) {
                if (binderObj.isChanged($(this))) {
                    values[name] = value;
                }
            } else {
                throw "Unexpected tag " + tagName + " found, containing binded values";
            }
        });
    }
    binderObj.changed = !$.isEmptyObject(values);
    return values;
};

Binder.prototype.getValue = function(element){
    var binderObj = this;
    var bracketIndex = element.attr(binderObj.DBFIELD_NAME_ATTRIBUTE).indexOf("[");
    if(bracketIndex == -1){
        //not part of a group
        return binderObj.parseValueFromElement(element);
    } else {
        return binderObj.parseValueFromGroupOfElements(element);
    }
};

Binder.prototype.parseNameFromElement = function (element){
    var binderObj = this;
    var bracketBegin = element.attr(binderObj.DBFIELD_NAME_ATTRIBUTE).indexOf("[");
    if(bracketBegin == -1){
        return element.attr(binderObj.DBFIELD_NAME_ATTRIBUTE);
    } else {
        return element.attr(binderObj.DBFIELD_NAME_ATTRIBUTE).substr(0, bracketBegin);
    }
}

/**
 * This function checks all elements in the same group. It expects that the elements in group are boolean and returns array of values of all of them
 * @param element
 * @returns array of values
 */
Binder.prototype.parseValueFromGroupOfElements = function (element) {
    var binderObj = this;
    var bracketBegin, bracketEnd, value;
    var groupName = binderObj.parseNameFromElement(element);
    var targets = binderObj.area.find("[" + binderObj.DBFIELD_NAME_ATTRIBUTE + "^=" + groupName + "]");
    var values = [];
    if (targets.length > 0) {
        targets.each(function () {
            bracketBegin = $(this).attr(binderObj.DBFIELD_NAME_ATTRIBUTE).indexOf("[");
            bracketEnd = $(this).attr(binderObj.DBFIELD_NAME_ATTRIBUTE).indexOf("]");
            value = $(this).attr(binderObj.DBFIELD_NAME_ATTRIBUTE).substr(bracketBegin + 1, bracketEnd - bracketBegin - 1);
            if(binderObj.parseValueFromElement($(this)))
                values.push(value);
        });
    }
    return values;
};

Binder.prototype.parseValueFromElement = function(element){
    if(element.is(":checkbox")) return element.is(":checked");
    if(element.prop("tagName") == "BUTTON") return element.hasClass(this.BUTTON_CHECKED_CLASS);
    return element.val();
}

Binder.prototype.isChanged = function (element){
    var binderObj = this;
    var changed = element.attr(binderObj.ORIGINAL_VALUE_ATTRIBUTE) != this.getValue(element);
    if(changed){
        binderObj.validate(element);
    }
    return changed;
};

Binder.prototype.disableBtn = function (btn, disable, spin = false) {
    if (btn.length > 0) {
        if (disable) {
            if(spin){
                btn.find("i.fa").addClass(this.SPINNER_CLASS);
            }
            btn.prop("disabled", true);
            btn.attr("disabled", "disabled");
        } else {
            btn.find("i.fa").removeClass(this.SPINNER_CLASS);
            btn.prop("disabled", false);
            btn.removeAttr("disabled");
        }
    }
};

Binder.prototype.validate = function(element, value2 = null) {
    var binderObj = this;
    var valid = true;
    if(typeof(binderObj.isValid) == "boolean"){
        valid = binderObj.isValid;
    } else {
        var name = binderObj.parseNameFromElement(element);
        var value = this.getValue(element);
        valid = binderObj.isValid(name, value, value2);
    }

    if (!valid) {
        element.addClass("is-invalid");
        throw "Validation error for field " + name;
    } else {
        element.removeClass("is-invalid");
    }
};