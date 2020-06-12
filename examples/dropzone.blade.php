<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>DropzoneJS</title>

    <link rel="stylesheet" href="//cdn.jsdelivr.net/gh/enyo/dropzone/dist/dropzone.css">
</head>
<body>
    <h1>DropzoneJS</h1>

    <form action="{{ url('upload') }}" class="dropzone" id="my-dropzone">
        @csrf
        <span class="btn btn-default">
            <input id="browseButton" type="file" multiple="multiple" style="display: none">
        </span>
    </form>

    <script src="//cdn.jsdelivr.net/gh/enyo/dropzone/dist/dropzone.js"></script>
    <script>
        Dropzone.autoDiscover = false;

        // https://gitlab.com/meno/dropzone/-/wikis/faq#chunked-uploads
        const dz = new Dropzone('#my-dropzone', {
            chunking: true,
            method: 'POST',
            chunkSize: 1024 * 1024,
            parallelChunkUploads: true
        });
    </script>
</body>
</html>
