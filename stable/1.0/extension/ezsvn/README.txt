/*
    eZSVN for eZ publish
    Copyright (C) 2004  xrow GbR, Hannover Germany

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
Björn Dieding   ( bjoern@xrow.de )

State: Beta

PubSVN:
http://pubsvn.ez.no/viewcvs/community/trunk/extension/ezsvn/
Thread in forum:
http://ez.no/community/forum/developer/extension_ezsvn_pubsvn

Description:
This extenstion delivers tools for eZ publish to work more closly togehter with SVN Repositories.
This toolkit is meant for heavy eZ developers that work on many eZ installations on multiple servers.
The basic idea is that you develop in a local environment and syncronise your sources over svn.

Screenshots:
http://pubsvn.ez.no/viewcvs/*checkout*/community/trunk/extension/ezsvn/doc/screenshot_1.jpg (admin)
http://pubsvn.ez.no/viewcvs/*checkout*/community/trunk/extension/ezsvn/doc/screenshot_2.jpg (shell)

Currently it has following functionality

- A soap interface to supply a client with data
- A client shell script

It comes with following modules/views

svn/configserver <- SOAP Server

=============
Installation
=============
- Copy extension into extenstion folder
- Activate extenstion admin->setup->extensions
- Import package ( eZSVN-1[1].0-1.ezpkg ) admin->setup->packages

=============
Usage
=============
- Create Object SVN-Composition with SVN-Items
- Use shell client to update sources from SVN repositories
  . extension/ezsvn/bin/ezsvn.php
