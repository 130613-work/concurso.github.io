select * from usuarios;
SELECT usuario, clave FROM usuarios WHERE usuario = 'jurado1';
use concurso_web;
DROP TABLE IF EXISTS usuarios;
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    clave VARCHAR(255) NOT NULL,
    rol ENUM('administrador', 'profesor', 'jurado') NOT NULL
);

ALTER TABLE concurso ADD obra_obligatoria VARCHAR(255);

UPDATE concurso SET obra_obligatoria = 'Cuarteto en Fa Mayor' WHERE id = 1;

CREATE TABLE concurso (
    id INT PRIMARY KEY,
    estado ENUM('abierto', 'cerrado') NOT NULL DEFAULT 'abierto',
    fase ENUM('primera', 'segunda', 'final') NOT NULL DEFAULT 'primera'
);

ALTER TABLE concurso 
MODIFY COLUMN fase ENUM('eliminatoria1', 'eliminatoria2', 'final') NOT NULL DEFAULT 'eliminatoria1';

UPDATE concurso SET fase = 'eliminatoria1' WHERE id = 1;

INSERT INTO concurso (id, estado, fase) VALUES (1, 'abierto', 'primera'); -- solo una fila

CREATE TABLE cuartetos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) UNIQUE NOT NULL,
    obra_obligatoria VARCHAR(255) NOT NULL,
    obra_libre VARCHAR(255) NOT NULL,
    estado ENUM('en_concurso', 'eliminado') DEFAULT 'en_concurso',
    profesor_id INT,
    FOREIGN KEY (profesor_id) REFERENCES usuarios(id)
);

ALTER TABLE cuartetos MODIFY estado ENUM('en_concurso', 'eliminado', 'pendiente', 'clasificado', 'finalista') DEFAULT 'en_concurso';

CREATE TABLE jurado_conservatorio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jurado_id INT,
    dia INT,
    conservatorio CHAR(1),
    UNIQUE(jurado_id, dia, conservatorio)
);



CREATE TABLE alumnos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    edad INT NOT NULL,
    dni VARCHAR(20) UNIQUE NOT NULL,
    instrumento VARCHAR(50) NOT NULL,
    cuarteto_id INT,
    FOREIGN KEY (cuarteto_id) REFERENCES cuartetos(id)
);

CREATE TABLE evaluaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jurado_id INT NOT NULL,
    cuarteto_id INT NOT NULL,
    fase ENUM('eliminatoria1', 'eliminatoria2', 'final') NOT NULL,
    
    afinacion TINYINT UNSIGNED CHECK (afinacion <= 10),
    tecnica TINYINT UNSIGNED CHECK (tecnica <= 10),
    coordinacion TINYINT UNSIGNED CHECK (coordinacion <= 10),
    expresion TINYINT UNSIGNED CHECK (expresion <= 10),
    interpretacion TINYINT UNSIGNED CHECK (interpretacion <= 10),

    puntuacion DECIMAL(5,2) GENERATED ALWAYS AS (
        (afinacion + tecnica + coordinacion + expresion + interpretacion) / 5
    ) STORED,

    comentario TEXT,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (jurado_id) REFERENCES usuarios(id),
    FOREIGN KEY (cuarteto_id) REFERENCES cuartetos(id),
    
    UNIQUE (jurado_id, cuarteto_id, fase) -- Un jurado solo puede evaluar un cuarteto una vez por fase
);

CREATE TABLE jurado_conservatorio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jurado_id INT NOT NULL,
    conservatorio ENUM('A','B','C') NOT NULL,
    dia INT NOT NULL,
    FOREIGN KEY (jurado_id) REFERENCES usuarios(id)
);

SELECT * FROM usuarios WHERE rol = 'alumno';

select*from concurso;
select*from cuartetos;
select*from alumnos;
select*from evaluaciones;
select*from notas;

ALTER TABLE concurso
DROP COLUMN obra_obligatoria;

DESCRIBE cuartetos;

ALTER TABLE usuarios ADD COLUMN rol ENUM('administrador', 'profesor', 'jurado') NOT NULL DEFAULT 'profesor';

-- Desactiva temporalmente las restricciones de clave foránea
SET FOREIGN_KEY_CHECKS = 0;

-- Borra las notas de evaluación de los jurados
DELETE FROM notas;

-- Borra los alumnos (si están vinculados a cuartetos)
DELETE FROM alumnos;

-- Borra los cuartetos registrados
DELETE FROM cuartetos;

-- (Opcional) Reinicia los AUTO_INCREMENT
ALTER TABLE cuartetos AUTO_INCREMENT = 1;
ALTER TABLE alumnos AUTO_INCREMENT = 1;
ALTER TABLE notas AUTO_INCREMENT = 1;

-- Vuelve a activar las restricciones de clave foránea
SET FOREIGN_KEY_CHECKS = 1;

SELECT * FROM usuarios WHERE id = 8 AND rol = 'jurado';


ALTER TABLE cuartetos 
ADD COLUMN dia INT, 
ADD COLUMN conservatorio ENUM('A', 'B', 'C');
