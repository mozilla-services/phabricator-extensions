<?php


class EmailInlineComment {
  /** @var string */
  public $fileContext;
  /** @var string */
  public $link;
  /** @var string @deprecated */
  public $text;
  /** @var EmailCommentMessage */
  public $message;
  /** @var string "reply" if context is EmailReplyContext, otherwise "code" */
  public $contextKind;
  /** @var EmailReplyContext|EmailCodeContext */
  public $context;

  /**
   * @param string $fileContext
   * @param string $link
   * @param EmailCommentMessage $message
   * @param string $contextKind
   * @param EmailCodeContext|EmailReplyContext $context
   */
  public function __construct(string $fileContext, string $link, EmailCommentMessage $message, string $contextKind, $context) {
    $this->fileContext = $fileContext;
    $this->link = $link;
    $this->text = $message->asText;
    $this->message = $message;
    $this->contextKind = $contextKind;
    $this->context = $context;
  }
}