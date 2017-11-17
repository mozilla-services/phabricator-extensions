<?php

class PhabricatorFeedIDQuery
  extends PhabricatorFeedQuery {

  private $filterPHIDs;

  public function getOrderableColumns() {
    $table = ($this->filterPHIDs ? 'ref' : 'story');
    return array(
      'key' => array(
        'table'  => $table,
        'column' => 'id',
        'type'   => 'int',
        'unique' => true,
      ),
    );
  }
}
