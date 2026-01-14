#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Procesador directo del Excel de vehículos
Lee fila por fila y mapea por posición de columna
"""

import xlrd
import json
from datetime import datetime
import re

# Abrir el Excel
excel_file = r'C:\laragon\www\secmautos\docs y dbs\Registro Vehículos.xls'
workbook = xlrd.open_workbook(excel_file, formatting_info=False)
sheet = workbook.sheet_by_index(0)

print(f"Total filas: {sheet.nrows}")
print(f"Total columnas: {sheet.ncols}")

# Identificar la fila donde empiezan los datos (después de los encabezados)
# Buscar la fila que tenga "BJP009" o similar en la primera columna
fila_inicio_datos = None
for i in range(min(10, sheet.nrows)):
    try:
        valor = sheet.cell_value(i, 0)
        if valor and isinstance(valor, str):
            # Si parece una patente (letras y números, 6-7 caracteres)
            if re.match(r'^[A-Z]{2,3}\d{3,4}$', valor.replace(' ', '')):
                fila_inicio_datos = i
                break
    except:
        pass

if not fila_inicio_datos:
    print("No se encontró el inicio de los datos")
    exit(1)

print(f"Inicio de datos en fila: {fila_inicio_datos}")

# Mapeo de columnas (índice) según el análisis del Excel
# Basado en inspección de filas con datos completos
# Los encabezados están en las filas 3 y 4
COL_PATENTE = 0      # Dominio
COL_MARCA = 2        # Marca
COL_MODELO = 3       # Modelo
COL_ANO = 4          # Año (fila 4) o Modelo V (puede contener año como float)
COL_TIPO = 6         # Tipo (Sedan, Pick Up, etc.)
COL_MOTOR = 7        # Motor
COL_CHASIS = 8       # Chasis
COL_TITULO_DNRPA = 9 # Título DNRPA (formato: XXXX/XXXXXX/XXXXXXXX)
COL_TITULAR = 10     # Titular ("en el" en fila 4)
COL_UTILIZADO_POR = 11  # Apellido y Nombre
COL_VENDIDO = 12     # Vendido
COL_REGISTRO = 13    # Registro del Automotor
COL_DIR_REGISTRO = 14  # Dirección del Registro

# Procesar vehículos
vehiculos = []
patentes_procesadas = set()

for row_idx in range(fila_inicio_datos, sheet.nrows):
    try:
        # Leer patente
        patente_raw = sheet.cell_value(row_idx, COL_PATENTE)
        if not patente_raw or not isinstance(patente_raw, str):
            continue

        patente = patente_raw.strip().replace(' ', '')

        # Validar formato de patente (vieja: ABC123, nueva: AB123CD)
        # Incluir también tractores y maquinarias con formatos diferentes
        if not re.match(r'^[A-Z0-9]{3,7}$', patente):
            continue

        # Evitar duplicados
        if patente in patentes_procesadas:
            continue
        patentes_procesadas.add(patente)

        # Leer marca
        marca = None
        try:
            marca_raw = sheet.cell_value(row_idx, COL_MARCA)
            if marca_raw and isinstance(marca_raw, str):
                marca = marca_raw.strip()
        except:
            pass

        # Leer modelo
        modelo = None
        try:
            modelo_raw = sheet.cell_value(row_idx, COL_MODELO)
            if modelo_raw and isinstance(modelo_raw, str):
                modelo = modelo_raw.strip()

            # Agregar variante si existe
            try:
                variante_raw = sheet.cell_value(row_idx, COL_MODELO_V)
                if variante_raw and isinstance(variante_raw, str):
                    variante = variante_raw.strip()
                    # Si la variante no es un año, agregarla al modelo
                    if not variante.isdigit() and variante not in ['0', '1']:
                        modelo = f"{modelo} {variante}" if modelo else variante
            except:
                pass
        except:
            pass

        # Leer año (puede estar en varias columnas)
        anio = None
        for col in [COL_ANO, 6, 18]:  # Probar varias posiciones
            try:
                anio_raw = sheet.cell_value(row_idx, col)
                if anio_raw:
                    if isinstance(anio_raw, (int, float)):
                        anio_val = int(anio_raw)
                        if 1950 < anio_val < 2030:
                            anio = anio_val
                            break
                    elif isinstance(anio_raw, str) and anio_raw.isdigit():
                        anio_val = int(anio_raw)
                        if 1950 < anio_val < 2030:
                            anio = anio_val
                            break
            except:
                pass

        # Leer tipo
        tipo_vehiculo = 'Auto'  # Por defecto
        try:
            tipo_raw = sheet.cell_value(row_idx, COL_TIPO)
            if tipo_raw and isinstance(tipo_raw, str):
                tipo_str = tipo_raw.strip()
                tipo_map = {
                    'Pick Up': 'Camioneta',
                    'Sedan': 'Auto',
                    'Rural': 'Auto',
                    'Todo Terreno': 'Camioneta',
                    'Triciclo': 'Triciclo',
                    'Tractor': 'Tractor',
                    'Furgon': 'Van',
                    'Coupe': 'Auto',
                    'Chasis con Cabina': 'Camion',
                    'Tte de Pasajeros': 'Van'
                }
                tipo_vehiculo = tipo_map.get(tipo_str, 'Auto')
        except:
            pass

        # Leer motor
        motor = None
        try:
            motor_raw = sheet.cell_value(row_idx, COL_MOTOR)
            if motor_raw:
                motor_str = str(motor_raw).strip()
                if motor_str and motor_str not in ['', '0', '0.0']:
                    motor = motor_str
        except:
            pass

        # Leer chasis
        chasis = None
        try:
            chasis_raw = sheet.cell_value(row_idx, COL_CHASIS)
            if chasis_raw:
                chasis_str = str(chasis_raw).strip()
                if chasis_str and chasis_str not in ['', '0', '0.0']:
                    chasis = chasis_str
        except:
            pass

        # Leer título DNRPA
        titulo_dnrpa = None
        try:
            titulo_raw = sheet.cell_value(row_idx, COL_TITULO_DNRPA)
            if titulo_raw:
                titulo_str = str(titulo_raw).strip()
                # Verificar formato XXXX/XXXXXX/XXXXXXXX o XXXXX/XXXXXX/XXXXXXXXXX
                # Ignorar textos como "Titulo en Papel", "Scaneado", etc.
                if re.search(r'\d{2,5}/\d{5,7}/[A-F0-9]{8,12}', titulo_str):
                    titulo_dnrpa = titulo_str
        except:
            pass

        # Leer titular
        titularidad = None
        try:
            titular_raw = sheet.cell_value(row_idx, COL_TITULAR)
            if titular_raw and isinstance(titular_raw, str):
                titularidad = titular_raw.strip()
        except:
            pass

        # Leer empleado actual (Utilizado por)
        empleado_actual = None
        try:
            utilizado_raw = sheet.cell_value(row_idx, COL_UTILIZADO_POR)
            if utilizado_raw and isinstance(utilizado_raw, str) and utilizado_raw.strip():
                empleado_actual = utilizado_raw.strip()
        except:
            pass

        # Leer estado (vendido/disponible)
        estado = 'disponible'
        try:
            vendido_raw = sheet.cell_value(row_idx, COL_VENDIDO)
            if vendido_raw and isinstance(vendido_raw, str):
                vendido_lower = vendido_raw.strip().lower()
                if 'vendido' in vendido_lower:
                    estado = 'vendido'  # CAMBIADO: usar 'vendido' en lugar de 'baja'
                elif 'robado' in vendido_lower:
                    estado = 'baja'  # Solo robo y destrucción van a 'baja'
                elif 'destruccion' in vendido_lower:
                    estado = 'baja'
                elif 'en proceso' in vendido_lower:
                    estado = 'mantenimiento'
        except:
            pass

        # Construir observaciones (sin incluir "Utilizado por", ahora en campo separado)
        observaciones_parts = []
        try:
            registro_raw = sheet.cell_value(row_idx, COL_REGISTRO)
            if registro_raw and isinstance(registro_raw, str):
                observaciones_parts.append(f"Registro: {registro_raw.strip()}")
        except:
            pass

        try:
            dir_registro_raw = sheet.cell_value(row_idx, COL_DIR_REGISTRO)
            if dir_registro_raw and isinstance(dir_registro_raw, str):
                observaciones_parts.append(f"Dirección: {dir_registro_raw.strip()}")
        except:
            pass

        observaciones = ' | '.join(observaciones_parts) if observaciones_parts else None

        # Detección adicional de maquinaria agrícola por marca
        if marca and tipo_vehiculo not in ['Tractor', 'Maquinaria Agricola']:
            marca_lower = marca.lower()
            marcas_agricolas = ['john deere', 'zoomlion', 'new holland', 'case', 'massey ferguson',
                               'fendt', 'valtra', 'deutz', 'kubota', 'mahindra']
            if any(m in marca_lower for m in marcas_agricolas):
                if tipo_vehiculo == 'Tractor' or (modelo and 'tractor' in modelo.lower()):
                    tipo_vehiculo = 'Tractor'
                else:
                    tipo_vehiculo = 'Maquinaria Agricola'

        # Crear objeto vehículo
        vehiculo = {
            'patente': patente,
            'marca': marca,
            'modelo': modelo,
            'tipo_vehiculo': tipo_vehiculo,
            'anio': anio,
            'motor': motor,
            'chasis': chasis,
            'titulo_dnrpa': titulo_dnrpa,
            'titularidad': titularidad,
            'empleado_actual': empleado_actual,
            'estado': estado,
            'observaciones': observaciones
        }

        vehiculos.append(vehiculo)

    except Exception as e:
        print(f"Error en fila {row_idx}: {e}")
        continue

print(f"\n[OK] Procesados {len(vehiculos)} vehiculos")
print(f"Con marca: {sum(1 for v in vehiculos if v['marca'])}")
print(f"Con modelo: {sum(1 for v in vehiculos if v['modelo'])}")
print(f"Con anio: {sum(1 for v in vehiculos if v['anio'])}")
print(f"Con motor: {sum(1 for v in vehiculos if v['motor'])}")
print(f"Con chasis: {sum(1 for v in vehiculos if v['chasis'])}")
print(f"Con titulo DNRPA: {sum(1 for v in vehiculos if v['titulo_dnrpa'])}")
print(f"Con titular: {sum(1 for v in vehiculos if v['titularidad'])}")
print(f"Con empleado actual: {sum(1 for v in vehiculos if v['empleado_actual'])}")
print(f"Con observaciones: {sum(1 for v in vehiculos if v['observaciones'])}")
print(f"\nEstados:")
print(f"  - Disponible: {sum(1 for v in vehiculos if v['estado'] == 'disponible')}")
print(f"  - Vendido: {sum(1 for v in vehiculos if v['estado'] == 'vendido')}")
print(f"  - Mantenimiento: {sum(1 for v in vehiculos if v['estado'] == 'mantenimiento')}")
print(f"  - Baja (robado/destruido): {sum(1 for v in vehiculos if v['estado'] == 'baja')}")

# Generar JSON final
output_data = {
    'total': len(vehiculos),
    'fecha_exportacion': datetime.now().strftime('%Y-%m-%d'),
    'origen': 'Registro Vehículos.xls',
    'vehiculos': vehiculos
}

output_file = r'C:\laragon\www\secmautos\docs y dbs\vehiculos_importar_completo.json'
with open(output_file, 'w', encoding='utf-8') as f:
    json.dump(output_data, f, ensure_ascii=False, indent=2)

print(f"\n[OK] JSON generado: {output_file}")

# Mostrar algunos ejemplos
print("\n" + "=" * 80)
print("PRIMEROS 10 VEHÍCULOS:")
print("=" * 80)
for i, v in enumerate(vehiculos[:10], 1):
    print(f"\n{i}. {v['patente']} - {v['marca']} {v['modelo']}")
    if v['anio']:
        print(f"   Año: {v['anio']}")
    if v['titulo_dnrpa']:
        print(f"   DNRPA: {v['titulo_dnrpa']}")
    if v['titularidad']:
        print(f"   Titular: {v['titularidad']}")
    print(f"   Estado: {v['estado']}")
