CKEDITOR.skin.name = 'skeleton';
CKEDITOR.skin.ua_editor="";
CKEDITOR.skin.ua_dialog="";
CKEDITOR.skin.icons = {};

var icons = ['blockquote', 'bold', 'bulletedlist', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'italic', 'link', 'linkbutton', 'numberedlist', 'removeformat', 'source', 'strike', 'table', 'underline', 'unlink'],
    path = CKEDITOR.skin.path() + 'icons/';

for (var i = 0; i < icons.length; i++) {
    CKEDITOR.skin.addIcon(icons[i], path + icons[i] + '.svg');
}