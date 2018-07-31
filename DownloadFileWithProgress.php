<?php

/**
 * Class DownloadFileWithProgress - the implementation is taken out of context
 */
class DownloadFileWithProgress
{
    protected function downloadRemoteFile($fileURL, $fileName)
    {
        $result = false;

        if (strpos($fileURL, "http://") !== false) {
            $request_type = "http://";
        } elseif (strpos($fileURL, "https://") !== false) {
            $request_type = "https://";
        }

        $fileURL = str_replace($request_type, '', $fileURL);
        $parts = explode('@', $fileURL);
        $url = $request_type . $parts[1];

        $fp = fopen($fileName, 'w');

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOPROGRESS => false,
            CURLOPT_PROGRESSFUNCTION => [$this, 'downloadingProgress'],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERPWD => $parts[0],
            CURLOPT_FILE => $fp,
            CURLOPT_BUFFERSIZE => 128000
        ];

        $ch = curl_init();

        curl_setopt_array($ch, $options);

        $result = curl_exec($ch);

        curl_close($ch);

        fclose($fp);

        if (!$result) {
            return false;
        }

        touch($fileName);
        return $result;
    }

    /**
     * @param $resource - descriptor
     * @param $size - file size in bytes
     * @param $downloaded - already downloaded (in bytes)
     * @param $uploadSize
     * @param $uploaded
     */
    private function downloadingProgress($resource, $size, $downloaded, $uploadSize, $uploaded)
    {
        if ($size > 0) {
            $messagesTmpFile = DIR_FS_WORK . 'import_messages_tmp';

            if (!file_exists($messagesTmpFile)) {
                fclose(fopen($messagesTmpFile, 'w'));
            }

            $messages = unserialize(file_get_contents($messagesTmpFile));
            array_pop($messages);

            $messages[] = [
                'type' => 'success',
                'code' => 'message_start_download_file',
                'message' => 'Downloading file. ' . round(($size / 1024 / 1024),
                        1) . ' MB / ' . round(($downloaded / 1024 / 1024), 1) . ' MB'
            ];

            file_put_contents($messagesTmpFile, serialize($messages));
        }
    }
}