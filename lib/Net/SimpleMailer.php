<?php
namespace Net;

use Finfo;

/**
 * SimpleMailer
 *
 * Simple Mail library in PHP.
 * This is PHP mail function wrapper.
 */
class SimpleMailer {
    private $from,
            $to,
            $subject,
            $message,
            $files;

    private $finfo;
    private $separator;

    public function __construct($separator = "\r\n") {
        $this->to = array();
        $this->files = array();
        $this->message = '';
        $this->subject = '';
        $this->finfo = new Finfo(FILEINFO_MIME_TYPE);
        $this->separator = $separator;
    }

    /**
     * Set receiver or receivers of the mail.
     */
    public function to($to) {
        $this->to[] = $to;
        return $this;
    }

    /**
     * Set sender of the mail.
     */
    public function from($from) {
        $this->from = $from;
        return $this;
    }

    /**
     * Set subject of the email.
     */
    public function subject($subject) {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Set message to be sent.
     *
     * @param string $message message
     */
    public function message($message) {
        $this->message = $message;
        return $this;
    }

    /**
     * Attach file.
     *
     * @param string $file file-path
     */
    public function attachment($file) {
        $this->files[] = $file;
        return $this;
    }

    /**
     * Convert SimpleMailer to array.
     *
     * @return array array expression. [0] => $to, [1] => $subject, [2] => $body, [3] => $headers, [4] => $additional
     */
    public function toArray() {
        $boundary = '=_' . md5(uniqid('', true));

        $to = implode(',', $this->to);
        $from = $this->from;
        $subject = $this->subject;
        $message = $this->message;

        if (empty($from)) {
            throw new \InvalidArgumentException('`from` is not set. You must set `from`');
        }

        if (empty($to)) {
            throw new \InvalidArgumentException('`to` is not set. You must set one `to` at least');
        }

        $internal_encoding = mb_internal_encoding();
        mb_internal_encoding('UTF-8');
        $subject = mb_encode_mimeheader($this->subject, mb_internal_encoding(), 'B', $this->separator);
        mb_internal_encoding($internal_encoding);

        $headers = array(
            'MIME-Version' => '1.0',
            'Content-Type' => 'multipart/mixed;' . $this->separator . " boundary=\"{$boundary}\"",
            'From'   => $from,
            'Sender' => $from,
        );

        $message_body = '';
        if ($message !== '') {
            $message_headers = array(
                'Content-Transfer-Encoding' => 'quoted-printable',
                'Content-Type'              => 'text/plain; charset=UTF-8',
            );
            $encoded_message = chunk_split(quoted_printable_encode($message), 76, $this->separator);
            $message_body = $this->buildPart($message_headers, $boundary, $encoded_message);
        }

        $attachments_body = '';
        foreach ($this->files as $file) {
            if (!is_file($file)) {
                throw new \RuntimeException("File `$file` does not exists.");
            }

            $filename = basename($file);
            $file_contents = file_get_contents($file);
            $encoded_attachment = chunk_split(base64_encode($file_contents), 76, $this->separator);

            $attachments_headers = array(
                'Content-Transfer-Encoding' => 'base64',
                'Content-Type'              => "application/octet-stream;" . $this->separator . " name={$filename}",
                'Content-Disposition'       => "attachment;" . $this->separator ." filename={$filename};" . $this->separator . " size=" . strlen($file_contents),
            );
            $attachments_body .= $this->buildPart($attachments_headers, $boundary, $encoded_attachment);
        }

        $body = $message_body . $this->separator . $attachments_body . "--{$boundary}--";
        $headers = $this->buildHeaders($headers);

        return array($to, $subject, $body, $headers, '-f'.escapeshellcmd($from));
    }

    /**
     * Send email using PHP's mail function.
     */
    public function send() {
        list($to, $subject, $body, $headers, $additional) = $this->toArray();
        mail($to, $subject, $body, $headers, $additional);
    }

    private function buildHeaders(array $headers) {
        $lines = array();
        foreach ($headers as $key => $value) {
            $lines[] = "{$key}: {$value}";
        }

        return implode($this->separator, $lines);
    }

    private function buildPart(array $headers, $boundary, $encoded_body) {
        return "--{$boundary}" . $this->separator . $this->buildHeaders($headers) . $this->separator . $this->separator . $encoded_body;
    }

    private function getFileMimeType($filepath) {
        return $this->finfo->file($finfo, $filename);
    }

    public function __destruct() {
        $this->finfo = null;
    }
}
