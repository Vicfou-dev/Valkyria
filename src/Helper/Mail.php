<?php

namespace Valkyria\Helper;

class Mail {

    protected $_wrap = 78;

    protected $_to = array();

    protected $_subject;

    protected $_message;

    protected $_headers = array();

    protected $_params;

    protected $_attachments = array();

    protected $_uid;

    public function __construct(){
        $this->reset();
    }

    public function reset(){ 

        $this->_to = array();
        $this->_headers = array();
        $this->_subject = null;
        $this->_message = null;
        $this->_wrap = 78;
        $this->_params = null;
        $this->_attachments = array();
        $this->_uid = $this->getUniqueId();
        return $this;

    }

    public function setTo(string $email,$name = null){
        $this->_to[] = $this->formatHeader($email,$name);
        return $this;
    }

    public function getTo() {
        return $this->_to;
    }

    public function setFrom(string $email,$name = null) {
        $this->addMailHeader('From', $email, $name);
        return $this;
    }

    public function setCc(array $pairs){
        return $this->addMailHeaders('Cc', $pairs);
    }

    public function setBcc(array $pairs){
        return $this->addMailHeaders('Bcc', $pairs);
    }

    public function setReplyTo(string $email, $name = null){
        return $this->addMailHeader('Reply-To', $email, $name);
    }

    public function setHtml(){
        return $this->addGenericHeader(
            'Content-Type', 'text/html; charset="utf-8"'
        );
    }

    public function setSubject($subject){
        $this->_subject = $this->encodeUtf8(
            $this->filterOther((string) $subject)
        );
        return $this;
    }

    public function getSubject(){
        return $this->_subject;
    }
 
    public function setMessage(string $message){
        $this->_message = str_replace("\n.", "\n..", $message);
        return $this;
    }

    public function getMessage(){
        return $this->_message;
    }

    public function addAttachment(string $path, $filename = null, $data = null){
        $filename = empty($filename) ? basename($path) : $filename;
        $filename = $this->encodeUtf8($this->filterOther((string) $filename));
        $data = empty($data) ? $this->getAttachmentData($path) : $data;
        $this->_attachments[] = array(
            'path' => $path,
            'file' => $filename,
            'data' => chunk_split(base64_encode($data))
        );
        return $this;
    }

    public function getAttachmentData(string $path){
        $filesize = filesize($path);
        $handle = fopen($path, "r");
        $attachment = fread($handle, $filesize);
        fclose($handle);
        return $attachment;
    }

    public function addMailHeader(string $header, string $email,$name){
        $address = $this->formatHeader( $email,  $name);
        $this->_headers[] = sprintf('%s: %s', $header, $address);
        return $this;
    }

    public function addMailHeaders($header, array $pairs){

        if (count($pairs) === 0) {
            throw new \InvalidArgumentException(
                'You must pass at least one name => email pair.'
            );
        }

        $addresses = array();

        foreach ($pairs as $name => $email) {
            $name = is_numeric($name) ? null : $name;
            $addresses[] = $this->formatHeader($email, $name);
        }

        $this->addGenericHeader($header, implode(',', $addresses));
        return $this;
    }

    public function addGenericHeader($header, $value){
        $this->_headers[] = $header . ': ' . $value;
        return $this;
    }

    public function getHeaders(){
        return $this->_headers;
    }

    public function setParameters(string $additionalParameters){
        $this->_params = $additionalParameters;
        return $this;
    }

    public function getParameters(){
        return $this->_params;
    }

    public function setWrap($wrap = 78){
        $wrap = (int) $wrap;
        if ($wrap < 1) {
            $wrap = 78;
        }
        $this->_wrap = $wrap;
        return $this;
    }

    public function getWrap(){
        return $this->_wrap;
    }

    public function hasAttachments(){
        return !empty($this->_attachments);
    }

    public function assembleAttachmentHeaders(){
        $head = array();
        $head[] = "MIME-Version: 1.0";
        $head[] = "Content-Type: multipart/mixed; boundary=\"{$this->_uid}\"";
        return implode(PHP_EOL, $head);
    }

