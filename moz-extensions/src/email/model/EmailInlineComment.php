<?php


class EmailInlineComment {
  /** @var string */
  public $fileContext;
  /** @var string */
  public $link;
  /** @var string */
  public $text;
  /** @var string "reply" if context is EmailReplyContext, otherwise "code" */
  public $contextKind;
  /** @var EmailReplyContext|EmailCodeContext */
  public $context;

  /**
   * @param string $fileContext
   * @param string $link
   * @param string $text
   * @param string $contextKind
   * @param EmailCodeContext|EmailReplyContext $context
   */
  public function __construct(string $fileContext, string $link, string $text, string $contextKind, $context) {
    $this->fileContext = $fileContext;
    $this->link = $link;
    $this->text = $text;
    $this->contextKind = $contextKind;
    $this->context = $context;
  }
}