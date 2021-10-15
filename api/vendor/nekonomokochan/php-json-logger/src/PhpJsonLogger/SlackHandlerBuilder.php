<?php
namespace Nekonomokochan\PhpJsonLogger;

/**
 * Class SlackHandlerBuilder
 *
 * @package Nekonomokochan\PhpJsonLogger
 */
class SlackHandlerBuilder
{
    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $channel;

    /**
     * @var string
     */
    private $username;

    /**
     * @var bool
     */
    private $useAttachment;

    /**
     * @var string
     */
    private $iconEmoji;

    /**
     * @var int
     */
    private $level;

    /**
     * @var bool
     */
    private $bubble;

    /**
     * @var bool
     */
    private $useShortAttachment;

    /**
     * @var bool
     */
    private $includeContextAndExtra;

    /**
     * @var array
     */
    private $excludeFields;

    /**
     * SlackHandlerBuilder constructor.
     *
     * @param string $token
     * @param string $channel
     */
    public function __construct(string $token, string $channel)
    {
        $this->setToken($token);
        $this->setChannel($channel);
        $this->setUsername('nekonomokochan/php-json-logger');
        $this->setUseAttachment(true);
        $this->setIconEmoji(':cat:');
        $this->setLevel(LoggerBuilder::CRITICAL);
        $this->setBubble(true);
        $this->setUseShortAttachment(false);
        $this->setIncludeContextAndExtra(true);
        $this->setExcludeFields([]);
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken(string $token)
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     */
    public function setChannel(string $channel)
    {
        $this->channel = $channel;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername(string $username)
    {
        $this->username = $username;
    }

    /**
     * @return bool
     */
    public function isUseAttachment(): bool
    {
        return $this->useAttachment;
    }

    /**
     * @param bool $useAttachment
     */
    private function setUseAttachment(bool $useAttachment)
    {
        $this->useAttachment = $useAttachment;
    }

    /**
     * @return string
     */
    public function getIconEmoji(): string
    {
        return $this->iconEmoji;
    }

    /**
     * @param string $iconEmoji
     */
    private function setIconEmoji(string $iconEmoji)
    {
        $this->iconEmoji = $iconEmoji;
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @param int $level
     */
    public function setLevel(int $level)
    {
        $this->level = $level;
    }

    /**
     * @return bool
     */
    public function isBubble(): bool
    {
        return $this->bubble;
    }

    /**
     * @param bool $bubble
     */
    private function setBubble(bool $bubble)
    {
        $this->bubble = $bubble;
    }

    /**
     * @return bool
     */
    public function isUseShortAttachment(): bool
    {
        return $this->useShortAttachment;
    }

    /**
     * @param bool $useShortAttachment
     */
    private function setUseShortAttachment(bool $useShortAttachment)
    {
        $this->useShortAttachment = $useShortAttachment;
    }

    /**
     * @return bool
     */
    public function isIncludeContextAndExtra(): bool
    {
        return $this->includeContextAndExtra;
    }

    /**
     * @param bool $includeContextAndExtra
     */
    private function setIncludeContextAndExtra(bool $includeContextAndExtra)
    {
        $this->includeContextAndExtra = $includeContextAndExtra;
    }

    /**
     * @return array
     */
    public function getExcludeFields(): array
    {
        return $this->excludeFields;
    }

    /**
     * @param array $excludeFields
     */
    private function setExcludeFields(array $excludeFields)
    {
        $this->excludeFields = $excludeFields;
    }

    /**
     * @return \Monolog\Handler\SlackHandler
     * @throws \Monolog\Handler\MissingExtensionException
     */
    public function build()
    {
        return new \Monolog\Handler\SlackHandler(
            $this->getToken(),
            $this->getChannel(),
            $this->getUsername(),
            $this->isUseAttachment(),
            $this->getIconEmoji(),
            $this->getLevel(),
            $this->isBubble(),
            $this->isUseShortAttachment(),
            $this->isIncludeContextAndExtra(),
            $this->getExcludeFields()
        );
    }
}
