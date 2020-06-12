<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>simple-uploader.js</title>
</head>
<body>
    <h1>simple-uploader.js</h1>

    <span class="btn btn-default">
        <input id="browseButton" type="file" multiple="multiple">
    </span>

    <script src="//cdn.jsdelivr.net/gh/simple-uploader/Uploader/dist/uploader.min.js"></script>
    <script>
        function getCookieValue(a) {
            const b = document.cookie.match('(^|;)\\s*' + a + '\\s*=\\s*([^;]+)');
            return b ? b.pop() : '';
        }

        const uploader = new Uploader({
            target: "{{ url('upload') }}",
            headers: {'X-XSRF-TOKEN': decodeURIComponent(getCookieValue('XSRF-TOKEN'))},
            forceChunkSize: true,
        });
        uploader.assignBrowse(document.getElementById('browseButton'));

        uploader.on('fileAdded', function (file, event) {
            setTimeout(() => {
                uploader.upload();
            })
        });
    </script>
</body>
</html>
