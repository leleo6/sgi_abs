# SGI-ABS — Sistema de Información de Gestión Académica y Seguimiento de Prácticas

**Institución Educativa ABS** · `abs.edu.co`  
Versión 1.0 · PHP 8.2+ · MySQL 8.0+ · Docker Compose · Bootstrap 5  

---

## 📋 Descripción

**SGI-ABS** es una plataforma web completa orientada a la gestión académica y el seguimiento integral del avance de estudiantes de programas técnicos y tecnológicos.

El sistema implementa el **Motor de Elegibilidad Curricular (*Regla del Estudiante Limpio*)**, el cual valida en tiempo real el cumplimiento del **100% de la malla curricular obligatoria** antes de habilitar el acceso a la etapa práctica, supervisando todo el ciclo formativo laboral hasta la emisión del **Paz y Salvo de Grado** con código digital de verificación.

---

## 📚 Documentación del Proyecto

Toda la documentación técnica, funcional, de infraestructura y de calidad se encuentra organizada en la carpeta [**`docs/`**](file:///home/leo/Downloads/proyects/code/sgi_abs/docs):

| Documento | Descripción | Enlace |
| :--- | :--- | :--- |
| **Manual Técnico** | Arquitectura MVC/DAO, Modelo de Datos (DER), Triggers, Seguridad y Core. | [📖 Ver Manual Técnico](docs/MANUAL_TECNICO.md) |
| **Manual de Usuario** | Guía de uso paso a paso para los 6 roles, flujos de práctica y paz y salvo. | [👤 Ver Manual de Usuario](docs/MANUAL_USUARIO.md) |
| **Manual de Despliegue** | Guía de orquestación Docker, copias de seguridad, persistencia y producción. | [🚀 Ver Manual de Despliegue](docs/MANUAL_DESPLIEGUE.md) |
| **Auditoría QA y Arquitectura** | Matriz de pruebas esenciales, análisis SOLID/DRY y propuesta refactorizada. | [🛡️ Ver Auditoría QA](docs/AUDITORIA_QA_ARQUITECTURA.md) |

---

## ⚡ Inicio Rápido con Docker

El proyecto se encuentra totalmente contenedorizado y configurado para desplegarse con un solo comando.

### 1. Clonar y configurar entorno
```bash
git clone <url-del-repositorio> sgi_abs
cd sgi_abs
cp .env.example .env
```

### 2. Levantar la plataforma
```bash
docker compose up -d --build
```
*(El servicio inicializa automáticamente la base de datos MySQL, aplica migraciones DDL, carga datos semilla e instala dependencias de Composer).*

### 3. URLs de acceso local

| Servicio | URL Local | Descripción |
| :--- | :--- | :--- |
| **SGI-ABS Web** | [**http://localhost:8085**](http://localhost:8085) | Aplicación web principal |
| **phpMyAdmin** | [**http://localhost:8086**](http://localhost:8086) | Administrador visual de BD (Usuario: `root`, Clave: `root_password`) |
| **MySQL Directo** | `localhost:3308` | Conexión externa (DBeaver / Workbench / CLI) |

---

## 🔑 Usuarios y Credenciales de Prueba

El sistema incluye 6 roles institucionales preconfigurados:

| Rol Institucional | Correo Electrónico | Contraseña Predeterminada |
| :--- | :--- | :--- |
| **Administrador** | `admin@abs.edu.co` | `Admin2026!` |
| **Registro Académico** | `registro@abs.edu.co` | `Registro2026!` |
| **Coordinación** | `coordinacion@abs.edu.co` | `Coord2026!` |
| **Docente** | `docente@abs.edu.co` | `Docente2026!` |
| **Resp. de Prácticas** | `practicas@abs.edu.co` | `Practicas2026!` |
| **Estudiante** | `estudiante@abs.edu.co` | `Estudiante2026!` |

---

## 🏗️ Arquitectura y Estructura del Código

```
sgi_abs/
├── config/             # Configuración del entorno y Singleton Database PDO
├── core/               # Núcleo del sistema (Auth, Audit, Controller, FileUpload, Session)
├── controllers/        # Controladores HTTP por módulo de negocio
├── models/             # Capa DAO (Data Access Object) con BaseModel genérico
├── views/              # Plantillas PHP limpias con Bootstrap 5.3
├── public/             # Raíz web pública (DocumentRoot de Apache)
│   ├── index.php       # Front Controller
│   └── assets/         # CSS, JS y librerías del cliente
├── uploads/            # Almacenamiento seguro fuera del DocumentRoot
├── sql/                # Scripts DDL (esquemas, vistas, triggers y semillas)
├── docker/             # Dockerfile, Apache conf, PHP ini y entrypoint de inicio
├── docs/               # Manuales técnicos, de usuario, despliegue y auditoría
├── docker-compose.yml  # Orquestación de contenedores (app, db, phpmyadmin)
└── .env                # Variables de entorno locales
```

---

## 🛡️ Características Principales de Seguridad

- **Seguridad en Autenticación:** Bloqueo automático de cuentas tras 5 intentos fallidos consecutivos contra ataques de fuerza bruta.
- **Protección contra Inyección:** 100% de consultas preparadas mediante PDO (`bindValue` / `execute` parametrizado).
- **Protección CSRF:** Tokens criptográficos obligatorios en todas las peticiones `POST` validados con `hash_equals()`.
- **Mitigación IDOR:** Validación en servidor de la pertenencia de los recursos a la matrícula del usuario autenticado.
- **Almacenamiento Seguro de Archivos:** Las evidencias se guardan en la carpeta `/uploads` fuera de la raíz web pública, verificando extensión, inspección binaria MIME con `finfo` y hash SHA-256.
- **Trazabilidad Inmutable:** Servicio de auditoría *append-only* que registra usuario, acción, IP y snapshot JSON del cambio.

---

## 📜 Licencia y Autoría

Desarrollado para la **Institución Educativa ABS** · Departamento de Tecnología y Sistemas.  
Todos los derechos reservados © 2026.
