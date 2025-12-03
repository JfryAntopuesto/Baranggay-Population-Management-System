<?php
class person
{
    private $fName;
    private $lName;
    private $mName;
    private $userName;
    private $password;
    protected $role;
    private $birthdate;
    private $gender;
    private $modID;

    // Getter and Setter for fName
    public function getFName()
    {
        return $this->fName;
    }

    public function setFName($fName)
    {
        $this->fName = $fName;
    }

    // Getter and Setter for lName
    public function getLName()
    {
        return $this->lName;
    }

    public function setLName($lName)
    {
        $this->lName = $lName;
    }

    // Getter and Setter for mName
    public function getMName()
    {
        return $this->mName;
    }

    public function setMName($mName)
    {
        $this->mName = $mName;
    }

    // Getter and Setter for userName
    public function getUserName()
    {
        return $this->userName;
    }

    public function setUserName($userName)
    {
        $this->userName = $userName;
    }

    // Getter and Setter for password
    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    // Getter and Setter for birthdate
    public function getBirthdate()
    {
        return $this->birthdate;
    }

    public function setBirthdate($birthdate)
    {
        $this->birthdate = $birthdate;
    }

    // Getter and Setter for gender
    public function getGender()
    {
        return $this->gender;
    }

    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    // Getter for role
    public function getRole()
    {
        return $this->role;
    }
    
    // Getter and Setter for modID
    public function getModID()
    {
        return $this->modID;
    }
    
    public function setModID($modID)
    {
        $this->modID = $modID;
    }
}

class user extends person
{
    public function getRole()
    {
       $this->role = "resident";
       return $this->role;
    }
}

class staff extends person
{
    public function getRole()
    {
        $this->role = "staff";
        return $this->role;
    }
}