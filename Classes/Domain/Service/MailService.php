<?php
namespace NeosRulez\Shop\CouponFinisher\Domain\Service;

/*
 * This file is part of the NeosRulez.Shop.CouponFinisher package.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\View\FusionView;

/**
 * Class Mail
 *
 * @Flow\Scope("singleton")
 */
class MailService
{

    /**
     * @Flow\InjectConfiguration(package="NeosRulez.Shop.CouponFinisher", path="Mail.template.fusionPath")
     * @var string
     */
    protected $fusionPath;

    /**
     * @Flow\InjectConfiguration(package="NeosRulez.Shop.CouponFinisher", path="Mail.template.package")
     * @var string
     */
    protected $package;

    /**
     * @Flow\InjectConfiguration(package="NeosRulez.Shop.CouponFinisher", path="Mail.senderMail")
     * @var string
     */
    protected $senderMail;

    protected $defaultViewObjectName = FusionView::class;

    /**
     * @param array $variables
     * @param string $subject
     * @param string $recipient
     * @param array $attachments
     * @return void
     */
    public function sendMail(array $variables, string $subject, string $recipient, array $attachments = []): void
    {
        $fusionView = new FusionView();
        $fusionView->setPackageKey($this->package);
        $fusionView->setFusionPath($this->fusionPath);
        $fusionView->assignMultiple($variables);

        $mail = new \Neos\SwiftMailer\Message();
        $mail
            ->setFrom($this->senderMail)
            ->setTo(array(str_replace(' ', '', $recipient) => str_replace(' ', '', $recipient)))
            ->setReplyTo([$this->senderMail => $this->senderMail])
            ->setSubject($subject);
        $mail->setBody($fusionView->render(), 'text/html');

        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $attachment = \Swift_Attachment::fromPath($attachment);
                $mail->attach($attachment);
            }
        }

        $mail->send();
    }

}
