<?php
/**
 * UsuarioController: gestión de usuarios y roles (RF-01).
 * Acceso: SOLO Dueño.
 *
 *   ?c=usuario                      lista
 *   ?c=usuario&a=crear              formulario nuevo (GET) / guardar (POST)
 *   ?c=usuario&a=editar&id=N        formulario edición (GET) / actualizar (POST)
 *   ?c=usuario&a=estado             activar / desactivar (POST)
 *
 * Los usuarios NO se borran: tienen órdenes y pagos asociados. Se desactivan.
 */
class UsuarioController extends Controller
{
    private Usuario $modelo;

    public function __construct()
    {
        $this->requiereRol('dueno');
        $this->modelo = new Usuario();
    }

    public function index(): void
    {
        $this->vista('usuarios/index', [
            'titulo'     => 'Usuarios',
            'menuActivo' => 'usuario',
            'usuarios'   => $this->modelo->todos(),
        ]);
    }

    public function crear(): void
    {
        $datos   = ['nombre' => '', 'usuario' => '', 'rol' => 'lavador', 'telefono' => '', 'activo' => 1];
        $errores = [];

        if ($this->esPost()) {
            $datos   = $this->leerFormulario();
            $errores = $this->validar($datos, null);

            if (!$errores) {
                $this->modelo->crear($datos);
                Sesion::flash('exito', 'Usuario "' . $datos['usuario'] . '" creado correctamente.');
                $this->redirigir('usuario');
            }
        }

        $this->vista('usuarios/formulario', [
            'titulo'     => 'Nuevo usuario',
            'menuActivo' => 'usuario',
            'datos'      => $datos,
            'errores'    => $errores,
            'id'         => null,
        ]);
    }

    public function editar(): void
    {
        $id     = (int) ($_GET['id'] ?? 0);
        $actual = $this->modelo->buscarPorId($id);
        if (!$actual) {
            Sesion::flash('error', 'El usuario no existe.');
            $this->redirigir('usuario');
        }

        $datos   = $actual;
        $errores = [];

        if ($this->esPost()) {
            $datos   = $this->leerFormulario();
            $errores = $this->validar($datos, $id);

            if (!$errores) {
                $this->modelo->actualizar($id, $datos);
                Sesion::flash('exito', 'Usuario "' . $datos['usuario'] . '" actualizado.');
                $this->redirigir('usuario');
            }
        }

        $this->vista('usuarios/formulario', [
            'titulo'     => 'Editar usuario',
            'menuActivo' => 'usuario',
            'datos'      => $datos,
            'errores'    => $errores,
            'id'         => $id,
        ]);
    }

    /** Activa o desactiva un usuario (botón de la lista). */
    public function estado(): void
    {
        if (!$this->esPost()) {
            $this->redirigir('usuario');
        }

        $id     = (int) ($_POST['id'] ?? 0);
        $actual = $this->modelo->buscarPorId($id);

        if (!$actual) {
            Sesion::flash('error', 'El usuario no existe.');
        } elseif ($id === $this->usuario['id']) {
            Sesion::flash('error', 'No puede desactivar su propio usuario.');
        } else {
            $activar = !(int) $actual['activo'];
            $this->modelo->cambiarEstado($id, $activar);
            Sesion::flash('exito', 'Usuario "' . $actual['usuario'] . '" ' . ($activar ? 'activado.' : 'desactivado.'));
        }

        $this->redirigir('usuario');
    }

    // ------------------------------------------------------------------
    // Métodos privados de apoyo (el enrutador NO permite llamarlos por URL)
    // ------------------------------------------------------------------

    /** Lee y normaliza los campos del formulario. */
    private function leerFormulario(): array
    {
        return [
            'nombre'    => $this->entrada('nombre'),
            'usuario'   => strtolower($this->entrada('usuario')),
            'rol'       => $this->entrada('rol'),
            'telefono'  => preg_replace('/\D/', '', $this->entrada('telefono')),   // solo dígitos
            'activo'    => isset($_POST['activo']) ? 1 : 0,
            'password'  => (string) ($_POST['password'] ?? ''),
            'password2' => (string) ($_POST['password2'] ?? ''),
        ];
    }

    /**
     * Reglas de validación. Devuelve [campo => mensaje]; vacío = todo bien.
     * $id es null al crear y el id del usuario al editar.
     */
    private function validar(array $d, ?int $id): array
    {
        $errores = [];

        if (mb_strlen($d['nombre']) < 3 || mb_strlen($d['nombre']) > 100) {
            $errores['nombre'] = 'Escriba el nombre completo (entre 3 y 100 caracteres).';
        }

        if (!preg_match('/^[a-z0-9._]{3,50}$/', $d['usuario'])) {
            $errores['usuario'] = 'Use de 3 a 50 caracteres: letras minúsculas, números, punto o guion bajo.';
        } elseif ($this->modelo->existeUsuario($d['usuario'], $id)) {
            $errores['usuario'] = 'Ese nombre de usuario ya está en uso.';
        }

        if (!in_array($d['rol'], Usuario::ROLES, true)) {
            $errores['rol'] = 'Seleccione un rol válido.';
        }

        if ($d['telefono'] !== '' && !preg_match('/^3\d{9}$/', $d['telefono'])) {
            $errores['telefono'] = 'El celular debe tener 10 dígitos y empezar por 3.';
        }

        // Al crear la contraseña es obligatoria; al editar, solo si se quiere cambiar
        if ($id === null || $d['password'] !== '') {
            if (strlen($d['password']) < 8) {
                $errores['password'] = 'La contraseña debe tener al menos 8 caracteres.';
            } elseif ($d['password'] !== $d['password2']) {
                $errores['password2'] = 'Las contraseñas no coinciden.';
            }
        }

        // El Dueño no puede quitarse a sí mismo el rol ni desactivarse (quedaría sin acceso)
        if ($id !== null && $id === $this->usuario['id']) {
            if ($d['rol'] !== 'dueno') {
                $errores['rol'] = 'No puede cambiar su propio rol.';
            }
            if (!$d['activo']) {
                $errores['activo'] = 'No puede desactivar su propio usuario.';
            }
        }

        return $errores;
    }
}
