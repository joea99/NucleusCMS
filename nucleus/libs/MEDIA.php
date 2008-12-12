<?php
/*
 * Nucleus: PHP/MySQL Weblog CMS (http://nucleuscms.org/)
 * Copyright (C) 2002-2007 The Nucleus Group
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * (see nucleus/documentation/index.html#license for more info)
 */
/**
 * Media classes for nucleus
 *
 * @license http://nucleuscms.org/license.txt GNU General Public License
 * @copyright Copyright (C) 2002-2007 The Nucleus Group
 * @version $Id$
 */


/**
  * Represents the media objects for a certain member
  */
class MEDIA {

	/**
	  * Gets the list of collections available to the currently logged
	  * in member
	  *
	  * @returns array of dirname => display name
	  */
	function getCollectionList() {
		global $member, $DIR_MEDIA;

		$collections = array();

		// add private directory for member
		$collections[$member->getID()] = 'Private Collection';

		// add global collections
		if (!is_dir($DIR_MEDIA)) return $collections;

		$dirhandle = opendir($DIR_MEDIA);
		while ($dirname = readdir($dirhandle)) {
			// only add non-numeric (numeric=private) dirs
			if (@is_dir($DIR_MEDIA . $dirname) && ($dirname != '.') && ($dirname != '..') && ($dirname != 'CVS') && (!is_numeric($dirname)))  {
				$collections[$dirname] = $dirname;
			}
		}
		closedir($dirhandle);

		return $collections;

	}

	/**
	  * Returns an array of MEDIAOBJECT objects for a certain collection
	  *
	  * @param $collection
	  *		name of the collection
	  * @param $filter
	  *		filter on filename (defaults to none)
	  */
	function getMediaListByCollection($collection, $filter = '') {
		global $DIR_MEDIA;

		$filelist = array();

		// 1. go through all objects and add them to the filelist

		$mediadir = $DIR_MEDIA . $collection . '/';

		// return if dir does not exist
		if (!is_dir($mediadir)) return $filelist;

		$dirhandle = opendir($mediadir);
		while ($filename = readdir($dirhandle)) {
			// only add files that match the filter
			if (!@is_dir($filename) && MEDIA::checkFilter($filename, $filter))
				array_push($filelist, new MEDIAOBJECT($collection, $filename, filemtime($mediadir . $filename)));
		}
		closedir($dirhandle);

		// sort array so newer files are shown first
		usort($filelist, 'sort_media');

		return $filelist;
	}

	function checkFilter($strText, $strFilter) {
		if ($strFilter == '')
			return 1;
		else
			return is_integer(strpos(strtolower($strText), strtolower($strFilter)));
	}

	/**
	  * checks if a collection exists with the given name, and if it's
	  * allowed for the currently logged in member to upload files to it
	  */
	function isValidCollection($collectionName) {
		global $member, $DIR_MEDIA;

		// avoid directory traversal
		$media = realpath($DIR_MEDIA);
		$collectionDir = realpath( $DIR_MEDIA . $collectionName );
		if (strpos($collectionDir,$media)!==0) return false;

		// private collections only accept uploads from their owners
		$collectionName=substr($collectionDir,strlen($media));
		if (preg_match('/^[0-9]+[\/\\\\]?$/',$collectionName))
			return ((int)$member->getID() == (int)$collectionName);

		// other collections should exists and be writable
		return (@is_dir($collectionDir) && @is_writable($collectionDir));
       }

