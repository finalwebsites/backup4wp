<?php

class MailerooClient {

    const API_ENDPOINT = 'https://smtp.maileroo.com/';

    private $api_key;
    private $data = [];
    private $attachments = [];
    private $inline_attachments = [];

    public function __construct($api_key) {

        $this->api_key = $api_key;

        $this->reset();

    }

    public function reset() {

        $this->data['from'] = '';
        $this->data['to'] = '';
        $this->data['cc'] = '';
        $this->data['bcc'] = '';
        $this->data['reply_to'] = '';
        $this->data['subject'] = '';
        $this->data['html'] = '';
        $this->data['plain'] = '';
        $this->data['tracking'] = 'yes';
        $this->data['reference_id'] = '';
        $this->data['tags'] = '';
        $this->data['template_id'] = '';
        $this->data['template_data'] = '';

        $this->attachments = [];
        $this->inline_attachments = [];

    }

    public function setFrom($name, $address) {
        $this->data['from'] .= $name . ' <' . $address . '>,';
        return $this;
    }

    public function setTo($name, $address) {
        $this->data['to'] .= $name . ' <' . $address . '>,';
        return $this;
    }

    public function setCc($name, $address) {
        $this->data['cc'] .= $name . ' <' . $address . '>,';
        return $this;
    }

    public function setBcc($name, $address) {
        $this->data['bcc'] .= $name . ' <' . $address . '>,';
        return $this;
    }

    public function setReplyTo($name, $address) {
        $this->data['reply_to'] .= $name . ' <' . $address . '>,';
        return $this;
    }

    public function setSubject($subject) {
        $this->data['subject'] = $subject;
        return $this;
    }

    public function setHtml($html) {
        $this->data['html'] = $html;
        return $this;
    }

    public function setPlain($plain) {
        $this->data['plain'] = $plain;
        return $this;
    }

    public function addAttachment($file_path, $file_name, $file_type) {

        if (file_exists($file_path)) {
            $this->attachments[] = new CURLFile($file_path, $file_type, $file_name);
        }

        return $this;

    }

    public function addInlineAttachment($file_path, $file_name, $file_type) {

        if (file_exists($file_path)) {
            $this->inline_attachments[] = new CURLFile($file_path, $file_type, $file_name);
        }

        return $this;

    }

    public function setReferenceId($reference_id) {
        $this->data['reference_id'] = $reference_id;
        return $this;
    }

    public function setTags($tags) {
        $this->data['tags'] = json_encode($tags);
        return $this;
    }

    public function setTracking($tracking) {
        $this->data['tracking'] = ($tracking ? 'yes' : 'no');
        return $this;
    }

    public function setTemplateId($template_id) {
        $this->data['template_id'] = $template_id;
        return $this;
    }

    public function setTemplateData($template_data) {
        $this->data['template_data'] = json_encode($template_data);
        return $this;
    }

    private function sendRequest($endpoint, $method) {

        $url = self::API_ENDPOINT . $endpoint;

        $headers = [
            'X-API-Key: ' . $this->api_key,
            'Content-Type: multipart/form-data'
        ];

        $post_fields = $this->data;

        foreach ($this->attachments as $key => $attachment) {
            $post_fields['attachments[' . $key . ']'] = $attachment;
        }

        foreach ($this->inline_attachments as $key => $inline_attachment) {
            $post_fields['inline_attachments[' . $key . ']'] = $inline_attachment;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);

    }

    private function removeTrailingCommas() {

        $keys = ['from', 'to', 'cc', 'bcc', 'reply_to'];

        foreach ($keys as $key) {

            if (isset($this->data[$key])) {
                $this->data[$key] = rtrim($this->data[$key], ',');
            }

        }

    }

    public function sendBasicEmail() {

        $this->removeTrailingCommas();

        return $this->sendRequest('/send', 'POST');

    }

    public function sendTemplateEmail() {

        $this->removeTrailingCommas();

        return $this->sendRequest('/send-template', 'POST');

    }

    public function generateReferenceId() {

        try {

            return bin2hex(random_bytes(12));

        } catch (Exception $e) {

            return openssl_random_pseudo_bytes(12);

        }

    }

}