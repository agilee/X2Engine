/*****************************************************************************************
 * X2Engine Open Source Edition is a customer relationship management program developed by
 * X2Engine, Inc. Copyright (C) 2011-2014 X2Engine Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY X2ENGINE, X2ENGINE DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 * 
 * You can contact X2Engine, Inc. P.O. Box 66752, Scotts Valley,
 * California 95067, USA. or at email address contact@x2engine.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * X2Engine" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by X2Engine".
 *****************************************************************************************/

if (!auxlib) var auxlib = {};
auxlib.DEBUG = true;

auxlib.error = function (message) {
    if (auxlib.DEBUG && x2.DEBUG) console.log ('Error: ' + message);
};

// display error message in red after prevElem
auxlib.createErrorFeedbackBox = function (argsDict) {
    var prevElem = argsDict['prevElem']; // required
    var message = argsDict['message']; // required
    var classes = argsDict['classes'];
    classes = typeof classes === 'undefined' ? [] : classes;

    var feedbackBox = $('<div>', {'class': 'auxlib-error-msg-container'}).append (
        $("<span>", { 
            'class': "auxlib-error-box-msg",
            'text': message
        })
    );
    for (var i in classes) {
        $(feedbackBox).addClass (classes[i]);
    }
    $(prevElem).after (feedbackBox);
}

// delete error message created with createErrorFeedbackBox
auxlib.destroyErrorFeedbackBox = function (prevElem) {
    $(prevElem).next ('.auxlib-error-msg-container').remove ();
}

/*
Creates a feedback box div containing the specified message. The feedback box is
placed in the dom after the specified previous element. The box is faded out and,
after a specified delay, is removed.
Parameters:
    argsDict - a dictionary containing arguments
        prevElement (required) - the element after which the box will get placed
        message (required) - a string
        delay - the time after which the box will get removed
        disableButton - a button which will be disabled until the feedback box fades out
        classes - an array. css classes which will be added to the feedback box
*/
auxlib.createReqFeedbackBox = function (argsDict) {
    var prevElem = argsDict['prevElem']; // required
    var message = argsDict['message']; // required
    var delay = argsDict['delay'];
    var classes = argsDict['classes'];
    var disableButton = argsDict['disableButton'];
    classes = typeof classes === 'undefined' ? [] : classes;
    delay = typeof delay === 'undefined' ? 2000 : delay;
    disableButton = typeof disableButton === 'undefined' ? prevElem : disableButton;

    if ((disableButton).attr ('disabled')) return;
    $(disableButton).attr ('disabled', 'disabled');

    var feedbackBox = $('<div>', {'class': 'feedback-container'}).append (
        $("<span>", { 
            'class': "feedback-msg",
            'text': message
        })
    );
    for (var i in classes) {
        $(feedbackBox).addClass (classes[i]);
    }
    $(prevElem).after (feedbackBox);
    auxlib._startFeedbackBoxFadeOut (feedbackBox, delay, prevElem, disableButton);
    return feedbackBox;
}


/*
Private function.
Removes a feedback box created by createReqFeedbackBox () after a specified delay.
Specified button will be disabled until delay elapses.
Parameters:
    feedbackBox - a jQuery element created by createReqFeedbackBox ()
    delay - in milliseconds
*/
auxlib._startFeedbackBoxFadeOut = function (feedbackBox, delay, button, disableButton) {
    $(feedbackBox).children ().fadeOut (delay, function () {
        $(feedbackBox).remove ();
        $(disableButton).removeAttr ('disabled');
    });
}


/*
Returns true if parent element has an error box, false otherwise.
*/
auxlib.errorBoxExists = function (parentElem) {
    return ($(parentElem).find ('.error-summary-container').length > 0);
};



/*
Removes an error div created by createErrorBox ().  
Parameters:
	parentElem - a jQuery element which contains the error div
*/
auxlib.destroyErrorBox = function (parentElem) {
	var $errorBox = $(parentElem).find ('.error-summary-container');
	if ($errorBox.length !== 0) {
		$errorBox.remove ();
	}
}

/*
Returns a jQuery element corresponding to an error box. The error box will
contain the specified errorHeader and a bulleted list of the specified error
messages.
Parameters:
	errorHeader - a string
	errorMessages - an array of strings
*/
auxlib.createErrorBox = function (errorHeader, errorMessages) {
	var errorBox = $('<div>', {'class': 'error-summary-container'}).append (
		$("<div>", { 'class': "error-summary"}).append (
			$("<p>", { text: errorHeader }),
			$("<ul>")
	));
	for (var i in errorMessages) {
		var msg = errorMessages[i];
		$(errorBox).find ('.error-summary').
			find ('ul').append ($("<li> " + msg + " </li>"));
	}
	return errorBox;
}




