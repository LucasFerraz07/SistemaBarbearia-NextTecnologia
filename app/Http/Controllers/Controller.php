<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Sistema Barbearia API',
    version: '1.0.0',
    description: 'API RESTful para gerenciamento de barbearia',
    contact: new OA\Contact(email: 'seuemail@email.com')
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
#[OA\Server(
    url: 'http://localhost:8000',
    description: 'Servidor local de desenvolvimento'
)]
abstract class Controller
{
    //
}