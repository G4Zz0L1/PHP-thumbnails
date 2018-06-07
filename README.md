# PHP-thumbnails

A simple script which uses Verot's class.upload.php to generate thumbnails

Basic setup:

- If you want to use the "seo friendly" mode, put the following in the .htaccess
```htaccess
RewriteRule ^thumb/(.*)$ /thumb.php?arg=$1 [NC,L,QSA]
```

- Change the default img at line 40
- Create a writable folder "cache" at the same level of this script, it'll be used to cache the files

The file must be called like:
```php
thumb.php?width=width&height=height&file=path/to/file.ext
```
If you enable the "seo friendly" mode, instead
```php
thumb/width/height/path/to/file
```