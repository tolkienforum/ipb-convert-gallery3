# Converter Gallery 3.0.x -> IP.Gallery


The project contains an additional Gallery converter for the Invisionboard Converter Application.


IP.Gallery Converter to Convert Gallery 3 (Menalto Gallery) to Invisionboard Gallery

## Features:
-   Forum Perms and Groups are mapped, but since the members are not converted the effect is minimal.
-   Members are not converted. The queries have been adopted but member creation is commented out.  
    Note: it is possible to add a member-id mapping to allow to associate the comments to the right member.  
    (This is due my gallery had no common user-base with the forum and the mapping required some knowledge about
    who-is-who - and my Gallery only contained around 35 users)
-   Albums that contain no images or only 1 (I used that to have a cover image) will become categories.
-   Albums with images will become album in their according category.
-   Images will be added to their album
-   Comments will be converted.
 
I have not tested all cases (e.g. images and albums within the same album).

## Instructions:

### Versions:

The script was made for:

-   [Gallery 3.0.9](http://galleryproject.org/)
-   [Invisionboard / 	IP.Board 3.4.6](http://www.invisionpower.com/apps/board/)
-   [IPS Converters 3.4 (Build 1.2.10)](http://community.invisionpower.com/files/file/4715-ips-converters/) and Gallery 5.0.5

**Backup EVERYTHING!! I mean it!**  
Dump the Gallery Database
Dump the Forum Database
Zip/Copy the Forum and the Gallery Image Folder

The Gallery database is only used for reading. Obviously the Invisionboard database will be changed. Same
is valid for the folder structure.

### Prepare the Gallery:

-   It is important that some data is properly set. Therefore the repair maintenance task must be run:
    -   Login to Gallery3 with an administrator account
    -   go to 'Admin' --> 'Maintenance'
    -   run 'Fix your Gallery' - this will properly set the image paths for your gallery items.
-   make the albums folder readable to Invisionbard (<gallery3_home>/var/albums).  
    You only need the *albums* folder (no *thumbs* etc).

### Prepare Invisionboard

- Install the converter app (see [Invisionpower Convert](http://www.invisionpower.com/convert))
- copy the gallery3.php file from this repository to the converter.
- Login to the IP.Board administration console and go to the converter app.
- Start a new conversion. Choose the *Menalto Gallery 3* converter. Some steps will require additional information.

## Log-Files and Errors:

Usually the conversion fails with some SQL error. It can be found in *<IP.Board_Home>/cache/sql_error_latest.cgi*
