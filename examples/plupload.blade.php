<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Plupload</title>
</head>
<body>
    <h1>Plupload</h1>

    <div id="container">
        <a id="pickfiles" href="javascript:void(0);">[Select files]</a>
        <a id="uploadfiles" href="javascript:void(0);">[Upload files]</a>
    </div>

    <ul id="filelist">
        <li>Your browser doesn't have HTML5 support.</li>
    </ul>

    <script src="//cdn.jsdelivr.net/gh/moxiecode/plupload/js/plupload.full.min.js"></script>
    <script>
        function getCookieValue(a) {
            const b = document.cookie.match('(^|;)\\s*' + a + '\\s*=\\s*([^;]+)');
            return b ? b.pop() : '';
        }

        const uploadUrl = "{{ url('upload') }}";
        const uploader = new plupload.Uploader({
            browse_button: 'pickfiles',
            container: document.getElementById('container'),
            url: uploadUrl,
            chunk_size: 1024 * 1024,

            headers: {'X-XSRF-TOKEN': decodeURIComponent(getCookieValue('XSRF-TOKEN'))},

            init: {
                PostInit: function () {
                    document.getElementById('filelist').innerHTML = '';

                    document.getElementById('uploadfiles').onclick = () => {
                        uploader.start();
                        return false;
                    };
                },

                FilesAdded: function (up, files) {
                    plupload.each(files, function (file) {
                        const node = document.createElement('li');
                        node.innerHTML = file.name + ' (' + plupload.formatSize(file.size) + ')';
                        document.getElementById('filelist').append(node);
                    });
                },
            }
        });

        uploader.init();
    </script>
</body>
</html>
