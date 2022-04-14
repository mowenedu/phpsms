<?php

namespace Toplan\PhpSms;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

/**
 * Class AliyunAgent
 *
 * @property string $accessKeyId
 * @property string $accessKeySecret
 * @property string $signName
 * @property string $regionId
 */
class AliyunAgent extends Agent implements TemplateSms
{
    protected static $sendUrl = 'https://dysmsapi.aliyuncs.com/';


    public function sendTemplateSms($to, $tempId, array $data)
    {
        AlibabaCloud::accessKeyClient($this->accessKeyId, $this->accessKeySecret)
                        ->regionId('cn-hangzhou')
                        ->asDefaultClient();

        try {
            $result = AlibabaCloud::rpc()
                          ->product('Dysmsapi')
                          // ->scheme('https') // https | http
                          ->version('2017-05-25')
                          ->action('SendSms')
                          ->method('POST')
                          ->host('dysmsapi.aliyuncs.com')
                          ->options([
                                        'query' => [
                                          'RegionId' => "cn-hangzhou",
                                          'PhoneNumbers' => $to,
                                          'SignName' => $this->signName,
                                          'TemplateCode' => $tempId,
                                          'TemplateParam' => $this->getTempDataString($data),
                                          'SmsUpExtendCode' => "60",
                                        ],
                                    ])
                          ->request();
            $this->setResult($result->toArray());
        } catch (ClientException $e) {
            $this->result(Agent::INFO, 'request failed. ' . $e->getErrorMessage() . PHP_EOL);
        } catch (ServerException $e) {
            $this->result(Agent::INFO, 'request failed. ' . $e->getErrorMessage() . PHP_EOL);
        }
    }

    protected function setResult($result)
    {
        if ($result['Code'] == 'OK') {
            $this->result(Agent::SUCCESS, true);
            if (isset($result['BizId'])) $this->result(Agent::INFO, $result['BizId']);
        } else {
            $this->result(Agent::INFO, $result['Message'] ?? 'request failed');
        }
    }

    protected function getTempDataString(array $data)
    {
        $data = array_map(function ($value) {
            return (string) $value;
        }, $data);

        return json_encode($data);
    }
}
