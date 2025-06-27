<?php
class SmtpMailer {
    private $smtp_host = '127.0.0.1';
    private $smtp_port = 1025;
    private $debug = true;

    public function send($to, $subject, $body, $from = 'no-reply@example.com') {
        try {
            if ($this->debug) {
                error_log("Attempting to send email to: $to");
            }

            $socket = @fsockopen($this->smtp_host, $this->smtp_port, $errno, $errstr, 30);
            if (!$socket) {
                throw new Exception("Could not connect to SMTP host: $errstr ($errno)");
            }

            // Read greeting
            $response = $this->readResponse($socket);
            if (!$this->isValidResponse($response, 220)) {
                throw new Exception("Invalid greeting from SMTP server: $response");
            }

            // Send HELO
            if (!$this->sendCommand($socket, "HELO localhost", 250)) {
                throw new Exception("HELO command failed");
            }

            // Send MAIL FROM
            if (!$this->sendCommand($socket, "MAIL FROM:<{$from}>", 250)) {
                throw new Exception("MAIL FROM command failed");
            }

            // Send RCPT TO
            if (!$this->sendCommand($socket, "RCPT TO:<{$to}>", 250)) {
                throw new Exception("RCPT TO command failed");
            }

            // Send DATA
            if (!$this->sendCommand($socket, "DATA", 354)) {
                throw new Exception("DATA command failed");
            }

            // Prepare email content
            $email = "From: {$from}\r\n";
            $email .= "To: {$to}\r\n";
            $email .= "Subject: {$subject}\r\n";
            $email .= "Content-Type: text/html; charset=UTF-8\r\n";
            $email .= "MIME-Version: 1.0\r\n";
            $email .= "\r\n";
            $email .= $body;
            $email .= "\r\n.\r\n";

            // Send email content
            fwrite($socket, $email);
            if (!$this->isValidResponse($this->readResponse($socket), 250)) {
                throw new Exception("Failed to send email content");
            }

            // Send QUIT
            $this->sendCommand($socket, "QUIT", 221);

            fclose($socket);
            
            if ($this->debug) {
                error_log("Successfully sent email to: $to");
            }
            
            return true;
        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            if (isset($socket) && is_resource($socket)) {
                fclose($socket);
            }
            return false;
        }
    }

    private function sendCommand($socket, $command, $expectedCode = null) {
        if ($this->debug) {
            error_log("SMTP -> $command");
        }
        
        fwrite($socket, $command . "\r\n");
        $response = $this->readResponse($socket);
        
        if ($expectedCode !== null) {
            return $this->isValidResponse($response, $expectedCode);
        }
        
        return true;
    }

    private function readResponse($socket) {
        $response = '';
        while ($str = fgets($socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == ' ') break;
        }
        if ($this->debug) {
            error_log("SMTP <- $response");
        }
        return $response;
    }
    
    private function isValidResponse($response, $expectedCode) {
        $code = intval(substr($response, 0, 3));
        return $code === $expectedCode;
    }
}
