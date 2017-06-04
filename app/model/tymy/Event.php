<?php

namespace Tymy;

use Nette;

/**
 * Description of Tymy
 *
 * @author matej
 */
final class Event extends Tymy{
    
    public function select() {
        if (!isset($this->recId))
            throw new APIException('Event ID not set!');
        
        $this->fullUrl .= "event/" .$this->recId;
        
        return $this;
    }
    
    protected function postProcess() {
        $data = $this->getData();
        
        $data->webName = \Nette\Utils\Strings::webalize($data->caption);

        $this->timezone($data->closeTime);
        $this->timezone($data->startTime);
        $this->timezone($data->endTime);
        if (property_exists($data, "attendance"))
            foreach ($data->attendance as $att) {
                if(!property_exists($att, "preStatus")) $att->preStatus = "UNKNOWN"; //set default value
                if(!property_exists($att, "preDescription")) $att->preDescription = ""; //set default value
                if($att->userId == $this->user->getId()){
                    $data->myAttendance = $att;
                }
                if (property_exists($att, "preDatMod"))
                    $this->timezone($att->preDatMod);
            }
    }
    
}
