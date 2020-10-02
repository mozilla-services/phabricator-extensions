<?php


class PublicPing {
  /** @var PhabricatorUser */
  public $targetUser;
  /** @var EmailCommentMessage|null */
  public $mainCommentMessage;
  /** @var EmailInlineComment[] */
  public $inlineComments;

  /**
   * @param PhabricatorUser $targetUser
   */
  public function __construct(PhabricatorUser $targetUser) {
    $this->targetUser = $targetUser;
    $this->inlineComments = [];
  }

  public function setMainComment(EmailCommentMessage $message) {
    $this->mainCommentMessage = $message;
  }

  public function appendInlineComment(EmailInlineComment $inlineComment) {
    $this->inlineComments[] = $inlineComment;
  }

  public function intoPublicBody(string $actorEmail, string $transactionLink): ?EmailRevisionCommentPinged {
    $recipient = EmailRecipient::from($this->targetUser, $actorEmail);
    if (!$recipient) {
      return null;
    }

    return new EmailRevisionCommentPinged(
      $recipient,
      $transactionLink,
      $this->mainCommentMessage,
      $this->inlineComments
    );
  }
}