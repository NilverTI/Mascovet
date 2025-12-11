# Sistema de Veterinaria --- Laravel 11, Vue 3, PostgreSQL, AWS SNS, Cron Jobs y Envío de SMS

### Autor

**Nilver T.I**\
GitHub: https://github.com/NilverTI\
Redes: https://nilverti.bio.link/

### Descripción general

Este proyecto es un Sistema de Veterinaria desarrollado con **Laravel
11** para el backend y **Vue 3** para el panel administrativo. Gestiona
citas, vacunas, cirugías, disponibilidad de doctores, pagos,
recordatorios por SMS, correos automáticos y despliegue en AWS usando
PostgreSQL, JWT, SNS y Cron Jobs.

## 🚀 Características

-   Backend con Laravel 11
-   SPA con Vue 3
-   PostgreSQL
-   JWT
-   AWS SNS (SMS)
-   Cron Jobs
-   Roles y permisos
-   Exportación Excel
-   KPIs & Dashboard
-   Calendario completo
-   Historial médico

## 🛠 Instalación Backend

``` bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

## 🖥 Instalación Frontend

``` bash
npm install
npm run dev
```

## 🔐 Autenticación JWT

-   POST /api/auth/login\
-   Authorization: Bearer {token}

## 📡 AWS SNS

Configurar en `.env`:

    AWS_ACCESS_KEY_ID=
    AWS_SECRET_ACCESS_KEY=
    AWS_REGION=us-east-1
    AWS_SNS_SENDER_ID=Veterinaria

## ⏱ Cron Job

    * * * * * php /ruta/artisan schedule:run >> /dev/null 2>&1

## 📤 Exportaciones

-   Citas\
-   Vacunas\
-   Cirugías

## ☁️ Deploy AWS

Incluye EC2, RDS, SNS, Certbot, NGINX.

## 📦 Licencia

MIT

## 📞 Contacto

**Nilver T.I**\
GitHub: https://github.com/NilverTI\
Redes: https://nilverti.bio.link/
