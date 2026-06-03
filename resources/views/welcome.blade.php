<!DOCTYPE html>
<html>
<head>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/suggestions-jquery@20.4.1/dist/js/jquery.suggestions.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/suggestions-jquery@20.4.1/dist/css/suggestions.min.css">
</head>
<body>
    <div id="app"></div>
    <script>
        window.appName = '{{ config('app.name') }}';
    </script>
</body>
</html>