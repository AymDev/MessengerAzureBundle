# Messenger Azure Service Bus Bundle
A **Symfony 4 / 5 / 6** bundle providing a **Symfony Messenger** *transport* for **Azure Service Bus** using the *Azure REST API*.

![Testing](https://github.com/AymDev/MessengerAzureBundle/workflows/Testing/badge.svg)
![Coding Standards](https://github.com/AymDev/MessengerAzureBundle/workflows/Coding%20Standards/badge.svg)
![Bundle installation](https://github.com/AymDev/MessengerAzureBundle/workflows/Bundle%20installation/badge.svg)
[![Latest Stable Version](https://poser.pugx.org/aymdev/messenger-azure-bundle/v)](//packagist.org/packages/aymdev/messenger-azure-bundle)
[![License](https://poser.pugx.org/aymdev/messenger-azure-bundle/license)](//packagist.org/packages/aymdev/messenger-azure-bundle)

## Installation

You only need to install the bundle using **Composer**:
```shell
composer require aymdev/messenger-azure-bundle
```
As it uses [Symfony HttpClient](https://symfony.com/doc/current/http_client.html),
you will need to install a [PSR-18 client](https://symfony.com/doc/current/http_client.html#psr-18-and-psr-17).
Example:
```shell
composer require nyholm/psr7
```

## Configuration

### Transport DSN

Your DSN must respect the following format to build the authentication header for a specific *namespace*:
```
azure://KEY_NAME:KEY_VALUE@NAMESPACE
```
>Where `KEY_NAME` is your **shared access key name**, `KEY_VALUE` is your **shared access key** and `NAMESPACE` is your
>*Azure Service Bus* **namespace**.

### Transport options

Detailed list of transport options:

| Option name | Description | Required | Default value |  
| ------------- | ------------- | ----: | ----: |
| `entity_path`  | The **topic** or **queue** name.  | Yes | |
| `subscription`  | The subcription name to consume messages from a **topic**.  | Only for *topic consumer transports* | |
| `token_expiry`  | [SAS token](https://docs.microsoft.com/en-us/azure/service-bus-messaging/service-bus-sas#generate-a-shared-access-signature-token) validity duration in seconds.  | | `3600` |
| `receive_mode`  | Set to `peek-lock` to perform a [non destructive read](https://docs.microsoft.com/en-us/rest/api/servicebus/peek-lock-message-non-destructive-read) or to `receive-and-delete` to perform a [destructive-read](https://docs.microsoft.com/en-us/rest/api/servicebus/receive-and-delete-message-destructive-read)  | | `peek-lock` |

Example `config/packages/messenger.yaml`:
```yaml
framework:
    messenger:
        transports:
            azure_transport:
                dsn: '%env(AZURE_SERVICE_BUS_DSN)%'
                serializer: 'App\Messenger\YourAzureSerializer'
                options:
                    entity_path: 'your-topic'
                    subscription: 'subscription-name'
                    token_expiry: 60
                    receive_mode: 'receive-and-delete'
```

## Stamps

This transport provides a few stamps:

### AzureMessageStamp

The `AymDev\MessengerAzureBundle\Messenger\Stamp\AzureMessageStamp` stamp is added to sent and received messages and
contains:

 - the *topic* or *queue* name
 - the original sent/received message
 - the subscription name for received messages from *topics*
 - the delete URL for received messages in `peek-lock` receive mode 

### AzureBrokerPropertiesStamp

The `AymDev\MessengerAzureBundle\Messenger\Stamp\AzureBrokerPropertiesStamp` stamp is used for the [message properties](https://docs.microsoft.com/en-us/rest/api/servicebus/message-headers-and-properties).
It is automatically decoded when consuming a message and is encoded when producing a message if added to the *envelope*.

### AzureReceivedStamp

The `AymDev\MessengerAzureBundle\Messenger\Stamp\AzureReceivedStamp` stamp holds the original message body and an optional deletion URL.
>This stamp is deprecated in favor of the AzureMessageStamp and will be removed in v2.

## Serialization

There is no serializer provided, but here is the expected array structure of an encoded envelope:

 - `body`: your plain text message
 - `headers`: optional HTTP headers (either received from *Azure Service Bus* response or to send to the REST API)