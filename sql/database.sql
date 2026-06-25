-- Base de datos para acortador de URLs
CREATE DATABASE IF NOT EXISTS sistema_entregas
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sistema_entregas;

-- Tabla Usuarios
CREATE TABLE IF NOT EXISTS Usuario (
    idUsuario       INT             AUTO_INCREMENT,
    correo          VARCHAR(255)    NOT NULL UNIQUE,
    nombreUsuario   VARCHAR(100)    NOT NULL UNIQUE,
    nombre          VARCHAR(255)    NOT NULL,
    contrasena      VARCHAR(255)    NOT NULL,
    rol             ENUM('ESTUDIANTE','PROFESOR') NOT NULL DEFAULT 'ESTUDIANTE',
    fechaCreacion   DATETIME        NOT NULL DEFAULT NOW(),
    PRIMARY KEY (idUsuario),
    CONSTRAINT chk_correo CHECK (correo REGEXP '^[a-zA-Z0-9._%+\\-]+@[a-zA-Z0-9.\\-]+\\.[a-zA-Z]{2,}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Tabla Curso
CREATE TABLE IF NOT EXISTS Curso (
    idCurso         INT             AUTO_INCREMENT,
    idUsuario       INT             NOT NULL,
    nombreCurso     VARCHAR(255)    NOT NULL UNIQUE,
    descripcion     TEXT            NULL,
    PRIMARY KEY (idCurso),
    CONSTRAINT fk_Curso_idUsuario FOREIGN KEY (idUsuario) REFERENCES Usuario(idUsuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla Tarea
CREATE TABLE IF NOT EXISTS Tarea (
    idTarea         INT             NOT NULL AUTO_INCREMENT,
    idUsuario       INT             NOT NULL,
    idCurso         INT             NOT NULL,
    nombreTarea     VARCHAR(255)    NOT NULL,
    descripcion     TEXT            NULL,
    fechaCreacion   DATETIME        NOT NULL DEFAULT NOW(),
    fechaEntrega    DATETIME        NULL,
    esGrupal        TINYINT(1)      NOT NULL,
    PRIMARY KEY (idTarea),
    CONSTRAINT fk_tarea_idUsuario FOREIGN KEY (idUsuario) REFERENCES Usuario(idUsuario),
    CONSTRAINT fk_tarea_curso FOREIGN KEY (idCurso) REFERENCES Curso(idCurso),
    CONSTRAINT chk_fechaEntrega CHECK (fechaEntrega > fechaCreacion),
    CONSTRAINT chk_esGrupal CHECK (esGrupal IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla GrupoTrabajo
CREATE TABLE IF NOT EXISTS GrupoTrabajo (
    idGrupoTrabajo              INT             NOT NULL AUTO_INCREMENT,
    idUsuario                   INT             NOT NULL,
    idCurso                     INT             NOT NULL,
    nombreGrupoTrabajo          VARCHAR(255)    NOT NULL,
    PRIMARY KEY (idGrupoTrabajo),
    CONSTRAINT fk_profesor FOREIGN KEY (idUsuario) REFERENCES Usuario(idUsuario),
    CONSTRAINT fk_curso FOREIGN KEY (idCurso) REFERENCES Curso(idCurso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ArchivoP (
    idArchivoP                  INT             NOT NULL AUTO_INCREMENT,
    idUsuario                   INT             NOT NULL,
    nombreArchivoP              VARCHAR(255)    NOT NULL,
    ruta                        VARCHAR(500)    NOT NULL,
    contenido                   LONGTEXT        NULL,
    fechaCreacion               DATETIME        NOT NULL DEFAULT NOW(),
    fechaModificacion           DATETIME        NULL,
    firma                       VARCHAR(64)     NULL,
    PRIMARY KEY (idArchivoP),
    CONSTRAINT fk_archivop_usuario FOREIGN KEY (idUsuario) REFERENCES Usuario(idUsuario),
    CONSTRAINT chk_nombreArchivoP CHECK (nombreArchivoP LIKE '%.py')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla Entrega
CREATE TABLE IF NOT EXISTS Entrega (
    idEntrega           INT             NOT NULL AUTO_INCREMENT,
    idUsuario           INT             DEFAULT 0,
    idGrupoTrabajo      INT             NULL DEFAULT 0,
    idTarea             INT             NOT NULL,
    idArchivoP          INT             NULL DEFAULT 0,
    fechaCreacion       DATETIME        NOT NULL DEFAULT NOW(),
    version             INT             NOT NULL,
    nota                DECIMAL(5,2)    NULL,
    comentarioProfesor  TEXT            NULL,
    PRIMARY KEY (idEntrega),
    CONSTRAINT fk_entrega_estudiante FOREIGN KEY (idUsuario) REFERENCES Usuario(idUsuario),
    CONSTRAINT fk_entrega_tarea FOREIGN KEY (idTarea) REFERENCES Tarea(idTarea),
    CONSTRAINT chk_version CHECK (version >= 1),
    CONSTRAINT chk_nota CHECK (nota BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla EstudianteXCurso
CREATE TABLE IF NOT EXISTS EstudianteXCurso (
    idEstXCrs           INT         NOT NULL AUTO_INCREMENT,
    idUsuario           INT         NOT NULL,
    idCurso             INT         NOT NULL,
    PRIMARY KEY (idEstXCrs),
    CONSTRAINT fk_exc_estudiante FOREIGN KEY (idUsuario) REFERENCES Usuario(idUsuario),
    CONSTRAINT fk_exc_curso FOREIGN KEY (idCurso) REFERENCES Curso(idCurso),
    CONSTRAINT uq_estudiante_curso UNIQUE (idUsuario, idCurso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla EstudianteXGrupoTrabajo
CREATE TABLE IF NOT EXISTS EstudianteXGrupoTrabajo (
    idEstXGrpTrb        INT         NOT NULL AUTO_INCREMENT,
    idUsuario           INT         NOT NULL,
    idGrupoTrabajo      INT         NOT NULL,
    PRIMARY KEY (idEstXGrpTrb),
    CONSTRAINT fk_exg_estudiante FOREIGN KEY (idUsuario) REFERENCES Usuario(idUsuario),
    CONSTRAINT fk_exg_grupo FOREIGN KEY (idGrupoTrabajo) REFERENCES GrupoTrabajo(idGrupoTrabajo),
    CONSTRAINT uq_estudiante_grupo UNIQUE (idUsuario, idGrupoTrabajo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Para el avance 4
-- TODO: crear tabla Bitacora para registrar historial de operaciones sobre archivos
-- Se conecta con ArchivoLogDecorator.php → registrarBitacora()
-- CREATE TABLE IF NOT EXISTS Bitacora (
--     idBitacora      INT          NOT NULL AUTO_INCREMENT,
--     idUsuario       INT          NOT NULL,
--     nombreArchivo   VARCHAR(255) NOT NULL,
--     accion          ENUM('CREAR','MODIFICAR') NOT NULL,
--     fecha           DATETIME     NOT NULL DEFAULT NOW(),
--     PRIMARY KEY (idBitacora),
--     CONSTRAINT fk_bitacora_usuario FOREIGN KEY (idUsuario) REFERENCES Usuario(idUsuario)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla SolicitudCurso
CREATE TABLE IF NOT EXISTS SolicitudCurso (
    idSolicitud     INT         NOT NULL AUTO_INCREMENT,
    idUsuario       INT         NOT NULL,
    idCurso         INT         NOT NULL,
    estado          ENUM('PENDIENTE','ACEPTADA','RECHAZADA') NOT NULL DEFAULT 'PENDIENTE',
    fechaSolicitud  DATETIME    NOT NULL DEFAULT NOW(),
    PRIMARY KEY (idSolicitud),
    CONSTRAINT fk_sol_usuario FOREIGN KEY (idUsuario) REFERENCES Usuario(idUsuario),
    CONSTRAINT fk_sol_curso   FOREIGN KEY (idCurso)   REFERENCES Curso(idCurso),
    CONSTRAINT uq_sol_usuario_curso UNIQUE (idUsuario, idCurso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO Usuario (correo, nombreUsuario, nombre, contrasena, rol) VALUES
('javi0409@estudiantec.cr', 'javi0409', 'Javier Lee Liang', '$2y$10$Sgy8b8YX.pIavn3CzNcqi.v4C/ZbVV7e0zXl1Q5QBA0G5EtsvswOC', 'ESTUDIANTE');
INSERT IGNORE INTO Usuario (correo, nombreUsuario, nombre, contrasena, rol) VALUES
('andres941@outlook.com', 'andres941', 'Andrés Lee Liang', '$2y$10$WsJMzboyx2KKxhdTra7f5exRhGnqds8n0o/HEyZIAOsP5WN3YUANG', 'PROFESOR');

INSERT IGNORE INTO Curso (idUsuario, nombreCurso) VALUES
(2, 'Introducción a la programación');

INSERT IGNORE INTO Curso (idUsuario, nombreCurso) VALUES
(2, 'Taller de programación');

INSERT IGNORE INTO EstudianteXCurso (idUsuario, idCurso) VALUES
(1, 1);

INSERT IGNORE INTO EstudianteXCurso (idUsuario, idCurso) VALUES
(1, 2);
