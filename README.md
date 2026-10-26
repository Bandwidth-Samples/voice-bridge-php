# Voice Bridge

<a href="https://dev.bandwidth.com/docs/voice/programmable-voice/quickStart/">
  <img src="./icon-voice.svg" title="Voice Quick Start Guide" alt="Voice Quick Start Guide"/>
</a>

 # Table of Contents

<!-- TOC -->

- [Description](#description)
- [Bandwidth](#bandwidth)
- [Environmental Variables](#environmental-variables)
- [Running the Application](#running-the-application)
- [Callback URLs](#callback-urls)
    - [Ngrok](#ngrok)

<!-- /TOC -->

# Description
This sample app masks a phone call through a Bandwidth phone number using Bandwidth's Bridge BXML verb. Once configured, anyone who calls your Bandwidth phone number will be bridged to the masked number without either party members knowing the other's phone number.

In the Bandwidth Dashboard, set the Application's `Call initiated callback URL` to `<BASE_CALLBACK_URL>/callbacks/inbound/voice`. This can also be done via the Dashboard API by setting `CallInitiatedCallbackUrl`. Once configured, any calls made to a number associated with that application will cause this app to create a new call to the `USER_NUMBER`. This call will come from your `BW_NUMBER` and the original call will be bridged to this one once accepted.

Once the callee at `USER_NUMBER` confirms the call, the call flow will appear as such:

`Random Number <-> BW_NUMBER <-> USER_NUMBER` - if the `BW_NUMBER` environment variable is the number called.

`Random Number <-> Other BW_NUMBER <-> BW_NUMBER <-> USER_NUMBER` - if the `BW_NUMBER` environment variable is different from the number called.

# Bandwidth

In order to use the Bandwidth API users need to set up the appropriate application at the [Bandwidth Dashboard](https://dashboard.bandwidth.com/) and create API credentials.

To create an application log into the [Bandwidth Dashboard](https://dashboard.bandwidth.com/) and navigate to the `Applications` tab.  Fill out the **New Application** form selecting the service (Messaging or Voice) that the application will be used for.  All Bandwidth services require publicly accessible Callback URLs, for more information on how to set one up see [Callback URLs](#callback-urls).

For more information about API credentials see [here](https://dev.bandwidth.com/guides/accountCredentials.html#top)

# Environmental Variables
The sample app uses the below environmental variables.
```sh
BW_ACCOUNT_ID                 # Your Bandwidth Account Id
BW_CLIENT_ID                  # Your Bandwidth API OAuth Client Id
BW_CLIENT_SECRET              # Your Bandwidth API OAuth Client Secret
BW_NUMBER                     # The Bandwidth phone number involved with this application
USER_NUMBER                   # The number dialed out to when an inbound call is received on your Bandwidth number
BW_VOICE_APPLICATION_ID       # Your Voice Application Id created in the dashboard
BASE_CALLBACK_URL             # Your public base url to receive Bandwidth Webhooks. No trailing '/'
LOCAL_PORT                    # The port number you wish to run the sample on
```

# Running the Application

To install the required packages for this app, run the command:

```sh
composer install
```

Use the following command to run the application:

```sh
composer start
```

# Callback URLs

For a detailed introduction to Bandwidth Callbacks see https://dev.bandwidth.com/guides/callbacks/callbacks.html

Below are the callback paths:
* `/callbacks/outbound/voice`
* `/callbacks/inbound/voice`

## Ngrok

A simple way to set up a local callback URL for testing is to use the free tool [ngrok](https://ngrok.com/).  
After you have downloaded and installed `ngrok` run the following command to open a public tunnel to your port (`$LOCAL_PORT`)
```cmd
ngrok http $LOCAL_PORT
```
You can view your public URL at `http://127.0.0.1:4040` after ngrok is running.  You can also view the status of the tunnel and requests/responses here.
