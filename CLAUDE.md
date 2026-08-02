# IUDocs — Contexto para agentes Claude

> Este archivo se auto-carga en Claude Code para esta carpeta y **manda** al trabajar acá.
> Idioma de trabajo: **español (Argentina), informal ("vos")**.
>
> ℹ️ La app se llama **IUDocs** (antes "Altillo"). La **carpeta y el repo git siguen siendo
> `altillo`** (no se renombraron para no romper rutas/remotos); el nombre de marca visible es IUDocs.

---

## 1. Qué es IUDocs

**Portal colaborativo de apuntes y exámenes para estudiantes universitarios** (arranca con
Biotecnología). Los estudiantes inician sesión con **Google**, navegan por **materias**, y
suben **apuntes** y **exámenes** a cada materia. Cualquiera puede **comentar** los materiales.

El acceso es **con aprobación**: te registrás con Google y quedás **pendiente**; una **admin**
(la novia de Nicolás, estudiante de biotecnología) decide si te habilita, y puede
**bloquear/desbloquear** a cualquiera. Nace de un dolor real: los apuntes de la cursada
desparramados en mil WhatsApp y Drives distintos.

**No es** un Drive genérico: el valor está en estar **ordenado por materia**, ser
**colaborativo** (comentarios) y **curado** (la admin controla quién entra).

## 2. Perfil del usuario (Nicolás)

Dev **Full Stack** (React, Vue, Laravel, Node/Express, Python/Django). Proyecto para el
**portfolio / búsqueda laboral**. IUDocs suma **variedad B2C / colaborativo** y un **stack
distinto** al de DocuMind (Python/React): acá es **PHP/Laravel + Vue**. Muestra OAuth,
subida de archivos, roles/moderación y features sociales.

---

## 3. Cómo trabajar (metodología — MUY importante)

- Actuá como **Senior Tech Lead / mentor** Y como **UI/UX Designer** (ver §7), no como autómata.
- **Definir arquitectura, requisitos y roadmap antes de escribir código.**
- **Desarrollo incremental**: una feature a la vez; las grandes se parten en subtareas de
  **≤30-45 min**. Implementá una subtarea completa, verificá que funcione, y recién ahí seguí.
- **Intercalar backend y frontend**: por cada etapa de backend, construí el frontend
  correspondiente antes de avanzar.
- Tras 1-3 componentes, **PARÁ y esperá confirmación**. Decí qué probar y el resultado esperado.
- Cuando una feature queda aprobada: **commit** (Nicolás hace los commits/push, darle el
  comando listo para copy-paste, **sin atribución a Claude**).
- Explicá decisiones con pros/cons; para elegir caminos usá **preguntas concretas**.

---

## 4. Stack lockeado

| Área | Decisión |
|---|---|
| **Framework** | **Laravel 13** (monolito con **Inertia.js**) — un solo deploy |
| **Frontend** | **Vue 3** + Inertia + **Vite** · **Tailwind** · scaffolding base de **Laravel Breeze (stack vue)** |
| **UI/Design System** | componentes propios estilo shadcn (reutilizar > extender > crear); **light-first, acento ámbar cálido** (ver §7). shadcn-vue se puede sumar. |
| **Auth** | **Google OAuth** vía **Laravel Socialite** (encima del scaffolding de Breeze). Estados de usuario: pendiente / activo / bloqueado. |
| **DB** | **SQLite en local** (dev, cero setup) · **MySQL en Hostinger** (prod). Migraciones DB-agnósticas. |
| **Archivos** | disco local de Laravel (`storage`); en Hostinger es **persistente**. |
| **Deploy** | **Hostinger** (Laravel + Inertia; subir build, no usar el builder de Git de Hostinger). |
| **Tests** | Pest. |

## 5. Modelo de datos

| Entidad | Campos clave |
|---|---|
| **User** | google_id, name, email, avatar, **role** (`admin`/`member`), **status** (`pending`/`active`/`blocked`) |
| **Materia** | nombre, descripción, (año/cuatrimestre opcional) |
| **Material** | materia_id, user_id, **tipo** (`apunte`/`examen`), título, archivo (path), mime, tamaño |
| **Comment** | material_id, user_id, texto |
| **Carrera** | nombre · **N-a-N con Materia** (pivote `carrera_materia`). Al crear una materia es **obligatorio** asignarle ≥1 carrera. El home del alumno filtra por carrera con tabs. |

**Permisos:**
- Ver/usar el contenido → **todos menos los bloqueados**. Los `pending` **navegan libremente** (se sacó el gating de aprobación), pero **siguen apareciendo en "Solicitudes pendientes"** para que la admin apruebe/rechace cuando quiera. Solo los `blocked` ven la pantalla de bloqueo.
- Subir material → cualquier usuario activo.
- **Borrar material → solo el dueño o un admin.**
- Aprobar/rechazar registros y bloquear/desbloquear usuarios → **admin**.
- Crear/gestionar **materias** → **admin** (refleja el plan de estudios, queda ordenado).
- La **primera admin** (la novia) se configura por email (seed/config): al loguear con ese email, queda `admin` + `active`.

## 6. Roadmap

| # | Feature |
|---|---|
| 0 | Scaffolding (Laravel+Inertia+Vue+Tailwind+Breeze) · Design System ámbar · **[falta: Node 20+ para build]** |
| 1 | **Login con Google** (Socialite) + estados de usuario (pending/active/blocked) + gating (pantalla "esperando aprobación") |
| 2 | **Panel de admin**: aprobar/rechazar registros · bloquear/desbloquear usuarios |
| 3 | **Materias** (admin crea/gestiona) + grilla de materias |
| 4 | **Materiales** (apuntes + exámenes) por materia: subir con tipo · listar/filtrar · descargar/preview · **borrar (dueño o admin)** |
| 5 | **Comentarios** por material |
| 6 | Pulido (buscador, estados vacíos/loading, responsive) + **deploy a Hostinger** |

