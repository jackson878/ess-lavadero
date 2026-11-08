<?php
/**
 * InicioController: página de bienvenida para Cajero y Lavador.
 * (En las fases 4 y 5 cada uno tendrá su pantalla de trabajo propia.)
 */
class InicioController extends Controller
{
    public function index(): void
    {
        $usuario = $this->requiereLogin();

        $this->vista('inicio/index', [
            'titulo'     => 'Inicio',
            'menuActivo' => '',
            'opciones'   => menu_por_rol($usuario['rol']),
        ]);
    }
}
