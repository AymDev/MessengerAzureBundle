# UPGRADE FROM 1.x to 2.0

## Versions support

The supported versions have been upgraded according to the current supported versions at the time of the release.

 - The minimum **PHP** version supported has been upgraded from **7.3** to **8.1**.
 - **Symfony** versions supported are the supported LTS (**5.4** & **6.4**) and the current version **7**.

## AzureReceivedStamp removal

As documented, the `AzureReceivedStamp` was deprecated since **v1.1** and has been removed in **v2**. You should use
`AzureMessageStamp` instead.

## DSN parsing change

A breaking change has been introduced involuntarily in **v1.5** by changing the way the DSN is parsed, causing errors
for some non encoded characters in the keys.
The side effect was not desired but the behaviour won't change.

You are now encouraged to URL encode your keys before putting them in the DSN:

 - wrong: `azure://key/name:key+value@namespace`
 - correct: `azure://key%2Fname:key%2Bvalue@namespace`

>**Tip**: you can quickly URL encode a key from a single command: `php -r "echo urlencode('some/key');"`
