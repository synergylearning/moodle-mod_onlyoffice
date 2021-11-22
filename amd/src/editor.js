define(['jquery', 'core/str'], function ($, str) {
    // OnlyOffice editor element.
    const $onlyOfficeEditor = $('#onlyoffice-editor');

    // The config we're sending to OnlyOffice.
    let CONFIG = '';

    /**
     * Display an error message
     * @param error
     */
    const displayError = function(error) {
        const errorIsAvailable = str.get_string(error, 'onlyoffice');

        $.when(errorIsAvailable).done(function(localizedStr) {
            $onlyOfficeEditor.text = localizedStr;
            $onlyOfficeEditor.text(localizedStr).addClass("error");
        });
    };

    /**
     * What to do when OnlyOffice has loaded.
     */
    const loadOpenOffice = function() {
        // DocsAPI must be defined at this point.
        if (typeof DocsAPI === "undefined") {
            displayError('docserverunreachable');
            return;
        }

        // Create our OnlyOffice editor.
        new DocsAPI.DocEditor("onlyoffice-editor", CONFIG);
    };

    return {
        init: function () {
            CONFIG = JSON.parse($('input[name="config"]').val());
            loadOpenOffice();
        }
    };
});