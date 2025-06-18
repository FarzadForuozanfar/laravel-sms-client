<?php

namespace Asanak\Sms;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\{GuzzleException, RequestException, ConnectException};

class SmsClient
{
    private string $username;
    private string $password;
    private string $baseUrl;
    private Client $client;
    private bool $logger;

    public function __construct(string $username, string $password, string $baseUrl, bool $logger = false)
    {
        $this->username = $username;
        $this->password = $password;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->client = new Client();
        $this->logger = $logger;
    }

    private function sendRequest(string $endpoint, array $payload): array
    {
        $url = "{$this->baseUrl}{$endpoint}";

        $payload = array_merge([
            'username' => $this->username,
            'password' => $this->password,
        ], $payload);

        try {
            $response = $this->client->post($url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);

            $body = $response->getBody()->getContents();

            if ($this->logger) {
                Log::info("SMS API response", [
                    'endpoint' => $endpoint,
                    'body' => $body
                ]);
            }

            return json_decode($body, true);

        } catch (RequestException $e) {
            $responseBody = $e->hasResponse()
                ? json_decode($e->getResponse()->getBody()->getContents(), true)
                : null;

            if ($this->logger) {
                Log::error("HTTP RequestException", [
                    'endpoint' => $endpoint,
                    'error' => $e->getMessage(),
                    'http-code' => $e->getCode(),
                    'response' => $responseBody
                ]);
            }

            if ($responseBody) {
                return $responseBody;
            }
            throw new \RuntimeException("HTTP error: " . $e->getMessage(), $e->getCode(), $e);

        } catch (ConnectException $e) {
            if ($this->logger) {
                Log::critical("Connection error", [
                    'endpoint' => $endpoint,
                    'error' => $e->getMessage()
                ]);
            }

            throw new \RuntimeException("Connection failed: " . $e->getMessage(), $e->getCode(), $e);

        } catch (GuzzleException $e) {
            if ($this->logger) {
                Log::error("Unhandled GuzzleException", [
                    'endpoint' => $endpoint,
                    'error' => $e->getMessage()
                ]);
            }

            throw new \RuntimeException("Guzzle error: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function sendSms(string $source, string $destination, string $message, int $send_to_black_list = 1): array
    {
        return $this->sendRequest('/webservice/v2rest/sendsms', [
            'source' => $source,
            'message' => $message,
            'destination' => $destination,
            'send_to_black_list' => $send_to_black_list
        ]);
    }

    public function p2p(array $source, array $destination, array $message, array $send_to_black_list): array
    {
        $data = [];

        foreach ($source as $i => $src) {
            $data[] = [
                'source' => $src,
                'destination' => $destination[$i] ?? '',
                'message' => $message[$i] ?? '',
                'send_to_black_list' => $send_to_black_list[$i] ?? false
            ];
        }

        return $this->sendRequest('/webservice/v2rest/p2psendsms', [
            'data' => $data
        ]);
    }

    public function template(int $template_id, array $parameters, string $destination, int $send_to_black_list = 1): array
    {
        return $this->sendRequest('/webservice/v2rest/template', [
            'template_id' => $template_id,
            'parameters' => $parameters,
            'destination' => $destination,
            'send_to_black_list' => $send_to_black_list
        ]);
    }

    public function msgStatus(string|array $msg_ids): array
    {
        return $this->sendRequest('/webservice/v2rest/msgstatus', [
            'msgid' => is_array($msg_ids) ? implode(',', $msg_ids) : $msg_ids
        ]);
    }

    public function getCredit(): array
    {
        return $this->sendRequest('/webservice/v2rest/getcredit', []);
    }

    public function getRialCredit(): array
    {
        return $this->sendRequest('/webservice/v2rest/getcredit', []);
    }

    public function getTemplates(): array
    {
        return $this->sendRequest('/webservice/v2rest/templatelist', []);
    }
}
