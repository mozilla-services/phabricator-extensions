<?php

/*
 * This file is a sublass of
 * src/applications/feed/conduit/FeedQueryConduitAPIMethod.php
 * that was needed to give the ability to filter transactions based on story id values.
 */

final class FeedQueryIDConduitAPIMethod extends FeedQueryConduitAPIMethod {

  public function getAPIMethodName() {
    return 'feed.query_id';
  }

  private function getDefaultLimit() {
    return 100;
  }

  public function execute(ConduitAPIRequest $request) {
    $results = array();
    $user = $request->getUser();

    $view_type = $request->getValue('view');
    if (!$view_type) {
      $view_type = 'data';
    }

    $limit = $request->getValue('limit');
    if (!$limit) {
      $limit = $this->getDefaultLimit();
    }

    $query = id(new PhabricatorFeedIDQuery())
      ->setOrder('oldest')
      ->setLimit($limit)
      ->setViewer($user);

    $filter_phids = $request->getValue('filterPHIDs');
    if ($filter_phids) {
      $query->withFilterPHIDs($filter_phids);
    }

    $after = $request->getValue('after');
    if (strlen($after)) {
      $query->setAfterID($after);
    }

    $before = $request->getValue('before');
    if (strlen($before)) {
      $query->setBeforeID($before);
    }

    $stories = $query->execute();

    if ($stories) {
      foreach ($stories as $story) {

        $story_data = $story->getStoryData();

        $data = null;

        try {
          $view = $story->renderView();
        } catch (Exception $ex) {
          // When stories fail to render, just fail that story.
          phlog($ex);
          continue;
        }

        $view->setEpoch($story->getEpoch());
        $view->setUser($user);

        switch ($view_type) {
          case 'html':
            $data = $view->render();
          break;
          case 'html-summary':
            $data = $view->render();
          break;
          case 'data':
            $data = array(
              'id' => $story_data->getID(),
              'class' => $story_data->getStoryType(),
              'epoch' => $story_data->getEpoch(),
              'authorPHID' => $story_data->getAuthorPHID(),
              'chronologicalKey' => $story_data->getChronologicalKey(),
              'data' => $story_data->getStoryData(),
            );
          break;
          case 'text':
            $data = array(
              'id' => $story_data->getID(),
              'class' => $story_data->getStoryType(),
              'epoch' => $story_data->getEpoch(),
              'authorPHID' => $story_data->getAuthorPHID(),
              'chronologicalKey' => $story_data->getChronologicalKey(),
              'objectPHID' => $story->getPrimaryObjectPHID(),
              'text' => $story->renderText(),
            );
          break;
          default:
            throw new ConduitException('ERR-UNKNOWN-TYPE');
        }

        $results[$story_data->getPHID()] = $data;
      }
    }

    $result = array(
      'data' => $results,
    );

    return $result;
  }

}
