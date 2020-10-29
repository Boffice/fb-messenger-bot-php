<?php

namespace FbMessengerBot;

/**
 * Class Messenger
 * 
 */
class Messenger
{
    /**
     * Post request type
     */
    const TYPE_POST = 'POST';

    /**
     * Get request type
     */
    const TYPE_GET = 'GET';

    /**
     *
     * @var string
     */
    protected $url = 'https://graph.facebook.com/v2.11/';

    /**
     *
     * @var string
     */
    protected $accessToken;

    /**
     * @var string
     */
    protected $verifyToken;

    /**
     * @var Body
     */
    protected $body;

    /**
     * @var array
     */
    protected $services = [];

    /**
     * Messenger constructor
     *
     * @param string $accessToken
     * @param string $verifyToken
     */
    public function __construct($accessToken, $verifyToken)
    {
        $this->setAccessToken($accessToken);

        if (!empty($verifyToken)) {
            $this->setVerifyToken($verifyToken);
        }
    }

    /**
     * Set access token
     *
     * @param string $accessToken Access token
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * Get access token
     *
     * @return string Access token
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Set verify token
     *
     * @param string $verifyToken Verify token
     */
    public function setVerifyToken($verifyToken)
    {
        $this->verifyToken = $verifyToken;
    }

    /**
     * Get verify token
     *
     * @return string Verify token
     */
    public function getVerifyToken()
    {
        return $this->verifyToken;
    }

    /**
     * Set body
     *
     * @param Body $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * Get body
     *
     * @return Body
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Send message
     *
     * @param int $recipientId Recipient id
     * @param Message $message
     *
     * @return array
     */
    public function send($recipientId, Message $message)
    {
        $body = new Body;

        $body->setRecipient($recipientId);
        $body->setMessage($message);

        return $this->sendApi($body);
    }

    /**
     * Sender action
     *
     * @param int $recipientId Recipient id
     * @param string $type
     *
     * @return array
     */
    public function senderAction($recipientId, $type)
    {
        $body = new Body;

        $body->setRecipient($recipientId);
        $body->setSenderAction($type);

        return $this->sendApi($body);
    }

    /**
     * Send api
     *
     * @param Boyd $body
     *
     * @return array
     */
    public function sendApi(Body $body)
    {
        $this->setBody($body);

        $helper = new Helper;
        $body = $helper->objectToArray($body);

        return $this->api('me/messages', $body);
    }

    /**
     * Request to Facebook API
     *
     * @param string $url Url
     * @param array  $body Body
     * @param string $type Request type (POST)
     *
     * @return array
     */
    public function api($url, $body = null, $type = self::TYPE_POST)
    {
        $body['access_token'] = $this->accessToken;

        $this->setBody($body);

        $headers = [
            'Content-Type: application/json',
        ];

        if ($type == self::TYPE_GET) {
            $url .= '?'.http_build_query($body);
        }

        $curl = curl_init($this->url . $url);

        if($type == self::TYPE_POST) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($body));
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }

    /**
     * Listen message
     *
     * @return array
     */
    public function listen()
    {
        if (!empty($_REQUEST['hub_verify_token']) && $_REQUEST['hub_verify_token'] === $this->verifyToken) {
            echo $_REQUEST['hub_challenge'];
            exit;
        }

        return json_decode(file_get_contents('php://input'), true);
    }

    /**
     * Populate service
     *
     * @param string $name Service name
     *
     * @return object
     */
    public function __get($name)
    {
        if (!in_array($name, ['message'])) {
            $trace = debug_backtrace();

            throw new \Exception(sprintf('Undefined property via __get(): %s in %s on line %u', $name, $trace[0]['file'], $trace[0]['line']));
        }

        if (isset($this->services[$name])) {
            return $this->services[$name];
        }

        $serviceName = sprintf('%s\\%s', __NAMESPACE__, ucfirst($name));

        $this->services[$name] = new $serviceName($this);

        return $this->services[$name];
    }
}
