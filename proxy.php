<?php
namespace  Proxy;

interface ProxyInterface
{
    /**
     * @return mixed
     */
    public function sendData();

}

class Proxy
{
    protected $data;
    protected $url;
    protected $headers;
    protected $authorizedDomains;

    public function __construct($url, $headers, $authorizedDomains, $data = null)
    {

        $this->url = $url;
        $this->data = $data;
        $this->headers = $headers;
        $this->authorizedDomains = $authorizedDomains;
    }

    /**
     * Wrapper for curlExecute function.
     *
     * @param array $curl_options
     * @return mixed|string
     * @throws Exception
     */
    public function proxyCurl($curl_options)
    {
        try {
               $this->validateSender();
               $result = $this->curlExecute($curl_options);
        } catch (Exception $e) {
               throw new Exception($e->getMessage());
        }

        return $result;
    }

    /**
     * Sends curl request based on the curl_options array
     *
     * @param array $curl_options
     * @return mixed|string
     */
    public function curlExecute($curl_options)
    {
        $response = '';
        if (!empty($this->url)) {
            $curl = curl_init($this->url);
            if (!empty($curl_options)) {
                //Loop through and build the curl request.
                foreach ($curl_options as $key => $value) {
                    curl_setopt($curl, $key, $value);
                }
            }

            $result = curl_exec($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            // If there is an error, get the response code.
            $response = ($httpcode > 206) ? $this->getResponseCode($httpcode) : $result;
        }

        return $response;
    }

    /**
     * Checks to make sure that the requests is coming from an authorized domain.
     *
     * @return bool
     * @throws Exception
     */
    public function validateSender()
    {
        $host = parse_url($_SERVER['HTTP_REFERER']);

        if (!in_array($host['host'], $this->authorizedDomains)) {
            throw new Exception('Unauthorized Domain');
        }

        return true;
    }

    /**
     * Checks a curl response code against a list of codes taken from wikipedia,org.
     *
     * @param string $code
     * @return string
     */
    public function getResponseCode($code)
    {

        $response_text = '';
        $http_status_codes = array(
            100 => 'Informational: Continue',
            101 => 'Informational: Switching Protocols',
            102 => 'Informational: Processing',
            200 => 'Successful: OK',
            201 => 'Successful: Created',
            202 => 'Successful: Accepted',
            203 => 'Successful: Non-Authoritative Information',
            204 => 'Successful: No Content',
            205 => 'Successful: Reset Content',
            206 => 'Successful: Partial Content',
            207 => 'Successful: Multi-Status',
            208 => 'Successful: Already Reported',
            226 => 'Successful: IM Used',
            300 => 'Redirection: Multiple Choices',
            301 => 'Redirection: Moved Permanently',
            302 => 'Redirection: Found',
            303 => 'Redirection: See Other',
            304 => 'Redirection: Not Modified',
            305 => 'Redirection: Use Proxy',
            306 => 'Redirection: Switch Proxy',
            307 => 'Redirection: Temporary Redirect',
            308 => 'Redirection: Permanent Redirect',
            400 => 'Client Error: Bad Request',
            401 => 'Client Error: Unauthorized',
            402 => 'Client Error: Payment Required',
            403 => 'Client Error: Forbidden',
            404 => 'Client Error: Not Found',
            405 => 'Client Error: Method Not Allowed',
            406 => 'Client Error: Not Acceptable',
            407 => 'Client Error: Proxy Authentication Required',
            408 => 'Client Error: Request Timeout',
            409 => 'Client Error: Conflict',
            410 => 'Client Error: Gone',
            411 => 'Client Error: Length Required',
            412 => 'Client Error: Precondition Failed',
            413 => 'Client Error: Request Entity Too Large',
            414 => 'Client Error: Request-URI Too Long',
            415 => 'Client Error: Unsupported Media Type',
            416 => 'Client Error: Requested Range Not Satisfiable',
            417 => 'Client Error: Expectation Failed',
            418 => 'Client Error: I\'m a teapot',
            419 => 'Client Error: Authentication Timeout',
            420 => 'Client Error: Enhance Your Calm',
            420 => 'Client Error: Method Failure',
            422 => 'Client Error: Unprocessable Entity',
            423 => 'Client Error: Locked',
            424 => 'Client Error: Failed Dependency',
            424 => 'Client Error: Method Failure',
            425 => 'Client Error: Unordered Collection',
            426 => 'Client Error: Upgrade Required',
            428 => 'Client Error: Precondition Required',
            429 => 'Client Error: Too Many Requests',
            431 => 'Client Error: Request Header Fields Too Large',
            444 => 'Client Error: No Response',
            449 => 'Client Error: Retry With',
            450 => 'Client Error: Blocked by Windows Parental Controls',
            451 => 'Client Error: Redirect',
            451 => 'Client Error: Unavailable For Legal Reasons',
            494 => 'Client Error: Request Header Too Large',
            495 => 'Client Error: Cert Error',
            496 => 'Client Error: No Cert',
            497 => 'Client Error: HTTP to HTTPS',
            499 => 'Client Error: Client Closed Request',
            500 => 'Server Error: Internal Server Error',
            501 => 'Server Error: Not Implemented',
            502 => 'Server Error: Bad Gateway',
            503 => 'Server Error: Service Unavailable',
            504 => 'Server Error: Gateway Timeout',
            505 => 'Server Error: HTTP Version Not Supported',
            506 => 'Server Error: Variant Also Negotiates',
            507 => 'Server Error: Insufficient Storage',
            508 => 'Server Error: Loop Detected',
            509 => 'Server Error: Bandwidth Limit Exceeded',
            510 => 'Server Error: Not Extended',
            511 => 'Server Error: Network Authentication Required',
            598 => 'Server Error: Network read timeout error',
            599 => 'Server Error: Network connect timeout error',
        );

        if (!empty($code)) {
            if (in_array($code, $http_status_codes)) {
                $response_text = $http_status_codes[$code];
            }
        }

         return $response_text;
    }
}

class PostProxy extends Proxy implements ProxyInterface
{
    /**
     * Prepares curl options for a POST request.
     *
     * @return mixed|string
     * @throws Exception
     */
    public function sendData()
    {
         $curl_options = array(
             CURLOPT_POSTFIELDS => $this->data,
             CURLOPT_HTTPHEADER => $this->headers,
             CURLOPT_RETURNTRANSFER => true,
             CURLOPT_VERBOSE => true,
         );

         $result = $this->proxyCurl($curl_options);

         return $result;
    }
}

class PutProxy extends Proxy implements ProxyInterface
{
    /**
     * Prepares curl options for a PUT request.
     *
     * @return mixed|string
     * @throws Exception
     */
    public function sendData()
    {
        $curl_options = array(
            CURLOPT_POSTFIELDS => $this->data,
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_VERBOSE => true,
        );

        $result = $this->proxyCurl($curl_options);

        return $result;
    }
}

class GetProxy extends Proxy implements ProxyInterface
{
    /**
     * Prepares curl options for a GET request.
     *
     * @return mixed|string
     * @throws Exception
     */
    public function sendData()
    {
        $curl_options = array(
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_VERBOSE => true,
        );

        $result = $this->proxyCurl($curl_options);

        return $result;
    }
}

class ProxyFactory
{
    /**
     * @param string $url
     * @param array $headers
     * @param array $authorizedDomains
     * @param array $data
     *
     * @return GetProxy|PostProxy|PutProxy
     */
    public static function create($url, $headers, $authorizedDomains, $data)
    {
        $method = $_SERVER['REQUEST_METHOD'];
        switch (strtolower($method)) {
            case 'post':
                $proxy = new PostProxy($url, $headers, $authorizedDomains, $data);
                break;

            case 'put':
                $proxy = new PutProxy($url, $headers, $authorizedDomains, $data);
                break;

            default:
                $proxy = new GetProxy($url, $headers, $authorizedDomains, $data);
        }

        return $proxy;
    }
}