/*
Select an option from a select element
Parameters:
	selector - a jquery selector for the select element
	setting - the value of the option to be selected
*/
auxlib.selectOptionFromSelector = function (selector, setting) {
    if (!$(selector).children ('[value="' + setting + '"]').length) return;
	$(selector).children (':selected').removeAttr ('selected');
	$(selector).children ('[value="' + setting + '"]').attr ('selected', 'selected');
    $(selector).val (setting).change ();
}


/*
Set object properties. Default property values are used where an expected property value 
is not defined.
*/
auxlib.applyArgs = function (obj, defaultArgs, args) {
	for (var i in defaultArgs) {
		if (typeof args[i] === 'undefined') {
			obj[i] = defaultArgs[i];
		} else {
			obj[i] = args[i];
		}
	}
}

/**
 * Calls callback when user clicks outside of elem
 * @param object elem jQuery element(s)
 * @param function callback 
 * @param boolean one if true, event handler will be bound until user clicks outside element
 */
auxlib.onClickOutside = (function () {
    var i = 0; // used to give events unique ids                               
    return function (elem, callback, one, eventNamespace) {
        var eventNamespace = typeof eventNamespace === 'undefined' ? ++i : eventNamespace; 
        var one = typeof one === 'undefined' ?  false : one;          
        var selector = elem.selector;

        var clickCallback = function (evt) {
            // clicked outside if target or target's parents do not match specified elements
            if ($.inArray ($(evt.target)[0], $(elem)) === -1 && 
                $(evt.target).closest (selector).length === 0) {

                callback.call (elem);
                return true;
            } 
            return false;
        };
        var evtName = 'click.onClickOutside' + eventNamespace;
        $("body").unbind (evtName);
        if (one) {
            $("body").one (evtName, function (evt) {
                if (!clickCallback (evt)) { // didn't click outside, rebind
                    auxlib.onClickOutside (elem, callback, one, eventNamespace);
                }
            });
        } else {
            $("body").bind ('click.onClickOutside' + eventNamespace, clickCallback);
        }
        return evtName; 
    };
}) ();

auxlib.makeDialogClosableWithOutsideClick = function (dialogElem) {
    $("body").on ('click', function (evt) {
        if ($(dialogElem).closest (".ui-dialog").length &&
            $(dialogElem).is (':visible') &&
            $(dialogElem).dialog ("isOpen") &&
            !$(evt.target).is ("a") &&
            !$(evt.target).closest ('.ui-dialog').length) {

            $(dialogElem).dialog ("close");
        }
    });
};


// convert css value in pixels to an int
auxlib.pxToInt = function (str) {
    return parseInt (auxlib.rStripPx (str), 10);
}

// remove trailing 'px'
auxlib.rStripPx = function (str) {
    return str.replace (/px$/, '');
}

/*
Used to replace Object.keys which is not available in ie8
*/
auxlib.keys = function (obj) {
    if (typeof obj !== 'object') return false;
    var keys = []; 
    for (var key in obj) {
        keys.push (key);
    }
    return keys;
};

/*
Used to replace Object.create which is not available in ie8
*/
auxlib.create = function (prototype) {
    function dummyFn () {};
    dummyFn.prototype = prototype;
    return new dummyFn ();
};

/*
Remove cursor from input by focusing on a temporary dummy input element.
*/
auxlib.removeCursorFromInput = function (elem) {
    $(elem).append ($("<input>", {"id": "auxlib-dummy-input"}));
    var x = window.scrollX;
    var y = window.scrollY;
    $("#auxlib-dummy-input").focus ();
    window.scrollTo (x, y); // prevent scroll from focus event
    $("#auxlib-dummy-input").remove ();
};

/*
Returns a JSON string with form field names as keys and corresponding form field values as values
*/
auxlib.formToJSON = function (formElem) {
    var arr = $(formElem).serializeArray ();
    var JSONarr = {};
    var name, value;
    for (var i in arr) {
        name = arr[i]['name'];
        value = arr[i]['value'];
        if (name) JSONarr[name] = value;
    }
    return JSONarr;
};

auxlib.htmlEncode = function (text) {
    return $('<div>', { 'text': text }).html ();
};

auxlib.htmlDecode = function (html) {
    return $('<div>', { 'html': html }).text ();
};

/*
Saves settings as a property of the miscLayoutSettings JSON field of the profile model. This 
should be used to make miscellaneous layout settings persistent.
Parameters:
    settingName - string - must be an existing property name of the JSON field
    settingVal - mixed - the value to which the JSON field property will get set
*/
auxlib.saveMiscLayoutSetting = function (settingName, settingVal) {
    $.ajax ({
        url: auxlib.saveMiscLayoutSettingUrl,
        type: 'POST',
        data: {
            settingName: settingName,
            settingVal: settingVal,
        },
        success: function () {
        }
    });
};

