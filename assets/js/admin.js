jQuery(document).ready(function ( $ ) {
	if(jQuery('.hd_mpi_free_selectize').length){
		jQuery('.hd_mpi_free_selectize').selectize({
		    plugins: ['remove_button'],
		    persist: false,
		    create: true,
		    render: {
		        item: function(data, escape) {
		            return '<div>' + escape(data.text) + '</div>';
		        }
		    },
		    onDelete: function(values) {
		        return confirm(values.length > 1 ? 'Are you sure you want to remove these ' + values.length + ' items?' : 'Are you sure you want to remove "' + values[0] + '"?');
		    }
		});
	}

	if(jQuery('.hd_mpi_module_selectize').length){

		jQuery('.hd_mpi_module_selectize').selectize({
		    maxItems: null,
		    plugins: ['remove_button'],
		    valueField: 'title',
		    labelField: 'title',
		    searchField: 'title',
		    options: hd_mpi_module_options,
		    create: false,
		    onDelete: function(values) {
		        return confirm(values.length > 1 ? 'Are you sure you want to remove these ' + values.length + ' items?' : 'Are you sure you want to remove "' + values[0] + '"?');
		    }
		});
	}

	if(jQuery('.hd_mpi_tags_selectize').length){
		jQuery('.hd_mpi_tags_selectize').selectize({
		    maxItems: null,
		    plugins: ['remove_button'],
		    valueField: 'title',
		    labelField: 'title',
		    searchField: 'title',
		    options: hd_mpi_tags_options,
		    create: false,
		    onDelete: function(values) {
		        return confirm(values.length > 1 ? 'Are you sure you want to remove these ' + values.length + ' items?' : 'Are you sure you want to remove "' + values[0] + '"?');
		    }
		});
	}

	if(jQuery('.hd_mpi_labels_selectize').length){
		jQuery('.hd_mpi_labels_selectize').selectize({
		    maxItems: null,
		    plugins: ['remove_button'],
		    valueField: 'title',
		    labelField: 'title',
		    searchField: 'title',
		    options: hd_mpi_labels_options,
				persist: false,
		    create: true,
		    onDelete: function(values) {
		        return confirm(values.length > 1 ? 'Are you sure you want to remove these ' + values.length + ' items?' : 'Are you sure you want to remove "' + values[0] + '"?');
		    }
		});
	}

	// Sync with morphing portals
	$("#hd_mpi_button_sync").live('click',function(e){
  			var answer = confirm("Are you sure you want to start sync with portal?");
		    if (answer){
			    $( "#hd_list_of_events" ).empty();
			    $('<p>Sync started!</p>').appendTo( "#hd_list_of_events" );
			    mpImportNextAjax(0);
			  }
  });

  function mpImportNextAjax(offset){
		  var ajaxFunction = 'hd_mpi_sync_morphing_portals';
			$.ajax({
		      url: ajaxurl,
		      dataType: 'JSON',
		      data: {
		          'action': ajaxFunction,
		          'offset': offset
		      },
		      success:function(response) {
		        if(response){
			      if(response.result == 'success'){
				    offset += 1;
				    var finished = response.finished;

						if(response.text && response.text !== ''){
							response_text = "<p id='hd_import_status'>" + response.text + ".</p>";
	  					$(response_text).appendTo( "#hd_list_of_events" );
						}

  					if(finished){
				  	  $('<p>Sync completed with success!</p>').appendTo( "#hd_list_of_events" );
				  	  setTimeout(function(){
						window.location.reload(1);
					  }, 5000);
			    	}
			    	else{
					  mpImportNextAjax(offset);
					}
			      }
			      else{
							if(response.text && response.text !== ''){
				    		response_text = "<p id='hd_import_status'>" + response.text + "</p>";
				    		$( "#hd_import_status" ).remove();
								$(response_text).appendTo( "#hd_list_of_events" );
				     	}
			      }

		        }
		      },
		      error: function(errorThrown){
		      }
		    });

  	}

  	if(jQuery('#module_url').length && jQuery('#module_html').length){
	  	$( "#module_url" ).change(function() {
		  if($(this).val() == ''){
			  $("#module_html").prop('disabled', false);
		  }
		  else{
			  $("#module_html").prop('disabled', true);
		  }
		});

		$( "#module_html" ).change(function() {
		  if($(this).val() == ''){
			  $("#module_url").prop('disabled', false);
		  }
		  else{
			  $("#module_url").prop('disabled', true);
		  }
		});

  	}

  	$("#hd_mpi_settings_button").click(function(e) {
	  	var old_api = $("#hd_mpi_old_api").val();
	  	var new_api = $("#hd_mpi_api_token").val();
	  	if(old_api && new_api && new_api !== old_api){
	    	alert("After changing account you will need to syncronize items from the portal.");
	    }
	});

});
