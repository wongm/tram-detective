<?php
class Persistent
{
    var $filename;

    /**********************/
    function __construct($filename) 
    {
        $this->filename = $filename;
        if(!file_exists($this->filename)) $this->save();
    }
    /**********************/
    function save()
    {
        if($f = @fopen($this->filename,"w"))
        {
            if(@fwrite($f,serialize(get_object_vars($this))))
            {
                @fclose($f);
            }
            else die("Could not write to file ".$this->filename." at Persistant::save");
        }

    }
    /**********************/
    function open()
    {
        $vars = unserialize(file_get_contents($this->filename));
        foreach($vars as $key=>$val)
        {
            eval("$"."this->$key = $"."vars['"."$key'];");
        }
    }
    /**********************/
}

?>
