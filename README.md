Locomotive
==========

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/reaktivstudios/locomotive/badges/quality-score.png?b=master&s=86399ae1ed8459dbcaa0c4a5d5e34947d7454cf8)](https://scrutinizer-ci.com/g/reaktivstudios/locomotive/?branch=master) [![Code Coverage](https://scrutinizer-ci.com/g/reaktivstudios/locomotive/badges/coverage.png?b=master&s=656ebaea7636b3882b1834f7226c53327e826bb2)](https://scrutinizer-ci.com/g/reaktivstudios/locomotive/?branch=master)

## About
![Locomotive Logo](logo.png?raw=true "Locomotive Logo")

Creating batch processes in WordPress has never been so easy. If you've ever wanted to query a large dataset and perform simple and repeatable actions, then Locomotive is for you.

Locomotive allows developers to write a single function (or set of functions) to process actions across a large data set. These registered batch processes can be run with the click of a button from the WP admin as needed. Locomotive handles the complexity of batch processing by automatically chunking up data, checking for records that have already been processed and logging errors as they come in.

## Links
* [Documentation](https://github.com/reaktivstudios/locomotive/wiki)
* [Examples](https://github.com/reaktivstudios/locomotive/wiki/Examples)

## Quick Start Example

#### Register a standard post query
``` php
function my_post_query_batch_process() {

	register_batch_process( array(
		'name'     => 'Just another batch',
		'type'     => 'post',
		'callback' => 'my_callback_function',
		'args'     => array(
			'posts_per_page' => 1,
			'post_type'      => 'post',
		),
	) );
}
add_action( 'locomotive_init', 'my_post_query_batch_process' );
```

#### Hook In Callback Function
``` php
/**
 * This is what we want to do with each individual result during a batch routine.
 *
 * @param  array $result Individual result from batch query.
 */
function my_callback_function( $result ) {
	error_log( print_r( $result->post_title, true ) );
}
```

#### Custom Processor
Using a custom processor requires using the `loco_register_batch_processor` filter to register the processor type and a processor class that extends the `Rkv\Locomotive\Abstracts\Batch` class.

Register your custom callback to be used when the type matches your process type:
```
add_filter( 'loco_register_batch_processor', function( $return_value, $type, $args){
    if( 'hiroy' == $type' ){
        // Return name of class, not class instance
        return 'HiRoyProcessor';
    }elseif( 'hishawn' == $type ){
        // Return name of class, not class instance
        return 'HiShawnProcessor';
    }else{
        // No custom type matched
        // Return filter default to allow for other custom types or defaults
        return $return_value;
    }
    
    
}, 10, 3 );




#### Start Batch Process
![Locomotive Menu](screenshot.gif?raw=true "Locomotive Menu")

Navigate to Tools->Batches in the admin, select your batch, and click _Run_.

## Contributors
* [Josh Eaton](https://github.com/jjeaton)
* [Zach Wills](https://github.com/zachwills)
* [Andrew Norcross](https://github.com/norcross)
* [Jay Hoffmann](https://github.com/JasonHoffmann)

## Changelog

See [CHANGELOG.md](CHANGELOG.md).


#### [Pull requests](https://github.com/reaktivstudios/locomotive/pulls) are very much welcome and encouraged.
