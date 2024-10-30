jQuery(document).ready(function ( $ ) {
    tinymce.PluginManager.add('mpi_ph_button', function( editor, url ) {
        editor.addButton( 'mpi_ph_button', {
            title: 'Add placeholder',
            tooltip: 'Add MP Placeholder',
            image: hd_mpi_url + '/assets/img/favicon.ico',
              onclick: function() {
                editor.windowManager.open( {
                    title: 'Add Placeholder',
                    body: [{
                        type: 'textbox',
                        name: 'placeholder',
                        label: 'Placeholder ID',
                        placeholder: 'Placeholder id here'

                    },
					],
                    onsubmit: function( e ) {
                        editor.insertContent( '[hd_mp_placeholder id=' + e.data.placeholder +  ']');
                    }
                });
              }

        });
    });
});
