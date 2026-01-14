#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Cambiar todos los vehículos del JSON a estado 'disponible'
"""

import json
from datetime import datetime

# Leer JSON
json_file = r'C:\laragon\www\secmautos\docs y dbs\vehiculos_importar.json'
with open(json_file, 'r', encoding='utf-8') as f:
    data = json.load(f)

# Backup del original
backup_file = r'C:\laragon\www\secmautos\docs y dbs\vehiculos_importar_con_estados_originales.json'
with open(backup_file, 'w', encoding='utf-8') as f:
    json.dump(data, f, ensure_ascii=False, indent=2)

print(f"Backup guardado en: {backup_file}")

# Contar estados antes
estados_antes = {}
for v in data['vehiculos']:
    est = v['estado']
    estados_antes[est] = estados_antes.get(est, 0) + 1

print(f"\nEstados ANTES:")
for estado, count in sorted(estados_antes.items()):
    print(f"  {estado}: {count}")

# Cambiar todos a disponible
for v in data['vehiculos']:
    # Guardar el estado anterior en observaciones si no está ya
    if v['estado'] != 'disponible':
        obs_actual = v.get('observaciones', '')
        if obs_actual:
            v['observaciones'] = f"Estado original: {v['estado']} | {obs_actual}"
        else:
            v['observaciones'] = f"Estado original: {v['estado']}"

    v['estado'] = 'disponible'

# Contar estados después
estados_despues = {}
for v in data['vehiculos']:
    est = v['estado']
    estados_despues[est] = estados_despues.get(est, 0) + 1

print(f"\nEstados DESPUES:")
for estado, count in sorted(estados_despues.items()):
    print(f"  {estado}: {count}")

# Actualizar fecha
data['fecha_exportacion'] = datetime.now().strftime('%Y-%m-%d')

# Guardar JSON modificado
with open(json_file, 'w', encoding='utf-8') as f:
    json.dump(data, f, ensure_ascii=False, indent=2)

print(f"\nJSON actualizado: {json_file}")
print(f"Total vehiculos: {len(data['vehiculos'])}")
print(f"Todos ahora estan como 'disponible'")
print(f"\nEl estado original se guardo en observaciones para referencia")
