<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OpenAIService;

class TestController extends Controller
{
    public function index()
    {
        $prompt = "Escribe una historia muy corta sobre un robot curioso.";
        $respuesta = OpenAIService::prompt($prompt);


        if ($respuesta) {
            return response($respuesta);
        } else {
            return response('Error al obtener la respuesta de OpenAI.', 500);
        }
    }
}
