<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Blueimp</title>
</head>
<body>
    <h1>Blueimp</h1>

    <span class="btn btn-default">
        <input id="browseButton" type="file" multiple="multiple">
    </span>

    <script src="//ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="//cdn.jsdelivr.net/gh/blueimp/jQuery-File-Upload/js/vendor/jquery.ui.widget.js"></script>
    <script src="//cdn.jsdelivr.net/gh/blueimp/jQuery-File-Upload/js/jquery.iframe-transport.js"></script>
    <script src="//cdn.jsdelivr.net/gh/blueimp/jQuery-File-Upload/js/jquery.fileupload.js"></script>
    <script>
        const uploadUrl = "{{ url('upload') }}";
        $('#browseButton').fileupload({
            url: uploadUrl,
            maxChunkSize: 100 * 1024,
            formData: {
                _token: "{{ csrf_token() }}"
            },
            // Resuming file uploads
            // https://github.com/blueimp/jQuery-File-Upload/wiki/Chunked-file-uploads#resuming-file-uploads
            add: function (e, data) {
                $.ajax({
                    url: uploadUrl,
                    dataType: "json",
                    data: {
                        file: data.files[0].name,
                        totalSize: data.files[0].size
                    },
                    success: (result) => {
                        const file = result.file;
                        data.uploadedBytes = file && file.size;
                        $.blueimp.fileupload.prototype
                            .options.add.call(this, e, data);
                    },
                });
            },
        });
    </script>
</body>
</html>
