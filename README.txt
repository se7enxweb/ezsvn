/*
    SVN for eZ publish
    Copyright (C) 2003-2008  xrow GmbH, Hannover Germany

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
*/

Developed by
Björn Dieding   ( bjoern [at] xrow d o t t y com )


Description:
This extenstion delivers tools for eZ publish to work more closly togehter with SVN Repositories.
This toolkit is meant for heavy eZ developers that work on many eZ installations on multiple servers.
The basic idea is that you develop in a local environment and syncronise your sources over svn.

Currently it has following functionality

- Multiple shell scripts
- Multiple views for managing stuff online

=============
Installation
=============
- Copy extension into extenstion folder

=============
Usage
=============
- Use shell client to create xml file from your current installation 
  php extension/ezsvn/bin/buildxml.php
- Use shell client to update sources from SVN repositories
  php extension/ezsvn/bin/svn.php
