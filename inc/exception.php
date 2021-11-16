<?php

class siteException extends Exception {

        function __construct($message, $code = 0, Exception $previous = null) {
                $htmlError = '<!DOCTYPE html>
                        <html lang="en">
                        <head>
                                <meta name="viewport" content="width=device-width, initial-scale=1">
                                <title>' . $this->title . '</title>
                                <link rel="stylesheet" type="text/css" href="style.css?' . mt_rand(5, 15). mt_rand(5, 15). mt_rand(5, 15). mt_rand(5, 15) . '" />
                        </head>
                        <body>
                        <div id="fatalError"><div id="fatalErrorInner"><span>Something\'s gone wrong!</span>' . $message . '</div></div>
                        </body>
                        </html>';

                parent::__construct($htmlError, $code, $previous);
        }
}
?>
