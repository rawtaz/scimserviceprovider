# SCIM Service Provider

## Running tests

To run the test, you can use [insomnia UI](https://docs.insomnia.rest).

![screenshot insomnia ui](./screenshots/insomnia.png)

For CI, there is still [a bug](https://github.com/Kong/insomnia/issues/4747) we need to find a fix.

## TODO

## Quick "Deploy" to test

```
cd apps
wget https://lab.libreho.st/libre.sh/scim/nextcloud-scim/-/archive/main/nextcloud-scim-main.zip
unzip nextcloud-scim-main.zip
rm nextcloud-scim-main.zip
rm -rf scimserviceprovider
mv nextcloud-scim-main scimserviceprovider
```