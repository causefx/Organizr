<?php namespace GO\Traits;

trait Mailer
{
    /**
     * Get email configuration.
     *
     * @return array
     */
    public function getEmailConfig()
    {
        if (! isset($this->emailConfig['subject']) ||
            ! is_string($this->emailConfig['subject'])
        ) {
            $this->emailConfig['subject'] = 'Cronjob execution';
        }

        if (! isset($this->emailConfig['from'])) {
            $this->emailConfig['from'] = ['cronjob@server.my' => 'My Email Server'];
        }

        if (! isset($this->emailConfig['body']) ||
            ! is_string($this->emailConfig['body'])
        ) {
            $this->emailConfig['body'] = 'Cronjob output attached';
        }

        if (! isset($this->emailConfig['transport']) ||
            ! ($this->emailConfig['transport'] instanceof \Swift_Transport)
        ) {
            $this->emailConfig['transport'] = new \Swift_SendmailTransport();
        }

        return $this->emailConfig;
    }

    /**
     * Send files to emails.
     *
     * @param  array  $files
     * @return void
     */
    private function sendToEmails(array $files)
    {
        $config = $this->getEmailConfig();

        $mailer = new \Swift_Mailer($config['transport']);

        $message = (new \Swift_Message())
            ->setSubject($config['subject'])
            ->setFrom($config['from'])
            ->setTo($this->emailTo)
            ->setBody($config['body'])
            ->addPart('<q>Cronjob output attached</q>', 'text/html');

        foreach ($files as $filename) {
            $message->attach(\Swift_Attachment::fromPath($filename));
        }

        $mailer->send($message);
    }
}
