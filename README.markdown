NanoBBS - a light-weight and fast bulletin board software
=========================================================

Original Software
-----------------
NanoBBS is a one-file, light-weight and fast bulletin board software, intended
as a proof-of-concept that a bulletin board software can be made this simple,
made of one single file, with only PHP as a requirement.
This software is released under the MIT license.


Features
--------

 * Comment Posting (with avatars)

 * Web-scale Database

 * Image EXIF extraction

 * Fast and Secure


Installation
------------
The software uses Riak as the backend database - a clustered key value store with similar 
properties to ADDB. Riak can be downloaded from: http://basho.com/resources/downloads/	

Copy all code files to the deployment directory, including the .htaccess file.

Checklist:

 * Riak Database

 * Webserver with PHP

 * APC extension for PHP


Benchmark
---------
On a 2ghz Core2duo with latest stable Ubuntu packages the software performs well, but 
we advise using Varnish or nginx to accelerate the HTTP service. With Varnish it managed 
to sustain 4000 connections per second, a 5x increase in capacity.

	Server Software:        Apache/2.2
	Server Hostname:        nanobbs.localhost
	Server Port:            80

	Document Path:          /cbJYNGIwUcsF.html
	Document Length:        3122 bytes

	Concurrency Level:      5
	Time taken for tests:   30.001 seconds
	Complete requests:      25520
	Failed requests:        0
	Write errors:           0
	Non-2xx responses:      25522
	Keep-Alive requests:    0
	Total transferred:      84375732 bytes
	HTML transferred:       79679684 bytes
	Requests per second:    850.64 [#/sec] (mean)
	Time per request:       5.878 [ms] (mean)
	Time per request:       1.176 [ms] (mean, across all concurrent requests)
	Transfer rate:          2746.51 [Kbytes/sec] received