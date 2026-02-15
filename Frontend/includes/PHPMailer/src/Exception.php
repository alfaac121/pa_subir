<?php
/**
 * PHPMailer Exception class - Simplified
 * PHP Version 5.5 and later
 */

namespace PHPMailer\PHPMailer;

class Exception extends \Exception
{
    /**
     * Prettify error message output
     *
     * @return string
     */
    public function errorMessage()
    {
        return '<strong>' . htmlspecialchars($this->getMessage()) . "</strong><br />\n";
    }
}
