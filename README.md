# API de productos con Laravel Passport

API REST desarrollada con Laravel 13 y Laravel Passport 13. Incluye autenticacion OAuth2 mediante Password Grant, renovacion de tokens, cierre de sesion con revocacion y autorizacion por scopes para el CRUD de productos.

## Requisitos

- PHP 8.3 o superior con las extensiones requeridas por Laravel.
- Composer 2.
- SQLite, MySQL o PostgreSQL.

## Instalacion

```bash
git clone https://github.com/SrLeo07/DESARROLLO-WEB.git
cd DESARROLLO-WEB
composer install
cp .env.example .env
php artisan key:generate
```

La configuracion predeterminada usa SQLite. Crea `database/database.sqlite` si no existe y ejecuta:

```bash
php artisan migrate --seed
php artisan passport:keys
php artisan passport:client --password --name="Cliente Password Grant" --provider=users
```

El ultimo comando muestra una sola vez el identificador y el secreto del cliente. Guardalos exclusivamente en el archivo `.env` local:

```dotenv
PASSPORT_PASSWORD_CLIENT_ID=
PASSPORT_PASSWORD_CLIENT_SECRET=
```

No agregues `.env`, secretos, access tokens, refresh tokens ni las claves de `storage/*.key` al repositorio. Todos estos archivos ya estan excluidos por `.gitignore`.

Inicia la aplicacion:

```bash
php artisan serve
```

La semilla de desarrollo crea `demo@example.com` con la contrasena `password123`. Es una cuenta de demostracion: no debe usarse en produccion.

## Tokens y scopes

- Access token: 1 hora.
- Refresh token: 30 dias.
- Personal access token: 6 meses.
- Scope predeterminado: `productos.read`.
- Scopes disponibles: `productos.read`, `productos.write`, `productos.delete`, `usuarios.read`, `admin` y `reportes`.

El campo `scopes` del login es opcional. Si se omite, el token recibe solamente `productos.read`.

## Endpoints

| Metodo | Ruta | Autenticacion / scope |
| --- | --- | --- |
| POST | `/api/login` | Publica |
| POST | `/oauth/token` | Cliente OAuth; se usa para renovar |
| GET | `/api/me` | `auth:api` |
| POST | `/api/logout` | `auth:api` |
| GET | `/api/productos` | `productos.read` |
| GET | `/api/productos/{id}` | `productos.read` |
| POST | `/api/productos` | `productos.write` |
| PUT/PATCH | `/api/productos/{id}` | `productos.write` |
| DELETE | `/api/productos/{id}` | `productos.delete` |

## Ejemplos equivalentes a Postman

Los valores entre `<...>` son marcadores. Nunca publiques credenciales o tokens reales.

Login con permisos de CRUD completo:

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"demo@example.com","password":"password123","scopes":["productos.read","productos.write","productos.delete"]}'
```

Consultar el usuario y productos:

```bash
curl http://localhost:8000/api/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <ACCESS_TOKEN>"

curl http://localhost:8000/api/productos \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <ACCESS_TOKEN>"
```

Crear un producto:

```bash
curl -X POST http://localhost:8000/api/productos \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <ACCESS_TOKEN>" \
  -d '{"name":"Teclado mecanico","description":"Ejemplo","price":75.50,"stock":10}'
```

Renovar el token:

```bash
curl -X POST http://localhost:8000/oauth/token \
  -H "Accept: application/json" \
  -d "grant_type=refresh_token" \
  -d "refresh_token=<REFRESH_TOKEN>" \
  -d "client_id=<PASSPORT_PASSWORD_CLIENT_ID>" \
  -d "client_secret=<PASSPORT_PASSWORD_CLIENT_SECRET>" \
  -d "scope=productos.read"
```

Cerrar sesion revoca tanto el access token como su refresh token:

```bash
curl -X POST http://localhost:8000/api/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <ACCESS_TOKEN>"
```

## Pruebas y formato

```bash
php artisan test
vendor/bin/pint --test
```

Las pruebas de integracion comprueban autenticacion `401`, login valido e invalido, `/me`, validacion de scopes, permisos `200/403`, CRUD, renovacion del refresh token y revocacion al cerrar sesion.
