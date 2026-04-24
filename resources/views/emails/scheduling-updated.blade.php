<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Agendamento Atualizado</title>
</head>
<body>
    <h1>Agendamento Atualizado</h1>
    <p><strong>Cliente:</strong> {{ $scheduling->client->user->name }}</p>
    <p><strong>Novo Início:</strong> {{ $scheduling->start_date }}</p>
    <p><strong>Novo Fim:</strong> {{ $scheduling->end_date }}</p>
</body>
</html>
