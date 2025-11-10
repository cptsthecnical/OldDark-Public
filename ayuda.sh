# snmp
snmpwalk -v2c -c <community-snmp> -Oneq <ip-snmp> .1 > dc1-kvm1.snmpwalk                 - Exporta árbol SNMP completo al archivo dc1-kvm1.snmpwalk.

# archivos
rsync -avzc --progress /ruta/origen/ usuario@host:/ruta/destino/                         - Copia eficiente de Linux a Linux, mantiene permisos y metadatos (usuarios, hard-links...).
scp -r /ruta/origen/ usuario@host:/ruta/destino/                                         - Copia directa pero más lenta, ideal usando Windows, si Windows no tiene rsync.
chattr +i /ruta/origen/documento.txt                                                     - Establece atributo inmutable (impide modificar/borrar el archivo, -i para revertirlo).
sudo du -h --max-depth=1 /var/lib | sort -rh | head -n 10                                - Comprueba el almacenamiento utilizado en /var/lib.

# compresión
tar -czvf prueba.tar.gz comprimir/                                                        - Comprime carpeta con gzip.
tar -xzvf prueba.tar.gz                                                                   - Extrae contenido si fue comprimido con gzip.

# samba
smbstatus | grep "nombre_del_archivo.xls"                                                 - Verifica si un archivo está abierto por Samba (lo detengo con kill -9).
smbstatus -L                                                                              - Lista todos los archivos abiertos vía Samba con usuarios y PIDs.

# forense
lsblk -e7 -o NAME,MAJ:MIN,RM,SIZE,RO,TYPE,MOUNTPOINT,FSTYPE,MODEL,MODE,STATE,VENDOR,UUID' - Muestra información detallada de los dispositivos de bloques detectados.
hdparm -I /dev/sda3                                                                       - Muestra capacidades del disco/SSD (modelo, firmware, modos DMA/UDMA, velocidades, características SMART soportadas, límites de seguridad, etc.).
smartctl -axH /dev/sda3                                                                   - Muestra todo SMART (atributos, historial de errores, tests) -x (tablas y logs vendor-specific), -H (salud PASSED/FAILED).

# nmap
nmap -p- --open -T5 -v -n [ip Víctima] -oG [Nombre del archivo de guardado.]              - escanea todos los puertos abiertos de la victima. Parámetros opcionales (-oG) lo guarda en el archivo índicado, (-n) no muestra los DNS.
nmap -sV --script vuln [Ip Víctima]                                                       - escanea las vulnerabilidades (CVE) en un host específico.
nping --icmp --icmp-type 13 [ip Host]                                                     - envia paquetes ICMP de tipo 13, son solicitudes Timestamp
Request. Este tipo de paquete se utiliza para solicitar la hora actual de un host remoto.

# redes
curl ifconfig.es                                                                          - curl: muestra la ip pública (también existe ifconfig.me).
tcpdump -i ens33 -nn host [ip host]                                                       - tcpdump: captura en eth0 todo el tráfico IP hacia o desde 192.168.1.1, con -nn cambia el nombre puertos y servicio por números (https por 443).
netstat -nlpt                                                                             - netstat: muestra qué procesos están escuchando en qué puertos TCP de tu máquina, con su PID correspondiente.
tcpdump                                                                                   - tcpdump: captura y analiza paquetes que permite inspeccionar el tráfico de red que atraviesa una interfaz.
nslookup [dns o ip]                                                                       - nslookup: resuelve nombres DNS a direcciones IP (y viceversa).
ss                                                                                        - ss: muestra sockets/ conexiones de red (puertos, estados, procesos asociados).
ps aux --sort=-%cpu | head -n 20                                                          - ps -aux: instantánea estática de todos los procesos.

