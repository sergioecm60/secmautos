#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Análisis completo del Excel de vehículos para SECMAUTOS
Extrae TODOS los campos relevantes para importación
"""

import pandas as pd
import json
from datetime import datetime

# Leer el archivo Excel
excel_file = r'C:\laragon\www\secmautos\docs y dbs\Registro Vehículos.xls'

# Leer con xlrd (formato antiguo .xls), los encabezados están en la fila 2
df = pd.read_excel(excel_file, engine='xlrd', header=2)

# Mostrar todas las columnas
print("=" * 80)
print("COLUMNAS DEL EXCEL:")
print("=" * 80)
for i, col in enumerate(df.columns, 1):
    print(f"{i:2d}. {col}")

print("\n" + "=" * 80)
print("PRIMERAS 3 FILAS COMPLETAS:")
print("=" * 80)
print(df.head(3).to_string())

print("\n" + "=" * 80)
print("TIPOS DE DATOS:")
print("=" * 80)
print(df.dtypes)

# Identificar qué columnas tienen datos útiles
print("\n" + "=" * 80)
print("COLUMNAS CON DATOS (% no vacío):")
print("=" * 80)
for col in df.columns:
    non_null = df[col].notna().sum()
    total = len(df)
    pct = (non_null / total) * 100 if total > 0 else 0
    if pct > 5:  # Solo mostrar columnas con más del 5% de datos
        print(f"{col:40s}: {non_null:3d}/{total:3d} ({pct:5.1f}%)")

# Mapeo propuesto de columnas Excel → Base de Datos
mapeo = {
    'Dominio': 'patente',
    'Marca': 'marca',
    'Modelo': 'modelo',
    'Modelo V': 'modelo_variante',
    'Tipo': 'tipo_vehiculo',
    'Motor': 'motor',
    'Chasis': 'chasis',
    'Año': 'anio',
    'Titulo (Reg Secc/Tramite/Control Web)': 'titulo_dnrpa',
    'Titular': 'titularidad',
    'Vendido': 'estado',
    'Registro del Automotor': 'registro',
    'Direccion del Registro del Automotor': 'registro_direccion',
    'Cedula': 'cedula_verde',
    'Comentario': 'observaciones'
}

print("\n" + "=" * 80)
print("MAPEO PROPUESTO:")
print("=" * 80)
for excel_col, db_field in mapeo.items():
    if excel_col in df.columns:
        print(f"✓ {excel_col:40s} → {db_field}")
    else:
        print(f"✗ {excel_col:40s} → {db_field} (NO EXISTE)")

# Generar JSON completo con todos los datos
vehiculos = []

for idx, row in df.iterrows():
    patente = str(row['Dominio']).strip() if pd.notna(row['Dominio']) else None

    # Saltar filas sin patente o patentes inválidas
    if not patente or patente == 'nan' or len(patente) < 3:
        continue

    # Construir el modelo completo (Modelo + Modelo V si existe)
    modelo = str(row['Modelo']).strip() if pd.notna(row['Modelo']) else ''
    if 'Modelo V' in df.columns and pd.notna(row['Modelo V']):
        variante = str(row['Modelo V']).strip()
        if variante and variante != 'nan':
            modelo = f"{modelo} {variante}".strip()

    # Determinar el tipo de vehículo
    tipo = 'Auto'  # Por defecto
    if 'Tipo' in df.columns and pd.notna(row['Tipo']):
        tipo_str = str(row['Tipo']).strip()
        if tipo_str:
            # Mapear tipos del Excel a los válidos
            tipo_map = {
                'Pick Up': 'Camioneta',
                'Sedan': 'Auto',
                'Rural': 'Auto',
                'Todo Terreno': 'Camioneta',
                'Triciclo': 'Moto',
                'Tractor': 'Maquinaria',
                'Furgon': 'Camioneta',
                'Coupe': 'Auto',
                'Chasis con Cabina': 'Camion',
                'Tte de Pasajeros': 'Combi'
            }
            tipo = tipo_map.get(tipo_str, 'Auto')

    # Determinar el estado
    estado = 'disponible'
    if 'Vendido' in df.columns and pd.notna(row['Vendido']):
        vendido = str(row['Vendido']).strip().lower()
        if vendido in ['vendido', 'si']:
            estado = 'baja'
        elif vendido == 'robado':
            estado = 'baja'
        elif vendido == 'en proceso':
            estado = 'disponible'

    # Año
    anio = None
    if 'Año' in df.columns and pd.notna(row['Año']):
        try:
            anio = int(float(row['Año']))
        except:
            pass

    # Motor
    motor = None
    if 'Motor' in df.columns and pd.notna(row['Motor']):
        motor_str = str(row['Motor']).strip()
        if motor_str and motor_str != 'nan':
            motor = motor_str

    # Chasis
    chasis = None
    if 'Chasis' in df.columns and pd.notna(row['Chasis']):
        chasis_str = str(row['Chasis']).strip()
        if chasis_str and chasis_str != 'nan':
            chasis = chasis_str

    # Título DNRPA
    titulo_dnrpa = None
    if 'Titulo (Reg Secc/Tramite/Control Web)' in df.columns:
        titulo = row['Titulo (Reg Secc/Tramite/Control Web)']
        if pd.notna(titulo):
            titulo_str = str(titulo).strip()
            if titulo_str and titulo_str != 'nan' and titulo_str.lower() not in ['titulo en papel', 'scaneado', 'se perdio el codigo']:
                titulo_dnrpa = titulo_str

    # Titular
    titular = None
    if 'Titular' in df.columns and pd.notna(row['Titular']):
        titular_str = str(row['Titular']).strip()
        if titular_str and titular_str != 'nan':
            titular = titular_str

    # Registro del automotor
    registro = None
    if 'Registro del Automotor' in df.columns and pd.notna(row['Registro del Automotor']):
        registro_str = str(row['Registro del Automotor']).strip()
        if registro_str and registro_str != 'nan':
            registro = registro_str

    # Construir observaciones
    observaciones_parts = []

    # Agregar información del registro
    if registro:
        observaciones_parts.append(f"Registro: {registro}")

    if 'Direccion del Registro del Automotor' in df.columns and pd.notna(row['Direccion del Registro del Automotor']):
        direccion = str(row['Direccion del Registro del Automotor']).strip()
        if direccion and direccion != 'nan':
            observaciones_parts.append(f"Dirección Registro: {direccion}")

    # Agregar comentarios si existen
    if 'Comentario' in df.columns and pd.notna(row['Comentario']):
        comentario = str(row['Comentario']).strip()
        if comentario and comentario != 'nan':
            observaciones_parts.append(comentario)

    # Información de utilizado por
    if 'Utilizado por:' in df.columns and pd.notna(row['Utilizado por:']):
        utilizado = str(row['Utilizado por:']).strip()
        if utilizado and utilizado != 'nan':
            observaciones_parts.append(f"Utilizado por: {utilizado}")

    observaciones = ' | '.join(observaciones_parts) if observaciones_parts else None

    # Crear el objeto vehículo
    vehiculo = {
        'patente': patente,
        'marca': str(row['Marca']).strip() if pd.notna(row['Marca']) else None,
        'modelo': modelo if modelo else None,
        'tipo_vehiculo': tipo,
        'anio': anio,
        'motor': motor,
        'chasis': chasis,
        'titulo_dnrpa': titulo_dnrpa,
        'titularidad': titular,
        'estado': estado,
        'observaciones': observaciones
    }

    vehiculos.append(vehiculo)

# Generar JSON
output_data = {
    'total': len(vehiculos),
    'fecha_exportacion': datetime.now().strftime('%Y-%m-%d'),
    'origen': 'Registro Vehículos.xls',
    'vehiculos': vehiculos
}

# Guardar JSON
output_file = r'C:\laragon\www\secmautos\docs y dbs\vehiculos_importar_completo.json'
with open(output_file, 'w', encoding='utf-8') as f:
    json.dump(output_data, f, ensure_ascii=False, indent=2)

print("\n" + "=" * 80)
print(f"✅ JSON GENERADO: {output_file}")
print("=" * 80)
print(f"Total de vehículos procesados: {len(vehiculos)}")
print(f"Con título DNRPA: {sum(1 for v in vehiculos if v['titulo_dnrpa'])}")
print(f"Con motor: {sum(1 for v in vehiculos if v['motor'])}")
print(f"Con chasis: {sum(1 for v in vehiculos if v['chasis'])}")
print(f"Con observaciones: {sum(1 for v in vehiculos if v['observaciones'])}")
print(f"Estado 'baja' (vendidos/robados): {sum(1 for v in vehiculos if v['estado'] == 'baja')}")
print(f"Estado 'disponible': {sum(1 for v in vehiculos if v['estado'] == 'disponible')}")

# Mostrar algunos ejemplos
print("\n" + "=" * 80)
print("EJEMPLOS DE VEHÍCULOS PROCESADOS:")
print("=" * 80)
for i, v in enumerate(vehiculos[:5], 1):
    print(f"\n{i}. {v['patente']}")
    for key, value in v.items():
        if value and key != 'patente':
            print(f"   {key}: {value}")
