<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\DashboardService;

final class DashboardController
{
    public function __construct(private readonly DashboardService $dashboard)
    {
    }

    public function resumo(Request $req): Response
    {
        return Response::json($this->dashboard->resumo());
    }
}
