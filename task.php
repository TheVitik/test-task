<?php

/*
    Необходимо доработать класс рассылки Newsletter, что бы он отправлял письма
    и пуш нотификации для юзеров из UserRepository.

    За отправку имейла мы считаем вывод в консоль строки: "Email {email} has been sent to user {name}"
    За отправку пуш нотификации: "Push notification has been sent to user {name} with device_id {device_id}"

    Так же необходимо реализовать функциональность для валидации имейлов/пушей:
    1) Нельзя отправлять письма юзерам с невалидными имейлами
    2) Нельзя отправлять пуши юзерам с невалидными device_id. Правила валидации можете придумать сами.
    3) Ничего не отправляем юзерам у которых нет имен
    4) На одно и то же мыло/device_id - можно отправить письмо/пуш только один раз

    Для обеспечения возможности масштабирования системы (добавление новых типов отправок и новых валидаторов),
    можно добавлять и использовать новые классы и другие языковые конструкции php в любом количестве.
    Реализация должна соответствовать принципам ООП
*/

interface Newsletter
{
    function send(): void;

    function isSent(array $user): bool;
}

class EmailNewsletter implements Newsletter
{
    const EMAIL_SENT_TEXT = "Email %s has been sent to user %s";
    private array $sentUsers = [];

    public function __construct(private UserRepository $repository, private SendEmailValidator $validator)
    {

    }

    public function send(): void
    {
        $users = $this->repository->getUsers();
        foreach ($users as $user) {
            if (
                $this->validator->hasName($user)
                && $this->validator->hasValidEmail($user)
                && !$this->isSent($user)
            ) {
                print(sprintf(self::EMAIL_SENT_TEXT . "\n", $user['email'], $user['name']));
                $this->sentUsers[] = $user;
            }
        }
    }

    function isSent(array $user): bool
    {
        foreach ($this->sentUsers as $sentUser) {
            if ($sentUser['email'] === $user['email']) {
                return true;
            }
        }

        return false;
    }
}

class PushNewsletter implements Newsletter
{
    const PUSH_SENT_TEXT = "Push notification has been sent to user %s with device_id %s";
    private array $sentUsers = [];

    public function __construct(private UserRepository $repository, private SendPushValidator $validator)
    {

    }

    public function send(): void
    {
        $users = $this->repository->getUsers();
        foreach ($users as $user) {
            if (
                $this->validator->hasName($user)
                && $this->validator->hasValidDeviceId($user)
                && !$this->isSent($user)
            ) {
                print(sprintf(self::PUSH_SENT_TEXT . "\n", $user['name'], $user['device_id']));
                $this->sentUsers[] = $user;
            }
        }
    }

    function isSent(array $user): bool
    {
        foreach ($this->sentUsers as $sentUser) {
            if ($sentUser['device_id'] === $user['device_id']) {
                return true;
            }
        }

        return false;
    }
}

abstract class Validator
{
    public function hasName(array $user): bool
    {
        if (empty($user['name'])) {
            return false;
        }

        return true;
    }
}

class SendEmailValidator extends Validator
{
    public function hasValidEmail($user): bool
    {
        if (empty($user['email'])) {
            return false;
        }
        if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return true;
    }
}

class SendPushValidator extends Validator
{
    const DEVICE_ID_PATTERN = "/^[a-zA-Z0-9]*$/";

    public function hasValidDeviceId($user): bool
    {
        if (empty($user['device_id'])) {
            return false;
        }
        if (!preg_match(self::DEVICE_ID_PATTERN, $user['device_id'])) {
            return false;
        }

        return true;
    }
}

class UserRepository
{
    public function getUsers(): array
    {
        return [
            [
                'name' => 'Ivan',
                'email' => 'ivan@test.com',
                'device_id' => 'Ks[dqweer4'
            ],
            [
                'name' => 'Peter',
                'email' => 'peter@test.com'
            ],
            [
                'name' => 'Mark',
                'device_id' => 'Ks[dqweer4'
            ],
            [
                'name' => 'Nina',
                'email' => '...'
            ],
            [
                'name' => 'Luke',
                'device_id' => 'vfehlfg43g'
            ],
            [
                'name' => 'Zerg',
                'device_id' => ''
            ],
            [
                'email' => '...',
                'device_id' => ''
            ]
        ];
    }
}

/**
 * Тут релизовать получение объекта(ов) рассылки Newsletter и вызов(ы) метода send()
 * $newsletter = //... TODO
 * $newsletter->send();
 * ...
 */

$emailNewsletter = new EmailNewsletter(new UserRepository(), new SendEmailValidator());
$emailNewsletter->send();

$pushNewsletter = new PushNewsletter(new UserRepository(), new SendPushValidator());
$pushNewsletter->send();



