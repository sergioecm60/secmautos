#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Inspeccionar filas del Excel para encontrar las columnas correctas
"""

import xlrd

excel_file = r'C:\laragon\www\secmautos\docs y dbs\Registro Vehículos.xls'
workbook = xlrd.open_workbook(excel_file, formatting_info=False)
sheet = workbook.sheet_by_index(0)

# Buscar algunas filas específicas que sabemos tienen datos completos
patentes_interes = ['FKC583', 'AB824PX', 'AC472XK', 'AC710CM', 'AC744GK']

print("=" * 100)
print("INSPECCION DE FILAS CON DATOS COMPLETOS")
print("=" * 100)

for row_idx in range(sheet.nrows):
    try:
        patente = str(sheet.cell_value(row_idx, 0)).strip()
        if patente in patentes_interes:
            print(f"\n{'=' * 100}")
            print(f"FILA {row_idx}: {patente}")
            print(f"{'=' * 100}")
            for col_idx in range(sheet.ncols):
                valor = sheet.cell_value(row_idx, col_idx)
                if valor:
                    print(f"Col {col_idx:2d}: {valor}")
    except:
        pass

# También mostrar las primeras 3 filas completas para ver los encabezados
print("\n" + "=" * 100)
print("PRIMERAS 3 FILAS (ENCABEZADOS)")
print("=" * 100)
for row_idx in range(min(5, sheet.nrows)):
    print(f"\nFILA {row_idx}:")
    for col_idx in range(sheet.ncols):
        try:
            valor = sheet.cell_value(row_idx, col_idx)
            if valor:
                print(f"  Col {col_idx:2d}: {valor}")
        except:
            pass
