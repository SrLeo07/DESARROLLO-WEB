# ✅ Configuración de Laravel Passport - Completada

## Estado de Implementación

### 1️⃣ Instalación de Passport
- ✅ **Instalado**: `laravel/passport` v13.7.6
- ✅ **Migraciones ejecutadas**:
  - `oauth_auth_codes_table`
  - `oauth_access_tokens_table`
  - `oauth_refresh_tokens_table`
  - `oauth_clients_table`
  - `oauth_device_codes_table`
- ✅ **Claves de encriptación** generadas

### 2️⃣ Credenciales (OAuth Clients)
Las siguientes credenciales se generaron automáticamente:

```bash
# Ejecutar para ver todos los clientes:
php artisan passport:client --no-interaction
```

**Clientes creados**:
1. **Personal Access Client** - Para acceso personal directo
2. **Default Client** - Para autenticación de aplicaciones

### 3️⃣ Tokens
- ✅ El método `login()` en `AuthController` genera tokens usando `$user->createToken()`
- ✅ Los tokens se retornan en formato Bearer: `"token_type": "Bearer"`
- ✅ El método `logout()` revoca los tokens usando `$user->token()->revoke()`

### 4️⃣ Rutas Protegidas
Las rutas API están protegidas con `middleware('auth:api')`:

```php
// Sin autenticación (Login)
POST /api/v1/login

// Protegidas - Requieren token
POST /api/v1/logout
GET /api/v1/products
POST /api/v1/products
GET /api/v1/products/{id}
PUT /api/v1/products/{id}
DELETE /api/v1/products/{id}
```

---

## 🔧 Guía de Uso en Postman

### Paso 1: Crear un Usuario
Usa la base de datos o crea un usuario con:
```bash
php artisan tinker
User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('password123'),
]);
```

### Paso 2: Login y Obtener Token
**Método**: `POST`  
**URL**: `http://localhost:8000/api/v1/login`

**Body (JSON)**:
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Respuesta** (Copia el `access_token`):
```json
{
    "message": "Inicio de sesion correcto.",
    "token_type": "Bearer",
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "user": { ... }
}
```

### Paso 3: Usar el Token en Requests Posteriores

Para **cualquier request** a rutas protegidas, agrega el header:

**Headers**:
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Ejemplo - Crear un Producto**:
- **Método**: `POST`
- **URL**: `http://localhost:8000/api/v1/products`
- **Headers**: `Authorization: Bearer {access_token}`
- **Body (JSON)**:
```json
{
    "name": "Producto Test",
    "description": "Descripción del producto",
    "price": 99.99,
    "stock": 5
}
```

### Paso 4: Logout (Revocar Token)
**Método**: `POST`  
**URL**: `http://localhost:8000/api/v1/logout`  
**Headers**: `Authorization: Bearer {access_token}`

---

## 📊 Diagrama de Flujo

```
1. Usuario → POST /login con email y password
2. Backend → Valida credenciales y genera token
3. Usuario ← Recibe access_token
4. Usuario → POST /products con header Authorization: Bearer {token}
5. Backend → Valida token y ejecuta operación
6. Usuario ← Recibe respuesta (200, 201, etc)
7. Usuario → POST /logout para revocar token
```

---

## ✅ Test Automatizado

Ejecuta el test para verificar todo el flujo:
```bash
php artisan test tests/Feature/PassportCrudTest.php
```

**Resultado esperado**: ✅ 2 tests passed

---

## 🔍 Troubleshooting

### El token no funciona
- Verifica que el encabezado sea exactamente: `Authorization: Bearer {token}`
- Valida que el token no haya expirado
- Asegúrate que la BD tiene la tabla `oauth_access_tokens`

### Error 401 Unauthorized
- Significa que el token es inválido o no se envió correctamente
- Revisa que el Authorization header está presente

### Error 403 Forbidden
- El usuario no tiene permisos para esa acción
- Revisa las políticas de autorización en los controladores

---

## 📝 Comandos Útiles

```bash
# Ver todos los clientes OAuth
php artisan passport:client --no-interaction

# Crear un cliente para password grant
php artisan passport:client --password --no-interaction

# Revocar todos los tokens de un usuario
php artisan tinker
User::find(1)->tokens()->forceDelete();
```

---

**Completado en**: 2026-08-29  
**Versión Laravel**: 13.17  
**Versión Passport**: 13.7.6
