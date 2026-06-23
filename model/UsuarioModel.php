<?php

require_once __DIR__ . '/Core/Database.php';

class UsuarioModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function buscarPorNombre(string $nombre): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Usuario WHERE nombre = ?");
        $stmt->execute([$nombre]);

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        return $usuario ?: null;
    }

    public function buscarPorCorreo(string $correo): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM Usuario WHERE correo = ?");
        $stmt->execute([$correo]);

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        return $usuario ?: null;
    }

    public function registrarUsuario(string $correo, string $nombre, string $nombreUsuario, string $contrasena, string $rol): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO Usuario (correo, nombre, nombreUsuario, contrasena, rol) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$correo, $nombre, $nombreUsuario, $contrasena, $rol]);
        return (int) $this->pdo->lastInsertId();
    }


    public function verificarRol(string $idUsuario): ?array
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $this->pdo->prepare("SELECT rol FROM Usuario WHERE idUsuario = ?");
        $stmt->execute([$idUsuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function verPerfil(string $idUsuario): ?array
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $this->pdo->prepare("SELECT * FROM Usuario WHERE idUsuario = ?");
        $stmt->execute([$idUsuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function cambiarNombre(string $idUsuario, string $nuevoNombre): bool
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $this->pdo->prepare("UPDATE Usuario SET nombre = ? WHERE idUsuario = ?");
        return $stmt->execute([$nuevoNombre, $idUsuario]);
    }

    public function cambiarCorreo(string $idUsuario, string $nuevoCorreo): bool
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $this->pdo->prepare("UPDATE Usuario SET correo = ? WHERE idUsuario = ?");
        return $stmt->execute([$nuevoCorreo, $idUsuario]);
    }
}