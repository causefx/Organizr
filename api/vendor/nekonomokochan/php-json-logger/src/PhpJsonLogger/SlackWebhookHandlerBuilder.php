<?php
namespace Nekonomokochan\PhpJsonLogger;

/**
 * Class SlackWebhookHandlerBuilder
 *
 * @package Nekonomokochan\PhpJsonLogger
 */
class SlackWebhookHandlerBuilder
{
    /**
     * @var string
     */
    private $webhookUrl;

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
     * SlackWebhookHandlerBuilder constructor.
     *
     * @param string $webhookUrl
     * @param string $channel
     */

    public function __construct(string $webhookUrl, string $channel = '')
    {
        $this->setWebookUrl($webhookUrl);
        $this->setChannel($channel);
        $this->setUsername('Organizr');
        $this->setUseAttachment(true);
        $this->setIconEmoji(':cat:');
        $this->setLevel(500);
        $this->setBubble(true);
        $this->setUseShortAttachment(false);
        $this->setIncludeContextAndExtra(true);
        $this->setExcludeFields([]);
    }

    /**
     * @return string
     */
    public function getWebookUrl(): string
    {
        return $this->webhookUrl;
    }

    /**
     * @param string $webhookUrl
     */
    public function setWebookUrl(string $webhookUrl)
    {
        $this->webhookUrl = $webhookUrl;
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
     * @return \Monolog\Handler\SlackWebhookHandler
     * @throws \Monolog\Handler\MissingExtensionException
     */
    public function build()
    {
		//
        return new \Monolog\Handler\SlackWebhookHandler(
            $this->getWebookUrl(),
            $this->getChannel(),
            $this->getUsername(),
            $this->isUseAttachment(),
            $this->getIconEmoji(),
            $this->isUseAttachment(),
            $this->isIncludeContextAndExtra(),
	        $this->getLevel(),
            $this->isBubble(),
            $this->getExcludeFields()
        );
    }
}
