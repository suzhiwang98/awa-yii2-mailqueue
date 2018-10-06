<?php
namespace doctorsu\mailerqueue;

use yii;

class MailerQueue extends \yii\swiftmailer\Mailer
{
    public $messageClass = "doctorsu\mailerqueue\Message";
    public $key          = "mails";
    public $db           = '1';
    public function process()
    {
        $redis = Yii::$app->redis;
        if (empty($redis)) {
            throw new \yii\base\InvalidConfException("redis not fount in config");
        }
        // 如果选择库成功并且 取得数据
        if ($redis->select($this->db) && $messages = $redis->lrange($this->key, 0, -1)) {
            //
            $messageObj = new Message;
            foreach ($messages as $message) {
                // 遍历redis数据
                $message = json_decode($message, true);
                // 如果发送失败
                if (empty($message) || !$this->setMessage($messageObj, $message)) {
                    throw new \ServerErrorHttpException("message error");
                }
                if ($messageObj->send()) {
                    $redis->lrem($this->key, -1, json_encode($message));
                }
            }
        }
        return true;
    }
    public function setMessage($messageObj, $message)
    {
        // 判断是否存在message实例
        if (empty($messageObj)) {
            return false;
        }
        if (!empty($message['from']) && !empty($message['to'])) {
            $messageObj->setFrom($message['from'])->setTo($message['to']);

            if (!empty($message['cc'])) {
                $messageObj->setCc($message['cc']);
            }
            if (!empty($message['bcc'])) {
                $messageObj->setBcc($message['bcc']);
            }
            if (!empty($message['reply_to'])) {
                $messageObj->setReplyTo($message['reply_to']);
            }
            if (!empty($message['charset'])) {
                $messageObj->setCharset($message['charset']);
            }
            if (!empty($message['subject'])) {
                $messageObj->setSubject($message['subject']);
            }
            if (!empty($message['html_body'])) {
                $messageObj->setHtmlbody($message['html_body']);
            }
            if (!empty($message['text_body'])) {
                $messageObj->setTextbody($message['text_body']);
            }
            return $messageObj;
        }
    }

}