	/**
	  * Adds an uploaded file to the media archive
	  *
	  * @param collection
	  *		collection
	  * @param uploadfile
	  *		the postFileInfo(..) array
	  * @param filename
	  *		the filename that should be used to save the file as
	  *		(date prefix should be already added here)
	  */
	function addMediaObject($collection, $uploadfile, $filename) {
		global $DIR_MEDIA, $manager;

		$manager->notify('PreMediaUpload',array('collection' => &$collection, 'uploadfile' => $uploadfile, 'filename' => &$filename));

		// don't allow uploads to unknown or forbidden collections
		if (!MEDIA::isValidCollection($collection))
			return _ERROR_DISALLOWED;

		// check dir permissions (try to create dir if it does not exist)
		$mediadir = $DIR_MEDIA . $collection;

		// try to create new private media directories if needed
		if (!@is_dir($mediadir) && is_numeric($collection)) {
			$oldumask = umask(0000);
			if (!@mkdir($mediadir, 0777))
				return _ERROR_BADPERMISSIONS;
			umask($oldumask);
		}

		// if dir still not exists, the action is disallowed
		if (!@is_dir($mediadir))
			return _ERROR_DISALLOWED;

		if (!is_writeable($mediadir))
			return _ERROR_BADPERMISSIONS;

		// add trailing slash (don't add it earlier since it causes mkdir to fail on some systems)
		$mediadir .= '/';

		if (file_exists($mediadir . $filename))
			return _ERROR_UPLOADDUPLICATE;

		// move file to directory
		if (is_uploaded_file($uploadfile)) {
			if (!@move_uploaded_file($uploadfile, $mediadir . $filename))
				return _ERROR_UPLOADMOVEP;
		} else {
			if (!copy($uploadfile, $mediadir . $filename))
				return _ERROR_UPLOADCOPY ;
		}

		// chmod uploaded file
		$oldumask = umask(0000);
		@chmod($mediadir . $filename, 0644);
		umask($oldumask);

		$manager->notify('PostMediaUpload',array('collection' => $collection, 'mediadir' => $mediadir, 'filename' => $filename));

		return '';

	}

	/**
	 * Adds an uploaded file to the media dir.
	 *
	 * @param $collection
	 *		collection to use
	 * @param $filename
	 *		the filename that should be used to save the file as
	 *		(date prefix should be already added here)
	 * @param &$data
	 *		File data (binary)
	 *
	 * NOTE: does not check if $collection is valid.
	 */
	function addMediaObjectRaw($collection, $filename, &$data) {
		global $DIR_MEDIA;

		// check dir permissions (try to create dir if it does not exist)
		$mediadir = $DIR_MEDIA . $collection;

		// try to create new private media directories if needed
		if (!@is_dir($mediadir) && is_numeric($collection)) {
			$oldumask = umask(0000);
			if (!@mkdir($mediadir, 0777))
				return _ERROR_BADPERMISSIONS;
			umask($oldumask);
		}

		// if dir still not exists, the action is disallowed
		if (!@is_dir($mediadir))
			return _ERROR_DISALLOWED;

		if (!is_writeable($mediadir))
			return _ERROR_BADPERMISSIONS;

		// add trailing slash (don't add it earlier since it causes mkdir to fail on some systems)
		$mediadir .= '/';

		if (file_exists($mediadir . $filename))
			return _ERROR_UPLOADDUPLICATE;

		// create file
		$fh = @fopen($mediadir . $filename, 'wb');
		if (!$fh)
			return _ERROR_UPLOADFAILED;
		$ok = @fwrite($fh, $data);
		@fclose($fh);
		if (!$ok)
			return _ERROR_UPLOADFAILED;

		// chmod uploaded file
		$oldumask = umask(0000);
		@chmod($mediadir . $filename, 0644);
		umask($oldumask);

		return '';

	}

}

/**
  * Represents the characteristics of one single media-object
  *
  * Description of properties:
  *  - filename: filename, without paths
  *  - timestamp: last modification (unix timestamp)
  *  - collection: collection to which the file belongs (can also be a owner ID, for private collections)
  *  - private: true if the media belongs to a private member collection
  */
class MEDIAOBJECT {

	var $private;
	var $collection;
	var $filename;
	var $timestamp;

	function MEDIAOBJECT($collection, $filename, $timestamp) {
		$this->private = is_numeric($collection);
		$this->collection = $collection;
		$this->filename = $filename;
		$this->timestamp = $timestamp;
	}

}

/**
  * User-defined sort method to sort an array of MEDIAOBJECTS
  */
function sort_media($a, $b) {
	if ($a->timestamp == $b->timestamp) return 0;
	return ($a->timestamp > $b->timestamp) ? -1 : 1;
}

?>
