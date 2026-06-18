<?php
class ArchivoLogDecorator extends ArchivoDecorator
{
    public function guardarArchivo(int $idUsuario, string $nombreArchivoP, string $ruta,
        string $contenido, string $fechaCreacion, string $fechaModificacion, string $firma): void
    {
        $this->wrapped->guardarArchivo($idUsuario, $nombreArchivoP, $ruta,
            $contenido, $fechaCreacion, $fechaModificacion, $firma);
        $this->registrarBitacora($idUsuario, $nombreArchivoP, 'CREAR');
    }

    public function actualizarArchivo(int $idUsuario, string $nombreArchivoP, string $ruta,
        string $contenido, string $fechaModificacionNueva, string $firma): void
    {
        $this->wrapped->actualizarArchivo($idUsuario, $nombreArchivoP, $ruta,
            $contenido, $fechaModificacionNueva, $firma);
        $this->registrarBitacora($idUsuario, $nombreArchivoP, 'MODIFICAR');
    }

    private function registrarBitacora(int $idUsuario, string $nombreArchivo, string $accion): void
    {
        // aquí insertas en tabla Bitacora cuando la tengas
        // por ahora puede quedar como log en archivo o vacío
        error_log("[BITACORA] Usuario $idUsuario — $accion — $nombreArchivo");
    }
}