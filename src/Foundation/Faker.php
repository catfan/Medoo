<?php

namespace Medoo\Foundation;

/**
 * @author Alireza Josheghani <josheghani.dev@gmail.com>
 * @version 1.0
 * @package Medoo Foundation | Faker
 * */

class Faker {

    /**
     * Random names for generator
     * you can add your names
     * */
    protected $Names = [
        'Alireza','Leyla','Maryam','Shahab',
        'Zeinab','John','Joe','Hamid','Atiye',
        'Mohammad','Abbas','Amir','Ali','MohammadHossein',
        'Saeed','Mehdi','Lale','Asal','Sarah','Mina','Mostafa',
        'Parastoo','Shayan','Sam','MohammadReza','Sahar','Amanda',
        'Mhanaz','Habib','Samane','Zahra','Zohre','Parmida','Shima'
    ];

    /**
     * Random family names for generator
     * you can add your family names
     * */
    protected $Families = [
        'Josheghani','Koutabadi','Yazdizadeh','ZamaniKhah',
        'Mohammadi','Doe','Jo','Mehdiyan','Hamidi','Amiri',
        'Nosrati','Hosseini','Shahghale','Ebadollahi','Beigloo',
        'Khoshnevis','Pegani','Moradi','Meshkini','Mirasadi',
        'Motaghi','Meshkini','Ahmadi','Derakhshande','Haghmoradi',
        'JannatAbadi','Cerny','Yazdi','Gholami','Habibi','Gohari'
    ];

    /**
     * Random email services for generator
     * you can add your email services
     * */
    protected $mailes = [
        'gmail.com','example.com','aio.co','ipenpal.io','yahoo.in',
        'chapar.ir','mail.biz','email.org','samplemail.co.uk','medoo.in'
    ];

    var $safeEmail; // generate safe email
    var $email; // generate email
    var $name; // generate name

    /**
     * Faker constructor.
     * @param $limit
     */
    public function __construct($limit)
    {
        $this->safeEmail = $this->makeSafeMail();
        $this->email = $this->makeSafeMail();
        $this->name = $this->makeName();
    }

    /**
     * @return string
     */
    public function makeName()
    {
        return $this->Names[rand(0,count($this->Names) - 1)]." ".
        $this->Families[rand(0,count($this->Families) - 1)];
    }

    /**
     * @return string
     */
    public function makeSafeMail()
    {
        return strtolower($this->Names[rand(0,count($this->Names) - 1)])."@".$this->mailes[rand(0,count($this->mailes) - 1)];
    }
}