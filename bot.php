#!/usr/bin/env php
<?php

require 'vendor/autoload.php';

require_once 'Constants.php';
require_once 'BotManager.php';
require_once 'Utils.php';
require_once 'MediaMessageHandler.php';
require_once 'DownloadMessageHandler.php';
require_once 'DropboxUploadMessageHandler.php';
require_once 'TelegramUploadMessageHandler.php';

if (!file_exists(TMP_DOWNLOADS))
    mkdir(TMP_DOWNLOADS);
$MadelineProto = makeLogin();
$offset = 0;
$conversations = array();
while (true) {
    $updates = $MadelineProto->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]);
    foreach ($updates as $update) {
        $offset = $update['update_id'] + 1;
        switch ($update['update']['_']) {
            case 'updateNewMessage':
            case 'updateNewChannelMessage':
                if (isset($update['update']['message']['out']) && $update['update']['message']['out']) {
                    continue;
                }
                try {
                    $destination = retrieveDestination($update);
                    if (isMediaIncoming($update)) {
                        handleMediaMessage($update, $conversations);
                    } else if (isTextMessage($update)) {
                        $message = retrieveFromMessage($update, 'message');
                        if (isDownloadableFile($message)) {
                            handleDownloadMessage($update);
                        } else if ($message == '/dropbox') {
                            handleDropboxMessage($update, $conversations);
                        } else if ($message == '/telegram') {
                            handleTelegramMessage($update, $conversations);
                        }
                    }
                } catch (RPCErrorException $e) {
                    sendMessage('@ilteoood', $e->getCode() . ': ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString(), null);
                }
        }
    }
}
