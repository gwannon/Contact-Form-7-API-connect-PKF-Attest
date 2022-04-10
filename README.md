# Contact-Form-7-API-connect-PKF-Attest

Para generar el formualrio solament ehay que meter el código corto [curso id="código del curso"] en el formulario de contacto. Si queremos meter campos extras podemos meterlos en el propio formulario como contenido del código corto.
Ejemplo de Uso:

[curso id="6821"][acceptance politica-privacidad] Acepto la <a href="#">política de privacidad</a>. [/acceptance][/curso]

Para mostrar los datos del formulario simplemente metenos el código corto [curso_allinfo] en el cuerpo del mensaje.

Para configurar el plugin tenemos que entrar en WP-Admin > Ajustes > PKF Attest. Campos:

* URL final de la API: https://dominioapi.com/WebInscripciones/api
* Usuario de la AP.
* Contraseña de la API.
* CSS personalizado: Campo para meter el CSS personalziado para los formularios. Todos los formualrios tienen #inscripcion_pkf como id principal para el desarrollo del CSS:

TODO. 
* Las formas de pago varian según se elige al contado o a plazos.
* Revisar si es un curso gratuito