<?php
// SmtpMailer: Simple SMTP client for sending emails via direct socket connection
class SmtpMailer {
    // SMTP server configuration
    private $smtp_host = '127.0.0.1'; // SMTP server address
    private $smtp_port = 1025;        // SMTP server port
    private $debug = true;            // Enable debug logging

    // Send an email using SMTP protocol
    public function send($to, $subject, $body, $from = 'no-reply@example.com') {
        try {
            if ($this->debug) {
                error_log("Attempting to send email to: $to");
            }

            // Open socket connection to SMTP server
            $socket = @fsockopen($this->smtp_host, $this->smtp_port, $errno, $errstr, 30);
            if (!$socket) {
                throw new Exception("Could not connect to SMTP host: $errstr ($errno)");
            }

            // Read initial server greeting (expect 220)
            $response = $this->readResponse($socket);
            if (!$this->isValidResponse($response, 220)) {
                throw new Exception("Invalid greeting from SMTP server: $response");
            }

            // Send HELO command (identify client)
            if (!$this->sendCommand($socket, "HELO localhost", 250)) {
                throw new Exception("HELO command failed");
            }

            // Specify sender email
            if (!$this->sendCommand($socket, "MAIL FROM:<{$from}>", 250)) {
                throw new Exception("MAIL FROM command failed");
            }

            // Specify recipient email
            if (!$this->sendCommand($socket, "RCPT TO:<{$to}>", 250)) {
                throw new Exception("RCPT TO command failed");
            }

            // Start email data section
            if (!$this->sendCommand($socket, "DATA", 354)) {
                throw new Exception("DATA command failed");
            }

            // Prepare email headers and body
            $email = "From: {$from}\r\n";
            $email .= "To: {$to}\r\n";
            $email .= "Subject: {$subject}\r\n";
            $email .= "Content-Type: text/html; charset=UTF-8\r\n";
            $email .= "MIME-Version: 1.0\r\n";
            $email .= "\r\n";
            $email .= $body;
            $email .= "\r\n.\r\n"; // End of data marker

            // Send email content to server
            fwrite($socket, $email);
            if (!$this->isValidResponse($this->readResponse($socket), 250)) {
                throw new Exception("Failed to send email content");
            }

            // Close SMTP session
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

    // Send a command to the SMTP server and check for expected response code
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

    // Read response from SMTP server (handles multi-line responses)
    private function readResponse($socket) {
        $response = '';
        while ($str = fgets($socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == ' ') break; // End of response
        }
        if ($this->debug) {
            error_log("SMTP <- $response");
        }
        return $response;
    }
    
    // Check if SMTP response code matches expected code
    private function isValidResponse($response, $expectedCode) {
        $code = intval(substr($response, 0, 3));
        return $code === $expectedCode;
    }
}