---

## 7. Rol UI/UX Designer + Design System (aplica a todo el frontend)

Además de programar, actuar como **UI/UX Designer con experiencia en SaaS moderno**.
Objetivo: interfaz **moderna, profesional, limpia y coherente** (referencia: Linear, Notion,
Stripe, Vercel, shadcn/ui). **Toda la app sigue un mismo Design System**, no pantallas sueltas.

- **Reutilización (prioridad):** reutilizar un componente > extenderlo por props > crear uno
  nuevo. No duplicar casi-idénticos por un color/ícono: configurar por props.
- **Consistencia:** botones, inputs, selects, modales, cards, tablas, badges, alertas, toasts,
  dropdowns, tooltips, **estados vacíos**, **skeletons** y **loaders** consistentes.
- **Estados por token:** hover / focus-visible / disabled / loading / error / empty.
- **Layout:** encabezados y jerarquía uniformes, acción principal en lugar previsible, espaciados homogéneos.
- **Responsive:** mobile / tablet / desktop (sidebar colapsa a drawer en mobile).
- **Accesibilidad:** buen contraste, foco visible, labels correctos, navegación por teclado.
- Justificar cada cambio visual: **qué problema, qué principio, por qué mejora**. Avisar impacto si toca componentes compartidos.

### Design tokens (base) — **light-first, ámbar cálido**
- **Acento:** ámbar — `#F59E0B` (amber-500), hover `#D97706` (amber-600).
- **Base:** fondo crema `#FEFCF8`, superficies/cards blanco, texto `#241C12`; neutros cálidos (stone).
- **Tipografía:** Inter (UI). Mono (JetBrains/ui-mono) para código/IDs si hace falta.
- **Spacing:** escala 4px. **Radios:** sm 6 · md 8 · lg 12. **Sombras:** sutiles, por capas.
- A diferencia de DocuMind (dark-first teal), IUDocs es **claro y cálido** ("apuntes / biblioteca").

---

## 8. Quirks del entorno (Windows + Laragon)

- **PHP 8.4.4** vive en Laragon: `C:\laragon\bin\php\php-8.4.4-nts-Win32-vs17-x64\php.exe`.
  El PATH de Git Bash tiene una entrada **vieja** (php-8.1.10 que ya no existe) → `php` "not found".
  Solución: `export PATH="/c/laragon/bin/php/php-8.4.4-nts-Win32-vs17-x64:/c/Users/nicol/composer:$PATH"`.
- **Node:** el stack (Tailwind v4 + Vite/rolldown) requiere **Node ≥ 20**. Node 18 (EOL) **rompe** el
  build (`node:util styleText`). **Actualizar a Node 22 LTS.**
- **Composer** global actualizado a 2.10+ (compat PHP 8.4).
- **MySQL de Laragon**: dio `auth_gssapi_client` (server distinto o sin arrancar) → por eso dev usa SQLite.
  Configurar MySQL recién para el deploy en Hostinger.
- **OneDrive**: el proyecto vive en `OneDrive\Escritorio\Proyectos CV\altillo` → OneDrive intenta
  sincronizar `vendor/` y `node_modules/` (miles de archivos) y **enlentece** todo. Idealmente
  excluir esas carpetas del sync de OneDrive.
- **git commit**: preferir `git commit -F <archivo>` si las comillas de `-m` dan problema (acentos).
- Usar el directorio scratchpad de la sesión para temporales, no `/tmp`.

## 9. Repo y portabilidad

- Repo GitHub (**público**): `github.com/nicolasgonzalez98/iudocs`.
- Este `CLAUDE.md` es la **fuente de verdad portable** — la memoria de Claude Code es local y NO viaja.
- IUDocs vive como subcarpeta del repo "meta" del portfolio (`Proyectos CV`), que lo **ignora** (repo propio).

## 10. Deploy (producción) — 🟢 ONLINE

**URL:** https://iudocs.nicolasngonzalez.com · **Hosting:** Hostinger (shared, PHP 8.4) · **DB:** MySQL (`u689345803_iudocs`).

- El código vive en el server en `~/domains/iudocs.nicolasngonzalez.com/app` (clonado de GitHub).
- El sitio sirve desde el `public` de Laravel vía **symlink**: `public_html -> app/public`.
- **Assets compilados versionados** (`public/build` NO está gitignoreado) → el server no necesita Node.
- `.env` de producción está solo en el server (APP_ENV=production, MySQL, `SESSION_SECURE_COOKIE=true`,
  mismas credenciales de Google que local + `ADMIN_EMAIL`). El redirect de Google prod está autorizado
  en Google Cloud: `https://iudocs.nicolasngonzalez.com/auth/google/callback`.
- Google OAuth en modo **Testing**: solo entran mails agregados como "usuarios de prueba"
  (o publicar la app; scopes básicos no requieren verificación).

**Actualizar producción** (por SSH, en `~/domains/iudocs.nicolasngonzalez.com/app`):
```
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
> Flujo: buildear local (`npm run build`) → commit (incluye `public/build`) → push → `git pull` en el server.
> Si cambia el `.env`, correr `php artisan config:clear` (o `config:cache` de nuevo).
