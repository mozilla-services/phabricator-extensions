#!/usr/bin/python2.7
""" Update the version,json to contain phabext information"""
from __future__ import print_function
import json
import sys

try:
    with open('/app/version.json', 'r') as f:
        mozphab_circle_data = json.load(f)
except IOError:
    print("version.json not found")
    mozphab_circle_data = {}
try:
    with open('/app/phabext.json', 'r') as g:
        phabext_circle_data = json.load(g)
except IOError:
    print("phabext.json not found")
    phabext_circle_data = {}
mozphab_circle_data.update(phabext_circle_data)

try:
    with open('/app/version.json', 'r+') as f:
        f.seek(0)
        json.dump(mozphab_circle_data, f)
        f.close()
except IOError:
    print("Could not create version.json")
    sys.exit()
