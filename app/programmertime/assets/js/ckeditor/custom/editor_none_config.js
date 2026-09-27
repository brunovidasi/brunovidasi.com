/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here.
	// For the complete reference:
	// http://docs.ckeditor.com/#!/api/CKEDITOR.config

	config.toolbar = null;
	
	// The toolbar groups arrangement, optimized for two toolbar rows.
	config.toolbar = [
		// { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ], items: [ 'Bold', 'Italic', 'Strike', '-', 'RemoveFormat' ] },
		// { name: 'links', items: [ 'Link', 'Unlink'] },
		// { name: 'clipboard', groups: [ 'clipboard', 'undo' ], items: [ 'Cut', 'Copy', 'PasteText', '-', 'Undo', 'Redo' ] },
		// { name: 'editing', groups: [ 'find', 'selection', 'spellchecker' ], items: [ 'Scayt' ] },
		// { name: 'insert', items: [ 'Image', 'Table', 'HorizontalRule', 'SpecialChar' ] },
		// { name: 'tools', items: [ 'Maximize' ] },
		// { name: 'document', groups: [ 'mode', 'document', 'doctools' ], items: [ 'Source' ] },
		// { name: 'others', items: [ '-' ] },
		//'/',
		// { name: 'paragraph', groups: [ 'list', 'indent', 'blocks', 'align' ], items: [ 'NumberedList', 'BulletedList', '-', 'Blockquote' ] },
		// { name: 'styles', items: [ 'Styles' ] },
		//{ name: 'about', items: [ 'About' ] }
	];
	
	config.width = 800;     // 800 pixels wide.
	config.height = 100;     // 100 pixels wide.
	
	// Remove some buttons, provided by the standard plugins, which we don't
	// need to have in the Standard(s) toolbar.
	config.removeButtons = 'Underline,Subscript,Superscript';

	// Se the most common block elements.
	config.format_tags = 'p;h1;h2;h3;pre';

	// Make dialogs simpler.
	config.removeDialogTabs = 'image:advanced;link:advanced';
};
