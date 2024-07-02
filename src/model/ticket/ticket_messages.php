<?php

namespace Ofey\Logan22\model\ticket;

class ticket_messages
{
    private int $id;
    private int $ticket_id;
    private int $user_id;
    private array $message;
    private \DateTime $date;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ticket_messages
     */
    public function setId(int $id): ticket_messages
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getTicketId(): int
    {
        return $this->ticket_id;
    }

    /**
     * @param int $ticket_id
     * @return ticket_messages
     */
    public function setTicketId(int $ticket_id): ticket_messages
    {
        $this->ticket_id = $ticket_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * @param int $user_id
     * @return ticket_messages
     */
    public function setUserId(int $user_id): ticket_messages
    {
        $this->user_id = $user_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): array
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return ticket_messages
     */
    public function setMessage(string $message): ticket_messages
    {
        $this->message[] = $message;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     * @return ticket_messages
     */
    public function setDate(\DateTime $date): ticket_messages
    {
        $this->date = $date;
        return $this;
    }


}