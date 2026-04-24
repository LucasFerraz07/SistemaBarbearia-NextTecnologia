<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Agendamento Cancelado</title>
</head>
<body>
    <h1>Agendamento Cancelado</h1>
    <p><strong>Cliente:</strong> {{ $scheduling->client->user->name }}</p>
    <p><strong>Data/Horário:</strong> {{ $scheduling->start_date }}</p>
</body>
</html>
