<?php

namespace Valkyria\Database;

use \PDO;

class Mysql{

	private $login;

	private $pass;

	private $connec;
	
	private $sth;

	public function __construct($config = array()){

		$this->login = $config['login'];
		$this->pass = $config['pass'];
        $this->db = $config['name'];
		$this->host = $config['host'];
        $this->Error = '';
		$this->connexion();

	}

	private function connexion(){
		try{

			$bdd = new PDO('mysql:host='.$this->host.';dbname='.$this->db, $this->login, $this->pass);
			$bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
			$bdd->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
			$this->connec = $bdd;

		}
		catch (PDOException $e){

			$this->Erreur = 'ERREUR PDO dans ' . $e->getFile() . ' L.' . $e->getLine() . ' : ' . $e->getMessage();
			die($this->Erreur);

		}
	}

	public function Query($Query){

		$Result = $this->connec->query($Query);
		
        if(!$Result) {

            $this->Error = $this->connec->errorInfo();
            error_log('[ERROR] SQL error : '. $this->Error);
			error_log('SQL request : '. $Query);
			return false;
			
		}
		
		else return $Result;
    }


    public function Prepare(String $Query){

		$this->sth = $this->connec->prepare($Query);
		return $this->sth;

	}

	public function Execute($Data){

		if(is_array($Data[0])) {

			foreach($Data as $Key => $Params) {

				call_user_func_array(array($this->sth, "bindValue"), $Params);
	
			}

			$Result = $this->sth->execute();

		} else $Result = $this->sth->execute($Data);

		if(!$Result) {
            $this->Error = $this->connec->errorInfo();
            error_log('[ERROR] SQL error : '. $this->Error);
            return false;
        }
		else return $this->sth;
	}

	public function GetError(){ return $this->sth->errorInfo(); }   
    public function GetObject($Query){ return $Query->fetchALL(PDO::FETCH_OBJ); }
    public function GetArray($Query){ return $Query->fetchALL(PDO::FETCH_ASSOC); }
	public function GetField($Query){ return $Query->fetchAll(PDO::FETCH_COLUMN); }
	public function GetNumberOfRow($Query){ return $Query->rowCount(); }
	public function Free($Query){ $Query->closeCursor();}
    public function InsertID(){ return $this->connec->lastInsertId(); }
    public function GetResult($Query,$Index){ 
		$array = $this->GetArray($Query);
		return isset($array[$Index]) ? $array[$Index] : false ;
	}
    

}