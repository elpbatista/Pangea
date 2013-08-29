$(function(){
	var dropbox = $('.dropbox'),
		message = $('.message', dropbox);
	
		dropbox.filedrop({
		// The name of the $_FILES entry:
		paramname:'pic',
		maxfiles: 5,
    maxfilesize: 2,
		url: '../post_file.php',
		
		uploadFinished:function(i,file,response){
			$.data(file).addClass('done');
			// response is the JSON object that post_file.php returns
		},
		
    	error: function(err, file) {
			switch(err) {
				case 'BrowserNotSupported':
					showMessage('Your browser does not support HTML5 file uploads!');
					break;
				case 'TooManyFiles':
					alert('Too many files! Please select 5 at most! (configurable)');
					break;
				case 'FileTooLarge':
					alert(file.name+' is too large! Please upload files up to 2mb (configurable).');
					break;
				default:
					break;
			}
		},
		
		// Called before each upload is started
		beforeEach: function(file){
			if(!file.type.match(/^image\//)){
				alert('Only images are allowed!');
				
				// Returning false will cause the
				// file to be rejected
				return false;
			}
		},
		
		uploadStarted:function(i, file, len){
			createImage(file);
		},
		
		progressUpdated: function(i, file, progress) {
			$.data(file).find('.progress').width(progress);
		}
    	 
	});
	
	var template = '<div class="preview">'+
						'<span class="imageHolder">'+
							'<img />'+
							'<span class="uploaded"></span>'+
						'</span>'+
						'<div class="progressHolder">'+
							'<div class="progress"></div>'+
						'</div>'+
					'</div>'; 
	
	
	function createImage(file){

		var preview = $(template), 
			image = $('img', preview);
			
		var reader = new FileReader();
		
		image.width = 100;
		image.height = 100;
		
		reader.onload = function(e){
			
			// e.target.result holds the DataURL which
			// can be used as a source of the image:
			
			image.attr('src',e.target.result);
		};
		
		// Reading the file as a DataURL. When finished,
		// this will trigger the onload function above:
		reader.readAsDataURL(file);
		
		message.hide();
		preview.appendTo(dropbox);
		
		// Associating a preview container
		// with the file, using jQuery's $.data():
		
		$.data(file,preview);
	}

	function showMessage(msg){
		message.html(msg);
	}

});

/*
@{
    ViewBag.Title = "Index";
}
<style type="text/css">
    #dropZone {
        background: gray;
        border: black dashed 3px;
        width: 200px;
        padding: 50px;
        text-align: center;
        color: white;
    }
</style>
<script type="text/javascript">
    $(function () {
        $('#dropZone').filedrop({
            url: '@Url.Action("UploadFiles")',
            paramname: 'files',
            maxFiles: 5,
            dragOver: function () {
                $('#dropZone').css('background', 'blue');
            },
            dragLeave: function () {
                $('#dropZone').css('background', 'gray');
            },
            drop: function () {
                $('#dropZone').css('background', 'gray');
            },
            afterAll: function () {
                $('#dropZone').html('The file(s) have been uploaded successfully!');
            },
            uploadFinished: function (i, file, response, time) {
                $('#uploadResult').append('<li>' + file.name + '</li>');
            }
        });
    });
</script>

<h2>File Drag & Drop Upload Demo</h2>
<div id="dropZone">Drop your files here</div>


Uploaded Files:
<ul id="uploadResult">

</ul>

*/