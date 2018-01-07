<?php

namespace Craft;

/**
 * Class MandrillService
 */
class MandrillService extends AbstractMandrillService
{
    /**
     * @param string $subject
     *
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->emailModel->subject = $subject;

        return $this;
    }

    /**
     * @param UserModel $user
     *
     * @return AbstractMandrillService
     */
    public function setUser(UserModel $user)
    {
        $this->user = $user;

        $this->addTo($user->email, $user->firstName, $user->lastName);

        return $this;
    }

    /**
     * @param string $email
     * @param string $firstName
     * @param string|null $lastName
     *
     * @return MandrillService
     */
    public function addTo($email, $firstName, $lastName = null)
    {
        $this->emailModel->toEmail = $email;
        $this->emailModel->toFirstName = $firstName;
        $this->emailModel->toLastName = $lastName;

        return $this;
    }

    /**
     * @param string $email
     * @param string $name
     *
     * @return MandrillService
     */
    public function setFrom($email, $name = '')
    {
        $this->emailModel->fromEmail = $email;
        if (!empty($name)) {
            $this->emailModel->fromName = $name;
        }

        return $this;
    }

    /**
     * @param string      $key
     * @param array       $variables
     * @param string|null $locale
     *
     * @return MandrillService
     */
    public function setByEmailKey($key, $variables = [], $locale = null)
    {
        $message = craft()->emailMessages->getMessage($key, $locale);
        $message->htmlBody = StringHelper::parseMarkdown($message->body);

        $this->emailModel->subject = $message->subject;
        $this->emailModel->htmlBody = $message->htmlBody;
        $this->emailModel->body = $message->body;

        $variables['emailKey'] = $key;
        $this->contentVariables = $variables;

        return $this;
    }

    /**
     * @param string $htmlBody
     * @param string $textBody
     * @param array  $variables
     *
     * @return $this
     */
    public function setContent($htmlBody, $textBody = null, $variables = [])
    {
        $this->contentVariables = $variables;

        // set HTML
        $this->emailModel->htmlBody = $htmlBody;

        // and set Text variant
        if (!empty($textBody)) {
            $this->emailModel->body = $textBody;
        }

        return $this;
    }

    /**
     * @param string $name
     * @param string $content
     * @param string $type
     *
     * @return MandrillService
     */
    public function addAttachment($name, $content, $type)
    {
        $this->emailModel->addStringAttachment($content, $name, 'base64', $type);

        return $this;
    }

    /**
     * @param string      $path
     * @param string|null $altName
     * @param string|null $mimeType
     *
     * @return MandrillService
     * @throws \Exception
     */
    public function addAttachmentFile($path, $altName = null, $mimeType = null)
    {
        $name = basename($path);
        if (!empty($altName)) {
            $name = $altName;
        }

        $contents = file_get_contents($path);
        $this->addAttachment($name, $contents, $mimeType);

        return $this;
    }

    /**
     * @param string $name
     * @param string $content
     * @param string $type
     *
     * @return MandrillService
     * @throws \Exception
     */
    public function addImage($name, $content, $type)
    {
        if (substr($type, 0, 5) !== 'image/') {
            throw new \Exception(sprintf('MimeType "%s" must start with "image/".', $type));
        }

        $this->message->addImage($name, $content, $type);

        return $this;
    }

    /**
     * @param string      $path
     * @param string|null $altName
     * @param string|null $mimeType
     *
     * @return MandrillService
     * @throws \Exception
     */
    public function addImageFile($path, $altName = null, $mimeType = null)
    {
        if (!file_exists($path) || !is_readable($path)) {
            throw new \Exception(sprintf('Image "%s" not found or not readable.', $path));
        }

        $name = basename($path);
        if (!empty($altName)) {
            $name = $altName;
        }

        $contents = file_get_contents($path);

        if (empty($mimeType)) {
            $mimeType = mime_content_type($path);
        }

        $this->addImage($name, $contents, $mimeType);

        return $this;
    }

    /**
     * @param string $name
     *
     * @return MandrillService
     */
    public function addTag($name)
    {
        $this->message->addTag($name);

        return $this;
    }
}
