
Seltzer CRM REST API

## Contents ##
1. Overview
2. Installation and Usage

## Overview ##
This is a quick way to expose your own SQL queries as a REST
URL that can be accessed from various internet connected
devices or other web apps simply via HTTP.


## Installation and Usage ##
Simply make sure the api folder and its files are copied onto
the same server as the rest of the Seltzer CRM directly into
the /crm folder.  So it should be accessed via the URL similar
to:

http://yourserver.com/crm/api/...

Then make sure to edit the dbconnect.php file in this api folder
to match the settings required to connect to your seltzer database.

### Usage ###
An example query might be to check if a member is allowed in
using their RFID scanned at the door via an RFID reader
attached to a Raspberry Pi based on their payment status.

Simply read the RFID serial via a python script or similar program
then put that string on the end of the URL like so:

Say the RFID reader returned this string after reading the card:
345A33008C

Then you'd stick it on the URL like this:

http://yourserver.com/crm/api/query.php?action=doorLockCheck&rfid=345A33008C

Then use whatever HTTP request functionality in your script to GET that URL
and the response should resemble something like "true" or "false".

Then your script would be able to know immediately if it should open the
door or not by turning a servo on the deadbolt or turning off the power
to the magnetic lock via relay switch, etc.