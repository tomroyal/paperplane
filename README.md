# paperplane
A simple way to test iOS IPA files.

Paperplane was designed for www.mndigital.co as a simple, lightweight way for us to:

* Install .IPA files (ad-hoc, enterprise, etc) on iOS devices, and
* Share installation links with colleagues

.. all without going through Apple's Testflight system. 

It requires:

* A Heroku app running PHP (hobby tier is fine)
* A Heroku PostgreSQL database (again, hobby tier is fine)
* A Dropbox app set up with folder-level permissions

Once logged in, you can upload IPA files, which are stored in the Dropbox app folder. Uploaded IPAs can be installed or shared via a one-time link.

It's designed to be multi-user, so several people can maintain their own list of apps within one Dropbox backend.

The app upload, install and share systems are complete. Coming next: a setup script, password management etc.
