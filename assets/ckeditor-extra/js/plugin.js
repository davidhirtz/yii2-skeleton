/**
 * Extra buttons set via CKEDITOR.buttons.
 */
CKEDITOR.plugins.add('extra', {
    init: function (editor) {
        var buttons = CKEDITOR.buttons;

        function addButtonCommand(button) {
            var style = new CKEDITOR.style(button.definition);

            editor.attachStyleStateChange(style, function (state) {
                !editor.readOnly && editor.getCommand(button.command).setState(state);
            });

            editor.addCommand(button.command, new CKEDITOR.styleCommand(style));

            editor.ui.addButton(button.name, {
                label: button.label,
                command: button.command,
                icon: button.icon
            });
        }

        for (var i = 0; i < buttons.length; i++) {
            addButtonCommand(buttons[i]);
        }

        CKEDITOR.remove('buttons');
    }
});

CKEDITOR.on('dialogDefinition', function (event) {
    var dialogName = event.data.name;

    if (dialogName === 'link') {
        var dialogDefinition = event.data.definition,
            infoTab = dialogDefinition.getContents('info'),
            url = infoTab.get('url'),
            linkType = infoTab.get('linkType'),
            targetTab = dialogDefinition.getContents('target'),
            targetType = targetTab.get('linkTargetType');

        // Removes the "Protocol" dropdown from the "Link Info" tab.
        infoTab.remove('protocol');

        // Removes the "Browse Server" button from the "Link Info" tab.
        infoTab.remove('browse');

        // Remove "Link to anchor in the text" option from the "Link Info" tab and uses the regular link type for anchors.
        linkType.setup = function (data) {
            if (data.type === 'anchor') {
                data.url = {protocol: '', url: '#' + data.anchor.name};
                data.type = 'url';
            }

            this.setValue(data.type || 'url');
        }

        linkType.items = [linkType.items[0], linkType.items[2], linkType.items[3]];
        infoTab.remove('anchorOptions');

        // Overrides the default url events.
        url.onKeyUp = function (data) {
        };

        url.setup = function (data) {
            this.allowOnChange = false;

            if (data.url) {
                this.setValue((typeof data.url.protocol == 'string' ? data.url.protocol : '') + data.url.url);
            }

            this.allowOnChange = true;
        };

        url.commit = function (data) {
            data.url = {protocol: '', url: this.getValue()};
        };

        // Only show <not set> and <_blank> targets from the "Target" tab.
        targetType.items = [targetType.items[0], targetType.items[3]];
        targetTab.elements[0].widths = ['100%'];

        // Cannot remove 'popupFeatures' without breaking the target="_blank" functionality, hiding it seems to solve it
        targetTab.get('popupFeatures').style = 'display:none';

        // Hide link target name.
        targetTab.get('linkTargetName').style = 'display:none';
    }
});

CKEDITOR.on('instanceReady', function (event) {
    event.editor.dataProcessor.writer.selfClosingEnd = '>';
    event.editor.dataProcessor.writer.setRules('p', {
        breakBeforeOpen: false,
        breakAfterClose: false
    });
});