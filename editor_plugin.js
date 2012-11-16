(function() {
	tinymce.create('tinymce.plugins.f6s', {

		init : function( ed, url ) {
			var t = this;
			t.url = url;

			// Replace shortcode before editor content set
			ed.onBeforeSetContent.add(function( ed, o ) {
				o.content = t._do_spot( o.content );
			});

			// Replace shortcode as its inserted into editor (which uses the exec command)
			ed.onExecCommand.add(function( ed, cmd ) {
				if ( 'mceInsertContent' == cmd )
					tinyMCE.activeEditor.setContent( t._do_spot( tinyMCE.activeEditor.getContent() ) );
			});

			// Replace the image back to shortcode on save
			ed.onPostProcess.add(function( ed, o ) {
				if ( o.get )
					o.content = t._get_spot( o.content );
			});
		},

		_do_spot : function( co ) {
			return co.replace(/\[f6s-data\]((.|[\r\n])*?)\[\/f6s-data\]/g, function( a, b ) {
				return '<img src="' + f6s_plugin_dir + '/images/empty.gif" class="f6sDealList mceItem" data-options="' + tinymce.DOM.encode( b ) + '" />';
			});
		},

		_get_spot : function( co ) {

			function getAttr(s, n) {
				n = new RegExp( n + '=\"([^\"]+)\"', 'g' ).exec( s );
				return n ? tinymce.DOM.decode( n[1] ) : '';
			};

			return co.replace( /(?:<p[^>]*>)*(<img[^>]+>)(?:<\/p>)*/g, function( a, im ) {
				var cls = getAttr( im, 'class' );

				if ( -1 != cls.indexOf( 'f6sDealList' ) )
				return '[f6s-data]' + tinymce.trim( getAttr( im, 'data-options' ) ) + '[/f6s-data]';

				return a;
			});
		}
	});

	tinymce.PluginManager.add( 'f6s', tinymce.plugins.f6s );
})();
