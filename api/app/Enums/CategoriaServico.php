<?php

declare(strict_types=1);

namespace App\Enums;

enum CategoriaServico: string
{
    use EnumTrait;

    case Conserto = 'conserto';
    case SobMedida = 'sob_medida';
    case Ajuste = 'ajuste';
    case Outro = 'outro';
}
