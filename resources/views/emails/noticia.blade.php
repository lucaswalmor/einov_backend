<!DOCTYPE html>
<html>
<head>
    <title>Nova Notícia</title>
</head>
<body>
    <h1>{{ $novaNoticia['titulo'] }}</h1>
    <p>Leia mais: <a href="{{ $novaNoticia['link'] }}">{{ $novaNoticia['link'] }}</a></p>
</body>
</html>
