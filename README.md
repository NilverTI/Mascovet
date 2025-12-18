# Sistema de Veterinaria — Laravel 11, Vue 3, PostgreSQL, AWS SNS, Cron Jobs y Envío de SMS
---

## 📘 Descripción General

Este proyecto es un **Sistema Integral de Veterinaria** desarrollado con **Laravel 11** (Backend) y **Vue 3** (Frontend Administrativo).  

El sistema permite gestionar de manera completa todo el proceso operativo de una clínica veterinaria:

✔ Reserva de citas médicas  
✔ Gestión de vacunas  
✔ Programación de cirugías  
✔ Disponibilidad de veterinarios  
✔ Pagos y facturación  
✔ Envío de SMS mediante AWS SNS  
✔ Correos automáticos  
✔ Cron Jobs para ejecutar tareas programadas  
✔ Panel administrativo moderno  
✔ Dashboards con indicadores (KPIs)

Ideal para proyectos personales, emprendimientos o implementación comercial para múltiples clientes.

---

# 🚀 Tecnologías Principales

### **Backend — Laravel 11**
- API REST escalable  
- Seguridad con **JWT Tokens**  
- Validación con **Middleware y Guards**  
- Seeders & Factories  
- Jobs & Queues  
- Servicios integrados (SNS, Email, Storage, etc.)  

### **Frontend — Vue 3 (SPA)**
- Componentes reutilizables  
- Rutas protegidas  
- Composition API  
- Axios para consumo de API  
- Materialize como Framework UI  

### **Base de Datos**
- **PostgreSQL**  
- Relaciones optimizadas  
- Índices por campos de consulta  
- Migraciones automatizadas  

### **Servicios Externos**
- **AWS SNS** para envío de SMS  
- **AWS EC2** para deploy  
- **AWS RDS PostgreSQL**  
- **Certbot + NGINX** para HTTPS  

---

# 🧩 Funcionalidades Complejas del Sistema

## 👩‍⚕️ Gestión de Veterinarios y Pacientes
- Registro y administración de médicos veterinarios  
- Perfiles completos de mascotas  
- Historial clínico detallado  

## 📅 Gestión de Citas, Vacunas y Cirugías
- Programación avanzada con disponibilidad  
- Calendarización visual  
- Recordatorios por SMS y email  
- Estados de proceso (pendiente, atendido, cancelado)  

## 💳 Pagos
- Control de ingresos por servicio  
- Historial contable  
- Filtrado avanzado por fechas y tipos  

## 🔐 Roles y Permisos
- Administrador  
- Veterinario  
- Recepcionista  
- Cliente  
- Personalizado por módulo  

## 📊 Dashboard & KPIs
- Citas por día / semana / mes  
- Servicios más solicitados  
- Ingresos totales  
- Productividad de veterinarios  

## 📤 Exportación
- Exportar citas, vacunas y cirugías a Excel  
- Filtros por fecha, veterinario, paciente o servicio  

---

# 🛠 Instalación Backend (Laravel 11)

```bash
composer install
cp .env.example .env
php artisan key:generate

# Configurar PostgreSQL en .env

php artisan migrate --seed
php artisan serve
```

---

# 🖥 Instalación Frontend (Vue 3)

```bash
npm install
npm run dev
```

---

# 🔐 Autenticación (JWT)

### Login
```http
POST /api/auth/login
Content-Type: application/json
```

Respuesta:
```json
{
  "token": "Bearer {jwt_token}"
}
```

### Rutas protegidas  
Enviar encabezado:

```
Authorization: Bearer {token}
```

---

# 📡 Configuración de AWS SNS (Envío de SMS)

En **.env**

```
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_REGION=us-east-1
AWS_SNS_SENDER_ID=Veterinaria
```

Uso en código:
- Recordatorios automáticos  
- Confirmaciones de citas  
- Alertas a clientes  

---

# ⏱ Cron Jobs

En el crontab del servidor:

```
* * * * * php /ruta/del/proyecto/artisan schedule:run >> /dev/null 2>&1
```

Permite:
- Recordatorios automáticos  
- Procesar colas  
- Limpieza del sistema  

---

# ☁️ Deploy en AWS

### Componentes usados:
- EC2 (Servidor Ubuntu)
- RDS PostgreSQL
- SNS (SMS)
- NGINX + Certbot (HTTPS)
- Supervisor (Jobs & Queues)

Incluye:
- Configuración de firewall  
- Deploy continuo con GitHub  
- Entorno de producción optimizado  

---

# 📦 Licencia
Este proyecto está bajo la licencia **MIT**.

---

## 👨‍💻 Equipo del Proyecto

### 🧑‍💻 Autores

- **Nilver Tantalean I.**  
  🔗 GitHub: [@NilverTI](https://github.com/NilverTI)  
  🌐 Redes: https://nilverti.bio.link/

- **Euler I. Goicochea F.**  
  🔗 GitHub: [@L1lboX](https://github.com/L1lboX)

- **Diana P. Cajo V.**  
  📸 Instagram: [@patricia.dx](https://www.instagram.com/patricia.dx/)

- **Stephany P. Cruz L.**  
  📸 Instagram: [@stechi_24](https://www.instagram.com/stechi_24/)

---

### 👔 CEO 

**Bryan A. Millones M.**  
📸 Instagram: [@bryan_clown_](https://www.instagram.com/bryan_clown_/)

💼 **Rol:** Programador Senior – Full Stack  
🚀 **Responsabilidades:** Arquitectura, liderazgo técnico y toma de decisiones. Buscsa Novia (Sin traumas)

### 404: CONEXIÓN INESTABLE 👾
