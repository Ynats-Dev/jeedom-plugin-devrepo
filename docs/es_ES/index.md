---
layout: default
lang: es_ES
---

Descripción
===

DevRepo actualiza sus plugins **en desarrollo** directamente desde una rama de su repositorio Git (**Gogs**, **Gitea** o **GitHub**), sin git ni acceso SSH a la box. Resulta especialmente práctico cuando Jeedom se ejecuta en un contenedor Docker.

Para cada plugin seguido, un clic en **Actualizar** descarga el archivo de la rama, comprueba que se trata del plugin correcto, reemplaza sus archivos y luego ejecuta su función de actualización (como una actualización normal desde el Market).

> Reservado para el desarrollo: los archivos ausentes de la rama no se eliminan y no se realiza ninguna copia de seguridad antes de la actualización.

Configuración
===

![Lista de plugins seguidos](../images/devrepo_screenshot.png)

La lista de plugins seguidos se encuentra en la página del plugin: **Plugins → Programación → DevRepo**. Para cada plugin:

- **Plugin**: su identificador (nombre de su carpeta en `plugins/`), p. ej. `groupir`. Se proponen los plugins instalados. Se puede indicar un plugin aún no instalado: DevRepo crea su carpeta y solo queda activarlo en la gestión de plugins;
- **Repositorio**: URL del repositorio, p. ej. `https://servidor/propietario/repositorio.git` o `https://github.com/propietario/repositorio`;
- **Rama**: rama a seguir (`beta` por defecto);
- **Auto**: actualiza el plugin automáticamente con cada nuevo commit en la rama (ver más abajo);
- **Token**: necesario si el repositorio es privado (Gogs / Gitea: *Configuración → Aplicaciones*; GitHub: *Settings → Developer settings → Personal access tokens*). Se almacena **cifrado** y nunca se vuelve a mostrar: deje el campo vacío para conservarlo o use la goma para eliminarlo.

Haga clic en **Guardar** (la lista también se guarda automáticamente antes de una actualización).

Uso
===

- **Comprobar**: compara la última actualización de cada plugin con el último commit de su rama (*al día* o *nuevo commit*);
- **Actualizar**: descarga la rama y reemplaza los archivos del plugin. La columna *Última actualización* indica la fecha y el commit instalados (detalle al pasar el ratón).

Actualización automática
===

Para los plugins con la casilla **Auto** marcada, DevRepo comprueba la rama **cada 5 minutos**: en cuanto se envía un nuevo commit, el plugin se actualiza como con el botón *Actualizar*. Un icono de robot en *Última actualización* indica una actualización realizada automáticamente.

- La tarea corresponde a la línea **cron5** del panel *Funcionalidades* de la configuración del plugin: desactivarla suspende todas las actualizaciones automáticas;
- si la actualización a un commit falla, no se reintenta en bucle: DevRepo espera al siguiente commit (o a un clic en *Actualizar*). El error figura en el log `devrepo`;
- un repositorio temporalmente inaccesible simplemente se vuelve a comprobar en la siguiente pasada.

Webhook: actualización inmediata
===

En lugar de esperar a la comprobación, el repositorio puede avisar a Jeedom en cada push. El botón <i class="fas fa-plug"></i> de una línea muestra la URL del webhook de ese plugin (acceso interno y externo de Jeedom):

- en el repositorio: *Configuración → Webhooks* (Gogs, Gitea o GitHub), pegue la URL, evento **push**, tipo de contenido **JSON**;
- la actualización solo se hace si **Auto** está marcado y la rama enviada es la seguida;
- el repositorio debe poder acceder a Jeedom: un Gogs / Gitea de la red local usa el acceso interno; GitHub necesita el acceso externo.

La URL contiene la clave API de DevRepo (*Ajustes → Sistema → Configuración → API*): no la comparta.

Modo tiempo real
===

Si el repositorio no puede acceder a Jeedom (GitHub y una box no expuesta en internet), el botón **Tiempo real** comprueba las ramas de los plugins **Auto** **cada 10 segundos durante 1 hora** (por defecto), el tiempo de una sesión de desarrollo. El botón muestra el tiempo restante; un clic lo detiene. Durante este modo, la comprobación cada 5 minutos queda suspendida.

La duración se elige en la lista junto al botón: de 15 minutos a 8 horas. Se aplica en la próxima activación.

En GitHub sin token (60 peticiones por hora como máximo), la comprobación se espacia a 90 segundos: introduzca un token para aprovechar los 10 segundos.

Notificación
===

Cada actualización automática (webhook, tiempo real o comprobación cada 5 minutos) muestra una notificación en todas las páginas de Jeedom abiertas, con el commit instalado, o el error en caso de fallo. Si la página de DevRepo está abierta, la línea del plugin se actualiza.

DevRepo también puede actualizarse a sí mismo: añada `devrepo` a la lista con su repositorio.

En caso de error, se muestra un mensaje explícito (repositorio no encontrado, token rechazado, archivo que no contiene el plugin correcto, permisos de escritura...). El detalle está en el log `devrepo`.
