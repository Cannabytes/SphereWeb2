<?php

namespace Ofey\Logan22\model\ticket;

use DateTime;

class ticket_data
{
    private int $id;
    private string $type;
    private int $userId;
    private \DateTime $date;
    private string $message_last_user_id;
    private int $last_message_id;
    private string $message;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ticket_data
     */
    public function setId(int $id): ticket_data
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return ticket_data
     */
    public function setType(string $type): ticket_data
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return ticket_data
     */
    public function setUserId(int $userId): ticket_data
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * @param DateTime $date
     * @return ticket_data
     */
    public function setDate(DateTime $date): ticket_data
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return int
     */
    public function getLastMessageId(): int
    {
        return $this->last_message_id;
    }

    /**
     * @param int $last_message_id
     * @return ticket_data
     */
    public function setLastMessageId(int $last_message_id): ticket_data
    {
        $this->last_message_id = $last_message_id;
        return $this;
    }

    public function getMessageLastUserId(): string
    {
        return $this->message_last_user_id;
    }

    public function setMessageLastUserId(string $message_last_user_id): ticket_data
    {
        $this->message_last_user_id = $message_last_user_id;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

}