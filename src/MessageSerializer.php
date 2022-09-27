<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP;

use Yiisoft\Factory\Factory;
use Yiisoft\Yii\Queue\AMQP\Exception\NoKeyInPayloadException;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Message\MessageInterface;

class MessageSerializer implements MessageSerializerInterface
{
    public function __construct(private Factory $factory)
    {
    }

    public function serialize(MessageInterface $message): string
    {
        $payload = [
            'name' => $message->getHandlerName(),
            'data' => $message->getData(),
            'behaviors' => [],
        ];
        foreach ($message->getBehaviors() as $behavior) {
            $payload['behaviors'][] = [
                'class' => $behavior::class,
                '__construct()' => $behavior->getConstructorParameters(),
            ];
        }

        return json_encode($payload, JSON_THROW_ON_ERROR);
    }

    public function unserialize(string $value): MessageInterface
    {
        $payload = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        $name = $payload['name'] ?? null;
        if (!is_string($name)) {
            throw new NoKeyInPayloadException('name', $payload);
        }

        $message = new Message($name, $payload['data'] ?? null);
        foreach ($payload['behaviors'] as $behavior) {
            $message->attachBehavior($this->factory->create($behavior));
        }

        return $message;
    }
}
