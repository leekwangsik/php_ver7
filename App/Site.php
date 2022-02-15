<?php
namespace App;

trait Site {

    public static $CONFIG = [
        "name" => "대한신장학회", //사이트명
        "address" => "",
        "tel" => "",
        "fax" => "",
        "email" => "ksn@ksn.or.kr",
        "ecareNum" => "130",
        "checked_ip" => [],
	    "admin_password" => [],
        "pcRoot" => "/home/virtual/ver7/htdocs",
        /*"mobileRoot" => "/home/virtual/ksn/mobile",*/ //모바일 사이트 사용시
        "uploadRoot" => "/home/virtual/ver7/htdocs",

        "pcUrl" => "http://ver7.m2comm.co.kr",
        /*"mobileUrl" => "https://m.ksn.or.kr",*/ //모바일 사이트 사용시
    ];

    public static $COMMON = [
        "email" => ["chol.com", "daum.net", "gmail.com", "hanmail.net", "hotmail.com", "kakao.com", "nate.com", "naver.com"],
        "phone" => ["010", "011", "016", "017", "018", "019"],
    ];
}