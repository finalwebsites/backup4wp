<?php

namespace Maileroo;

use CURLFile;
use Exception;

class MailerooClient {

    const EMAIL_API_ENDPOINT = 'https://smtp.maileroo.com/';
    const CONTACTS_API_ENDPOINT = 'https://manage.maileroo.app/';

    private $api_key;
    private $email_data = [];
    private $email_attachments = [];
    private $email_inline_attachments = [];

    public function __construct($api_key) {
        $this->api_key = $api_key;
        $this->resetEmailData();
    }

    private function resetEmailData() {

        $this->email_data['from'] = '';
        $this->email_data['to'] = '';
        $this->email_data['cc'] = '';
        $this->email_data['bcc'] = '';
        $this->email_data['reply_to'] = '';
        $this->email_data['subject'] = '';
        $this->email_data['html'] = '';
        $this->email_data['plain'] = '';
        $this->email_data['tracking'] = 'yes';
        $this->email_data['reference_id'] = '';
        $this->email_data['tags'] = '';
        $this->email_data['template_id'] = '';
        $this->email_data['template_data'] = '';

        $this->email_attachments = [];
        $this->email_inline_attachments = [];

    }

    public function setFrom($name, $address) {
        $this->email_data['from'] .= $name . ' <' . $address . '>,';
        return $this;
    }

    public function setTo($name, $address) {
        $this->email_data['to'] .= $name . ' <' . $address . '>,';
        return $this;
    }

    public function setCc($name, $address) {
        $this->email_data['cc'] .= $name . ' <' . $address . '>,';
        return $this;
    }

    public function setBcc($name, $address) {
        $this->email_data['bcc'] .= $name . ' <' . $address . '>,';
        return $this;
    }

    public function setReplyTo($name, $address) {
        $this->email_data['reply_to'] .= $name . ' <' . $address . '>,';
        return $this;
    }

    public function setSubject($subject) {
        $this->email_data['subject'] = $subject;
        return $this;
    }

    public function setHtml($html) {
        $this->email_data['html'] = $html;
        return $this;
    }

    public function setPlain($plain) {
        $this->email_data['plain'] = $plain;
        return $this;
    }

    public function addAttachment($file_path, $file_name, $file_type) {

        if (file_exists($file_path)) {
            $this->email_attachments[] = new CURLFile($file_path, $file_type, $file_name);
        }

        return $this;

    }

    public function addInlineAttachment($file_path, $file_name, $file_type) {

        if (file_exists($file_path)) {
            $this->email_inline_attachments[] = new CURLFile($file_path, $file_type, $file_name);
        }

        return $this;

    }

    public function setReferenceId($reference_id) {
        $this->email_data['reference_id'] = $reference_id;
        return $this;
    }

    public function setTags($tags) {
        $this->email_data['tags'] = json_encode($tags);
        return $this;
    }

    public function setTracking($tracking) {
        $this->email_data['tracking'] = ($tracking ? 'yes' : 'no');
        return $this;
    }

    public function setTemplateId($template_id) {
        $this->email_data['template_id'] = $template_id;
        return $this;
    }

    public function setTemplateData($template_data) {
        $this->email_data['template_data'] = json_encode($template_data);
        return $this;
    }

    private function sendEmailRequest($endpoint, $method = 'POST') {

        $url = self::EMAIL_API_ENDPOINT . $endpoint;

        $headers = [
            'X-API-Key: ' . $this->api_key,
            'Content-Type: multipart/form-data'
        ];

        $post_fields = $this->email_data;

        foreach ($this->email_attachments as $key => $attachment) {
            $post_fields['attachments[' . $key . ']'] = $attachment;
        }

        foreach ($this->email_inline_attachments as $key => $inline_attachment) {
            $post_fields['inline_attachments[' . $key . ']'] = $inline_attachment;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {

            return [
                'success' => false,
                'message' => $error
            ];

        }

        return json_decode($response, true);

    }

    private function sendCustomRequest($url, $method = 'GET', $data = [], $send_json = true, $parse_json = false) {

        $headers = [
            'X-API-Key: ' . $this->api_key
        ];

        if ($send_json) {
            $headers[] = 'Content-Type: application/json';
        } else {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {

            if ($send_json) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }

        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {

            return [
                'success' => false,
                'message' => $error
            ];

        }

        if ($parse_json) {
            return json_decode($response, true);
        }

        return $response;

    }

    private function removeTrailingCommas() {

        $keys = ['from', 'to', 'cc', 'bcc', 'reply_to'];

        foreach ($keys as $key) {

            if (isset($this->email_data[$key])) {
                $this->email_data[$key] = rtrim($this->email_data[$key], ',');
            }

        }

    }

    public function sendBasicEmail() {

        $this->removeTrailingCommas();

        $response = $this->sendEmailRequest('/send', 'POST');

        $this->resetEmailData();

        if ($response['success'] === true) {
            return true;
        }

        throw new Exception($response['message']);

    }

    public function sendTemplateEmail() {

        $this->removeTrailingCommas();

        $response = $this->sendEmailRequest('/send-template', 'POST');

        $this->resetEmailData();

        if ($response['success'] === true) {
            return true;
        }

        throw new Exception($response['message']);

    }

    public function generateReferenceId() {

        try {

            return bin2hex(random_bytes(12));

        } catch (Exception $e) {

            return openssl_random_pseudo_bytes(12);

        }

    }

    public function createContact($list_id, $contact) {

        $url = self::CONTACTS_API_ENDPOINT . "v1/contact/{$list_id}";

        $response = $this->sendCustomRequest($url, 'PUT', $contact, true, true);

        if ($response['success'] === true) {
            return true;
        }

        throw new Exception($response['message']);

    }

    public function updateContact($list_id, $email_address, $contact) {

        $url = self::CONTACTS_API_ENDPOINT . "v1/contact/{$list_id}/{$email_address}";

        $response = $this->sendCustomRequest($url, 'PATCH', $contact, true, true);

        if ($response['success'] === true) {
            return true;
        }

        throw new Exception($response['message']);

    }

    public function deleteContact($list_id, $email_address) {

        $url = self::CONTACTS_API_ENDPOINT . "v1/contact/{$list_id}/{$email_address}";

        $response = $this->sendCustomRequest($url, 'DELETE', [], true, true);

        if ($response['success'] === true) {
            return true;
        }

        throw new Exception($response['message']);

    }

    public function getContact($list_id, $email_address) {

        $url = self::CONTACTS_API_ENDPOINT . "v1/contact/{$list_id}/{$email_address}";

        $response = $this->sendCustomRequest($url, 'GET', [], false, true);

        if ($response['success'] === true) {
            return $response['contact'];
        }

        throw new Exception($response['message']);

    }

    public function listContacts($list_id, $query = '', $page = 1) {

        $url = self::CONTACTS_API_ENDPOINT . "v1/contacts/{$list_id}?query={$query}&page={$page}";

        $response = $this->sendCustomRequest($url, 'GET', [], false, true);

        if ($response['success'] === true) {
            return $response['data'];
        }

        throw new Exception($response['message']);

    }

}