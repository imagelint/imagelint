# Imagelint

A laravel based image manipulation / optimization server. The main goal is it's ease of use to provide optimized images for your frontend.

#### Why imagelint?
 
 - Automatically optimize and resize your images
 - Great performance because files are cached and served directly by nginx
 - Backend interface with statistics and many features to modify your images 

#### Example

    # Change this:
    https://yourserver.com/avatars/me.png

    # To this:    
    https://imagelint.yourserver.com/yourserver.com/avatars/me.png?il-width=100&il-height=100

This returns the image `yourserver.com/avatars/me.png` optimized, resized to 100x100px and is served as webp when the browser supports it.

#### Todo
 - Write installation instructions
 - Make "test your site" tool clientside so we don't generate as much server load
 - Buffer syslog-ng entries


#### Installation

in progress...

