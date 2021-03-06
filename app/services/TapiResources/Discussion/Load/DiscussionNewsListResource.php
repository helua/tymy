<?php

namespace Tapi;

/**
 * Project: tymy_v2
 * Description of DiscussionNewsListResource
 *
 * @author kminekmatej created on 19.12.2017, 14:10:17
 */
class DiscussionNewsListResource extends DiscussionResource {
    
    public function init() {
        parent::globalInit();
        $this->setCacheable(FALSE);
        return $this;
    }
    
    public function preProcess() {
        $this->setUrl("discussions/newOnly");
        return $this;
    }

    protected function postProcess() {
        $this->options->warnings = 0;
        foreach ($this->data as $discussion) {
            $this->options->warnings += $discussion->newPosts;
        }
    }
    

}
