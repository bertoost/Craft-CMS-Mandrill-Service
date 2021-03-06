<?php

namespace Craft;

/**
 * Class Mandrill_MessageModel
 */
class Mandrill_MessageModel extends BaseModel
{
    /**
     * @var array
     */
    private $recipientList = [];

    /**
     * @var array
     */
    private $attachmentList = [];

    /**
     * @var array
     */
    private $imageList = [];

    /**
     * @var array
     */
    private $tagList = [];

    /**
     * Defines all attributes allowed for a Mandrill message
     *
     * @return array
     */
    public function defineAttributes()
    {
        return array_merge(parent::defineAttributes(), [
            'from_email'  => [AttributeType::String, 'required' => true],
            'from_name'   => [AttributeType::String, 'required' => true],
            'subject'     => [AttributeType::String, 'required' => true],
            'text'        => [AttributeType::String, 'required' => true],
            'html'        => [AttributeType::String],
            'to'          => [AttributeType::Mixed, 'default' => []],
            'tags'        => [AttributeType::Mixed, 'default' => []],
            'attachments' => [AttributeType::Mixed, 'default' => []],
            'images'      => [AttributeType::Mixed, 'default' => []],
        ]);
    }

    /**
     * @param string $email
     * @param string $name
     *
     * @return Mandrill_MessageModel
     */
    public function setFrom($email, $name = '')
    {
        $this->from_email = $email;

        if (!empty($name)) {
            $this->from_name = $name;
        }

        return $this;
    }

    /**
     * @param string $email
     * @param string $name
     *
     * @return Mandrill_MessageModel
     */
    public function addTo($email, $name)
    {
        $recipient = [
            'email' => $email,
        ];

        if (!empty($name)) {
            $recipient['name'] = $name;
        }

        // store in email key, to avoid adding the same recipient twice
        $this->recipientList[$email] = $recipient;

        // override local 'to' attribute
        $this->to = array_values($this->recipientList);

        return $this;
    }

    /**
     * @param string $name
     * @param string $content
     * @param string $type
     *
     * @return Mandrill_MessageModel
     */
    public function addAttachment($name, $content, $type)
    {
        // store in name key, to avoid adding the same attachment twice
        $this->attachmentList[$name] = [
            'type'    => $type,
            'name'    => $name,
            'content' => base64_encode($content),
        ];

        // override local 'to' attribute
        $this->attachments = array_values($this->attachmentList);

        return $this;
    }

    /**
     * @param string $name
     * @param string $content
     * @param string $type
     *
     * @return Mandrill_MessageModel
     */
    public function addImage($name, $content, $type)
    {
        // store in name key, to avoid adding the same attachment twice
        $this->imageList[$name] = [
            'type'    => $type,
            'name'    => $name,
            'content' => base64_encode($content),
        ];

        // override local 'to' attribute
        $this->images = array_values($this->imageList);

        return $this;
    }

    /**
     * @param string $name
     *
     * @return Mandrill_MessageModel
     */
    public function addTag($name)
    {
        if (is_array($name)) {
            foreach ($name as $item) {
                $this->addTag($item);
            }

            return $this;
        }

        if (!in_array($name, $this->tagList)) {
            $this->tagList[] = $name;
        }

        $this->tags = $this->tagList;

        return $this;
    }

    /**
     * @param EmailModel $emailModel
     *
     * @return Mandrill_MessageModel
     */
    public function convertFromEmailModel(EmailModel $emailModel)
    {
        $fromEmail = $emailModel->fromEmail;
        if (null !== ($configFromEmail = craft()->config->get('mandrillFromEmail'))) {
            $fromEmail = $configFromEmail;
        }

        $fromName = $emailModel->fromName;
        if (null !== ($configFromName = craft()->config->get('mandrillFromName'))) {
            $fromName = $configFromName;
        }

        $this
            ->setFrom($fromEmail, $fromName)
            ->addTo($emailModel->toEmail, $emailModel->toFirstName . ' ' . $emailModel->toLastName);

        // attachments
        if (!empty($emailModel->stringAttachments)) {
            foreach ($emailModel->stringAttachments as $attachment) {
                $this->addAttachment($attachment['fileName'], $attachment['string'], $attachment['type']);
            }
        }

        if (!empty($emailModel->attachments)) {
            foreach ($emailModel->attachments as $attachment) {
                if (IOHelper::fileExists($attachment['path'])) {
                    $contents = IOHelper::getFileContents($attachment['path']);
                    $this->addAttachment($attachment['name'], $contents, $attachment['type']);
                }
            }
        }

        $this->subject = $emailModel->subject;

        $hasBody = (integer)!empty($emailModel->body);
        $hasHTMLBody = (integer)!empty($emailModel->htmlBody);
        $msgState = ((string)$hasBody . (string)$hasHTMLBody);

        switch ($msgState) {
            case '10': // text only
                $this->text = $emailModel->body;
                $this->html = nl2br($emailModel->body);
                break;

            case '01': // html only
                $text = preg_replace("/\n\s+/", "\n", rtrim(html_entity_decode(strip_tags($emailModel->htmlBody))));
                $this->text = $text;
                $this->html = $emailModel->htmlBody;
                break;

            case '11': // both
                $this->text = $emailModel->body;
                $this->html = $emailModel->htmlBody;
                break;
        }

        return $this;
    }
}
