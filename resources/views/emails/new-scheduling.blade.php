<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Novo Agendamento</title>
</head>
<body>
    <h1>Novo Agendamento Criado</h1>
    <p><strong>Cliente:</strong> {{ $scheduling->client->user->name }}</p>
    <p><strong>Início:</strong> {{ $scheduling->start_date }}</p>
    <p><strong>Fim:</strong> {{ $scheduling->end_date }}</p>
</body>
</html>
