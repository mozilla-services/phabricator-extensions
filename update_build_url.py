#!/usr/bin/python2.7
""" Update the version,json to contain phabext information"""
import json
import sys

try:
    with open('/app/mozphab.json', 'r') as f:
        mozphab_circle_data = json.load(f)
except IOError:
    print "mozphab.json not found"
    mozphab_circle_data = {}
try:
    with open('/app/phabext.json', 'r') as g:
        phabext_circle_data = json.load(g)
except IOError:
    print "phabext.json not found"
    phabext_circle_data = {}
mozphab_circle_data.update(phabext_circle_data)

try:
    with open('/app/version.json', 'w') as f:
        json.dump(mozphab_circle_data, f)
except IOError:
    print "Could not create version.json"
    sys.exit()
