# SCIM Service Provider

This app allows to provision users and groups in Nextcloud from a scim client. It is based on [audriga/scim-server-php](https://github.com/audriga/scim-server-php) SCIM library.

You can see the [video](https://hot-objects.liiib.re/meet-liiib-re-recordings/pair_2022-05-02-15-40-37.mp4) that shows how it works.

---

## Table of content

1. [How to use](#how-to-use)
   1. [Installation](#installation)
   2. [Authentication](#authentication)
      1. [Basic authentication](#basic-authentication)
      2. [Bearer token authentication](#bearer-token-authentication)
         1. [JWT generation (for admins only!)](#jwt-generation-for-admins-only)
         2. [Usage of the JWT](#usage-of-the-jwt)
2. [Use with Keycloak](#use-with-keycloak)
3. [Use with AzureAD](#use-with-azuread)
4. [Running tests](#running-tests)
5. [Todo](#todo)
6. [Disclaimer](#disclaimer)
7. [NextGov Hackathon](#nextgov-hackathon)

---

## How to use

### Installation
We plan to publish on the Nextcloud app store, but in the mean time you can use instructions bellow.

```
cd custom_apps
wget https://lab.libreho.st/libre.sh/scim/nextcloud-scim/-/archive/main/nextcloud-scim-main.zip
unzip nextcloud-scim-main.zip
rm nextcloud-scim-main.zip
rm -rf scimserviceprovider
mv nextcloud-scim-main scimserviceprovider
cd scimserviceprovider
composer install
```

### Authentication
Currently, this app supports both Basic authentication, as well as Bearer token authentication via JWTs. One can change between these two authentication modes by setting the `auth_type` config parameter in the config file under `/lib/Config/config.php` to either `basic` or `bearer`.

#### Basic authentication
In order to authenticate via Basic auth, send SCIM requests to the SCIM endpoints of the following form:

> `http://<path-to-nextcloud>/index.php/apps/scimserviceprovider/<Resource>`

where `<Resource>` designates a SCIM resource, such as `Users` or `Groups`.

For example:

```
$ curl http://<path-to-nextcloud>/index.php/apps/scimserviceprovider/<Resource> -u someusername:pass123 -H 'Content-Type: application/scim+json'
```

#### Bearer token authentication
In order to authenticate via a Bearer token, send SCIM requests to the SCIM endpoints of the following form:

> `http://<path-to-nextcloud>/index.php/apps/scimserviceprovider/bearer/<Resource>`

where `<Resource>` designates a SCIM resource, such as `Users` or `Groups`. Also, make sure to provide the Bearer token in the `Authorization` header of the SCIM HTTP request.

##### JWT generation (for admins only!)
Before providing the token, though, you'd need to obtain one. This is done with the help of a script which can generate JWTs and which is part of `scim-server-php`, the SCIM library by audriga, used as a dependency in this app.

A JWT can be generated as follows:

```
$ vendor/audriga/scim-opf/bin/generate_jwt.php --username someusername --secret topsecret123
```

where

- `--username` is the username of the user that you want to generate a JWT
- `--secret` is the secret key set in the `jwt` config parameter in the config file under `/lib/Config/config.php`, used for signing the JWT

**Note:** the generated JWT has a claim, called `user` which contains the username that was passed to the JWT generation script and which is later also used for performing the actual authentication check in Nextcloud. For example, it could look like something like this: `{"user":"someusername"}.`

##### Usage of the JWT
A sample usage of JWT authentication as an example:

```
$ curl http://<path-to-nextcloud>/index.php/apps/scimserviceprovider/<Resource> -H 'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyIjoiYWRtaW4ifQ.Oetm7xvhkYbiItRiqNx-z7LZ6ZkmDe1z_95igbPUSjA' -H 'Content-Type: application/scim+json'
```

## Use with Keycloak

You can use with the [SCIM plugin we developped for keycloak](https://lab.libreho.st/libre.sh/scim/keycloak-scim).

## Use with AzureAD

You can provision users from AzureAD to Nextcloud with this app. For this, you need to do the following:

- Enable Bearer token authentication via JWTs (see [Authentication](#authentication))
- Generate a JWT (see [JWT Generation](#jwt-generation-for-admins-only)) and provide it to AzureAD
- Finally, point AzureAD to `https://<path-to-nextcloud>/index.php/apps/scimserviceprovider/bearer`

## Running tests

To run the test, you can use [insomnia UI](https://docs.insomnia.rest).

![screenshot insomnia ui](./screenshots/insomnia.png)

For CI, there is still [a bug](https://github.com/Kong/insomnia/issues/4747) we need to find a fix.

## Todo

 - [ ] Meta -> ([can't implement yet](https://github.com/nextcloud/server/issues/22640))
    - createdAt
    - lastModified
 - [ ] ExternalID
    - [ ] Groups - [waiting for feedback](https://help.nextcloud.com/t/add-metadata-to-groups/139271)
 - [ ] json exceptions
 - [ ] group member removal
 - [ ] pagination
 - [ ] CI/CD
   - [ ] Lint cs:check
   - [ ] test psalm
   - [ ] test insomnia
   - [ ] publish app on app store
 - [ ] Allow for simultaneous usage of basic auth and bearer token auth (see **Authentication TODOs / Open issues**)

### Authentication TODOs / Open issues
#### Support for simultaneously using basic auth and bearer token auth in parallel
Solution idea:

- Instead of having two different sets of endpoints which are disjunct from each other for supporting both auth types, one could add an authentication middleware which intercepts requests and checks the `Authorization` header's contents
- Depending on whether the header has as first part of its value the string `Basic` or `Bearer`, the middleware can decide which authentication logic to call for performing the authentication with the provided authentication credentials
- In case of `Bearer`, the current implementation of bearer token authentication via JWTs can be used
- In case of `Basic`, one could take a closer look at how Nextcloud performs basic authentication for API endpoints and possibly make use of methods like [checkPassword](https://github.com/nextcloud/server/blob/master/lib/private/User/Manager.php#L237) from the [Manager](https://github.com/nextcloud/server/blob/master/lib/private/User/Manager.php) class for Nextcloud users

## Disclaimer
This app relies on the fixes, being introduced to Nextcloud in [PR #34172](https://github.com/nextcloud/server/pull/34172), since Nextcloud can't properly handle the `Content-Type` header value for SCIM (`application/scim+json`) otherwise. In the meantime until this PR is merged, SCIM clients interacting with this app might need to resort to using the standard value of `application/json` instead.

## NextGov Hackathon

This app was started during the [Nextgov hackathon](https://eventornado.com/submission/automatic-sso-saml-sync-from-identity-provider-keycloak-through-a-well-known-protocol-scim?s=1#idea)!
