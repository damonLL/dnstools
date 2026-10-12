CC = gcc

# Make sure to set your LIBS and INCLUDES appropriately.
# Check your specific installation for these files and
# modify these values below.

LIBS = -L/usr/local/mysql/lib/mysql
INCLUDES = -I/usr/local/mysql/include/mysql -I/usr/include

all: clean dnstools

# Swap the commented line below to create a dynamically linked
# or statically linked executable

dnstools:
#	$(CC) -static -o dnstools dnstools.c $(INCLUDES) $(LIBS) -lmysqlclient -lz
	$(CC) -o dnstools dnstools.c $(INCLUDES) $(LIBS) -lmysqlclient -lz

clean:
	rm dnstools
