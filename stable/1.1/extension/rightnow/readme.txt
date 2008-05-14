<code>
/*
    RightNow CRM Connector for eZ publish
    Copyright (C) 2006  xrow GbR, Hannover Germany, http://xrow.de

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
*/

### BEGIN INIT INFO
# Provides:     rightnow
# Depends:	
# OS:		any
# Version:	> eZ 3.5		
# Developed:	Björn Dieding  ( bjoern@xrow.de )
# Short-Description: rightnow crm integration connector api
# Description:       Integration of RightNow CRM into eZ publish
# Resources:	 http://www.rightnow.com
### END INIT INFO
</code>

About:
RightNow (NASDAQ: RNOW) is leading the industry beyond CRM to high-impact Customer Experience Management solutions. More than 1,800 companies around the world turn to RightNow to drive a superior customer experience across the frontlines of their business. As a win-on-service strategy becomes a business imperative, Customer Experience Management solutions have become essential for business success.

Founded in 1997, RightNow is headquartered in Bozeman, Montana, with additional offices in North America, Europe and Asia.


Installation:

1.)

enable the extension

edit settings/override/site.ini.append.php
[ExtensionSettings]
ActiveExtensions[]=rightnow

2.) Configure RightNow API

edit settings/override/rightnow.ini.append.php

[RightNowSettings]
APIInterface=http://<your_domain>/cgi-bin/<your_interface>.cfg/php/xml_api/parse.php
SecretString=secretpass

Support and service:
service@xrow.de