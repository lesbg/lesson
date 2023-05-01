#!/bin/sh
if [ -d "$2" ]
then
	mv "$1" "$2"
	chown "$3"."apache" "$2"
	chmod 666 "$2"
else
	echo "Directory $2 doesn't exist!"
fi
