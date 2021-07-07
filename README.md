# Ever PS Product Images Import for Prestashop 1.6 - 1.7

Bulk import product images

https://www.team-ever.com/prestashop-import-dimages-produits-en-masse/

## Prestashop bulk import product images free module
This free module allows you to bulk import product images on Prestashop 1.6 up to 1.7

[You can make a donation to support the development of free modules by clicking on this link](https://www.paypal.com/donate?hosted_button_id=3CM3XREMKTMSE)

## Creation of an FTP directory of images
From your FTP space, create a directory at the root of your Prestashop store which will host the images to import. Name it without spaces, without special characters, without accents, as the web standards imply.

Place all of your product images there, make sure that they have the product reference or EAN13 in their name, and possibly a prefix.

## Configuring image import
In the configuration of the bulk image import module, first determine whether you want to use the EAN13 or the product number as a basis.

Specify the name of the directory in which the images are located, which is therefore located at the root of your Prestashop.

If the name of your images has a prefix, specify it in the following field, and if you have prefixed the reference of your products and you wish to use this information to import your images, specify in the last field the prefix used on product references.

Save, then simply click "Import Product Images" to proceed with the bulk import. Make sure you have a high enough max_execution_time on your hosting in case you have a lot of images to import.

## Usage checks before importing
Before launching an import, make sure that at the root of your FTP space, the folder containing the images to import has exactly the same name as the one in the module configuration.

Also check that the images are well named, without spaces, with either the product reference or its EAN13 (if necessary, the import will not work)

Also check that these are images in jpg format, Prestashop will totally refuse any import of images that are not compliant, or images that are too large.
