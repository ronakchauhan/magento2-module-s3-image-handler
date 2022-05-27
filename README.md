# README #

This README would normally document whatever steps are necessary to get your application up and running.

### What is this repository for? ###

* AWS S3 serverless image url replace
* Version - latest

### How do I get set up? ###

* bin/magento module:enable Y1_S3ImageHandler
* Stores > Configuration > Catalog > Web > Base URLs > Base URL  - for User Media Files and add your S3 url (https://magento-s3-image-sandbox.s3.eu-central-1.amazonaws.com/)
* Stores > Configuration > Advanced > System > Media storage - change it to Amazon S3
* Stores > Configuration > S3 Image handler > set configurations
* Required configurations for S3 image handler: access key, sercret key, serverless endpoint/custom endpoint 
* bin/magento setup:upgrade
* bin/magento cache:flush
