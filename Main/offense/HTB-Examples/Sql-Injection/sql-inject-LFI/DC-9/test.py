#!/usr/bin/python

import sys

if len (sys.argv) != 3 :
    print ("Usage: python test.py read append")
    sys.exit (1)

else :
    f = open(sys.argv[1], "r") # data you want to copy
    output = (f.read())

    f = open(sys.argv[2], "a") # and add to this file
    f.write(output)
    f.close()