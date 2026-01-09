<?php

/**
 *
 * Copyright (C) 2023, Bett Ingenieure GmbH - All Rights Reserved
 *
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL Bett Ingenieure GmbH BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace BettIngenieure\File;

class File extends AbstractFileSystemEntry {

    public function getFilePath() : string {
        return $this->source;
    }

    public function read() : string {

        if(!file_exists($this->source)) {
            throw new \RuntimeException('Source file "' . $this->source . '" not found');
        }

        return file_get_contents($this->source);
    }

    public function write($data) : void {

        (new Directory(dirname($this->source)))->create(null, true);

        $handle = fopen($this->source, "w");
        fwrite($handle, $data);
        fclose($handle);

        $this->inheritOwnershipIfRoot();
    }

    public function append($data) : void {

        (new Directory(dirname($this->source)))->create(null, true);

        $handle = fopen($this->source, "a");
        fwrite($handle, $data);
        fclose($handle);

        $this->inheritOwnershipIfRoot();
    }

    /**
     * Returns the extension without dot, f.e. php
     *
     * @return string
     */
    public function getExtension() : string {

        $extension = pathinfo($this->source, PATHINFO_EXTENSION);
        if ($extension != "")
            return $extension;
        return "";
    }

    public function generateNewFilename() : string {
        return RandGenerator::getNewFilename($this->getParentDirectoryPath(), $this->getExtension());
    }

    public function generateNewFilePath() : string {
        return $this->getParentDirectoryPath() . DIRECTORY_SEPARATOR . $this->generateNewFilename();
    }

    public function getContentType() : string {

        if(!file_exists($this->source)) {
            throw new \RuntimeException('Source file "' . $this->source . '" not found');
        }

        return \mime_content_type($this->source);
    }

    public function sendToBrowser(bool $forceDownload = true, string $filename = null) {

        // Clean all output buffer
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if(!file_exists($this->source)) {
            throw new \RuntimeException('Source file "' . $this->source . '" not found');
        }

        if($filename === null) {
            $filename = basename($this->source);
        }

        $contentType = $this->getContentType();
        $fileSize = filesize($this->source);

        header('Content-Transfer-Encoding: binary');
        header('Content-Type: ' . ($contentType == '' ? 'application/octet-stream' : str_replace(array(';', ':', "\n"), '', $contentType)));
        if ($forceDownload === true)
            header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"; ' . "filename*=utf-8''" . rawurlencode($filename));
        if ($fileSize > 0)
            header('Content-Length: ' . sprintf('%d', $fileSize));

        $handle = fopen($this->source, 'rb');
        fpassthru($handle);
        fclose($handle);
    }

    public static function includeDirectory(string $dirname) {

        if (file_exists($dirname)) {
            if ($files = scandir($dirname)) {
                spl_autoload_register(function ($className) use ($dirname) {
                    if (file_exists($dirname . DIRECTORY_SEPARATOR . "class." . $className . ".php")) {
                        include($dirname . DIRECTORY_SEPARATOR . "class." . $className . ".php");
                    }
                    if (file_exists($dirname . DIRECTORY_SEPARATOR . "trait." . $className . ".php")) {
                        include($dirname . DIRECTORY_SEPARATOR . "trait." . $className . ".php");
                    }
                }, true, true);

                foreach ($files as $file) {
                    if (substr($file, -4) == ".php")
                        include_once($dirname . DIRECTORY_SEPARATOR . $file);
                }
            }
        }
    }

    public static function sendStringToBrowser(string $data, string $filename, bool $forceDownload = true,
                                               string $contentType = null
    ) {

        // Clean all output buffer
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Validate content type
        if(
            $contentType === null ||
            $contentType == '' ||
            preg_match('/\w+\/[-+.\w]+/', $contentType) !== 1
        ) {
            $contentType = 'application/octet-stream';
        }

        $contentDisposition = $forceDownload ? 'attachment' : 'inline';

        // Output
        header('Content-Transfer-Encoding: binary');
        header('Content-Type: ' . $contentType);
        header(
            'Content-Disposition: ' . $contentDisposition . '; ' .
            'filename="' . str_replace('"', '', $filename) . '"; ' .
            "filename*=utf-8''" . rawurlencode($filename)
        );
        header('Content-Length: ' . strlen($data));

        echo $data;
    }

    public static function getReadableSize(float $size, int $precision = 0) {

        $units = ['B', 'KiB','MiB','GiB','TiB','PiB','EiB','ZiB','YiB'];

        $threshold = 1024;

        $i = 0;

        while($size > $threshold) {

            $size = $size / $threshold;
            $i++;

            if($i == count($units) -1) { // End if there are no more defined units
                break;
            }
        }

        return round($size, $precision) . ' ' . ($units[$i] ?? '');
    }

    public static function getPHPCodeFromArray(string $variableName, array $array, bool $openingTag = true) : string {

        $result = '';

        if($openingTag) {
            $result .= '<' . '?php' . PHP_EOL . PHP_EOL;
        }

        $result .= '$' . $variableName . ' = [];' . PHP_EOL;

        foreach($array as $key => $value) {

            $newKey = is_numeric($key) ? $key : "'" . str_replace("'", "\'", $key)  .  "'";
            $newValue = is_numeric($value) ? $value : "'" . str_replace("'", "\'", $value)  .  "'";

            $result .= '$' . $variableName . '[' . $newKey . '] = ' . $newValue . ';' . PHP_EOL;
        }

        return $result;
    }

    public function replaceContentWithin(string $delimiterBeginLine, string $delimiterEndLine, string $newContent) : void {

        $content = '';
        if($this->exists()) {
            $content = $this->read();
        }

        $this->write(
            self::replaceContentWithinDelimiter($delimiterBeginLine, $delimiterEndLine, $content, $newContent),
        );
    }

    public static function replaceContentWithinDelimiter(string $delimiterBeginLine, string $delimiterEndLine, string $original, string $replacement) : string {

        $begin = $delimiterBeginLine . PHP_EOL;
        $end = $delimiterEndLine;

        $replacement = $begin . $replacement . PHP_EOL . $end;

        $discoveredPositions = [];

        $offset = 0;
        while(true) {

            if(
                ($startPos = mb_strpos($original, $begin, $offset)) !== false
                && ($endPos = mb_strpos($original, $end, $startPos + mb_strlen($begin))) !== false
            ) {
                $discoveredPositions[] = ['start' => $startPos, 'end' => $endPos + mb_strlen($end)];
                $offset = $endPos;
                continue;
            }

            break;
        }

        if(count($discoveredPositions) == 0) {
            // No previous delimiter where found, so we insert the replacement at the top
            $discoveredPositions[] = ['start' => 0, 'end' => 0];
        }

        $result = "";

        $offset = 0;
        foreach($discoveredPositions as $key =>  $discoveredPosition) {

            // We replace only the last occurrence and remove other discovered fragments

            $result .= mb_substr($original, $offset, $discoveredPosition['start'] - $offset);

            if(count($discoveredPositions) -1 == $key) {
                $result .= $replacement;
                $result .= mb_substr($original, $discoveredPosition['end']);
            }

            $offset = $discoveredPosition['end'];
        }

        return $result;
    }

}