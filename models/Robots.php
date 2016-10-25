<?php

namespace Store\Toys;

use Phalcon\Validation;

use Phalcon\Mvc\Model,
    Phalcon\Mvc\Model\Message,
    Phalcon\Validation\Validator\Uniqueness,
    Phalcon\Validation\Validator\InclusionIn;


class Robots extends Model
{
    public function validation()
    {
        $validation = new Validation();
        $validation->add("type",
            new InclusionIn(
                [
                    "field"  => "type",
                    "domain" => [
                        "droid",
                        "mechanical",
                        "virtual",
                        ]
                ]
            )
        );

        $validation->add("name",
            new Uniqueness(
                [
                    "field"   => "name",
                    "message" => "The robot name must be unique",
                ]
            )
        );

        if($this->year < 0){
            $this->appendMessage(
                new Message("The year cannot be less than zero")
            );
        }

        if($this->validationHasFailed() === true){
            return false;
        }
    }
}