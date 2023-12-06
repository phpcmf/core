<!doctype html>
<html @if ($direction) dir="{{ $direction }}" @endif
      @if ($language) lang="{{ $language }}" @endif>
    <head>
        <meta charset="utf-8">
        <title>{{ $title }}</title>

        {!! $head !!}
    </head>

    <body>
        {!! $layout !!}

        <div id="modal"></div>
        <div id="alerts"></div>

        <script>
            document.getElementById('cmf-loading').style.display = 'block';
            var cmf = {extensions: {}};
        </script>

        {!! $js !!}

        <script id="cmf-json-payload" type="application/json">@json($payload)</script>

        <script>
            const data = JSON.parse(document.getElementById('cmf-json-payload').textContent);
            document.getElementById('cmf-loading').style.display = 'none';

            try {
                cmf.core.app.load(data);
                cmf.core.app.bootExtensions(cmf.extensions);
                cmf.core.app.boot();
            } catch (e) {
                var error = document.getElementById('cmf-loading-error');
                error.innerHTML += document.getElementById('cmf-content').textContent;
                error.style.display = 'block';
                throw e;
            }
        </script>

        {!! $foot !!}
    </body>
</html>
