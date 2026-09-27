/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config_completo ) {
	// Define changes to default configuration here.
	// For the complete reference:
	// http://docs.ckeditor.com/#!/api/CKEDITOR.config

	config_completo.toolbar = null;
	
	// The toolbar groups arrangement, optimized for two toolbar rows.
	/*config.toolbar = [
	{ name: 'document', groups: [ 'mode', 'document', 'doctools' ], items: [ 'Source' ] },
	{ name: 'clipboard', groups: [ 'clipboard', 'undo' ], items: [ 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ] },
	{ name: 'editing', groups: [ 'find', 'selection', 'spellchecker' ], items: [ 'Scayt' ] },
	'/',
	{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ], items: [ 'Bold', 'Italic', 'Strike', '-', 'RemoveFormat' ] },
	{ name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align' ], items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote' ] },
	{ name: 'links', items: [ 'Link', 'Unlink', 'Anchor' ] },
	{ name: 'insert', items: [ 'Image', 'Table', 'HorizontalRule', 'SpecialChar' ] },
	'/',
	{ name: 'styles', items: [ 'Styles', 'Format' ] },
	{ name: 'tools', items: [ 'Maximize' ] },
	{ name: 'others', items: [ '-' ] },
	{ name: 'about', items: [ 'About' ] }
];*/

	config_completo.toolbar = [
		{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ], items: [ 'Bold', 'Italic', 'Strike', '-', 'RemoveFormat' ] },
		{ name: 'clipboard', groups: [ 'clipboard', 'undo' ], items: [ 'Cut', 'Copy', 'PasteText', '-', 'Undo', 'Redo' ] },
		//{ name: 'editing', groups: [ 'find', 'selection', 'spellchecker' ], items: [ 'Scayt' ] },
		{ name: 'links', items: [ 'Link', 'Unlink'] },
		//{ name: 'document', groups: [ 'mode', 'document', 'doctools' ], items: [ 'Source' ] },
		//{ name: 'others', items: [ '-' ] },
		//'/',
		{ name: 'insert', items: [ 'Image', 'Table', 'HorizontalRule'] },
		{ name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align' ], items: [ 'NumberedList', 'BulletedList', '-', 'Blockquote' ] },
		//{ name: 'styles', items: [ 'Styles' ] },
	    { name: 'styles', items : [ 'Styles','TextColor'] },
		{ name: 'tools', items: [ 'Maximize' ] }
		//{ name: 'about', items: [ 'About' ] }
	];
	
	config_completo.width = 800;     // 800 pixels wide.
	config_completo.height = 300;     // 300 pixels wide.

	// Remove some buttons, provided by the standard plugins, which we don't
	// need to have in the Standard(s) toolbar.
	config_completo.removeButtons = 'Underline,Subscript,Superscript';

	// Se the most common block elements.
	config_completo.format_tags = 'p;h1;h2;h3;pre';

	// Make dialogs simpler.
	config_completo.removeDialogTabs = 'image:advanced;link:advanced';
	
	config_completo.stylesSet = 'my_styles';
	
};

CKEDITOR.stylesSet.add( 'my_styles', [
    // Inline styles
    { name: 'Destaque Laranja', element: 'span', styles: { 'font-size': '13px', 'color': '#f6822b' } },
    { name: 'Destaque Vinho' , element: 'span', styles: { 'font-size': '13px', 'color': '#7e031f' } },
	
    // Inline styles
    //{ name: 'Marker: Yellow', element: 'span', styles: { 'background-color': 'Yellow' } }
]);
