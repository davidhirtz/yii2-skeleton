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
    var dialogName = event.data.name,
        dialogDefinition = event.data.definition;

    if (dialogName === 'link') {
        var infoTab = dialogDefinition.getContents('info'),
            url = infoTab.get('url'),
            targetTab = dialogDefinition.getContents('target'),
            targetType = targetTab.get('linkTargetType');

        infoTab.remove('protocol');
        infoTab.remove('emailOptions');
        infoTab.remove('telOptions');
        infoTab.remove('anchorOptions');
        infoTab.remove('browse');

        // Cannot remove linkType without breaking the functionality...
        infoTab.get('linkType').style = 'display: none';

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

        // Remove useless targets.
        targetType.items = [targetType.items[0], targetType.items[3]];
        targetTab.remove('linkTargetName');
        targetTab.remove('popupFeatures');
        targetTab.elements[0].widths = ['100%'];
    }
});