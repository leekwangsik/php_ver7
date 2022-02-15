<?php
namespace App\Models;

trait FeeUser {

    public static $TABLE_NAME = "fee_users";

    public $sid;
    public $user_sid;
    public $year_price;
}