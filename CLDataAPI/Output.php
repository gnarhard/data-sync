<?php
/**
 * Parses data into properly-formatted HTML
 *
 *
 * @package    CLDataAPI
 * @subpackage CLDataAPI/output
 * @author     kenneth@nashvillegeek.com (Kenneth White)
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class CLDataAPI_Output
{
	public function getNiceDay($day) {
	    switch ($day) {
	        case 'Mo': return 'Monday';
	        case 'Tu': return 'Tuesday';
	        case 'We': return 'Wednesday';
	        case 'Th': return 'Thursday';
	        case 'Fr': return 'Friday';
	        case 'Sa': return 'Saturday';
	        case 'Su': return 'Sunday';
	    }
	}
}