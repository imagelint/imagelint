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

apt-get install time

apt-get install glib2.0-dev
apt-get install expat
apt-get install libjpeg-turbo8
apt-get install fftw3
apt-get install imagemagick
apt-get install orc-0.4
apt-get install libwebp-dev
apt-get install libvips
apt-get install libaom-dev
apt-get install libde265-dev
apt-get install git build-essential libxml2-dev libfftw3-dev libmagickwand-dev libopenexr-dev liborc-0.4-0 gobject-introspection libgsf-1-dev libglib2.0-dev liborc-0.4-dev
apt-get install automake libtool swig gtk-doc-tools 

git clone https://github.com/strukturag/libheif
cd libheif
./autogen.sh
./configure
make
make install

git clone https://github.com/libvips/libvips.git
cd libvips
./autogen.sh
make
make install

ln -s /var/www/html/rotating-wallpapers/libvips/tools/vips /usr/bin/vips
ln -s /var/www/html/rotating-wallpapers/libvips/tools/vips /usr/local/bin/vips

vips heifsave --compression=av1 t.jpg avif.avif
