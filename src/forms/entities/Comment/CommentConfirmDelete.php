<?php
/**
 * Created by PhpStorm.
 * User: neeravm
 * Date: 6/7/15
 * Time: 5:04 PM
 */

namespace RedTest\core\forms\entities\Comment;

use RedTest\core\forms\Form;


class CommentConfirmDelete extends Form {

  private $comment;

  public function __construct($cid) {
    $this->comment = comment_load($cid);
    if ($this->comment) {
      $this->includeFile('inc', 'comment', 'comment.admin');
      parent::__construct('comment_confirm_delete', $this->comment);
    }
  }

  public function submit() {
    $this->fillCommentConfirmDeleteValues(1);
    return $this->pressButton(t('Delete'), array(), $this->comment);
  }
}