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

/**
 * Set of functions for creating field criteria based on X2Fields data and dynamically loading attributes via AJAX.
 * Assumes the existence of several hidden template elements on the page.
 */
$(function() {
x2.fieldUtils = {

    templates:{
        conditionForm:$("#condition-templates li"),
        workflowStatusConditionForm: $("#workflow-condition-template li"),
        conditionAttrCell:$("#condition-templates > .x2fields-attribute"),
        conditionOpCell:$("#condition-templates > .x2fields-operator"),
        conditionValCell:$("#condition-templates > .x2fields-value")
    },
    
    attributeCache:{},
    
    enableChangedOperator:false,
    
    addChangeListener:function(elem) {
        $(elem)
            .on("change",".x2fields-operator select",function(){ x2.fieldUtils.updateValueCell(this); })
            .on("change",".x2fields-attribute select",function() {
                var fieldset = $(this).closest("fieldset");
                x2.fieldUtils.updateAttrListItem(fieldset,x2.fieldUtils.attributeCache[fieldset.data("modelClass")]);    // getModelAttributes() should already have been called so we can assume this is cached
            });
    
    },
    getModelAttributes:function(modelClass,callback) {
        if(modelClass === "API_params") {
            callback([{type:"API_params"}]);
        } else if(this.attributeCache[modelClass]) {
            callback(this.attributeCache[modelClass]);
        } else {
            $.ajax({
                url:yii.scriptUrl+"/studio/getFields",
                data:{model:modelClass},
                dataType:"json",
                success:function(response) {
                    x2.fieldUtils.attributeCache[modelClass] = response;
                    // console.debug(response);
                    callback(response);
                }
            });
        }
    },
    getOperators:function(fieldType) {
        switch(fieldType) {
            case 'date':
                var ops = ['=','<>','before','after','empty','notEmpty']; break;
            case 'rating':
            case 'currency':
                var ops = ['=','<>','<','>','<=','>=','empty','notEmpty','list','notList']; break;
            case 'boolean':
                var ops = ['=']; break;
            case 'visibility':
                var ops = ['=','list','notList']; break;
            case 'link':
                var ops = ['=','<>','empty','notEmpty']; break;
            case 'dropdown':
            case 'assignment':
                var ops = ['=','<>','empty','notEmpty','list','notList']; break;
            case 'tags':
                var ops = ['list']; break;
            default:    // 'varchar', 'email', 'url', 'text'
                var ops = ['=','<>','<','>','<=','>=','empty','notEmpty','contains','noContains','list','notList'];
        }
        if(this.enableChangedOperator)
            ops.push('changed');
        return ops;
    },
    /** 
     * Parses the value of the provided input. Deals with checkboxes, and 
     */
    getVal:function(elem) {
        if($(elem).attr("type") == "checkbox") {
            return $(elem).is(":checked");
        } else if ($(elem).hasClass ('rich-text')) {
            return $(elem).val ();
        } else {
            return $(elem).val();
        }
    },
    /** 
     * Helper method:
     * Makes an array of [name,label] pairs from the more complex array from by 
     * {@link getModelAttributes()}
     */
    parseAttributeList:function(attributeList) {
        var options = [];
        for(var i in attributeList)
            options.push([attributeList[i].name,attributeList[i].label]);
        return options
    },
    /** 
     * Helper method:
     * Loops through attributes array from {@link getModelAttributes()} and returns the one with 
     * the specified name.
     * Defaults to first attribute.
     */
    getSelectedAttribute:function(attrName,attributeList) {
        var attr = null;
        for(var i in attributeList) {
            if(attributeList[i].name == attrName) {
                attr = attributeList[i];
                break;
            }
        }
        if(attr === null && attributeList.length)
            attr = attributeList[0];
        return attr;
    },
    updateAttrListItem:function(elem,attributeList) {
        var attr = elem.find(".x2fields-attribute select").val();
        var selectedAttribute = {};
        $.extend(selectedAttribute,this.getSelectedAttribute(attr,attributeList));
        
        var operatorCell = elem.find(".x2fields-operator");
        var valueCell = elem.find(".x2fields-value");
        
        if(operatorCell.length)
            operatorCell.replaceWith(this.createOperatorCell(this.getOperators(selectedAttribute.type)));
        if(valueCell.length)
            valueCell.replaceWith(this.createValueCell(selectedAttribute));
        if(operatorCell.length)
            operatorCell.find("input").change();
    },
    createAttrListItem:function(modelClass,attributeList,attr,op,val) {
        var attributeOptions = this.parseAttributeList(attributeList);
        var selectedAttribute = {};
        $.extend(selectedAttribute,this.getSelectedAttribute(attr,attributeList),{value:val});
        
        var li = this.templates.conditionForm.clone();    // clone template condition form
        var fieldset = li.children("fieldset").first();
        fieldset.data("modelClass",modelClass)
        
        var attributeCell = this.createAttributeCell(attributeOptions,attr);
        var operatorCell = this.createOperatorCell(this.getOperators(selectedAttribute.type),op);
        var valueCell = this.createValueCell(selectedAttribute);
        
        attributeCell.appendTo(fieldset);                // add the attribute selector
        if(op !== false)
            operatorCell.appendTo(fieldset)    // add the operator selector (unless we don't want to)
        valueCell.appendTo(fieldset);        // add the value field
        if(op === 'empty' || op === 'notEmpty' || op === 'changed')
            valueCell.hide();
        
        return li;
    },
    createAttributeCell:function(attributeOptions,val) {
        var cell = this.templates.conditionAttrCell.clone();    // clone template cell
        cell.find("select").replaceWith(this.createInput({"type":"dropdown","name":"attribute","options":attributeOptions,"value":val}));
        return cell;
    },
    createOperatorCell:function(operators,val) {
        var cell = this.templates.conditionOpCell.clone();    // clone template cell
        cell.find("select").replaceWith(this.buildOperatorDropdown(operators,val))    // create dropdown
        return cell;
    },
    createValueCell:function(attributes) {
        attributes.name = "value";
        var cell = this.templates.conditionValCell.clone();    // clone template cell
        cell.find("input").replaceWith(this.createInput(attributes));
        return cell;
    },
    /**
     * Alters the value field based on the current operator: hides if operator is "empty" or "not empty", converts to multiselect if "in list" or "not in list"
     */
    updateValueCell:function(elem) {
        var operator = $(elem).val();
        var valueCell = $(elem).closest('fieldset').find('.x2fields-value');
        
        if(operator === 'empty' || operator === 'notEmpty' || operator === 'changed') {    //if set to empty or notempty, hide the value cell
            valueCell.fadeOut(222);
        } else {
            valueCell.fadeIn(222);
            // if(valueCell.closest("fieldset").data("multiple"))    // if this is a multiselect field, decide whether to allow multiple selections
                valueCell.find("select").attr("multiple",(operator === 'list' || operator === 'notList'? "multiple" : null));
        }
    },
    createInput:function(attributes) {

        var that = this;
        var dropdownOptions = attributes.options;
        
        var safeAttributes = {    // only these properties can actually be passed to $.attr()
            id:attributes.id,
            name:attributes.name,
            value:attributes.value
        };

        /**
         * This prevents auto formatting of the date/datetime input by the date/datetimepicker
         * when the picker is closed. This allows users to enter insertable attributes into
         * date/datetimepicker fields.
         */
        function preventAutoFormat (dateText, obj) {
            $(this).val (dateText); // overwrite formatted text 
        }

        switch(attributes.type) {
            case 'boolean':
                if(typeof attributes.value === "undefined")
                    attributes.value = attributes.defaultVal;
                if(attributes.value)
                    safeAttributes["checked"] = "checked";
                return $('<input type="checkbox" />').attr(safeAttributes);

            case 'visibility':
                return this.buildDropdown(x2.visibilityOptions,safeAttributes);
                
            case 'text':
            case 'richtext':
                return $(document.createElement('textarea')).attr(safeAttributes);
                
            case 'time':
                return $('<input type="text" />').attr(safeAttributes).timepicker({
                    constrainInput: false,
                    // showOtherMonths: true,
                    // selectOtherMonths: true,
                    dateFormat:yii.datePickerFormat,
                    onClose: preventAutoFormat
                });
            case 'dateTime':
                return $('<input type="text" />').attr(safeAttributes).datetimepicker({
                    constrainInput: false,
                    showOtherMonths: true,
                    selectOtherMonths: true,
                    dateFormat:yii.datePickerFormat,
                    timeFormat:yii.timePickerFormat,
                    minDate:null,
                    maxDate:null,
                    onClose: preventAutoFormat
                });
            case 'date':
                return $('<input type="text" />').attr(safeAttributes).datepicker({
                    constrainInput: false,
                    showOtherMonths: true,
                    selectOtherMonths: true,
                    dateFormat:yii.datePickerFormat,
                    onClose: preventAutoFormat
                });
            // allows you to create a dropdown whose options change depending on the value
            // of another dropdown field.
            case 'dependentDropdown':
                if(attributes.value !== undefined && attributes.value instanceof Array)
                    safeAttributes.multiple = "multiple";
                var fieldDependencySelector = 
                    'fieldset[name="' + attributes.dependency + '"] select';
                var dropdown = this.buildDropdown(dropdownOptions,safeAttributes);

                // store information about the dependent dropdown so that it can be updated
                // after the form is added to the DOM
                $(dropdown).attr ('data-dependent-dropdown', 'true');
                $(dropdown).data ('data-dependency', fieldDependencySelector);

                // look for a cached dropdown cache
                if (typeof attributes.dropdownCache !== 'undefined') {
                    $(dropdown).data ('dropdownCache', attributes.dropdownCache);
                }

                var first = true;

                // fetch new options when dependency field changes
                $(document).off ('change', fieldDependencySelector);
                $(document).on ('change', fieldDependencySelector, function fetchOptions () {
                    var cache = $(dropdown).data ('dropdownCache');

                    // initialize the cache
                    // store the cache in the jQuery data so that it can be saved in the config
                    // cache.
                    if (typeof cache === 'undefined') {
                        cache = {};

                        // the default dependency val is always the first option
                        var defaultDependencyVal = 
                            $(fieldDependencySelector).find ('option').first ().val ();

                        // add the default dropdown to the cache
                        // since this event gets triggered as soon as the form is added to the DOM,
                        // the current options are the default options.
                        var defaultOptions = [];
                        $(dropdown).find ('option').each (function () {
                            defaultOptions.push ([$(this).val (), $(this).html ()]);
                        });
                        cache[defaultDependencyVal] = defaultOptions;

                        $(dropdown).data ('dropdownCache', cache);
                    }

                    var dependencyVal = $(fieldDependencySelector).val ();

                    // look for dropdown in cache
                    if (typeof cache[dependencyVal] !== 'undefined') { // cache hit
                        var $newDropdown = that.buildDropdown (
                            cache[dependencyVal], safeAttributes);
                        $(dropdown).replaceWith ($newDropdown);
                        dropdown = $newDropdown;
                        if (first) {
                            $(dropdown).val (attributes.value);
                            first = false;
                        }

                        // transfer cache to new dropdown
                        $(dropdown).data ('dropdownCache', cache); 
                        return;
                    }

                    // cache miss
                    x2.forms.inputLoading (dropdown);
                    $.ajax ({
                        url: attributes.optionsSource,
                        type: 'get',
                        dataType: 'json',
                        data: {
                            workflowId: dependencyVal,
                            optional: (attributes.optional ? true : false)
                        },
                        success: function (data) {
                            x2.forms.inputLoadingStop (dropdown);
                            var $newDropdown = that.buildDropdown (data, safeAttributes);
                            var cache = $(dropdown).data ('dropdownCache');
                            $(dropdown).replaceWith ($newDropdown);
                            dropdown = $newDropdown;
                            cache[dependencyVal] = data;

                            if (first) {
                                $(dropdown).val (attributes.value);
                                first = false;
                            }

                            // transfer cache to new dropdown
                            $(dropdown).data ('dropdownCache', cache); 
                        }
                    });
                });

                return dropdown;
            case 'dropdown':
            case 'assignment':
            case 'optionalAssignment':
                if(attributes.value !== undefined && attributes.value instanceof Array)
                    safeAttributes.multiple = "multiple";
                return this.buildDropdown(dropdownOptions,safeAttributes);
                
            case 'tags':
                return $('<input type="text" />').attr(safeAttributes).bind("keydown",function(e) {
                    if(e.keyCode === $.ui.keyCode.TAB && $(this).data("autocomplete").menu.active) {
                        e.preventDefault();
                    }
                }).autocomplete({
                    minLength:0,
                    source:function(request,response) {
                        lastTag = request.term.split(/,\s*/).pop();    // delegate back to autocomplete, but extract the last term
                        response($.ui.autocomplete.filter(x2.allTags,lastTag));
                    },
                    focus:function() {    // prevent value inserted on focus
                        return false;
                    },
                    select:function(event,ui) {
                        var terms = this.value.split(/,\s*/);
                        // remove the current input
                        terms.pop();
                        // add the selected item
                        terms.push( ui.item.value );
                        // add placeholder to get the comma-and-space at the end
                        terms.push("");
                        this.value = terms.join(", ");
                        return false;
                    }
                });
                
            // allows you to create an autocomplete whose source changes depending on the value
            // of another autocomplete.
            case 'dependentAutocomplete':
                var textValue = '';

                if(typeof safeAttributes.value === 'string') {
                    textValue = safeAttributes.value.replace (/_[0-9]+$/, '');
                }

                var fieldDependencySelector = 
                    'fieldset[name="' + attributes.dependency + '"] select';

                var hidden = $('<input type="hidden" />').attr(safeAttributes);
                var input = $('<input type="text" />').val(textValue).autocomplete({
                    minLength:0,
                    source:attributes.linkSource,
                    select:function(event,ui) {
                        $(this).val(ui.item.value);
                        // console.debug(ui.item);
                        $(this).next("input").val(ui.item.value+'_'+ui.item.id);
                        return false; 
                    }
                }).keyup(function(){
                    $(this).next("input").val(input.val());
                });
                $(input).attr ('data-default-text', 'Start typing to suggest...');
                $(input).addClass ('x2-default-field');

                // fetch new autocomplete when dependency field changes
                $(document).on ('change', fieldDependencySelector, function () {
                    x2.forms.inputLoading (input);
                    $.ajax ({
                        type: 'GET',
                        url: attributes.getAutoCompleteUrl.replace (/\?.*$/, ''),
                        data: {
                            modelType: $(fieldDependencySelector).val ()
                        },
                        success: function (data) {
                            x2.forms.inputLoadingStop (input);
                            var inputParent = $(input).parent ();
                            // remove extra element associated with autocomplete
                            $(inputParent).find ('input').first ().next ('span').remove ();
                            // replace the autocomplete
                            $(inputParent).find ('input').first ().replaceWith (data);
                            input = $(inputParent).find ('input').first ();

                        }
                    });
                });
                
                // clear input when dependency field changes
                $(document).on ('change', fieldDependencySelector, function () {
                    $(input).val ('');
                });
                $(document).on ('keydown', fieldDependencySelector, function () {
                    $(input).val ('');
                });

                return $(input).add(hidden);
            
            case 'link':
                if(attributes.linkSource) {
                    // console.debug('still alive');
                    // return $('<input type="text" />').attr(attributes);
                    
                    // var fieldName = safeAttributes.name;
                    // delete safeAttributes.name;
                    
                    //autocomplete with hidden id
                    
                    var textValue = '';

                    if(typeof safeAttributes === 'string') {
                        textValue = safeAttributes.value.replace (/_[0-9]+$/, '');
                    }
                    
                    var hidden = $('<input type="hidden" />').attr(safeAttributes);
                    var input = $('<input type="text" />').val(textValue).autocomplete({
                        minLength:0,
                        source:attributes.linkSource,
                        select:function(event,ui) {
                            $(this).val(ui.item.value);
                            // console.debug(ui.item);
                            $(this).next("input").val(ui.item.value+'_'+ui.item.id);
                            return false; 
                        }
                    }).keyup(function(){
                        $(this).next("input").val(input.val());
                    });
                    $(input).attr ('data-default-text', 'Start typing to suggest...');
                    $(input).addClass ('x2-default-field');
                    //this is for when there is an initial id value supplied,
                    // .change(function() {    
                        //and we want the text to display, not the id
                        // var current = $(this).val();    
                        // if(current.match(/^\d+$/)) {
                            //we have saved the names of the record in our criteria json
                            // var match = $.grep(criteria,function(el,i) {    
                                // return current == el.value;
                            // });
                            // $(this).val(match[0].name);
                        // }
                    // });
                    return $(input).add(hidden);
                }
                // no break statement here; if there's no link source, just make a default input

            case 'varchar':
            case 'email':
            case 'credId':
            case 'url':
            case 'currency':
            case 'rating':
            default:
                return $('<input type="text" />').attr(safeAttributes);
        }
    },
    /**
     * Generates an operator dropdown from a flat array of operators, using x2.operatorList to get human-readable labels
     */
    buildOperatorDropdown:function(operators,val) {
        var operatorOptions = [];
        for(var i=0;i<operators.length;i++) {
            if(x2.operatorList[operators[i]])
                if(operators[i] === 'before')
                    operatorOptions.push(['<=',x2.operatorList[operators[i]]]);        // the 'before' and 'after' operators act
                else if(operators[i] === 'after')                                    // just like '<' and '>' in the back end,
                    operatorOptions.push(['>=',x2.operatorList[operators[i]]]);        // but they have different labels
                else
                    operatorOptions.push([operators[i],x2.operatorList[operators[i]]]);
        }
        return this.createInput({"type":"dropdown","name":"operator","options":operatorOptions,"value":val});
    },
    /**
     * Generates an HTML <select> element with the specified name and options
     */
    buildDropdown:function(options,attributes) {
        if(typeof attributes == "undefined")
            var attributes = {};
        var val = attributes.value;
        delete attributes.value;

        var dropdown = $(document.createElement('select')).attr(attributes);
        for(var i in options) {
            $(document.createElement('option')).attr('value',options[i][0]).text(options[i][1]).
                appendTo(dropdown);
        }
        dropdown.val(val);
        return dropdown;
        //return dropdown.val(val);
    },

    /**
     * This gets called after a new config menu form gets inserted into the DOM. 
     * This updates the dependent dropdown based on the value of the dropdown it depends on.
     */
    updateDependentDropdowns:function (form) {
        // look for dependent dropdown elements in the form
        $(form).find ('[data-dependent-dropdown="true"]').each (function () {
            // grab the selector of the dropdown element dependency and trigger its change event.
            // this causes the updated options to be fetched
            $($(this).data ('data-dependency')).change ();
        });
    },

    /**
     * Checks for the presence of a dependent dropdown cache and returns it if found. This is used
     * to save the dropdown cache when the config menu is torn down
     * @return null|object
     */
    checkForDependentDropdownCache: function (fieldset) {
            var select = $(fieldset).find ('select');
            if ($(select).length && $(select).data ('dropdownCache')) {
                return $(select).data ('dropdownCache');
            }
            return null;
    }

}
});