    public function assembleAttachmentBody(){

        $body = array();
        $body[] = "This is a multi-part message in MIME format.";
        $body[] = "--{$this->_uid}";
        $body[] = "Content-Type: text/html; charset=\"utf-8\"";
        $body[] = "Content-Transfer-Encoding: quoted-printable";
        $body[] = "";
        $body[] = quoted_printable_encode($this->_message);
        $body[] = "";
        $body[] = "--{$this->_uid}";
        foreach ($this->_attachments as $attachment) {
            $body[] = $this->getAttachmentMimeTemplate($attachment);
        }
        return implode(PHP_EOL, $body) . '--';
    }

    public function getAttachmentMimeTemplate($attachment){

        $file = $attachment['file'];
        $data = $attachment['data'];
        $head = array();
        $head[] = "Content-Type: application/octet-stream; name=\"{$file}\"";
        $head[] = "Content-Transfer-Encoding: base64";
        $head[] = "Content-Disposition: attachment; filename=\"{$file}\"";
        $head[] = "";
        $head[] = $data;
        $head[] = "";
        $head[] = "--{$this->_uid}";
        return implode(PHP_EOL, $head);
    }

    public function send(){

        $to = $this->getToForSend();
        $headers = $this->getHeadersForSend();

        if (empty($to)) {
            throw new \RuntimeException(
                'Unable to send, no To address has been set.'
            );
        }

        if ($this->hasAttachments()) {

            $message  = $this->assembleAttachmentBody();
            $headers .= PHP_EOL . $this->assembleAttachmentHeaders();

        } else {

            $message = $this->getWrapMessage();

        }
        return mail($to, $this->_subject, $message, $headers, $this->_params);
    }

    public function debug() {
        return '<pre>' . print_r($this, true) . '</pre>';
    }

    public function __toString() {
        return print_r($this, true);
    }

    public function formatHeader(string $email, $name = null)
    {
        $email = $this->filterEmail((string) $email);
        if (empty($name)) {
            return $email;
        }
        $name = $this->encodeUtf8($this->filterName((string) $name));
        return sprintf('"%s" <%s>', $name, $email);
    }

    public function encodeUtf8(string $value) {
        $value = trim($value);
        if (preg_match('/(\s)/', $value)) {
            return $this->encodeUtf8Words($value);
        }
        return $this->encodeUtf8Word($value);
    }

    public function encodeUtf8Word(string $value) {
        return sprintf('=?UTF-8?B?%s?=', base64_encode($value));
    }

    public function encodeUtf8Words(string $value) {
        $words = explode(' ', $value);
        $encoded = array();
        foreach ($words as $word) {
            $encoded[] = $this->encodeUtf8Word($word);
        }
        return join($this->encodeUtf8Word(' '), $encoded);
    }

    public function filterEmail(string $email){
        $rule = array(
            "\r" => '',
            "\n" => '',
            "\t" => '',
            '"'  => '',
            ','  => '',
            '<'  => '',
            '>'  => ''
        );
        $email = strtr($email, $rule);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return $email;
    }

    public function filterName(string $name){
        $rule = array(
            "\r" => '',
            "\n" => '',
            "\t" => '',
            '"'  => "'",
            '<'  => '[',
            '>'  => ']',
        );
        $filtered = filter_var(
            $name,
            FILTER_SANITIZE_STRING,
            FILTER_FLAG_NO_ENCODE_QUOTES
        );
        return trim(strtr($filtered, $rule));
    }

    public function filterOther(string $data){
        return filter_var($data, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
    }

    public function getHeadersForSend() {
        if (empty($this->_headers)) {
            return '';
        }
        return join(PHP_EOL, $this->_headers);
    }

    public function getToForSend(){
        if (empty($this->_to)) {
            return '';
        }
        return join(', ', $this->_to);
    }

    public function getUniqueId(){
        return md5(uniqid(time()));
    }

    public function getWrapMessage(){
        return wordwrap($this->_message, $this->_wrap);
    }
}