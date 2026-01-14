#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Parser de datos del Excel de vehículos
Extrae los datos del texto proporcionado por el usuario
"""

import json
from datetime import datetime
import re

# Texto raw de los datos (copiado del mensaje del usuario)
datos_raw = """BJP009  New Holand TL95E 4x4  0     Pedraza VyT SA La Margarita  Reg Seccional 52007     2008 21/2/2008 A 1-8670 CNH Argentina SA 30-71680187-6  32.580,00  3.420,90 36.000,90                 ESTA EN PROEMTUR
BRY458  Renault         Bernardo Pedraza Vendido
CRN538  Chevrolet Pick Up         Vendido
DJJ886  Harley Moto  0     Bernardo Pedraza Bernardo Pedraza Vendido Reg 85 Cap Fed Lavalle 1625, 4to P, 402    2008 31/12/07        67.768,60
DKD898  Chrysler Neon  1     No lo se Gustavo Sanchez Destruccion Total Reg 30 Cap Fed L Saenz Peña 352, 4to P                        No encontre DESTRUCCION TOTAL DESTRUCCION TOTAL
DKD898  Crysler Neon         Vendido
EFI277  Toyota Hilux 2x2  1     No lo se  Vendido No me consta                         No encontre SI Desde 04 Jun 12 Fernandez Daniel Alberto
EFI277   Trailer  0      Bernardo Pedraza Vendido No me consta
EIJ050  Toyota Corolla  1     Pedraza VyT SA Horacio Barbiso  Vendido Reg 97 Cap Fed Rodriguez Peña 426, 4to  xxx xxx 2005 02/04/2004 1-2223 Nuñez Autos SA   31.074,00  6.525,62 31.075,00 Nuñez Autos SA 20/1/2011 2-656 30165,28926 6334,710744 36500 No lo hicimos nosotros    31/1/2011 SI Desde 04 May 11 Corbalan Javier Federico Retirada  20 de oct de 11 GCBA    SI
EXW862  Crysler          Vendido
FBK942  Fiat Palio  1     Pedraza VyT SA Bruno Leonardo Vendido Reg 97 Cap Fed Rodriguez Peña 426, 4to    2007 6/07/06        28.000,00 Miguel Angel Morrone 22 de ago de 12 B 2-1253 29.950,00  29.950,00 41.143,00 29.950,00 Morrone, Miguel Angel 20-07672540-4 No SI Desde 23 Ago 12 Morrone Miguel
FCP883  Volskwagen Polo  1     Pedraza VyT SA Maximiliano Diaz  Vendido Reg 2 SDE Av Roca Sur 786  xxx xxx 2007 25/04/2006 53-574127 Volkswagen Arg. SA   26.979,12  5644,09 32644,74 Diaz, Maximiliano      23/3/2010 38.900,00 Diaz, Maximiliano 20-22942895-1 No encontre SI Desde 17 May 12 Canto Ricarrdo Marcelo No estaba
FDF660  Chrysler Caravan  1     No lo se Bernardo Pedraza Vendido Reg 97 Cap Fed Rodriguez Peña 426, 4to                        No encontre SI Desde 07 Dic 06 Locatelli Monica Edit
FEL180  Chevrolet Corsa Classic  1     Pedraza VyT SA Cacho Bus En Proceso Bellizi Reg 97 Cap Fed Rodriguez Peña 426, 4to    2006 28/09/2005 A 5-11405 Forest Car   19.586,78  4.113,22 23.700,00 Bellizi Veronica Alicia 24/08/12 B 2-1256 25.000,00  25.000,00 1/11/2011 25000 Bellizi, Veronica Alilcia 27-24204734-1 25/4/2011 NO Ver Nota Retirada  2 de jul de 12 En Proceso    Pedido a Nati 14 Set
FGE026  Toyota Hilux  1     Pedraza VyT SA Pedraza Cecilia  Vendido Reg 97 Cap Fed Rodriguez Peña 426, 4to  xxx xxx 2006 14/11/2005 A 1-6700 Treos Investments SA   81719,46  8580,54 90.300,00 Nuñez Autos SA 24/1/2011 A 2-657 74.380,17 15.619,83 90.000,00 19/7/2011 100.000,00 Lopez Maria y Lopez Casiana 23-31480469-4 2/2/2011 SI Desde 16 Ene 12 Cardozo Benitez Retirada  24 de ago de 11
FGY338  Audi Q7  1     Pedraza VyT SA Bernardo Pedraza Vendido Reg 2 SDE Av Roca Sur 787    2008 13/04/07 53-677115 Volkswagen Arg. SA   260.316,87  54.634,83 314.951,70 VOSA          10 de mar de 08 SI Desde 16 Jul 12 Service Marine SA Retirada  25 de nov de 08
FKC583  Audi A 4 2.0 TDI   Sedan 4 Puertas    01169/263396/37D9795D80  Bernardo Pedraza    Registro N 5, Olivos
FOF743  Volskwagen Bora  1     Pedraza VyT SA Moran, Gustavo Vendido Reg 2 SDE Av Roca Sur 794  AZUL 02/03/11 2007 31/03/2006 53-57145 Volkswagen Arg. SA   42.047,36  8.806,00 42.035,84 Moran, Gustavo  31/8/2012 B 2-1266 56.000,00  56.000,00 26/12/2011 56.000,00 Moran, Gustavo  20-22984895-1 No encontre SI Desde 06 Jun 12 Moran Gustavo
FOU404  MBA ML 350  1     Pedraza VyT SA Bernardo Pedraza Vendido Reg 2 SDE Av Roca Sur 786              VOSA          No encontre SI Desde 14 Abr 10 Olijnyk Guillermo Jose No estaba
FPA904  Toyota Hilux SW4  1     Pedraza, Pablo Pedraza, Pablo  Vendido No me consta      Comprda a Giacomo Gigantelli                    No encontre SI Desde 23 Nov 11 Daract Mauricio Secundino No estaba
FPC882  Volskwagen Polo  1     Pedraza VyT SA Lazarte, Victor En Proceso Bruno L                                Av Roca Sur 792  AZUL 02/03/11 2007 27/04/2006 A 53-574126 Volkswagen Arg. SA   27.000,64  5.644,09 26.979,12 Baier Leslie Jacqueline 13/9/2012 B 2-1268 35.000,00  35.000,00 13/9/2012 35.000,00 Baier Leslie Jacqueline 27-31302995-1 No se hizo SI Desde 10 Oct 12 Baier Leslie Jacqueline
FPC881  Volskwagen Polo  1     Pedraza VyT SA Mare Oscar  Vendido Reg 2 SDE Av Roca Sur 788    2007 25/04/06 53-574125 Volkswagen Arg. SA   26.979,12  5.644,09 32.644,74 Mare, Oscar Guillermo 24-08/-12 B 2-1255 31.000,00  31.000,00 11/6/2010 31.000,00  Mare, Oscar Guillermo 20-13984665-7 29/6/2010 SI Desde 02 Jul 10 Mare Oscar Guillermo Retirada  16 de jul de 10
FPC884  Volskwagen Polo  1     Pedraza VyT SA Pedraza, Santiago En Proceso Belani Reg 2 SDE Av Roca Sur 791  AZUL 26/02/11 2007 27/04/2006 53-574015 Volkswagen Arg. SA   26.736,71  5.588,68 26.979,12 Hubo Belani 1/8/2012 B 1-1245 35.000,00  35.000,00 23/8/2012 35.000,00 Belani, Hugo  20-24240941-9 No  Si Desde el 05 Oct 12 Belani, Hugo Miguel    En Proceso
FPC885  Volskwagen Bora  1     Pedraza VyT SA Luis Heredia Robado Reg 2 SDE Av Roca Sur 786                         ROBADO ROBADO ROBADO
FPN527  Volskwagen Polo  1     Pedraza VyT SA Moran, Gustavo Vendido Reg 2 SDE Av Roca Sur 786  AZUL 02/03/11 2007 25/04/06 53-574128 Volkswagen Arg. SA   27.000,64  5.644,09 26.715,21 Presutti, Maria Gabriela 31/1/2012 B 2-1148 40.000,00  40.000,00 9/1/2012 40.000,00 Presutti, Maria Gabriela 27-21906659-2 No SI 14 Feb 12 Presutti, Gabriela
FXJ887  Volskwagen Bora  1     Pedraza VyT SA Pedraza Rafael  Vendido Reg 2 SDE Av Roca Sur 787  xxx xxx 2007 23/10/2006  Volkswagen Arg. SA   43406  9089 43.385,12 Nuñez Autos SA 20/1/2011 A 2-655 37.190,00 7.810,00 45.000,00 No lo hicimos nosotros    18/2/2011 SI Desde 30 Set 11 Orleacq Carlos Mariano Retirada  18 de nov de 11"""

# Parsear los datos línea por línea
vehiculos = []
for linea in datos_raw.strip().split('\n'):
    partes = linea.split('  ')  # Doble espacio como delimitador
    partes = [p.strip() for p in partes if p.strip()]

    if len(partes) < 2:
        continue

    patente = partes[0].strip()

    # Extraer marca y modelo (generalmente las siguientes 2-3 partes)
    marca = partes[1] if len(partes) > 1 else None
    modelo = ' '.join(partes[2:4]) if len(partes) > 3 else (partes[2] if len(partes) > 2 else None)

    # Buscar el titular (generalmente contiene 'SA', 'SRL' o es un nombre)
    titular = None
    for parte in partes:
        if 'SA' in parte or 'SRL' in parte or 'Pedraza' in parte:
            titular = parte
            break

    # Determinar estado basado en palabras clave
    estado = 'disponible'
    linea_lower = linea.lower()
    if 'vendido' in linea_lower:
        estado = 'baja'
    elif 'robado' in linea_lower:
        estado = 'baja'
    elif 'destruccion total' in linea_lower:
        estado = 'baja'
    elif 'en proceso' in linea_lower:
        estado = 'mantenimiento'

    # Buscar año (4 dígitos que empiecen con 19 o 20)
    anio = None
    for parte in partes:
        match = re.search(r'\b(19|20)\d{2}\b', parte)
        if match:
            anio = int(match.group())
            break

    # Buscar código DNRPA (formato: XXXX/XXXXXX/XXXXXXXX)
    titulo_dnrpa = None
    for parte in partes:
        if re.search(r'\d{2,5}/\d{5,7}/[A-F0-9]{8,10}', parte):
            titulo_dnrpa = parte
            break

    vehiculo = {
        'patente': patente,
        'marca': marca if marca and marca != '0' else None,
        'modelo': modelo.strip() if modelo and modelo != '0' else None,
        'tipo_vehiculo': 'Auto',  # Por defecto
        'anio': anio,
        'motor': None,
        'chasis': None,
        'titulo_dnrpa': titulo_dnrpa,
        'titularidad': titular,
        'estado': estado,
        'observaciones': None
    }

    # Detectar tipo de vehículo por modelo
    modelo_lower = modelo.lower() if modelo else ''
    if any(x in modelo_lower for x in ['pick up', 'hilux', 'amarok', 'ranger']):
        vehiculo['tipo_vehiculo'] = 'Camioneta'
    elif any(x in modelo_lower for x in ['moto', 'can am', 'zanella']):
        vehiculo['tipo_vehiculo'] = 'Moto'
    elif any(x in modelo_lower for x in ['sprinter', 'trafic', 'kangoo']):
        vehiculo['tipo_vehiculo'] = 'Utilitario'
    elif 'trailer' in modelo_lower or 'tractor' in modelo_lower:
        vehiculo['tipo_vehiculo'] = 'Maquinaria'

    vehiculos.append(vehiculo)

print(f"Procesados {len(vehiculos)} vehículos del texto")
print(f"Con DNRPA: {sum(1 for v in vehiculos if v['titulo_dnrpa'])}")
print(f"Vendidos/Dados de baja: {sum(1 for v in vehiculos if v['estado'] == 'baja')}")

# Ahora leer el JSON anterior y completar con estos datos parseados
json_anterior = r'C:\laragon\www\secmautos\docs y dbs\vehiculos_importar.json'
with open(json_anterior, 'r', encoding='utf-8') as f:
    datos_anteriores = json.load(f)

# Crear un diccionario por patente para el merge
vehiculos_dict = {v['patente']: v for v in vehiculos}

# Completar datos del JSON anterior con los parseados
for vehiculo_ant in datos_anteriores['vehiculos']:
    patente = vehiculo_ant['patente']
    if patente in vehiculos_dict:
        # Actualizar con datos parseados
        vehiculo_ant.update({
            k: v for k, v in vehiculos_dict[patente].items()
            if v is not None
        })

# Guardar JSON final
output_file = r'C:\laragon\www\secmautos\docs y dbs\vehiculos_importar_completo.json'
datos_anteriores['fecha_exportacion'] = datetime.now().strftime('%Y-%m-%d')
with open(output_file, 'w', encoding='utf-8') as f:
    json.dump(datos_anteriores, f, ensure_ascii=False, indent=2)

print(f"\n✓ JSON actualizado: {output_file}")
print(f"Total vehículos: {datos_anteriores['total']}")
print(f"Con DNRPA: {sum(1 for v in datos_anteriores['vehiculos'] if v.get('titulo_dnrpa'))}")
print(f"Con año: {sum(1 for v in datos_anteriores['vehiculos'] if v.get('anio'))}")
print(f"Vendidos: {sum(1 for v in datos_anteriores['vehiculos'] if v.get('estado') == 'baja')}")