auxlib.validatePhotoFileExt = function (name) {
    var isLegalExtension = false;

    // name is valid
    if (name.match (/.+\..+/)) {
        var extension = name.split('.').pop ().toLowerCase ();

        var legalExtensions = ['png','gif','jpg','jpe','jpeg'];        
        if ($.inArray (extension, legalExtensions) !== -1)
            isLegalExtension = true;
    } 

    return isLegalExtension;
};

/**
 * @param array|object array
 * @return object An object whose keys are the values of array and whose values are the indices
 *  of array
 */
auxlib.flip = function (array) {
    var result = {};
    if (array instanceof Array) {
        var arrLen = array.length;
        for (var i = 0; i < arrLen; ++i) {
            result[array[i]] = i;
        }
    } else {
        for (var i in array) {
            result[array[i]] = i;
        }
    }
    return result;
};

auxlib.filter = function (callback, array) {
    if (array instanceof Array) {
        var newArr = [];
        var arrLen = array.length;
        for (var i = 0; i < arrLen; i++) {
            if (callback (array[i], i, array)) {
                newArr.push (array[i]);
            }
        }
    } else {
        var newObj = {};
        for (var i in array) {
            if (callback (array[i], i, array)) {
                newObj[i] = array[i];
            }
        }
    }
    return newArr;
};

/**
 * Used to map both arrays and objects 
 * @param function callback 
 * @param mixed array array or object 
 * @return mixed
 */
auxlib.map = function (callback, array) {
    if (array instanceof Array) {
        var arrLen = array.length;
        var newArr = [];
        for (var i = 0; i < arrLen; ++i) {
            newArr.push (callback (array[i])); 
        }
        return newArr;
    } else { 
        var newObj = {};
        for (var i in array) {
            newObj[i] = callback(array[i]);
        }
        return newObj;
    }
};

auxlib.sum = function (array) {
    return auxlib.reduce (function (a, b) { return a + b; }, array);
};

auxlib.reduce = function (callback, array) {
    var value = array[0];
    var arrLen = array.length;
    if (arrLen === 1) return value;
    var newArr = [];
    for (var i = 0; i < arrLen - 1; ++i) {
        value = callback (value, array[i + 1], i, array);
    }
    return value;
};


/**
 * "Magic getter" method which caches jQuery objects so they don't have to be
 * looked up a second time from the DOM
 */
auxlib.getElement = (function () {
    var elements = {};
    return function (selector) {
        if(typeof elements[selector] === 'undefined')
            elements[selector] = $(selector);
        return elements[selector];
    };
}) ();

auxlib.classToSelector = function (classStr) {
    return '.' + classStr.split (' ').join ('.');
};

/**
 * Uses the maskMoney jQuery plugin to convert the currency to a number.
 * @param string currencyStr A formatted curency string
 * @param string currency The user's currency setting
 * @return number the currency string converted to a number
 */
auxlib.currencyToNumber = function (currencyStr, currency) {
    var tmp = $('<input>', {
        id: 'auxlib-tmp-value-input',
        style: 'display: none;'
    });
    $('body').append (tmp);

    var number = $(tmp).val (currencyStr).maskMoney (x2.currencyInfo).maskMoney ('unmasked')[0];
    $(tmp).remove ();
    return number;
};

/**
 * Uses the maskMoney jQuery plugin to format the number as a currency string
 * @param number 
 * @param string currency The user's currency setting
 * @return string the formatted currency string 
 */
auxlib.numberToCurrency = function (number, currency) {
    var tmp = $('<input>', {
        id: 'auxlib-tmp-value-input',
        style: 'display: none;'
    });
    $('body').append (tmp);
    number = number.toFixed (2);

    var str = 
        $(tmp).val (number).maskMoney (x2.currencyInfo).maskMoney ('mask').val ()
    $(tmp).remove ();

    return str;
};

auxlib.assert = function (conditional, str) {
    if (!x2.DEBUG) return;
    if (console.assert) {
        /**/console.assert (conditional, str);
    } else {
        if (!conditional) {
            throw new Error (str);
        }
    }
}

auxlib.trace = function () { 
    if (!x2.DEBUG) return;
    if (console.trace) {
        /**/console.trace ();
    }
};

auxlib.getUnselected = function (elem) {
    return auxlib.map (function (a) {
        return $(a).val ();
    },$.makeArray ($(elem).children ().not (':selected')));
};


$(function () {

    /**
     * Sets up fixed y behavior. By setting the class of an element to fix-y and giving an attribute
     * data-fix-y set to some number, the element will be fixed the specified offset from the top
     * of the screen.
     */
    $('.fix-y').each (function () {
        var y = parseInt ($(this).attr ('data-fix-y'), 10);
        var that = this;
        $(window).scroll (function () {
            //console.log ('break'); 
            //console.log ($(that).css ('display'));
            $(that).css ({
                top: $(window).scrollTop () + y
            });
        });
    });
});

