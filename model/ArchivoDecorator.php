<?php

abstract class ArchivoDecorator implements IArchivoRepositorio
{
    public function __construct(protected IArchivoRepositorio $wrapped) {}

    // delega todo al wrapped por defecto, las subclases sobreescriben lo que necesitan
    public function guardarArchivo(int $idUsuario, string $nombreArchivoP, string $ruta,
        string $contenido, string $fechaCreacion, string $fechaModificacion, string $firma): void
    {
        $this->wrapped->guardarArchivo($idUsuario, $nombreArchivoP, $ruta,
            $contenido, $fechaCreacion, $fechaModificacion, $firma);
    }

    public function actualizarArchivo(int $idUsuario, string $nombreArchivoP, string $ruta,
        string $contenido, string $fechaModificacionNueva, string $firma): void
    {
        $this->wrapped->actualizarArchivo($idUsuario, $nombreArchivoP, $ruta,
            $contenido, $fechaModificacionNueva, $firma);
    }

    public function buscarArchivo(int $idUsuario, string $nombreArchivoP, string $ruta): ?array
    {
        return $this->wrapped->buscarArchivo($idUsuario, $nombreArchivoP, $ruta);
    }
}