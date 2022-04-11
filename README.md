# Contact-Form-7-API-connect-PKF-Attest

Para generar el formualrio solament ehay que meter el código corto [curso id="código del curso"] en el formulario de contacto. Si queremos meter campos extras podemos meterlos en el propio formulario como contenido del código corto.
Ejemplo de Uso:

[curso id="6821"][acceptance politica-privacidad] Acepto la <a href="#">política de privacidad</a>. [/acceptance][/curso]

Para mostrar los datos del formulario simplemente metenos el código corto [curso_allinfo] en el cuerpo del mensaje. Para sacar un dato en concreto podemos usar los siguientes códigos cortos:

* [curso_info_asistencia]
* [curso_info_plazos]
* [curso_info_pago]
* [curso_info_iban]
* [curso_info_estudiante_nombre]
* [curso_info_estudiante_fecha_nacimiento]
* [curso_info_estudiante_tipo_identidad]
* [curso_info_estudiante_identidad]
* [curso_info_estudiante_direccion]
* [curso_info_estudiante_cp]
* [curso_info_estudiante_ciudad]
* [curso_info_estudiante_telefono]
* [curso_info_estudiante_email]
* [curso_info_receptor_nombre] *
* [curso_info_receptor_fecha_nacimiento] *
* [curso_info_receptor_tipo_identidad] *
* [curso_info_receptor_identidad] *
* [curso_info_receptor_direccion] *
* [curso_info_receptor_cp] *
* [curso_info_receptor_ciudad] *
* [curso_info_receptor_telefono] *
* [curso_info_receptor_email] *

En caso de no haber datos del pagador muestra los datos del estudiante*

Para configurar el plugin tenemos que entrar en WP-Admin > Ajustes > PKF Attest. Campos:

* URL final de la API: https://dominioapi.com/WebInscripciones/api
* Usuario de la AP.
* Contraseña de la API.
* Email de aviso. Email al que se enviará un aviso en caso de que la API devuelva algún error.
* CSS personalizado: Campo para meter el CSS personalziado para los formularios. Todos los formualrios tienen #inscripcion_pkf como id principal para el desarrollo del CSS:

TODO. 
* Las formas de pago varian según se elige al contado o a plazos.
* Revisar si es un curso gratuito
* Si la llamada a la API da un error devolverlo al usuario e invalidar el envío https://stackoverflow.com/questions/36774134/wordpress-invalidate-cf7-after-api-call/49243202