# Aragón Open Data Pool V2

Aragón Open Data Pool V2 es la segunda versión de un proyecto piloto e innovador que demuestra la importancia de centralizar datos y servirlos para favorecer su uso y explotación [Aragón Open Data Pool](https://github.com/aragonopendata/open-data-pool). 

En esta segunda versión se han introducido diferentes mejoras entre las que cabe destacar:

- Normalización de las cargas conforme al nuevo modelo ontológico de Aragón Open Data [Estructura de Información Interoperable de Aragón](https://github.com/aragonopendata/EI2A-ontologia) generando los datos en el nuevo grafo ```<http://opendata.aragon.es/def/ei2av2>``` sin perder los datos cargados con la versión inicial que se encuentran en el grafo ```<http://opendata.aragon.es/def/ei2a>```
- Adaptación a la nueva versión del [API GA_OD_Core](https://opendata.aragon.es/GA_OD_Core/ui/)
- Vinculación con los datos del Banco de Datos de Aragón Open Data para mostrar la información de los datasets de los que provienen ciertas cargas realizadas.
- Integración con el Asistente de creación de conjuntos de datos de AOD para realizar la creación y actualización de los datos incorporados en la infraestructura semántica de Aragón Open Data.
- Nuevos formatos permitidos para la incorporación y actualización de nuevas cargas de datos.

Los datos de este proyecto cuentan con los estándares de la web semántica para su explotación, consulta y uso (SPARQL endpoint), que es su verdadero potencial.

El repositorio está formado por dos carpetas principales:

- API Publicacion y Administracion de cargas: Donde se encuentran el API de publicación de los datos y la administración de las cargas publicadas.
- Proceso de actualizacion de cargas: Proceso de sincronización que se ejecuta periódicamente para realizar la actualización de los datos de las cargas ya publicadas previamente y que requieren de una actualización continua de información.

Nota: para el correcto funcionamiento se deberían revisar los ficheros y adecuar la configuración de variables(por ejemplo: ips, datos autotenticación...) al entorno al que se quiera desplegar.

Desarrolladores: [Juan Valer](https://github.com/juanvaler-gnoss), [Unai Rudiez](https://github.com/Unai-R), [Fernando Martinez](https://github.com/fernandomartinez-gnoss).  

