<?php
namespace Valkyria\Exception;

use Exception;

class Handler{
	protected $dontReport = [];

	public function report(Exception $e){
		if ($this->shouldntReport($e)) { return; }
		
        if (method_exists($e, 'report')) { return $e->report(); }
        try {
            $logger = app('log');
        } catch (Exception $ex) {
            throw $ex; 
        }
        $logger->error($e, ['exception' => $e]);
	}
	
	public function shouldReport(Exception $e){
        return ! $this->shouldntReport($e);
	}
	
	protected function shouldntReport(Exception $e):Bool{
        foreach ($this->dontReport as $type) {
            if ($e instanceof $type) {
                return true;
            }
        }
        return false;
	}
	
}
