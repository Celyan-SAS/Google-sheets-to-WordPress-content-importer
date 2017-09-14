<?php
/**
 *	@package YD import csv
 *	@author Celyan
 *	@version 0.0.1
 */
/*
 Plugin Name: YD import csv
 Plugin URI: http://www.yann.com/
 Description: import csv on cpt/acf
 Version: 0.0.1
 Author: Yann Dubois
 Author URI: http://www.yann.com/
 License: GPL2
 */

include_once(dirname(__FILE__) . '/inc/main.php');

/** Controller Class **/
global $YD_importcsv_o;
$YD_importcsv_o = new importcsv();