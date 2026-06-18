<?php
class ArchivoFirmaDecorator extends ArchivoDecorator
{
    public function guardarArchivo(int $idUsuario, string $nombreArchivoP, string $ruta,
        string $contenido, string $fechaCreacion, string $fechaModificacion, string $firma): void
    {
        $this->validarFirma($contenido, $firma);
        $this->wrapped->guardarArchivo($idUsuario, $nombreArchivoP, $ruta,
            $contenido, $fechaCreacion, $fechaModificacion, $firma);
    }

    public function actualizarArchivo(int $idUsuario, string $nombreArchivoP, string $ruta,
        string $contenido, string $fechaModificacionNueva, string $firma): void
    {
        $this->validarFirma($contenido, $firma);
        $this->wrapped->actualizarArchivo($idUsuario, $nombreArchivoP, $ruta,
            $contenido, $fechaModificacionNueva, $firma);
    }

    private function validarFirma(string $contenido, string $firma): void
    {
        $esperada = hash('sha256', $contenido);
        if ($esperada !== $firma) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Firma no coincide con el contenido']);
            exit;
        }
    }
}