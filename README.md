# SCIM Service Provider

This app allows to provision users and groups in Nextcloud from a scim client.

You can see the [video](https://hot-objects.liiib.re/meet-liiib-re-recordings/pair_2022-05-02-15-40-37.mp4) that shows how it works.

## How to use

We plan to publish on the Nextcloud app store, but in the mean time, you can use instructions at the bottom.

## Use with Keycloak

You can use with the [SCIM plugin we developped for keycloak](https://lab.libreho.st/libre.sh/scim/keycloak-scim).

## Running tests

To run the test, you can use [insomnia UI](https://docs.insomnia.rest).

![screenshot insomnia ui](./screenshots/insomnia.png)

For CI, there is still [a bug](https://github.com/Kong/insomnia/issues/4747) we need to find a fix.

## Todo

 - [ ] Meta -> ([can't implement yet](https://github.com/nextcloud/server/issues/22640))
    - createdAt
    - lastModified
 - [ ] ExternalID
    - [ ] Users
    - [ ] Groups - [waiting for feedback](https://help.nextcloud.com/t/add-metadata-to-groups/139271)
 - [ ] if no emails in user, return nice array
 - [ ] json exceptions
 - [ ] group member removal
 - [ ] pagination
 - [ ] CI/CD
   - [ ] Lint cs:check
   - [ ] test psalm
   - [ ] test insomnia
   - [ ] publish app on app store


## Quick "Deploy" to test

```
cd apps
wget https://lab.libreho.st/libre.sh/scim/nextcloud-scim/-/archive/main/nextcloud-scim-main.zip
unzip nextcloud-scim-main.zip
rm nextcloud-scim-main.zip
rm -rf scimserviceprovider
mv nextcloud-scim-main scimserviceprovider
```

## NextGov Hackathon

This app was started during the [Nextgov hackathon](https://eventornado.com/submission/automatic-sso-saml-sync-from-identity-provider-keycloak-through-a-well-known-protocol-scim?s=1#idea)!
