<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Resumable.js</title>
</head>
<body>
    <h1>Resumable.js</h1>

    <span class="btn btn-default">
        <input id="browseButton" type="file" multiple="multiple">
    </span>

    <script src="//cdn.jsdelivr.net/gh/23/resumable.js/resumable.js"></script>
    <script>
        function getCookieValue(a) {
            const b = document.cookie.match('(^|;)\\s*' + a + '\\s*=\\s*([^;]+)');
            return b ? b.pop() : '';
        }

        const r = new Resumable({
            target: "{{ url('upload') }}",
            headers: {'X-XSRF-TOKEN': decodeURIComponent(getCookieValue('XSRF-TOKEN'))},
            forceChunkSize: true,
        });
        r.assignBrowse(document.getElementById('browseButton'));

        r.on('fileAdded', function (file, event) {
            setTimeout(() => {
                r.upload();
            })
        });
    </script>
</body>
</html>
