<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Flow.js</title>
</head>
<body>
    <h1>Flow.js</h1>

    <span class="btn btn-default">
        <input id="browseButton" type="file" multiple="multiple">
    </span>

    <script src="//cdn.jsdelivr.net/gh/flowjs/flow.js/dist/flow.min.js"></script>
    <script>
        function getCookieValue(a) {
            const b = document.cookie.match('(^|;)\\s*' + a + '\\s*=\\s*([^;]+)');
            return b ? b.pop() : '';
        }

        const flow = new Flow({
            target: "{{ url('upload') }}",
            headers: {'X-XSRF-TOKEN': decodeURIComponent(getCookieValue('XSRF-TOKEN'))},
            forceChunkSize: true,
        });
        flow.assignBrowse(document.getElementById('browseButton'));

        flow.on('fileAdded', function (file, event) {
            setTimeout(() => {
                flow.upload();
            })
        });
    </script>
</body>
</html>
