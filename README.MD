<p align="center">
    <img src="https://www.imagelint.com/img/logo.svg" alt="Imagelint logo" width="300">
</p>
<h4 align="center">
    Imagelint is an easy to install web server which takes care of resizing and optimizing your images.<br>
    It's a self-hostable alternative for services like Cloudinary or imgIX.
</h4>

## Key features

* Easy resizing via URL parameters.
* Automatic conversion to **webp** or **avif** if the browser supports it
* Compression of **jpg**, **png**, **gif** and **svg** files
* High performance nginx caching included
* Multi-Server capable

## Example optimization
The following [unsplash image](https://unsplash.com/photos/tGG_Y-lew_Y) was resized to a width of 500px and then optimized with imagelint.

<p align="center">
<img src="https://raw.githubusercontent.com/imagelint/imagelint/master/.github/img/github-graphic-shadow.jpg" alt="Comparing two images, one is optimized with imagelint and is only 13% of the size of the unoptimized one" width="838">
</p>

## Installation instructions

Work in progress. Check back soon :)

## Resource usage

Encoding images takes quite a bit of cpu power and memory. There are significant differences between the different file formats.

To get an impression of how much resources are needed, the following table shows an overview how much resources it takes to encode an image which is 1920x970px in size.
The encoding was executed on an intel 10 core processor (i9-7900X @ 3.30GHz).

| Jpg: time 	| Jpg: memory 	| webp: time 	| webp: memory 	| avif: time 	| avif: memory 	|
|-----------	|-------------	|------------	|--------------	|------------	|--------------	|
| 66 ms     	| 8.2 MB      	| 1315 ms     	| 13.57 MB     	| 5277 ms    	| 2696 MB      	|
