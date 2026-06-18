<?php

interface IArchivoRepositorio
{
    public function guardarArchivo(int $idUsuario, string $nombreArchivoP, string $ruta,
        string $contenido, string $fechaCreacion, string $fechaModificacion, string $firma): void;

    public function actualizarArchivo(int $idUsuario, string $nombreArchivoP, string $ruta,
        string $contenido, string $fechaModificacionNueva, string $firma): void;

    public function buscarArchivo(int $idUsuario, string $nombreArchivoP, string $ruta): ?array;
}