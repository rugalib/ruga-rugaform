/**
 * jQuery plugin for interactive forms.
 *
 * @author Roland Rusch, easy-smart solution GmbH <roland.rusch@easy-smart.ch>
 *
 *
 * HTML5 tags:
 * data-rugaform-editable : (never|always)
 * data-rugaform-off-value: value to send, if the checkbox input is not checked
 * data-rugaform-sendvalue: value to send, regardless of the input's status
 *
 */

;(function ($, window, document, undefined) {
    "use strict";
    const pluginName = "rugaform";
    const defaults = {
        url: '',

        submitdisabled: false,                  // Also submit disabled inputs
        trackchanges: true,                     // Marks changed inputs with css class selector.changed.
        instasave: false,                       // Send changes immediately to the backend without waiting for the user to click "save".
        requestedit: false,                     // Form is initially disabled. The user has to enable edit mode to make changes.
        alwayseditable: false,                  // The form contains inputs that are always editable. (TODO: why?)
        suppressreload: false,                  // Suppress all reloads. (TODO: do we still need this?)
        setfocusto: ":input:visible:first",     // Set focus to this field if edit mode starts (default: first visible)
        debug: false,                           // Send debug output to console

        event_root: "",                         // Use the form element as event root by default

        btn_save: "*[type=submit]",             // Selector for the "save" button
        key_save: "ctrl+s",                     // Hotkey to save the from

        btn_reset: "*[type=reset]",             // Selector for the "reset" button
        key_reset: "esc",                       // Hotkey to reset the form

        btn_startedit: "",                      // Selector for the "edit" button
        key_startedit: "ctrl+b",                // Hotkey to start edit mode

        btn_delete: "",                         // Selector for the "delete" button
        key_delete: "del",                      // Hotkey to delete the row

        btn_favourite: "",                      // Selector for the "favourite" button
        key_favourite: "",                      // Hotkey to set/unset the favourite flag

        row: null,                              // Form data

        // Callback hooks
        callback: {
            onSubmitSuccess: function (data, textStatus, jqXHR, errorThrown) {
            },
            onSubmitFailure: function (data, textStatus, jqXHR, errorThrown) {
            },
            onSubmit: function (data, textStatus, jqXHR, errorThrown) {
            },
            onDeleteSuccess: function (data, textStatus, jqXHR, errorThrown) {
            },
            onDeleteFailure: function (data, textStatus, jqXHR, errorThrown) {
            },
            onDelete: function (data, textStatus, jqXHR, errorThrown) {
            },
            onRefreshSuccess: function (data, textStatus, jqXHR, errorThrown) {
            },
            onRefreshFailure: function (data, textStatus, jqXHR, errorThrown) {
            },
            onRefresh: function (data, textStatus, jqXHR, errorThrown) {
            },
            onFavouriteSuccess: function (data, textStatus, jqXHR, errorThrown) {
            },
            onFavouriteFailure: function (data, textStatus, jqXHR, errorThrown) {
            },
            onFavourite: function (data, textStatus, jqXHR, errorThrown) {
            }
        },

        selector: {
            container: "rugaform__container",      // Set on the surrounding container element
            changed: "ruga__changed",              // Set on the changed inputs
            controlbutton: "ruga__controlbutton",  // This class is set on all the control buttons (save, reset, edit, ...)
            active: "ruga__active"                 // Set on the currently focused input.
        },

        content: {
            // The content of the "favourite" button is replaced by this strings
            favourite_on: '<i class="fa fa-star text-yellow"></i>',
            favourite_off: '<i class="fa fa-star-o text-gray"></i>'
        },

        // Data row attribute mapping (also see rugalib/ruga-db)
        data: {
            isFavourite: 'isFavourite',             // represents the status of the favourite flag
            isDisabled: 'isDisabled',               // tells that this data row is disabled
            canBeChangedBy: 'canBeChangedBy',       // user is allowed to change data in row
            isDeleted: 'isDeleted',                 // data row is marked deleted
            isDeletable: 'isDeletable',             // row can be deleted
            isNew: 'isNew',                         // the row is new (AKA not saved yet)
            idname: 'idname',                       // string that identifies the data row
            uniqueid: 'uniqueid',                   // unique id of the row
            html_href: 'html_href'                  // url to the form
        },

        validation: true,                           // Enable form validation.
        // Options for the form validation plugin
        validation_options: {
            debug: false
        },

        dummy: "dummy"
    };


    // The actual plugin constructor
    function Plugin(element, options) {
        this.id = null;
        this._name = pluginName;
        this.pluginName = pluginName;
        this._defaults = defaults;
        /** form element, this plugin belongs to */
        this.element = element;
        /** Container around the element */
        this.container = null;
        /** Outermost element to consider regarding events (ex. dialog) */
        this.eventRoot = null;
        /** Stores the initial status of the form inputs */
        this.initialFormStatus = null;
        /** Current edit mode (true=edit, false=readonly) */
        this.formEditMode = false;
        /** Input that last has focus received */
        this.inputWithFocus = null;

        // Merge given options into default and store to this.settings
        this.settings = $.extend(true, {}, defaults, options);

        // if (this.settings.debug) console.log(pluginName + '::Plugin( element, options ) | element=', element, ' | options=', options);
        this.debugOutput(pluginName + '::Plugin( element, options ) | element=', element, ' | options=', options);

        this.init();
    }

    Plugin.prototype = {

        init: function () {
            // register callbacks as prototype functions
            $.extend(Plugin.prototype, {
                callback_onSubmitSuccess: this.settings.callback.onSubmitSuccess,
                callback_onSubmitFailure: this.settings.callback.onSubmitFailure,
                callback_onSubmit: this.settings.callback.onSubmit,
                callback_onDeleteSuccess: this.settings.callback.onDeleteSuccess,
                callback_onDeleteFailure: this.settings.callback.onDeleteFailure,
                callback_onDelete: this.settings.callback.onDelete,
                callback_onRefreshSuccess: this.settings.callback.onRefreshSuccess,
                callback_onRefreshFailure: this.settings.callback.onRefreshFailure,
                callback_onRefresh: this.settings.callback.onRefresh,
                callback_onFavouriteSuccess: this.settings.callback.onFavouriteSuccess,
                callback_onFavouriteFailure: this.settings.callback.onFavouriteFailure,
                callback_onFavourite: this.settings.callback.onFavourite
            });
            this.initEventRoot();
            this.initContainer();
            this.initForm();
        },


        /**
         * Outputs debug messages to console if debug is set.
         * @param msg
         */
        debugOutput: function (...objects) {
            if (!this.settings.debug) return;
            console.log(this._name + ':', ...objects);
        },


        /**
         * Initialize the event root element.
         */
        initEventRoot: function () {
            this.debugOutput(this._name + '::initEventRoot()');
            if (this.settings.event_root === '') {
                this.eventRoot = this.element;
            } else {
                this.eventRoot = $(this.settings.event_root);
            }
        },


        /**
         * Initialize the container surrounding the form element.
         */
        initContainer: function () {
            this.debugOutput(this._name + '::initContainer()');

            // Create container if it does not exist
            this.container = this.element.closest('.' + this.settings.selector.container);
            if (this.container.length === 0) {
                $(this.element).wrap('<div class="' + this.settings.selector.container + '"></div>');
                this.container = this.element.closest('.' + this.settings.selector.container);
            }
        },


        /**
         * Initialize the form. Set unique id, store the initial status, handle edit mode,
         * fallback to html_href if action not set, update favourite button, set event handlers, initialize validation.
         */
        initForm: function () {
            this.debugOutput(this._name + '::initForm()');

            // Make sure a unique id is set
            this.element.uniqueId();

            // Save the current form input status
            this.initialFormStatus = this.getFormStatus();

            // If requestedit option is set, put the form in disabled mode.
            this.formEditMode = !this.settings.requestedit;
            this.setFormEditMode(this.formEditMode);


            if (!this.element.attr('action')) {
                this.element.attr('action', this.getRowValue(this.settings.data.html_href));
            }


            // Set "favourite" button initial status
            this.updateFavouriteButton();

            // Set the event handlers
            this.addEventHandlers();

            // Initialize form validation plugin if validation is set to true
            if (this.settings.validation) {
                if (this.settings.debug) this.settings.validation_options.debug = true;
                this.element.validate(this.settings.validation_options);
            }
        },


        /**
         * Updates the "favourite" button.
         * Sets the content as defined in this.settings.content.favourite_on and
         * this.settings.content.favourite_off and sets the button disabled status.
         */
        updateFavouriteButton: function () {
            this.debugOutput(this._name + '::updateFavouriteButton()');

            if (!!this.settings.btn_favourite) {
                const isFavourite = !!this.getRowValue(this.settings.data.isFavourite);
                if (isFavourite) $(this.settings.btn_favourite, this.element).html(this.settings.content.favourite_on);
                else $(this.settings.btn_favourite, this.element).html(this.settings.content.favourite_off);
                $(this.settings.btn_favourite, this.element).prop('disabled', !this.isFavouriteButtonEnabled());
            }
        },


        /**
         * Finds the label to the given input.
         *
         * @param el jQuery|HTMLElement
         * @returns jQuery|HTMLElement
         */
        findLabel: function (el) {
            this.debugOutput(this._name + '::findLabel(el) | el=', el);

            const elid = String($(el).attr('id'));
            let label = $('label[for="' + elid + '"]');

            if ((label.length === 0) && elid.lastIndexOf('-input'))
                label = $('label[for="' + elid.slice(0, elid.lastIndexOf('-input')) + '"]');

            if ((label.length === 0) && !elid.indexOf('-input'))
                label = $('label[for="' + elid + '-input"]');

            if (label.length === 0) {
                const parentElem = $(el).parent(),
                    parentTagName = parentElem.get(0).tagName.toLowerCase();

                if (parentTagName === "label") {
                    label = parentElem;
                }
            }
            return label;
        },


        /**
         * Returns the current status of the form and all the inputs.
         *
         * @returns {[]}
         */
        getFormStatus: function () {
            this.debugOutput(this._name + '::getFormStatus()');

            let status = [];
            $(':input', this.element).each(function (index, element) {
                const input = $(element);
                const label = this.findLabel(input);
                input.uniqueId();

                // copy id to name if no name is set
                if (!input.prop('name')) {
                    input.attr('name', input.prop('id'));
                }

                const obj = {
                    name: input.prop('name'),
                    id: input.prop('id'),
                    disabled: input.prop('disabled'),
                    checked: input.prop('checked'),
                    value: input.val(),
                    label: label.text() ? label.text() : input.prop('placeholder'),
                    editable: input.data('rugaform-editable')
                };
                if (obj.editable === 'always') this.settings.alwayseditable = true;
                status.push(obj);
            }.bind(this));

            return status;
        },


        /**
         * Returns initial (saved) status of input el.
         *
         * @param element jQuery|HTMLElement
         * @returns {[]}|null
         */
        getInitialStatus: function (element) {
            this.debugOutput(this._name + '::getInitialStatus(element) | element=', element);

            const el = $(element);

            let status = this.initialFormStatus.filter(function (e) {
                return (e.name === el.prop('name')) && (e.id === el.prop('id'));
            });
            status = !!status ? status[0] : null;

            return status;
        },


        /**
         * Sets edit mode of the form.
         *
         * @param mode bool
         */
        setFormEditMode: function (mode) {
            this.debugOutput(this._name + '::setFormEditMode(mode) | mode=', mode);

            // If edit mode feature ist disabled, always set form to edit mode
            if (!this.settings.requestedit) mode = true;

            // If no new mode (or not boolean) is given, re-apply the stored edit mode
            if (typeof mode !== 'boolean') mode = this.formEditMode;

            // Store the current form edit mode
            this.formEditMode = mode;

            $(':input', this.element).each(function (index, element) {
                const input = $(element);
                const initialstatus = this.getInitialStatus(input);
                let newdisabledstatus = true;

                if (this.formEditMode) {
                    // No stored status found => change nothing and quit
                    if (!initialstatus) return;

                    // Get initial status of the disabled flag to start with a defined value
                    newdisabledstatus = initialstatus.disabled;

                    // data row disabled? true=>disable input
                    if (this.getRowValue(this.settings.data.isDisabled) === true) newdisabledstatus = true;

                    // user can change data in row? false=>disable input
                    if (this.getRowValue(this.settings.data.canBeChangedBy) === false) newdisabledstatus = true;

                    // Attribute data-rugaform-editable is set? always=>enable input / never=>disable input
                    if (initialstatus.editable === 'always') newdisabledstatus = false;
                    if (initialstatus.editable === 'never') newdisabledstatus = true;

                    // data row is marked deleted? true=>disable input
                    if (this.getRowValue(this.settings.data.isDeleted) === true) newdisabledstatus = true;
                } else {
                    newdisabledstatus = true;
                    // Attribute data-rugaform-editable is set? always=>enable input / never=>disable input
                    if (initialstatus.editable === 'always') newdisabledstatus = false;
                    if (initialstatus.editable === 'never') newdisabledstatus = true;
                }

                // Set the input disabled flag to the new value
                this.setInputPropDisabled(input, newdisabledstatus);
            }.bind(this));

            // Set focus when edit mode starts
            if (this.formEditMode) {
                // get last focused input
                let element = $(this.inputWithFocus).filter(':visible:first');
                // if not found, use default/setting
                if (element.length === 0) element = $(this.settings.setfocusto, this.element);

                if (element.length > 0) {
                    // set focus
                    element.trigger('focus');

                    // set cursor to end of string
                    const index = element.val().length;
                    if (element[0].createTextRange) {
                        const range = element[0].createTextRange();
                        range.move("character", index);
                        range.select();
                    } else if (element[0].selectionStart != null) {
                        element[0].setSelectionRange(index, index);
                    }
                }
                if (this.settings.debug) console.log('Set focus to: ', element);
            }

            // Set "edit" and "delete" buttons disabled property
            $(this.settings.btn_startedit, this.eventRoot).prop('disabled', !this.isEditButtonEnabled());
            $(this.settings.btn_delete, this.eventRoot).prop('disabled', !this.isDeleteButtonEnabled());

            // Set "save" and "reset" buttons disabled property to !formEditMode
            $(this.settings.btn_save, this.eventRoot).prop('disabled', !this.formEditMode);
            $(this.settings.btn_reset, this.eventRoot).prop('disabled', !this.formEditMode);
        },


        /**
         * Set the input's disabled property.
         * @param input jQuery|HTMLElement
         * @param value bool
         */
        setInputPropDisabled: function (input, value) {
            this.debugOutput(this._name + '::setInputPropDisabled(input, value) | input=', input, ' | value=', value);

            if (input.hasClass('trumbowyg-textarea')) input.trumbowyg(value ? 'disable' : 'enable');
            else if (input.hasClass('selectized')) value ? input[0].selectize.disable() : input[0].selectize.enable();
            else input.prop('disabled', value);
        },


        /**
         * Register all event handlers.
         */
        addEventHandlers: function () {
            this.debugOutput(this._name + '::addEventHandlers()');

            // Register my handlers for the default form events
            this.element.on('submit.' + this.pluginName, null, this, this._onSubmit.bind(this));
            this.element.on('reset.' + this.pluginName, null, this, this._onReset.bind(this));

            // Bind change, click and keyup handlers to the inputs
            $(':input', this.element).each(function (index, element) {
                const input = $(element);
                input.on('change.' + this.pluginName, null, this, this._onChange.bind(this));
                input.on('click.' + this.pluginName, null, this, this._onClick.bind(this));
                input.on('keyup.' + this.pluginName, null, this, this._onKeyup.bind(this));

                // "save" hotkey
                if (!!this.settings.key_save) {
                    input.on('keydown.' + this.pluginName, null, this.settings.key_save, this._onSubmit.bind(this));
                }
                // "reset" hotkey
                if (!!this.settings.key_reset) {
                    input.on('keydown.' + this.pluginName, null, this.settings.key_reset, this._onReset.bind(this));
                }
                // "favourite" hotkey
                if (!!this.settings.key_favourite) {
                    input.on('keydown.' + this.pluginName, null, this.settings.key_favourite, this._onFavourite.bind(this));
                }
            }.bind(this));

            // On focusin@form: store the currently focused element and set this.settings.selector.active css class
            $(this.element).on('focusin.' + this.pluginName, ':input', function (e) {
                const input = $(e.target, this.element).filter(':not(.' + this.settings.selector.controlbutton + ')');
                if ((input.length > 0) && (input !== this.inputWithFocus)) {
                    $(this.inputWithFocus).removeClass(this.settings.selector.active);
                    this.inputWithFocus = input;
                    this.inputWithFocus.addClass(this.settings.selector.active);
                    if (this.settings.debug) console.log('this.inputWithFocus=', this.inputWithFocus);
                }
            }.bind(this));

            // On focusout@form: remove this.settings.selector.active css class
            $(this.element).on('focusout.' + this.pluginName, ':input', function (e) {
                const input = $(e.target, this.element).filter(':not(.' + this.settings.selector.controlbutton + ')');
                input.removeClass(this.settings.selector.active);
            }.bind(this));


            // "save" button (submit)
            $(this.settings.btn_save, this.eventRoot).addClass(this.settings.selector.controlbutton).on('click.' + this.pluginName, null, this, this._onSubmit.bind(this));
            // "save" hotkey
            if (!!this.settings.key_save) {
                $(document).on('keydown', null, this.settings.key_save, this._onSubmit.bind(this));
            }

            // "reset" button
            $(this.settings.btn_reset, this.eventRoot).addClass(this.settings.selector.controlbutton).on('click.' + this.pluginName, null, this, this._onReset.bind(this));
            // "reset" hotkey
            if (!!this.settings.key_reset) {
                $(document).on('keydown', null, this.settings.key_reset, this._onReset.bind(this));
            }

            // "edit" button
            $(this.settings.btn_startedit, this.eventRoot).addClass(this.settings.selector.controlbutton).on('click.' + this.pluginName, null, this, this._onStartEdit.bind(this));
            // "edit" hotkey
            if (!!this.settings.key_startedit) {
                $(document).on('keydown', null, this.settings.key_startedit, this._onStartEdit.bind(this));
            }

            // "delete" button
            $(this.settings.btn_delete, this.eventRoot).addClass(this.settings.selector.controlbutton).on('click.' + this.pluginName, null, this, this._onDelete.bind(this));
            // "delete" hotkey
            if (!!this.settings.key_delete) {
                $(document).on('keydown', null, this.settings.key_delete, this._onDelete.bind(this));
            }

            // "favourite" button
            $(this.settings.btn_favourite, this.eventRoot).addClass(this.settings.selector.controlbutton).on('click.' + this.pluginName, null, this, this._onFavourite.bind(this));
            // "favourite" hotkey
            if (!!this.settings.key_favourite) {
                $(document).on('keydown', null, this.settings.key_favourite, this._onFavourite.bind(this));
            }
        },


        /**
         * Save the form using AJAX.
         *
         * @param event
         * @returns {Promise}
         */
        submitForm: function (event) {
            this.debugOutput(this._name + '::submitForm()');

            const form = this.element;

            // Create messages if form is not valid
            if (this.settings.validation && !form.valid()) {
                $.each(form.validate().errorList, function (index, value) {
                    console.log('index=' + index + ' | value=', value);
                    const elStatus = this.getInitialStatus(value.element);
                    let msg = value.message;
                    if (elStatus.label !== '') msg = elStatus.label + ': ' + msg;
                    else msg = value.element.name + ': ' + msg;

                    alertify.notify(msg, 'error', 10, function () {
                        console.log('dismissed');
                    });
                }.bind(this));
                return $.Deferred().reject();
            }

            // disable buttons
            $(this.settings.selector.controlbutton, this.element).prop('disabled', true);

            // Serialize form data
            const serializedform = this.serializeFormData(event);

            return $.ajax({
                type: form.attr('method') === undefined ? 'POST' : form.attr('method'),
                url: form.prop('action'),
                data: serializedform,
                dataType: 'json',
                context: this,
                success: function (data, textStatus, jqXHR) {
                    if (data.result === undefined) {
                        // No result provided
                        this.callback_onSubmitSuccess(data, textStatus, jqXHR, null);
                        this.callback_onSubmit(data, textStatus, jqXHR, null);
                        return;
                    }

                    if ((data.result.finalSeverity !== 'DEBUG') && (data.result.finalSeverity !== 'INFORMATIONAL')) {
                        // Feedback from backend is NOT of severity DEBUG or INFORMATIONAL
                        // => failure
                        alertify.alert('Formular', data.result.finalMessage);

                        this.callback_onSubmitFailure(data, textStatus, jqXHR, null);
                    } else {
                        // => success
                        alertify.notify(data.result.finalMessage, 'success', 6, function () {
                            console.log('dismissed');
                        });
                        if (!!data.row) this.settings.row = data.row;

                        this.callback_onSubmitSuccess(data, textStatus, jqXHR, null);
                    }

                    this.callback_onSubmit(data, textStatus, jqXHR, null);
                }.bind(this),
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log("errorThrown=", errorThrown);
                    alertify.alert('Formular', 'Die Forumlar-Daten konnten nicht übermittelt werden.');
                    this.callback_onSubmitFailure(null, textStatus, jqXHR, errorThrown);
                    this.callback_onSubmit(null, textStatus, jqXHR, errorThrown);
                }.bind(this),
                complete: function (jqXHR, textStatus) {
                    if (this.settings.debug) {
                        if (jqXHR.responseJSON) console.log("AJAX: " + textStatus + " responseJSON=", jqXHR.responseJSON);
                        else console.log("AJAX: " + textStatus + " responseText=", jqXHR.responseText);
                    }

                    // Enable "save" button
                    $(this.settings.btn_save, this.eventRoot).prop('disabled', false);
                    // TODO: was machen wir hier?
                }.bind(this)
            });
        },


        /**
         * Delete the row using AJAX.
         *
         * @param event
         * @returns {Promise}
         */
        deleteForm: function (event) {
            this.debugOutput(this._name + '::deleteForm()');

            const form = this.element;

            // disable buttons
            $(this.settings.selector.controlbutton, this.element).prop('disabled', true);

            // Serialize form data
            const serializedform = this.serializeFormData(event);

            return $.ajax({
                type: 'DELETE',
                url: form.attr('action'),
                // contentType: 'application/json; charset=UTF-8',
                data: serializedform,
                dataType: 'json',
                context: this,
                success: function (data, textStatus, jqXHR) {
                    if ((data.result.finalSeverity !== 'DEBUG') && (data.result.finalSeverity !== 'INFORMATIONAL')) {
                        // Feedback from backend is NOT of severity DEBUG or INFORMATIONAL
                        // => failure
                        alertify.alert('Formular', data.result.finalMessage);

                        this.callback_onDeleteFailure(data, textStatus, jqXHR, null);
                    } else {
                        // => success
                        alertify.notify(data.result.finalMessage, 'success', 6, function () {
                            console.log('dismissed');
                        });
                        if (!!data.row) this.settings.row = data.row;

                        this.callback_onDeleteSuccess(data, textStatus, jqXHR, null);
                    }

                    this.callback_onDelete(data, textStatus, jqXHR, null);

                }.bind(this),
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log("errorThrown=", errorThrown);
                    alertify.alert('Formular', 'Der Lösch-Befehl konnte nicht an den Server übermittelt werden.');
                    this.callback_onDeleteFailure(null, textStatus, jqXHR, errorThrown);
                    this.callback_onDelete(null, textStatus, jqXHR, errorThrown);
                }.bind(this),
                complete: function (jqXHR, textStatus) {
                    if (this.settings.debug) {
                        if (jqXHR.responseJSON) console.log("AJAX: " + textStatus + " responseJSON=", jqXHR.responseJSON);
                        else console.log("AJAX: " + textStatus + " responseText=", jqXHR.responseText);
                    }

                    // Enable "save" button
                    $(this.settings.btn_save, this.eventRoot).prop('disabled', true);
                }.bind(this)
            });
        },


        /**
         * Fetch data from backend and refresh form.
         *
         * @returns {Promise}
         */
        refreshForm: function () {
            this.debugOutput(this._name + '::refreshForm()');
            /*
            this.setFormEditMode(false);

            $(':input', this.element).each(function (index, element) {
                const input = $(element);
                const input_name=input.attr('name');

                input.val(this.getRowValue(input_name));
                this.checkChange(input);
            }.bind(this));
            */
            location.reload();
            return Promise.resolve();
        },


        /**
         * Serialize form data.
         *
         * @param event
         * @returns {[]}
         */
        serializeFormData: function (event) {
            this.debugOutput(this._name + '::serializeFormData(event) | event=', event);

            const form = this.element;

            // Serialize form data
            const serializedform = $(form).serializeArray();

            // uniqueid mitschicken
            const uniqueid = this.getRowValue(this.settings.data.uniqueid);
            serializedform.push({name: 'rugaform_uniqueid', value: uniqueid});

            // Find checkboxes which would not be sent because they are not checked
            // Send the value from the data-off-value attribute
            $('input[type=checkbox]').each(function (index, element) {
                const el = $(element);
                if (!el.prop('disabled') || this.settings.submitdisabled) {
                    if (!el.prop('checked') && (el.prop('name') !== '')) {
                        let v = el.data('rugaform-off-value');
                        if (!!v) v = el.data('off-value');
                        serializedform.push({name: el.prop('name'), value: v});
                    }
                }
            }.bind(this));

            // Find inputs with attribute data-rugaform-sendvalue set.
            // Always send the value of this attribute
            $('input[data-rugaform-sendvalue]').each(function (index, element) {
                const el = $(element);
                if (el.prop('name') !== '') {
                    serializedform.push({name: el.prop('name'), value: el.val()});
                }
            }.bind(this));

            // Find disabled inputs and send their value if submitdisabled is true
            if (this.settings.submitdisabled) {
                $(':input').filter(':not([type=checkbox])').each(function (index, element) {
                    const el = $(element);
                    if (el.prop('disabled') && (el.prop('name') !== '')) {
                        let v = null;
                        if (element instanceof HTMLSelectElement) {
                            for (let option of element.selectedOptions) {
                                serializedform.push({name: el.prop('name'), value: option.value});
                            }
                            return;
                        } else {
                            v = el.val();
                        }
                        serializedform.push({name: el.prop('name'), value: v});
                    }
                }.bind(this));
            }

            if (typeof event !== 'undefined') {
                // Also send the button, which triggered the submit action
                if (event.target.name && event.target.value)
                    serializedform.push({name: event.target.name, value: event.target.value});

                // Set the reason for form submit
                if (event.type) {
                    const reason = event.type;
                    serializedform.push({name: 'rugaform_submit_reason', value: reason});
                }
            } else {
                serializedform.push({name: 'rugaform_submit_reason', value: 'unknown'});
            }

            if (this.settings.debug) {
                console.log('serializedform=', serializedform);
            }

            return serializedform;
        },


        /**
         * Toggle the favourite flag.
         *
         * @param event
         * @returns {Promise}
         */
        toggleFavourite: function (event) {
            this.debugOutput(this._name + '::toggleFavourite(event) | event=', event);

            const form = this.element;
            const isFavourite = !!this.getRowValue(this.settings.data.isFavourite);

            // Disable "favourite" button
            $(this.settings.btn_favourite, form).prop('disabled', true);

            return $.ajax({
                type: 'POST',
                url: form.attr('action'),
                data: {setfavourite: !isFavourite},
                dataType: 'json',
                context: this,
                success: function (data, textStatus, jqXHR) {
                    if ((data.result.finalSeverity !== 'DEBUG') && (data.result.finalSeverity !== 'INFORMATIONAL')) {
                        // Feedback from backend is NOT of severity DEBUG or INFORMATIONAL
                        // => failure
                        alertify.alert('Formular', data.result.finalMessage);

                        this.callback_onFavouriteFailure(data, textStatus, jqXHR, null);
                    } else {
                        // => success
                        this.settings.row.isFavourite = data.row.isFavourite;

                        this.callback_onFavouriteSuccess(data, textStatus, jqXHR, null);
                    }

                    this.callback_onFavourite(data, textStatus, jqXHR, null);
                }.bind(this),
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log("errorThrown=", errorThrown);
                    alertify.alert('Formular', 'Der Befehl konnte nicht an den Server übermittelt werden.');
                    this.callback_onFavouriteFailure(null, textStatus, jqXHR, errorThrown);
                }.bind(this),
                complete: function (jqXHR, textStatus) {
                    if (this.settings.debug) {
                        if (jqXHR.responseJSON) console.log("AJAX: " + textStatus + " responseJSON=", jqXHR.responseJSON);
                        else console.log("AJAX: " + textStatus + " responseText=", jqXHR.responseText);
                    }

                    // Enable "favourite" button
                    $(this.settings.btn_favourite, form).prop('disabled', this.isFavouriteButtonEnabled());
                }.bind(this)
            });
        },


        /**
         * Check if input was changed and set css class.
         *
         * @param element jQuery|HTMLElement
         * @returns boolean
         */
        checkChange: function (element) {
            this.debugOutput(this._name + '::checkChange(element) | element=', element);

            if (!this.settings.trackchanges) return false; // Feature is disabled
            const el = $(element);  // The input
            let elView = el;        // The visible part of the input
            let initialstatus = this.getInitialStatus(el);
            let isChanged = initialstatus;

            if ((el.attr('type') === 'checkbox') || (el.attr('type') === 'radio')) {
                if (el.data('toggle'))
                    elView = el.closest('.toggle');

                isChanged = (el.prop('checked') !== initialstatus.checked);
            } else {
                if (el.hasClass('select2-hidden-accessible')) {
                    elView = el.parent().find('.select2-container').eq(0);
                }

                isChanged = (el.val() !== initialstatus.value);
            }

            if (isChanged) elView.addClass(this.settings.selector.changed);
            else elView.removeClass(this.settings.selector.changed);
            return isChanged;
        },


        /**
         * Saves the given input.
         *
         * @param element jQuery|HTMLElement
         * @returns {Promise}
         */
        saveInput: function (element) {
            this.debugOutput(this._name + '::saveInput(element) | element=', element);

            const el = $(element);
            if (!this.settings.instasave) return Promise.resolve();   // The feature is disabled
            return Promise.resolve();
        },


        /**
         * Checks, if "edit" button should be enabled.
         * @returns boolean
         */
        isEditButtonEnabled: function () {
            this.debugOutput(this._name + '::isEditButtonEnabled()');

            if (this.formEditMode) return false; // Always disable "edit" button if already in edit mode
            const isDisabled = this.getRowValue(this.settings.data.isDisabled) === true;
            const isDeleted = this.getRowValue(this.settings.data.isDeleted) === true;
            const canBeChangedBy = this.getRowValue(this.settings.data.canBeChangedBy) === true;

            if (isDeleted) return false; // Can not edit deleted rows
            if (this.settings.alwayseditable) return true; // There is at least 1 input, that's always editable

            return !isDisabled && !isDeleted && canBeChangedBy;
        },


        /**
         * Checks, if "delete" button should be enabled.
         *
         * @returns boolean
         */
        isDeleteButtonEnabled: function () {
            this.debugOutput(this._name + '::isDeleteButtonEnabled()');

            const isDisabled = !!this.getRowValue(this.settings.data.isDisabled);
            const isDeleted = !!this.getRowValue(this.settings.data.isDeleted);
            const isDeletable = !!this.getRowValue(this.settings.data.isDeletable);
            const canBeChangedBy = !!this.getRowValue(this.settings.data.canBeChangedBy);
            const isNew = !!this.getRowValue(this.settings.data.isNew);

            return isDeletable && !isDisabled && !isDeleted && canBeChangedBy && !isNew;
        },


        /**
         * Checks, if "favourite" button should be enabled.
         *
         * @returns boolean
         */
        isFavouriteButtonEnabled: function () {
            this.debugOutput(this._name + '::isFavouriteButtonEnabled()');

            return true;
        },


        /**
         * Returns the value from data row.
         *
         * @param name string
         * @returns Anything
         */
        getRowValue: function (name) {
            // this.debugOutput(this._name + '::getRowValue(name) | name=', name);

            return this.settings.row ? this.settings.row[name] : null;
        },


        /**
         * Submit form programmatically.
         * @returns {Promise}
         */
        submit: function (event) {
            this.debugOutput(this._name + '::submit(event) | event=', event);

            return this.submitForm(event).then(function (data) {
                if ((data.result.finalSeverity !== 'DEBUG') && (data.result.finalSeverity !== 'INFORMATIONAL')) return;
                if (this.settings.suppressreload) return;
                if (data.result.successUrl !== '') location.href = data.result.successUrl;
            }.bind(this));
        },


        /**
         * Delete form programmatically.
         * @returns {Promise}
         */
        delete: function (event) {
            this.debugOutput(this._name + '::delete(event) | event=', event);

            return this.deleteForm(event).then(function (data) {
                if ((data.result.finalSeverity !== 'DEBUG') && (data.result.finalSeverity !== 'INFORMATIONAL')) return;
                if (this.settings.suppressreload) return;
                if (data.result.successUrl !== '') location.href = data.result.successUrl;
            }.bind(this));
        },


        /**
         * Refresh from programmatically.
         *
         * @returns {Promise}
         */
        refresh: function (event) {
            this.debugOutput(this._name + '::refresh(event) | event=', event);

            return this.refreshForm();
        },

        /**
         * Fetch data from backend and store to this.settings.data
         *
         * @returns {Promise}
         * @private
         */
        _refreshData: function () {
            this.debugOutput(this._name + '::_refreshData()');

            const form = this.element;

            // disable buttons
            $(this.settings.selector.controlbutton, this.element).prop('disabled', true);

            return $.ajax({
                type: 'GET',
                url: form.attr('action'),
                dataType: 'json',
                context: this,
                success: function (data, textStatus, jqXHR) {
                    if ((data.result.finalSeverity !== 'DEBUG') && (data.result.finalSeverity !== 'INFORMATIONAL')) {
                        // Feedback from backend is NOT of severity DEBUG or INFORMATIONAL
                        // => failure
                        alertify.alert('Formular', data.result.finalMessage);

                        this.callback_onRefreshFailure(data, textStatus, jqXHR, null);
                    } else {
                        // => success
                        alertify.notify(data.result.finalMessage, 'success', 6, function () {
                            console.log('dismissed');
                        });
                        if (!!data.row) this.settings.row = data.row;

                        this.callback_onRefreshSuccess(data, textStatus, jqXHR, null);
                    }

                    this.callback_onRefresh(data, textStatus, jqXHR, null);
                }.bind(this),
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log("errorThrown=", errorThrown);
                    alertify.alert('Formular', 'Die Forumlar-Daten konnten nicht übermittelt werden.');
                    this.callback_onRefreshFailure(null, textStatus, jqXHR, errorThrown);
                    this.callback_onRefresh(null, textStatus, jqXHR, errorThrown);
                }.bind(this),
                complete: function (jqXHR, textStatus) {
                    if (this.settings.debug) {
                        if (jqXHR.responseJSON) console.log("AJAX: " + textStatus + " responseJSON=", jqXHR.responseJSON);
                        else console.log("AJAX: " + textStatus + " responseText=", jqXHR.responseText);
                    }

                    // Enable "save" button
                    // $(this.settings.btn_save, this.eventRoot).prop('disabled', false);
                    // TODO: was machen wir hier?
                }.bind(this)
            });
        },


        dummy: function () {
        }
    }


    /**
     * Event handler "change".
     *
     * @param event
     * @returns {Promise}
     * @private
     */
    function _onChange(event) {
        this.debugOutput(this._name + '::_onChange(event) | event=', event);

        const element = event.currentTarget;
        this.checkChange(element);
        return this.saveInput(element);
    }


    /**
     * Event handler "click"
     *
     * @param event
     * @private
     */
    function _onClick(event) {
        this.debugOutput(this._name + '::_onClick(event) | event=', event);

        const element = event.currentTarget;
        this.checkChange(element);
    }


    /**
     * Event-Handler "keyup"
     *
     * @param event
     * @private
     */
    function _onKeyup(event) {
        this.debugOutput(this._name + '::_onKeyup(event) | event=', event);

        const element = event.currentTarget;
        this.checkChange(element);
    }


    /**
     * Event handler "submit"
     *
     * @param event
     * @returns {Promise}
     * @private
     */
    function _onSubmit(event) {
        this.debugOutput(this._name + '::_onSubmit(event) | event=', event);

        const element = event.currentTarget;
        event.preventDefault();
        event.stopPropagation();
        return this.submitForm(event).then(data => {
            if ((data.result.finalSeverity !== 'DEBUG') && (data.result.finalSeverity !== 'INFORMATIONAL')) return;
            if (this.settings.suppressreload) return;
            if (data.result.successUrl !== '') {
                location.href = data.result.successUrl;
                return;
            }
            this.refreshForm();
        });
    }


    /**
     * Event handler "reset"
     *
     * @param event
     * @private
     */
    function _onReset(event) {
        this.debugOutput(this._name + '::_onReset(event) | event=', event);

        const element = event.currentTarget;
        event.preventDefault();
        event.stopPropagation();
        alertify.error('Bearbeitung abgebrochen');
        this.refreshForm();
    }


    /**
     * Event handler "startedit"
     *
     * @param event
     * @private
     */
    function _onStartEdit(event) {
        this.debugOutput(this._name + '::_onStartEdit(event) | event=', event);

        const element = event.currentTarget;

        // quit if form is already in edit mode
        if (this.formEditMode) return;

        this.setFormEditMode(true);
    }


    /**
     * Event handler "delete"
     *
     * @param event
     * @private
     */
    function _onDelete(event) {
        this.debugOutput(this._name + '::_onDelete(event) | event=', event);

        const element = event.currentTarget;
        event.preventDefault();
        event.stopPropagation();
        alertify.confirm(
            'Bestätigung',
            'Soll der Datensatz ' + this.getRowValue(this.settings.data.idname) + ' wirklich gelöscht werden?',
            function () {
                alertify.success('Ok');
                return this.deleteForm(event).then(function (data) {
                    if (this.settings.suppressreload) return;
                    if (data.result.successUrl !== '') {
                        location.href = data.result.successUrl;
                        return;
                    }
                    this.refreshForm();
                });
            }.bind(this),
            function () {
                alertify.error('Löschen abgebrochen');
            }.bind(this)
        );
    }


    /**
     * Event handler "favourite"
     *
     * @param event
     * @returns {Promise}
     * @private
     */
    function _onFavourite(event) {
        this.debugOutput(this._name + '::_onFavourite(event) | event=', event);

        const element = event.currentTarget;
        return this.toggleFavourite(event).then(function (data) {
            this.updateFavouriteButton();
        });
    }


    $.extend(Plugin.prototype, {
        _onChange: _onChange,
        _onClick: _onClick,
        _onKeyup: _onKeyup,
        _onSubmit: _onSubmit,
        _onReset: _onReset,
        _onStartEdit: _onStartEdit,
        _onDelete: _onDelete,
        _onFavourite: _onFavourite
    });


    // A really lightweight plugin wrapper around the constructor,
    // preventing against multiple instantiations
    $.fn[pluginName] = function (options) {
        let plugin = $(this).data('plugin_' + pluginName);

        // has plugin instantiated ?
        if (plugin instanceof Plugin) {
            // if have options arguments, call plugin.init() again
            if (typeof options !== 'undefined') {
                plugin.init(options);
            }
        } else {
            // No instance yet
            plugin = new Plugin(this, options);
            $(this).data('plugin_' + pluginName, plugin);
        }

        return plugin;
    };


})(jQuery, window, document);

