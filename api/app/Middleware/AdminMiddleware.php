<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Enums\Perfil;
use App\Exceptions\ProibidoException;
use App\Models\Usuario;

/** Restringe a rota ao perfil administrador (403). */
final class AdminMiddleware
{
    public function tratar(Request $req): void
    {
        $usuario = $req->atributo('usuario');
        if (!$usuario instanceof Usuario || $usuario->perfil !== Perfil::Admin) {
            throw new ProibidoException('Somente o administrador pode acessar esta área.');
        }
    }
}
